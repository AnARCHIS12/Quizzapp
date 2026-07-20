<div class="max-w-md mx-auto my-8">
    <div class="glass-card rounded-2xl p-8 shadow-xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-violet-400 to-purple-500 bg-clip-text text-transparent">Réinitialiser le mot de passe</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Définissez votre nouveau mot de passe de connexion.</p>
        </div>

        <form action="/reset-password" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="token" value="<?php echo $token; ?>">

            <div>
                <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Nouveau mot de passe</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-transparent focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all outline-none"
                       placeholder="••••••••">
                <span class="text-xs text-slate-400 mt-1 block">Au moins 8 caractères requis.</span>
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Confirmer le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-transparent focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all outline-none"
                       placeholder="••••••••">
            </div>

            <button type="submit" 
                    class="w-full py-3 px-4 font-bold text-white rounded-xl shadow-lg shadow-violet-500/20 bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 transition-all duration-200">
                Enregistrer le mot de passe
            </button>
        </form>
    </div>
</div>
