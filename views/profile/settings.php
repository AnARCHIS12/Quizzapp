<div class="max-w-4xl mx-auto space-y-8" x-data="{ showDeleteModal: false }">
    <!-- Breadcrumb -->
    <div>
        <h1 class="text-3xl font-extrabold tracking-tight">Paramètres du compte</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Gérez vos informations de profil, votre sécurité et vos préférences.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Sidebar Quick info -->
        <div class="space-y-6">
            <div class="glass-card rounded-2xl p-6 shadow-md text-center">
                <img class="w-24 h-24 rounded-full border-4 border-violet-500 mx-auto mb-4 object-cover" 
                     src="<?php echo $user['avatar_url'] ?: 'https://api.dicebear.com/7.x/bottts/svg?seed=' . urlencode($user['username']); ?>" 
                     alt="Avatar">
                <h3 class="text-lg font-bold"><?php echo \App\Core\View::escape($user['username']); ?></h3>
                <p class="text-xs text-slate-400 mt-1"><?php echo \App\Core\View::escape($user['email']); ?></p>
            </div>
            
            <div class="glass-card rounded-2xl p-6 shadow-md space-y-4">
                <h4 class="text-sm font-bold uppercase tracking-wider text-slate-400">Actions Rapides</h4>
                <button @click="showDeleteModal = true" class="w-full text-left py-2 px-3 rounded-lg text-sm font-semibold text-red-500 hover:bg-red-500/10 transition-colors">
                    ❌ Supprimer mon compte
                </button>
            </div>
        </div>

        <!-- Settings Options Forms -->
        <div class="md:col-span-2 space-y-8">
            <!-- 1. General Profile Info Form -->
            <div class="glass-card rounded-2xl p-6 md:p-8 shadow-md">
                <h2 class="text-xl font-bold mb-6 tracking-tight">Informations Générales</h2>
                
                <form action="/settings/profile" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="username" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Nom d'utilisateur</label>
                            <input type="text" id="username" name="username" required value="<?php echo \App\Core\View::escape($user['username']); ?>"
                                   class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-transparent outline-none transition-all">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Adresse e-mail</label>
                            <input type="email" id="email" name="email" required value="<?php echo \App\Core\View::escape($user['email']); ?>"
                                   class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-transparent outline-none transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Modifier l'avatar</label>
                        <input type="file" name="avatar" accept="image/*"
                               class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-violet-500/10 file:text-violet-500 hover:file:bg-violet-500/20 file:cursor-pointer">
                        <span class="text-xs text-slate-400 mt-1 block">Taille max : 2 Mo. Formats : JPG, PNG, WEBP.</span>
                    </div>

                    <button type="submit" class="py-2.5 px-6 font-bold text-white rounded-xl bg-violet-600 hover:bg-violet-500 transition-all shadow-md">
                        Sauvegarder les modifications
                    </button>
                </form>
            </div>

            <!-- 2. Change Password Form -->
            <div class="glass-card rounded-2xl p-6 md:p-8 shadow-md">
                <h2 class="text-xl font-bold mb-6 tracking-tight">Changer le mot de passe</h2>

                <form action="/settings/password" method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div>
                        <label for="current_password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Mot de passe actuel</label>
                        <input type="password" id="current_password" name="current_password" required
                               class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-transparent outline-none transition-all"
                               placeholder="••••••••">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="new_password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Nouveau mot de passe</label>
                            <input type="password" id="new_password" name="new_password" required
                                   class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-transparent outline-none transition-all"
                                   placeholder="••••••••">
                            <span class="text-xs text-slate-400 mt-1 block">Au moins 8 caractères requis.</span>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Confirmer le nouveau mot de passe</label>
                            <input type="password" id="confirm_password" name="confirm_password" required
                                   class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-transparent outline-none transition-all"
                                   placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" class="py-2.5 px-6 font-bold text-white rounded-xl bg-violet-600 hover:bg-violet-500 transition-all shadow-md">
                        Changer mon mot de passe
                    </button>
                </form>
            </div>

            <!-- 2. Security 2FA settings -->
            <div class="glass-card rounded-2xl p-6 md:p-8 shadow-md">
                <h2 class="text-xl font-bold mb-4 tracking-tight">Sécurité & Double Authentification</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Ajoutez une couche de sécurité supplémentaire à votre compte en configurant un mot de passe à usage unique.</p>

                <?php if ((int)$user['two_factor_enabled'] === 1): ?>
                    <!-- 2FA is active -->
                    <div class="p-4 rounded-xl border border-emerald-500/20 bg-emerald-500/5 flex items-center justify-between mb-6">
                        <div class="flex items-center space-x-3">
                            <span class="p-2 bg-emerald-500/20 text-emerald-500 rounded-lg">🔒</span>
                            <div>
                                <h4 class="text-sm font-bold">Double authentification activée</h4>
                                <p class="text-xs text-slate-400">Votre compte est sécurisé.</p>
                            </div>
                        </div>
                        <form action="/settings/2fa/disable" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <button type="submit" class="py-2 px-4 rounded-xl text-xs font-bold text-red-500 hover:bg-red-500/10 border border-red-500/20 transition-all">
                                Désactiver
                            </button>
                        </form>
                    </div>
                <?php elseif ($user['two_factor_secret']): ?>
                    <!-- Secret generated but not verified yet -->
                    <div class="space-y-6 border border-amber-500/20 bg-amber-500/5 rounded-xl p-4 md:p-6 mb-6">
                        <h4 class="text-sm font-bold text-amber-500">Activez votre double authentification (2FA)</h4>
                        <ol class="text-xs text-slate-400 space-y-2 list-decimal list-inside">
                            <li>Téléchargez Google Authenticator ou une application équivalente.</li>
                            <li>Scannez le QR Code ci-dessous ou copiez le code secret.</li>
                            <li>Saisissez le code à 6 chiffres généré par l'application pour valider.</li>
                        </ol>

                        <div class="flex flex-col sm:flex-row items-center sm:space-x-6 space-y-4 sm:space-y-0">
                            <!-- QR code container using client-side generator to avoid package dependencies -->
                            <div class="bg-white p-2 rounded-xl border border-slate-200">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($qrCodeUri); ?>" alt="QR Code 2FA">
                            </div>
                            <div class="space-y-2 text-center sm:text-left">
                                <span class="text-xs text-slate-400 block">Code secret :</span>
                                <code class="px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded font-mono text-sm font-bold text-brand-500 select-all"><?php echo \App\Core\View::escape($user['two_factor_secret']); ?></code>
                            </div>
                        </div>

                        <form action="/settings/2fa/enable" method="POST" class="flex items-center space-x-4">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="text" name="code" required maxlength="6" placeholder="000000" pattern="[0-9]{6}"
                                   class="w-32 px-4 py-2 border border-slate-300 dark:border-slate-700 bg-transparent rounded-xl text-center text-lg font-semibold tracking-widest outline-none focus:ring-2 focus:ring-violet-500">
                            <button type="submit" class="py-2.5 px-6 font-bold text-white rounded-xl bg-violet-600 hover:bg-violet-500 transition-all">
                                Activer
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- No 2FA configured -->
                    <form action="/settings/2fa/generate" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <button type="submit" class="py-2.5 px-6 font-bold text-white rounded-xl bg-violet-600 hover:bg-violet-500 transition-all shadow-md">
                            Configurer la double authentification
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Account Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="glass-card rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-6 border border-red-500/20" @click.outside="showDeleteModal = false">
            <div class="text-center">
                <span class="text-4xl">⚠️</span>
                <h3 class="text-xl font-bold mt-2 text-red-500">Suppression de compte</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Cette action est définitive et entraînera la perte de tout votre historique, niveau, XP et succès débloqués.</p>
            </div>
            
            <form action="/settings/profile/delete" method="POST" class="w-full space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div>
                    <label for="delete_password" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Veuillez saisir votre mot de passe pour confirmer :</label>
                    <input type="password" id="delete_password" name="password" required
                           class="w-full px-3 py-2 text-sm bg-transparent border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none">
                </div>
                <div class="flex items-center space-x-4">
                    <button type="button" @click="showDeleteModal = false" class="flex-1 py-2 px-4 rounded-xl font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 transition-all text-sm">
                        Annuler
                    </button>
                    <button type="submit" class="flex-1 py-2 px-4 rounded-xl font-bold bg-red-600 text-white hover:bg-red-500 transition-all text-sm">
                        Oui, supprimer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
