<div class="max-w-md mx-auto my-8">
    <div class="glass-card rounded-2xl p-8 shadow-xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-violet-400 to-purple-500 bg-clip-text text-transparent">Connexion</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Bon retour parmi nous ! Prêt pour un nouveau défi ?</p>
        </div>

        <form action="/login" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div>
                <label for="login" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Nom d'utilisateur ou E-mail</label>
                <input type="text" id="login" name="login" required 
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-transparent focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all outline-none"
                       placeholder="pseudo ou mail@exemple.com">
            </div>

            <div>
                <div class="flex justify-between items-center mb-2">
                    <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Mot de passe</label>
                    <a href="/forgot-password" class="text-xs font-semibold text-violet-500 hover:underline">Mot de passe oublié ?</a>
                </div>
                <input type="password" id="password" name="password" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-transparent focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all outline-none"
                       placeholder="••••••••">
            </div>

            <button type="submit" 
                    class="w-full py-3 px-4 font-bold text-white rounded-xl shadow-lg shadow-violet-500/20 bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 transition-all duration-200">
                Se connecter
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
            Nouveau sur la plateforme ? <a href="/register" class="font-semibold text-violet-500 hover:underline">Créer un compte</a>
        </div>
    </div>
</div>
