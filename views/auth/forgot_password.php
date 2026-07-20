<div class="max-w-md mx-auto my-8">
    <div class="glass-card rounded-2xl p-8 shadow-xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-violet-400 to-purple-500 bg-clip-text text-transparent">Récupération de compte</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Saisissez l'adresse e-mail liée à votre compte pour recevoir un lien de réinitialisation.</p>
        </div>

        <form action="/forgot-password" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Adresse e-mail</label>
                <input type="email" id="email" name="email" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-transparent focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all outline-none"
                       placeholder="nom@exemple.com">
            </div>

            <button type="submit" 
                    class="w-full py-3 px-4 font-bold text-white rounded-xl shadow-lg shadow-violet-500/20 bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 transition-all duration-200">
                Envoyer le lien
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
            Je m'en souviens ! <a href="/login" class="font-semibold text-violet-500 hover:underline">Se connecter</a>
        </div>
    </div>
</div>
