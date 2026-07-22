<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Mail dispatch service handling template building and SMTP relaying
 */
class MailerService
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $encryption;
    private string $fromEmail;
    private string $fromName;
    private bool $useSMTP;
    private bool $allowSelfSigned;

    public function __construct()
    {
        $this->host = $_ENV['SMTP_HOST'] ?? '';
        $this->port = (int)($_ENV['SMTP_PORT'] ?? 587);
        $this->username = $_ENV['SMTP_USER'] ?? '';
        $this->password = $_ENV['SMTP_PASS'] ?? '';
        $this->encryption = $_ENV['SMTP_SECURE'] ?? 'tls';
        $this->fromEmail = $_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@quizapp.local';
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'Quizzapp';
        $this->useSMTP = !empty($this->host);
        $this->allowSelfSigned = filter_var($_ENV['SMTP_ALLOW_SELF_SIGNED'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Send email with HTML content
     */
    public function send(string $to, string $subject, string $htmlBody, string $altBody = ''): bool
    {
        if (!$this->useSMTP) {
            // Write to a log file instead of throwing an error for testing environment
            $logDir = dirname(__DIR__, 2) . '/logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            $logFile = $logDir . '/mail.log';
            $logContent = sprintf(
                "[%s] To: %s | Subject: %s\nBody:\n%s\n---------------------------------------------\n",
                date('Y-m-d H:i:s'),
                $to,
                $subject,
                $htmlBody
            );
            file_put_contents($logFile, $logContent, FILE_APPEND);
            return true;
        }

        $mail = new PHPMailer(true);

        try {
            // Server & Encoding settings
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isSMTP();
            $mail->Host = $this->host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->username;
            $mail->Password = $this->password;
            $mail->SMTPSecure = $this->encryption === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->port;

            if ($this->allowSelfSigned) {
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
            }

            // Recipients
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $altBody ?: strip_tags($htmlBody);

            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            // Log the error to mail log
            $logDir = dirname(__DIR__, 2) . '/logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            file_put_contents($logDir . '/mail.log', "[ERROR] Failed sending to {$to}: " . $mail->ErrorInfo . "\n", FILE_APPEND);
            return false;
        }
    }

    /**
     * Send verification link
     */
    public function sendVerificationEmail(string $to, string $username, string $token): bool
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $secure = isset($_SERVER['HTTPS']) || 
                  (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $protocol = $secure ? 'https' : 'http';
        $verifyLink = "{$protocol}://{$host}/verify-email?token=" . urlencode($token);
        
        $escapedUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $body = "
            <h2>Bonjour {$escapedUsername},</h2>
            <p>Merci de vous être inscrit sur Quizzapp !</p>
            <p>Veuillez confirmer votre compte en cliquant sur le lien ci-dessous :</p>
            <p><a href='{$verifyLink}' style='background-color:#6366f1;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Vérifier mon compte</a></p>
            <p>Ou copiez-collez ce lien : <br>{$verifyLink}</p>
        ";
        return $this->send($to, 'Vérification de votre compte - Quizzapp', $body);
    }

    /**
     * Send password reset link
     */
    public function sendPasswordResetEmail(string $to, string $username, string $token): bool
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $secure = isset($_SERVER['HTTPS']) || 
                  (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $protocol = $secure ? 'https' : 'http';
        $resetLink = "{$protocol}://{$host}/reset-password?token=" . urlencode($token);

        $escapedUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $body = "
            <h2>Bonjour {$escapedUsername},</h2>
            <p>Vous avez demandé une réinitialisation de votre mot de passe pour votre compte Quizzapp.</p>
            <p>Cliquez sur le bouton ci-dessous pour définir un nouveau mot de passe (ce lien expirera dans 1 heure) :</p>
            <p><a href='{$resetLink}' style='background-color:#f59e0b;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Réinitialiser mon mot de passe</a></p>
            <p>Ou copiez-collez ce lien : <br>{$resetLink}</p>
            <p>Si vous n'avez pas demandé cette réinitialisation, veuillez ignorer cet e-mail.</p>
        ";
        return $this->send($to, 'Réinitialisation de mot de passe - Quizzapp', $body);
    }
}
