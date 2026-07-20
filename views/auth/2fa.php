<div class="max-w-md mx-auto my-8">
    <div class="glass-card rounded-2xl p-8 shadow-xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-violet-400 to-purple-500 bg-clip-text text-transparent">Double Authentification</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Saisissez le code à 6 chiffres généré par votre application Google Authenticator.</p>
        </div>

        <form action="/login/2fa" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div>
                <label for="code" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Code de validation</label>
                <input type="text" id="code" name="code" required maxlength="6" pattern="[0-9]{6}" autocomplete="one-time-code"
                       class="w-full text-center text-2xl tracking-widest px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-transparent focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all outline-none"
                       placeholder="000000">
            </div>

            <button type="submit" 
                    class="w-full py-3 px-4 font-bold text-white rounded-xl shadow-lg shadow-violet-500/20 bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 transition-all duration-200">
                Confirmer
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
            Un problème ? <a href="/login" class="font-semibold text-violet-500 hover:underline">Retour à la connexion</a>
        </div>
    </div>
</div>
