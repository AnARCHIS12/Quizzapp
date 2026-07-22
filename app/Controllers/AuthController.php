<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Models\User;
use App\Services\MailerService;
use App\Services\TOTPService;

/**
 * Controller managing registration, session auth, 2FA validation, and profile dashboard updates
 */
class AuthController
{
    private MailerService $mailer;
    private TOTPService $totp;

    public function __construct(MailerService $mailer, TOTPService $totp)
    {
        $this->mailer = $mailer;
        $this->totp = $totp;
    }

    /**
     * Show registration form
     */
    public function showRegister(): void
    {
        View::render('auth/register', ['csrf_token' => Session::csrfToken()]);
    }

    /**
     * Handle registration submission
     */
    public function register(): void
    {
        // Rate Limiter for registration (Max 5 accounts per hour)
        if (!Session::checkRateLimit('register', 5, 3600)) {
            Session::setFlash('error', 'Trop de comptes créés récemment depuis votre adresse IP. Veuillez réessayer plus tard.');
            header('Location: /register');
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validations
        if (empty($username) || empty($email) || empty($password)) {
            Session::setFlash('error', 'Tous les champs sont obligatoires.');
            header('Location: /register');
            return;
        }

        if (strlen($username) > 50) {
            Session::setFlash('error', 'Le nom d\'utilisateur ne doit pas dépasser 50 caractères.');
            header('Location: /register');
            return;
        }

        if ($password !== $confirmPassword) {
            Session::setFlash('error', 'Les mots de passe ne correspondent pas.');
            header('Location: /register');
            return;
        }

        if (strlen($password) < 8) {
            Session::setFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
            header('Location: /register');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('error', 'Format d\'adresse e-mail invalide.');
            header('Location: /register');
            return;
        }

        if (User::findByUsername($username)) {
            Session::setFlash('error', 'Ce nom d\'utilisateur est déjà pris.');
            header('Location: /register');
            return;
        }

        if (User::findByEmail($email)) {
            Session::setFlash('error', 'Cette adresse e-mail est déjà enregistrée.');
            header('Location: /register');
            return;
        }

        $token = bin2hex(random_bytes(32));
        $hash = password_hash($password, PASSWORD_BCRYPT);

        User::create([
            'username' => $username,
            'email' => $email,
            'password_hash' => $hash,
            'verification_token' => $token,
            'email_verified' => 0
        ]);

        // Send email
        $this->mailer->sendVerificationEmail($email, $username, $token);

        Session::logAction(null, "Inscription utilisateur: {$username} ({$email})");
        Session::setFlash('success', 'Inscription réussie ! Veuillez vérifier votre boîte mail pour confirmer votre compte.');
        header('Location: /login');
    }

    /**
     * Verify email via link token
     */
    public function verifyEmail(array $params): void
    {
        $token = $_GET['token'] ?? '';
        $user = User::findByVerificationToken($token);

        if (!$user) {
            Session::setFlash('error', 'Jeton de vérification invalide ou expiré.');
            header('Location: /login');
            return;
        }

        User::update((int)$user['id'], [
            'email_verified' => 1,
            'verification_token' => null
        ]);

        Session::logAction((int)$user['id'], "Adresse e-mail vérifiée");
        Session::setFlash('success', 'Votre adresse e-mail a été vérifiée avec succès. Vous pouvez maintenant vous connecter.');
        header('Location: /login');
    }

    /**
     * Show login form
     */
    public function showLogin(): void
    {
        View::render('auth/login', ['csrf_token' => Session::csrfToken()]);
    }

    /**
     * Handle login authentication
     */
    public function login(): void
    {
        $loginInput = trim($_POST['login'] ?? ''); // username or email
        $password = $_POST['password'] ?? '';

        // Rate Limiter
        if (!Session::checkRateLimit('login', 5, 900)) {
            Session::logAction(null, "Blocage Rate Limit de connexion pour IP");
            Session::setFlash('error', 'Trop de tentatives de connexion infructueuses. Veuillez réessayer dans 15 minutes.');
            header('Location: /login');
            return;
        }

        if (empty($loginInput) || empty($password)) {
            Session::setFlash('error', 'Veuillez remplir tous les champs.');
            header('Location: /login');
            return;
        }

        // Find user by either email or username
        $user = str_contains($loginInput, '@') ? User::findByEmail($loginInput) : User::findByUsername($loginInput);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Session::logAction(null, "Échec de connexion pour l'identifiant: {$loginInput}");
            Session::setFlash('error', 'Identifiants incorrects.');
            header('Location: /login');
            return;
        }

        if ((int)$user['email_verified'] !== 1) {
            Session::setFlash('error', 'Veuillez vérifier votre adresse e-mail avant de vous connecter.');
            header('Location: /login');
            return;
        }

        // Check if 2FA is enabled
        if ((int)$user['two_factor_enabled'] === 1) {
            // Put user ID temporarily in session and redirect to 2FA verification page
            Session::set('tfa_user_id', (int)$user['id']);
            header('Location: /login/2fa');
            return;
        }

        // Authenticate user session
        $this->completeAuthentication($user);
    }

    /**
     * Show 2FA input form during login
     */
    public function show2FA(): void
    {
        if (!Session::has('tfa_user_id')) {
            header('Location: /login');
            return;
        }
        View::render('auth/2fa', ['csrf_token' => Session::csrfToken()]);
    }

    /**
     * Verify 2FA code during login
     */
    public function verify2FA(): void
    {
        $userId = Session::get('tfa_user_id');
        if (!$userId) {
            header('Location: /login');
            return;
        }

        // Rate Limiter for 2FA validation attempts (Max 5 attempts per 15 minutes)
        if (!Session::checkRateLimit('tfa_verify', 5, 900)) {
            Session::setFlash('error', 'Trop de tentatives échouées de double authentification. Veuillez réessayer dans 15 minutes.');
            header('Location: /login/2fa');
            return;
        }

        $code = trim($_POST['code'] ?? '');
        $user = User::findById($userId);

        if (!$user || !$this->totp->verifyCode($user['two_factor_secret'], $code)) {
            Session::logAction($userId, "Échec validation 2FA lors de la connexion");
            Session::setFlash('error', 'Code de double authentification invalide.');
            header('Location: /login/2fa');
            return;
        }

        Session::remove('tfa_user_id');
        $this->completeAuthentication($user);
    }

    /**
     * Successfully complete login steps
     */
    private function completeAuthentication(array $user): void
    {
        Session::regenerate();
        Session::set('user', [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role_id' => (int)$user['role_id'],
            'avatar_url' => $user['avatar_url']
        ]);

        Session::logAction((int)$user['id'], "Connexion réussie");
        Session::setFlash('success', "Bienvenue, {$user['username']} !");
        header('Location: /dashboard');
    }

    /**
     * Handle logout
     */
    public function logout(): void
    {
        $user = Session::get('user');
        if ($user) {
            Session::logAction((int)$user['id'], "Déconnexion");
        }
        Session::destroy();
        header('Location: /login');
    }

    /**
     * Show request password reset form
     */
    public function showForgotPassword(): void
    {
        View::render('auth/forgot_password', ['csrf_token' => Session::csrfToken()]);
    }

    /**
     * Handle request password reset link dispatch
     */
    public function forgotPassword(): void
    {
        // Rate Limiter for reset links requests (Max 5 per hour)
        if (!Session::checkRateLimit('forgot_password', 5, 3600)) {
            Session::setFlash('error', 'Trop de demandes de réinitialisation. Veuillez réessayer plus tard.');
            header('Location: /forgot-password');
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $user = User::findByEmail($email);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            User::update((int)$user['id'], [
                'reset_token' => $token,
                'reset_token_expires' => $expires
            ]);

            $this->mailer->sendPasswordResetEmail($email, $user['username'], $token);
            Session::logAction((int)$user['id'], "Demande réinitialisation mot de passe");
        }

        // Show generic confirmation to avoid user enumeration
        Session::setFlash('success', 'Si cette adresse e-mail correspond à un compte, un lien de réinitialisation vous a été envoyé.');
        header('Location: /forgot-password');
    }

    /**
     * Show reset password entry form
     */
    public function showResetPassword(): void
    {
        $token = $_GET['token'] ?? '';
        $user = User::findByResetToken($token);

        if (!$user) {
            Session::setFlash('error', 'Lien de réinitialisation invalide ou expiré.');
            header('Location: /login');
            return;
        }

        View::render('auth/reset_password', [
            'token' => $token,
            'csrf_token' => Session::csrfToken()
        ]);
    }

    /**
     * Process password reset change
     */
    public function resetPassword(): void
    {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $user = User::findByResetToken($token);

        if (!$user) {
            Session::setFlash('error', 'Lien de réinitialisation invalide ou expiré.');
            header('Location: /login');
            return;
        }

        if (empty($password) || strlen($password) < 8) {
            Session::setFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
            header("Location: /reset-password?token=" . urlencode($token));
            return;
        }

        if ($password !== $confirmPassword) {
            Session::setFlash('error', 'Les mots de passe ne correspondent pas.');
            header("Location: /reset-password?token=" . urlencode($token));
            return;
        }

        User::update((int)$user['id'], [
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'reset_token' => null,
            'reset_token_expires' => null
        ]);

        Session::logAction((int)$user['id'], "Changement de mot de passe via réinitialisation");
        Session::setFlash('success', 'Votre mot de passe a été modifié. Vous pouvez maintenant vous connecter.');
        header('Location: /login');
    }

    /**
     * Show User profile Dashboard
     */
    public function dashboard(): void
    {
        $sessionUser = Session::get('user');
        $user = User::findById($sessionUser['id']);
        $stats = User::getStatistics($sessionUser['id']);
        $achievements = User::getAchievements($sessionUser['id']);
        $history = User::getMatchHistory($sessionUser['id']);

        // Calculate success rate
        $successRate = 0.0;
        if ($stats && $stats['total_played'] > 0) {
            // Assume 10 questions per quiz for average statistics
            $totalQuestionsPlayed = $stats['total_played'] * 10;
            $successRate = round(($stats['correct_count'] / max(1, $totalQuestionsPlayed)) * 100, 1);
        }

        View::render('profile/dashboard', [
            'user' => $user,
            'stats' => $stats,
            'successRate' => $successRate,
            'achievements' => $achievements,
            'history' => $history,
            'csrf_token' => Session::csrfToken()
        ]);
    }

    /**
     * Show user settings page
     */
    public function showSettings(): void
    {
        $sessionUser = Session::get('user');
        $user = User::findById($sessionUser['id']);

        $qrCodeUri = '';
        if ($user['two_factor_secret']) {
            $qrCodeUri = $this->totp->getQRUri($user['username'], $user['two_factor_secret']);
        }

        View::render('profile/settings', [
            'user' => $user,
            'qrCodeUri' => $qrCodeUri,
            'csrf_token' => Session::csrfToken()
        ]);
    }

    /**
     * Update profile (username, email, avatar)
     */
    public function updateProfile(): void
    {
        $sessionUser = Session::get('user');
        $userId = $sessionUser['id'];

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($username) || empty($email)) {
            Session::setFlash('error', 'Le nom d\'utilisateur et l\'adresse e-mail sont requis.');
            header('Location: /settings');
            return;
        }

        if (strlen($username) > 50) {
            Session::setFlash('error', 'Le nom d\'utilisateur ne doit pas dépasser 50 caractères.');
            header('Location: /settings');
            return;
        }

        // Check uniqueness
        $existingUser = User::findByUsername($username);
        if ($existingUser && (int)$existingUser['id'] !== $userId) {
            Session::setFlash('error', 'Ce nom d\'utilisateur est déjà pris.');
            header('Location: /settings');
            return;
        }

        $existingEmail = User::findByEmail($email);
        if ($existingEmail && (int)$existingEmail['id'] !== $userId) {
            Session::setFlash('error', 'Cette adresse e-mail est déjà prise.');
            header('Location: /settings');
            return;
        }

        $updateData = [
            'username' => $username,
            'email' => $email
        ];

        // Hardened Avatar Upload (Mitigating RCE, file extension spoofing, and mime forgery)
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['avatar']['tmp_name'];
            $fileSize = $_FILES['avatar']['size'];

            // 1. Validate File Size (Max 2MB)
            if ($fileSize > 2 * 1024 * 1024) {
                Session::setFlash('error', 'La taille de l\'avatar ne doit pas dépasser 2 Mo.');
                header('Location: /settings');
                return;
            }

            // 2. Validate Magic Bytes / Image Content
            $imageInfo = @getimagesize($fileTmpPath);
            if ($imageInfo === false) {
                Session::setFlash('error', 'Le fichier envoyé n\'est pas une image valide.');
                header('Location: /settings');
                return;
            }

            // 3. Detect MIME type using Server finfo (not client headers)
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $fileTmpPath);
            finfo_close($finfo);

            $allowedMimeTypes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            if (!array_key_exists($mimeType, $allowedMimeTypes)) {
                Session::setFlash('error', 'Format d\'avatar invalide. Seuls JPEG, PNG et WEBP sont acceptés.');
                header('Location: /settings');
                return;
            }

            $forcedExtension = $allowedMimeTypes[$mimeType];

            // 4. Generate Cryptographically Secure Random Filename (Discarding client filename)
            $randomString = bin2hex(random_bytes(16));
            $newFileName = 'avatar_' . $userId . '_' . $randomString . '.' . $forcedExtension;

            $uploadDir = dirname(__DIR__, 2) . '/public/assets/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $updateData['avatar_url'] = '/assets/uploads/' . $newFileName;
                $sessionUser['avatar_url'] = $updateData['avatar_url'];
            }
        }

        User::update($userId, $updateData);

        // Update session details
        $sessionUser['username'] = $username;
        $sessionUser['email'] = $email;
        Session::set('user', $sessionUser);

        Session::logAction($userId, "Mise à jour des informations de profil");
        Session::setFlash('success', 'Profil mis à jour avec succès.');
        header('Location: /settings');
    }

    /**
     * Update password for authenticated user
     */
    public function updatePassword(): void
    {
        $sessionUser = Session::get('user');
        $userId = $sessionUser['id'];

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $user = User::findById($userId);

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            Session::setFlash('error', 'Mot de passe actuel incorrect.');
            header('Location: /settings');
            return;
        }

        if (empty($newPassword) || strlen($newPassword) < 8) {
            Session::setFlash('error', 'Le nouveau mot de passe doit contenir au moins 8 caractères.');
            header('Location: /settings');
            return;
        }

        if ($newPassword !== $confirmPassword) {
            Session::setFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
            header('Location: /settings');
            return;
        }

        User::update($userId, [
            'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT)
        ]);

        Session::logAction($userId, "Modification du mot de passe depuis les paramètres");
        Session::setFlash('success', 'Votre mot de passe a été modifié avec succès.');
        header('Location: /settings');
    }

    /**
     * Generate & store 2FA secret
     */
    public function generate2FA(): void
    {
        $sessionUser = Session::get('user');
        $userId = $sessionUser['id'];
        $secret = $this->totp->generateSecret();

        User::update($userId, [
            'two_factor_secret' => $secret,
            'two_factor_enabled' => 0 // not activated yet, needs verification code submit
        ]);

        Session::logAction($userId, "Génération d'une clé secrète 2FA");
        Session::setFlash('success', 'Clé 2FA générée. Scannez le code QR pour l\'activer.');
        header('Location: /settings');
    }

    /**
     * Enable 2FA with verification code
     */
    public function enable2FA(): void
    {
        $sessionUser = Session::get('user');
        $userId = $sessionUser['id'];
        $code = trim($_POST['code'] ?? '');

        $user = User::findById($userId);
        if (!$user || !$user['two_factor_secret']) {
            Session::setFlash('error', 'Aucune clé secrète 2FA n\'a été configurée.');
            header('Location: /settings');
            return;
        }

        if (!$this->totp->verifyCode($user['two_factor_secret'], $code)) {
            Session::setFlash('error', 'Code de confirmation 2FA incorrect. Veuillez réessayer.');
            header('Location: /settings');
            return;
        }

        User::update($userId, ['two_factor_enabled' => 1]);
        Session::logAction($userId, "Activation de la double authentification (2FA)");
        Session::setFlash('success', 'Double authentification activée avec succès.');
        header('Location: /settings');
    }

    /**
     * Disable 2FA
     */
    public function disable2FA(): void
    {
        $sessionUser = Session::get('user');
        $userId = $sessionUser['id'];

        User::update($userId, [
            'two_factor_secret' => null,
            'two_factor_enabled' => 0
        ]);

        Session::logAction($userId, "Désactivation de la double authentification (2FA)");
        Session::setFlash('success', 'Double authentification désactivée.');
        header('Location: /settings');
    }

    /**
     * Delete user account permanently (requiring password confirmation)
     */
    public function deleteAccount(): void
    {
        $sessionUser = Session::get('user');
        $userId = $sessionUser['id'];
        $password = $_POST['password'] ?? '';

        // Validate password before deleting to prevent session-hijacking deletion triggers
        $user = User::findById($userId);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            Session::logAction($userId, "Tentative échouée de suppression de compte (mot de passe incorrect)");
            Session::setFlash('error', 'Mot de passe incorrect. Impossible de supprimer le compte.');
            header('Location: /settings');
            return;
        }

        Session::logAction($userId, "Suppression définitive du compte utilisateur");
        User::delete($userId);
        
        Session::destroy();
        header('Location: /register');
    }
}
