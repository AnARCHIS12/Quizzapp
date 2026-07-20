<div class="max-w-4xl mx-auto space-y-8" x-data="{ code: '' }">
    <div class="text-center space-y-3">
        <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight">Mode Duel Privé</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-xl mx-auto">
            Défiez vos amis en temps réel. Créez un salon, partagez le code, puis choisissez les thèmes
            <strong>à tour de rôle</strong> avant la bataille !
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

        <!-- Join Lobby Card -->
        <div class="glass-card rounded-2xl p-6 md:p-8 shadow-md flex flex-col justify-between space-y-6 border border-slate-200/80 dark:border-slate-800/40">
            <div class="space-y-4">
                <div class="w-12 h-12 bg-amber-500/10 rounded-2xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold tracking-tight">Rejoindre un salon</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Saisissez le code à 6 caractères reçu de votre adversaire pour rejoindre son salon de duel.
                </p>
            </div>
            <div class="space-y-4">
                <div>
                    <label for="room_code" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Code du salon</label>
                    <input type="text" id="room_code" x-model="code" maxlength="6" placeholder="A1B2C3"
                           class="w-full text-center text-xl font-mono tracking-widest px-4 py-2.5 bg-white/60 dark:bg-slate-900/40 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-transparent outline-none uppercase transition-all">
                </div>
                <a :href="code.length === 6 ? '/duel/' + code.toUpperCase() : '#'"
                   :class="code.length === 6 ? 'bg-amber-500 hover:bg-amber-400 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 pointer-events-none'"
                   class="w-full py-3 px-4 font-bold rounded-xl text-center shadow-lg transition-all text-sm block">
                    Entrer dans le salon
                </a>
            </div>
        </div>

        <!-- Create Lobby Card -->
        <div class="glass-card rounded-2xl p-6 md:p-8 shadow-md flex flex-col justify-between space-y-6 border border-slate-200/80 dark:border-slate-800/40">
            <div class="space-y-4">
                <div class="w-12 h-12 bg-violet-500/10 rounded-2xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-violet-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold tracking-tight">Créer un salon de duel</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Créez un salon, invitez un ami avec le lien, puis choisissez ensemble les thèmes à tour de rôle
                    (3 choix chacun = 18 questions). La partie commence automatiquement !
                </p>
            </div>

            <!-- How it works mini infographic -->
            <div class="bg-slate-50 dark:bg-slate-800/30 rounded-2xl p-4 space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Déroulement</h4>
                <div class="space-y-2">
                    <div class="flex items-start gap-3">
                        <span class="w-5 h-5 rounded-full bg-violet-500 text-white text-[10px] font-black flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Vous créez le salon et partagez le lien</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="w-5 h-5 rounded-full bg-violet-500 text-white text-[10px] font-black flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Les deux joueurs cliquent "Prêt"</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="w-5 h-5 rounded-full bg-violet-500 text-white text-[10px] font-black flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Chacun choisit <strong>3 catégories</strong> à tour de rôle</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="w-5 h-5 rounded-full bg-amber-500 text-white text-[10px] font-black flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Duel lancé — <strong>18 questions</strong>, le plus rapide gagne !</p>
                    </div>
                </div>
            </div>

            <a href="/duel/lobby?create=1"
               class="w-full py-3 px-4 font-bold rounded-xl text-center shadow-lg transition-all text-sm block bg-violet-600 hover:bg-violet-500 text-white">
                Créer le salon
            </a>
        </div>
    </div>
</div>
