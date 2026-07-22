<div class="w-full max-w-2xl mx-auto my-2 sm:my-6 px-1 sm:px-0" x-data="duelArena()">
    <!-- Top Status Bar -->
    <div class="flex justify-between items-center mb-3 sm:mb-6 px-1">
        <a href="/duel" class="flex items-center gap-1.5 text-xs font-bold text-violet-500 hover:text-violet-400 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            <span class="hidden sm:inline">Quitter le salon</span>
            <span class="sm:hidden">Quitter</span>
        </a>
        <span class="text-xs font-semibold px-3 py-1 bg-violet-500/10 text-violet-500 rounded-lg capitalize" x-text="roomState"></span>
    </div>

    <!-- 1. CONNECTION ERROR -->
    <div x-show="roomState === 'offline'" class="glass-card rounded-2xl sm:rounded-3xl p-8 text-center shadow-xl space-y-4 border border-red-500/20">
        <svg class="w-12 h-12 text-red-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        <h2 class="text-xl sm:text-2xl font-extrabold text-red-500">Connexion perdue</h2>
        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">Impossible de joindre le serveur de duel.</p>
        <button @click="connectSocket()" class="mt-2 px-6 py-3 rounded-xl font-bold bg-violet-600 hover:bg-violet-500 text-white text-sm w-full sm:w-auto">
            Réessayer
        </button>
    </div>

    <!-- 2. LOBBY WAITING -->
    <div x-show="roomState === 'waiting'" x-cloak class="glass-card rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 shadow-xl space-y-5 sm:space-y-8">
        <div class="text-center space-y-1 sm:space-y-2">
            <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Salon de Duel Privé</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold" x-text="roomCode ? 'Salon ' + roomCode : 'Création...'"></h1>
        </div>

        <!-- Invitation link -->
        <div class="p-3 sm:p-4 bg-slate-100 dark:bg-slate-800/40 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-700 space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Lien d'invitation</h4>
            <p class="text-xs sm:text-sm font-mono font-semibold break-all select-all" x-text="getInvitationLink()"></p>
            <button @click="copyInvitationLink()" class="w-full py-2.5 bg-violet-600 hover:bg-violet-500 text-white rounded-xl text-xs font-bold shadow transition-all flex items-center justify-center gap-2">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"/></svg>
                <span>Copier le lien</span>
            </button>
        </div>

        <!-- Players -->
        <div class="space-y-3">
            <h3 class="text-base sm:text-lg font-bold tracking-tight">Joueurs (<span x-text="players.length"></span>)</h3>
            <div class="grid grid-cols-1 gap-2 sm:gap-3">
                <template x-for="p in players" :key="p.user_id">
                    <div class="p-3 sm:p-4 rounded-xl border border-slate-200 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-800/20 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full bg-violet-500 text-white font-extrabold flex items-center justify-center text-xs" x-text="p.username.substring(0,2).toUpperCase()"></div>
                            <span class="font-bold text-sm" x-text="p.username"></span>
                        </div>
                        <span :class="p.is_ready ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 'bg-amber-500/10 text-amber-500 border-amber-500/20'"
                              class="text-[10px] font-bold uppercase px-2 py-0.5 rounded border" x-text="p.is_ready ? 'Prêt ✓' : 'En attente'"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Ready button -->
        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-3">
            <p class="text-xs text-slate-400 text-center">La sélection des thèmes démarre dès que tous les joueurs (min. 2) sont prêts.</p>
            <button @click="markAsReady()" :disabled="isMeReady"
                    :class="isMeReady ? 'bg-emerald-600 text-white cursor-default' : 'bg-violet-600 hover:bg-violet-500 text-white shadow-lg'"
                    class="w-full px-6 py-3.5 font-bold rounded-xl transition-all text-sm flex items-center justify-center gap-2">
                <template x-if="isMeReady">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        <span>Prêt — en attente des adversaires</span>
                    </span>
                </template>
                <template x-if="!isMeReady">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                        <span>Signaler prêt</span>
                    </span>
                </template>
            </button>
        </div>
    </div>

    <!-- 3. CATEGORY SELECTION -->
    <div x-show="roomState === 'selecting'" x-cloak class="space-y-3 sm:space-y-6">
        <!-- Header card -->
        <div class="glass-card rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-xl">
            <div class="flex items-center justify-between mb-3 sm:mb-4">
                <div class="flex-1 min-w-0">
                    <span class="text-[10px] sm:text-xs font-bold uppercase tracking-widest text-violet-400">Sélection des thèmes</span>
                    <h2 class="text-lg sm:text-2xl font-extrabold mt-0.5 leading-tight">
                        <span x-show="currentPickerIsMe">🎯 C'est votre tour !</span>
                        <span x-show="!currentPickerIsMe" x-text="'⏳ ' + currentPickerName + '...'"></span>
                    </h2>
                </div>
                <div class="text-right flex-shrink-0 ml-4">
                    <span class="text-2xl sm:text-3xl font-black text-violet-500" x-text="picksDone"></span>
                    <span class="text-slate-400 text-xs"> / <span x-text="totalPicks"></span></span>
                    <p class="text-[10px] text-slate-400 mt-0.5">choix</p>
                </div>
            </div>
            <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                <div class="bg-gradient-to-r from-violet-500 to-purple-600 h-full rounded-full transition-all duration-500"
                     :style="'width: ' + (picksDone / totalPicks * 100) + '%'"></div>
            </div>
        </div>

        <!-- Picked so far -->
        <div x-show="allPicked.length > 0" class="glass-card rounded-2xl p-4 shadow-xl">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Thèmes choisis</h4>
            <div class="flex flex-wrap gap-1.5">
                <template x-for="pick in allPicked" :key="pick.user_id + '-' + pick.category_id + '-' + Math.random()">
                    <span :class="pick.user_id === myId ? 'bg-violet-500/10 text-violet-400 border-violet-500/30' : 'bg-blue-500/10 text-blue-400 border-blue-500/30'"
                          class="px-2.5 py-1 rounded-full text-xs font-bold border" x-text="getCategoryName(pick.category_id)"></span>
                </template>
            </div>
        </div>

        <!-- Category grid — 2 cols on mobile, 3 on sm+ -->
        <div class="glass-card rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-xl">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">
                <span x-show="currentPickerIsMe">Choisissez un thème :</span>
                <span x-show="!currentPickerIsMe">Catégories disponibles :</span>
            </h4>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 sm:gap-3">
                <template x-for="cat in categories" :key="cat.id">
                    <button @click="pickCategory(cat.id)"
                            :disabled="!currentPickerIsMe"
                            :class="currentPickerIsMe
                                ? 'hover:border-violet-500 hover:bg-violet-500/10 hover:scale-105 cursor-pointer active:scale-95'
                                : 'opacity-40 cursor-not-allowed'"
                            class="p-3 sm:p-4 rounded-xl border border-slate-200 dark:border-slate-700 text-left transition-all duration-200 bg-slate-50/50 dark:bg-slate-800/20 min-h-[80px] flex flex-col justify-between">
                        <div class="font-bold text-xs sm:text-sm leading-snug" x-text="cat.name"></div>
                        <div class="text-[10px] sm:text-[11px] text-slate-400 mt-1 line-clamp-2 leading-tight" x-text="cat.description || ''"></div>
                    </button>
                </template>
            </div>
        </div>

        <!-- Player legend -->
        <div class="flex justify-center gap-4 text-xs text-slate-400">
            <template x-for="p in players" :key="p.user_id">
                <div class="flex items-center gap-1.5">
                    <span :class="p.user_id === myId ? 'bg-violet-500' : 'bg-blue-500'" class="w-2.5 h-2.5 rounded-full"></span>
                    <span x-text="p.username + (p.user_id === myId ? ' (vous)' : '')"></span>
                </div>
            </template>
        </div>
    </div>

    <!-- 4. COUNTDOWN -->
    <div x-show="roomState === 'countdown'" x-cloak class="glass-card rounded-2xl sm:rounded-3xl p-10 sm:p-12 text-center shadow-xl space-y-4">
        <span class="text-6xl sm:text-7xl font-extrabold text-violet-500 animate-ping block" x-text="countdownVal"></span>
        <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight">Le combat commence !</h2>
        <p class="text-slate-400 text-xs sm:text-sm">Soyez rapide — la vitesse rapporte des points bonus.</p>
    </div>

    <!-- 5. LIVE PLAY -->
    <div x-show="roomState === 'playing' || roomState === 'feedback'" x-cloak class="glass-card rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 shadow-xl space-y-4 sm:space-y-6">
        <!-- Question header -->
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Q <span x-text="currentIndex + 1"></span>/<span x-text="totalQuestions"></span></span>
            <span class="text-xs font-bold text-violet-500">Score : <span x-text="myScore"></span> pts</span>
        </div>

        <!-- Timer bar -->
        <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
            <div class="bg-gradient-to-r from-violet-500 to-purple-600 h-full rounded-full transition-all duration-100" :style="'width: ' + timerPercent + '%'"></div>
        </div>

        <!-- Question text -->
        <h2 class="text-base sm:text-xl md:text-2xl font-bold tracking-tight leading-snug" x-text="currentQuestion.question_text"></h2>

        <!-- Answer Forms -->
        <div class="space-y-2 sm:space-y-3">
            <!-- MCQ -->
            <template x-if="['mcq', 'true_false', 'image', 'audio', 'video'].includes(currentQuestion.type)">
                <div class="grid grid-cols-1 gap-2 sm:gap-3">
                    <template x-for="ans in currentQuestion.answers" :key="ans.id">
                        <button @click="selectAnswer(ans.id)" :disabled="roomState === 'feedback' || hasSubmitted"
                                :class="getAnswerClass(ans)"
                                class="w-full text-left p-3 sm:p-4 rounded-xl border font-semibold text-sm transition-all flex items-center justify-between gap-2 min-h-[48px]">
                            <span x-text="ans.answer_text" class="leading-snug"></span>
                            <span x-show="selectedAnswer === ans.id" class="text-violet-500 flex-shrink-0">✓</span>
                        </button>
                    </template>
                </div>
            </template>

            <!-- Multi Choice -->
            <template x-if="currentQuestion.type === 'multi_choice'">
                <div class="grid grid-cols-1 gap-2 sm:gap-3">
                    <template x-for="ans in currentQuestion.answers" :key="ans.id">
                        <button @click="toggleMultiAnswer(ans.id)" :disabled="roomState === 'feedback' || hasSubmitted"
                                :class="getMultiAnswerClass(ans)"
                                class="w-full text-left p-3 sm:p-4 rounded-xl border font-semibold text-sm transition-all flex items-center justify-between gap-2 min-h-[48px]">
                            <span x-text="ans.answer_text" class="leading-snug"></span>
                            <span x-show="selectedMultiAnswers.includes(ans.id)" class="text-violet-500 flex-shrink-0">✓</span>
                        </button>
                    </template>
                </div>
            </template>

            <!-- Ranking -->
            <template x-if="currentQuestion.type === 'ranking'">
                <div class="space-y-2">
                    <p class="text-xs text-slate-400">Classez dans le bon ordre :</p>
                    <div class="space-y-2">
                        <template x-for="(ans, idx) in rankingList" :key="ans.id">
                            <div :class="getRankingClass(ans)" 
                                 class="p-3 sm:p-4 rounded-xl border font-semibold text-sm flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/20 gap-2">
                                <span x-text="ans.answer_text" class="leading-snug flex-1"></span>
                                <div class="flex space-x-1 flex-shrink-0" x-show="roomState === 'playing' && !hasSubmitted">
                                    <button @click="moveRanking(idx, -1)" :disabled="idx === 0" class="p-2 rounded-lg bg-slate-200 dark:bg-slate-700 hover:bg-violet-500 hover:text-white disabled:opacity-30 text-xs">▲</button>
                                    <button @click="moveRanking(idx, 1)" :disabled="idx === rankingList.length - 1" class="p-2 rounded-lg bg-slate-200 dark:bg-slate-700 hover:bg-violet-500 hover:text-white disabled:opacity-30 text-xs">▼</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Association -->
            <template x-if="currentQuestion.type === 'association'">
                <div class="space-y-2 sm:space-y-3">
                    <p class="text-xs text-slate-400">Associez chaque élément :</p>
                    <div class="grid grid-cols-1 gap-2">
                        <template x-for="ans in currentQuestion.answers" :key="ans.id">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-3 rounded-xl border border-slate-300 dark:border-slate-700 gap-2">
                                <span class="font-bold text-sm" x-text="ans.answer_text"></span>
                                <select x-model="associationAnswers[ans.id]" :disabled="roomState === 'feedback' || hasSubmitted"
                                        :class="getAssociationClass(ans)"
                                        class="w-full sm:w-52 px-3 py-2 rounded-lg border bg-white dark:bg-slate-900 font-medium text-xs outline-none">
                                    <option value="">-- Choisir --</option>
                                    <template x-for="opt in associationOptions" :key="opt">
                                        <option :value="opt" x-text="opt"></option>
                                    </template>
                                </select>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <!-- Submit -->
        <div class="flex flex-col sm:flex-row items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800 gap-3">
            <span class="text-xs text-slate-400 text-center sm:text-left">
                <span x-show="hasSubmitted && roomState === 'playing'">✓ Réponse envoyée. En attente des adversaires...</span>
            </span>
            <button x-show="roomState === 'playing' && !hasSubmitted" @click="submitCurrentAnswer()"
                    class="w-full sm:w-auto py-3 px-6 font-bold text-white bg-violet-600 hover:bg-violet-500 rounded-xl transition-all shadow-md text-sm">
                Soumettre ma réponse
            </button>
        </div>

        <!-- Live Scoreboard — compact on mobile -->
        <div class="mt-3 sm:mt-6 pt-3 sm:pt-6 border-t border-slate-100 dark:border-slate-800">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2 sm:mb-3">Classement en direct</h4>
            <div class="space-y-1.5 sm:space-y-2">
                <template x-for="(p, i) in players" :key="p.user_id">
                    <div class="flex justify-between items-center text-xs py-2 px-3 rounded-lg bg-slate-50 dark:bg-slate-800/20 border border-slate-200/50 dark:border-slate-800/40">
                        <div class="flex items-center space-x-2">
                            <span class="font-bold" x-text="(i + 1) + '.'"></span>
                            <span x-text="p.username" :class="p.user_id === myId ? 'font-extrabold text-violet-500' : 'font-medium'"></span>
                        </div>
                        <span class="font-bold" x-text="p.score + ' pts'"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Explanation -->
        <div x-show="roomState === 'feedback'" class="p-3 sm:p-4 rounded-2xl border bg-slate-50 dark:bg-slate-800/40 border-violet-500/20 space-y-2">
            <h4 class="text-xs font-bold uppercase tracking-wider text-violet-500">Explication</h4>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed" x-text="explanation"></p>
        </div>
    </div>

    <!-- 6. FINAL PODIUM -->
    <div x-show="roomState === 'finished'" x-cloak class="glass-card rounded-2xl sm:rounded-3xl p-6 sm:p-8 md:p-12 text-center shadow-xl space-y-6 sm:space-y-8">
        <svg class="w-12 h-12 sm:w-16 sm:h-16 text-amber-400 mx-auto" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Fin du Duel !</h1>
        <p class="text-xs sm:text-sm text-slate-400">Classement final :</p>

        <div class="max-w-sm mx-auto space-y-2 sm:space-y-3">
            <template x-for="(p, i) in players" :key="p.user_id">
                <div :class="i === 0 ? 'border-amber-500/20 bg-amber-500/5' : 'border-slate-200 dark:border-slate-800'"
                     class="p-3 sm:p-4 rounded-2xl border flex items-center justify-between shadow-sm">
                    <div class="flex items-center space-x-3">
                        <span class="text-base sm:text-lg font-bold" x-text="['🥇', '🥈', '🥉'][i] || '#' + (i+1)"></span>
                        <span class="font-bold text-sm sm:text-base" x-text="p.username" :class="p.user_id === myId ? 'text-violet-500 font-extrabold' : ''"></span>
                    </div>
                    <span class="font-bold text-sm text-violet-500" x-text="p.score + ' pts'"></span>
                </div>
            </template>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <button @click="submitPlayAgain()" class="px-6 py-3 rounded-xl font-bold text-white bg-violet-600 hover:bg-violet-500 shadow-md hover:shadow-lg transition-all text-sm w-full sm:w-auto">
                Rejouer
            </button>
            <a href="/duel" class="px-6 py-3 rounded-xl font-bold text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all text-sm w-full sm:w-auto text-center">
                Quitter
            </a>
        </div>
    </div>
</div>

<!-- WebSocket Arena JavaScript -->
<script>
function duelArena() {
    return {
        myId: <?php echo (int)$user['id']; ?>,
        myUsername: <?php echo json_encode($user['username']); ?>,
        wsUrl: '<?php echo $wsUrl; ?>?token=<?php echo $jwtToken; ?>',

        roomState: 'offline',
        roomCode: '<?php echo $roomCode === "lobby" ? "" : $roomCode; ?>',
        players: [],
        isMeReady: false,

        socket: null,

        currentQuestion: {},
        currentIndex: 0,
        totalQuestions: 0,
        myScore: 0,
        explanation: '',
        correctAnswers: [],

        timerValue: 0,
        timerPercent: 100,
        timerInterval: null,
        timeLimit: 20,
        timerStartTimestamp: 0,

        selectedAnswer: null,
        selectedMultiAnswers: [],
        rankingList: [],
        associationAnswers: {},
        associationOptions: [],
        hasSubmitted: false,

        categories: [],
        allPicked: [],
        pickOrder: [],
        currentPicker: null,
        picksDone: 0,
        totalPicks: 0,
        picksPerPlayer: 3,

        get currentPickerIsMe() { return this.currentPicker === this.myId; },
        get currentPickerName() {
            const p = this.players.find(p => p.user_id === this.currentPicker);
            return p ? p.username : '...';
        },
        getCategoryName(id) {
            const c = this.categories.find(c => c.id === id);
            return c ? c.name : '#' + id;
        },

        init() { this.connectSocket(); },

        connectSocket() {
            this.socket = new WebSocket(this.wsUrl);

            this.socket.onopen = () => {
                this.roomState = 'waiting';
                const urlParams = new URLSearchParams(window.location.search);
                const createParam = urlParams.get('create');

                if (createParam) {
                    this.socket.send(JSON.stringify({
                        action: 'create',
                        user_id: this.myId,
                        username: this.myUsername
                    }));
                } else if (this.roomCode) {
                    this.socket.send(JSON.stringify({
                        action: 'join',
                        room_code: this.roomCode,
                        user_id: this.myId,
                        username: this.myUsername
                    }));
                }
            };

            this.socket.onclose = () => {
                this.roomState = 'offline';
                clearInterval(this.timerInterval);
            };

            this.socket.onmessage = (event) => {
                const data = JSON.parse(event.data);
                this.handleSocketMessage(data);
            };
        },

        handleSocketMessage(data) {
            switch (data.type) {
                case 'room_created':
                    this.roomCode = data.room_code;
                    this.players = data.players;
                    window.history.replaceState(null, '', '/duel/' + this.roomCode);
                    break;

                case 'room_joined':
                    this.players = data.players;
                    break;

                case 'player_joined':
                case 'player_left':
                case 'player_ready':
                    this.players = data.players;
                    const me = this.players.find(p => p.user_id === this.myId);
                    this.isMeReady = me ? me.is_ready : false;
                    break;

                case 'category_selection_start':
                    this.roomState      = 'selecting';
                    this.categories     = data.categories;
                    this.pickOrder      = data.pick_order;
                    this.currentPicker  = data.current_picker;
                    this.picksPerPlayer = data.picks_per_player;
                    this.totalPicks     = data.pick_order.length;
                    this.picksDone      = 0;
                    this.allPicked      = [];
                    break;

                case 'category_picked':
                    this.currentPicker = data.current_picker;
                    this.picksDone     = data.picks_done;
                    this.allPicked     = data.all_picked;
                    break;

                case 'selection_complete':
                    this.allPicked      = data.picked;
                    this.picksDone      = data.picked.length;
                    this.totalQuestions = data.total_questions;
                    this.roomState = 'countdown';
                    this.startCountdown(() => {});
                    break;

                case 'new_question':
                    this.roomState       = 'playing';
                    this.currentIndex    = data.index;
                    this.totalQuestions  = data.total;
                    this.currentQuestion = data.question;
                    this.hasSubmitted    = false;
                    this.selectedAnswer  = null;
                    this.selectedMultiAnswers = [];
                    if (this.currentQuestion.type === 'ranking') {
                        this.rankingList = [...this.currentQuestion.answers];
                        this.rankingList.sort(() => Math.random() - 0.5);
                    }
                    if (this.currentQuestion.type === 'association') {
                        this.associationAnswers = {};
                        this.currentQuestion.answers.forEach(a => { this.associationAnswers[a.id] = ""; });
                        this.associationOptions = this.currentQuestion.answers.map(a => a.association_pair);
                        this.associationOptions.sort(() => Math.random() - 0.5);
                    }
                    this.startTimer();
                    break;

                case 'question_feedback':
                    clearInterval(this.timerInterval);
                    this.roomState      = 'feedback';
                    this.explanation    = data.explanation;
                    this.correctAnswers = data.correct_answers;
                    this.players        = data.scores;
                    const myP = this.players.find(p => p.user_id === this.myId);
                    if (myP) this.myScore = myP.score;
                    break;

                case 'game_over':
                    this.roomState = 'finished';
                    this.players   = data.podium;
                    break;

                case 'play_again_reset':
                    this.roomState   = 'waiting';
                    this.players     = data.players;
                    this.isMeReady   = false;
                    this.myScore     = 0;
                    this.currentIndex = 0;
                    this.hasSubmitted = false;
                    this.allPicked   = [];
                    break;

                case 'error':
                    alert("Erreur : " + data.message);
                    window.location.href = '/duel';
                    break;
            }
        },

        countdownVal: 3,
        startCountdown(callback) {
            this.countdownVal = 3;
            const cnt = setInterval(() => {
                this.countdownVal--;
                if (this.countdownVal <= 0) {
                    clearInterval(cnt);
                    callback();
                }
            }, 1000);
        },

        startTimer() {
            clearInterval(this.timerInterval);
            this.timerValue = this.timeLimit;
            this.timerPercent = 100;
            this.timerStartTimestamp = Date.now();
            this.timerInterval = setInterval(() => {
                this.timerValue -= 0.1;
                this.timerPercent = (this.timerValue / this.timeLimit) * 100;
                if (this.timerValue <= 0) {
                    clearInterval(this.timerInterval);
                    if (!this.hasSubmitted) this.submitCurrentAnswer(true);
                }
            }, 100);
        },

        markAsReady() {
            this.socket.send(JSON.stringify({ action: 'ready', room_code: this.roomCode }));
        },

        pickCategory(categoryId) {
            if (!this.currentPickerIsMe) return;
            this.socket.send(JSON.stringify({
                action: 'pick_category',
                room_code: this.roomCode,
                category_id: categoryId
            }));
        },

        selectAnswer(id) {
            if (this.roomState !== 'playing' || this.hasSubmitted) return;
            this.selectedAnswer = id;
        },

        toggleMultiAnswer(id) {
            if (this.roomState !== 'playing' || this.hasSubmitted) return;
            const index = this.selectedMultiAnswers.indexOf(id);
            if (index > -1) this.selectedMultiAnswers.splice(index, 1);
            else this.selectedMultiAnswers.push(id);
        },

        moveRanking(idx, direction) {
            const targetIdx = idx + direction;
            if (targetIdx < 0 || targetIdx >= this.rankingList.length) return;
            const temp = this.rankingList[idx];
            this.rankingList[idx] = this.rankingList[targetIdx];
            this.rankingList[targetIdx] = temp;
        },

        submitCurrentAnswer(timedOut = false) {
            this.hasSubmitted = true;
            clearInterval(this.timerInterval);
            let answerValue = null;
            if (!timedOut) {
                switch (this.currentQuestion.type) {
                    case 'mcq': case 'true_false': case 'image': case 'audio': case 'video':
                        answerValue = this.selectedAnswer; break;
                    case 'multi_choice':
                        answerValue = this.selectedMultiAnswers; break;
                    case 'ranking':
                        answerValue = {};
                        this.rankingList.forEach((item, index) => { answerValue[item.id] = index + 1; });
                        break;
                    case 'association':
                        answerValue = this.associationAnswers; break;
                }
            }
            const timeSpent = (Date.now() - this.timerStartTimestamp) / 1000;
            this.socket.send(JSON.stringify({
                action: 'submit_answer',
                room_code: this.roomCode,
                answer: answerValue,
                time_spent: timeSpent
            }));
        },

        submitPlayAgain() {
            this.socket.send(JSON.stringify({ action: 'play_again', room_code: this.roomCode }));
        },

        getInvitationLink() {
            return window.location.origin + '/duel/' + this.roomCode;
        },

        copyInvitationLink() {
            navigator.clipboard.writeText(this.getInvitationLink())
                .then(() => alert("Lien copié !"))
                .catch(() => {
                    // Fallback for older browsers
                    const el = document.createElement('textarea');
                    el.value = this.getInvitationLink();
                    document.body.appendChild(el);
                    el.select();
                    document.execCommand('copy');
                    document.body.removeChild(el);
                    alert("Lien copié !");
                });
        },

        getAnswerClass(ans) {
            if (this.roomState === 'playing') {
                return this.selectedAnswer === ans.id
                    ? 'border-violet-500 bg-violet-500/10'
                    : 'border-slate-300 dark:border-slate-700 bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800/40';
            }
            const ca = this.correctAnswers.find(c => c.id === ans.id);
            if (ca) return 'border-emerald-500 bg-emerald-500/10 text-emerald-500 font-bold';
            if (this.selectedAnswer === ans.id) return 'border-red-500 bg-red-500/10 text-red-500';
            return 'border-slate-200 dark:border-slate-800 opacity-50';
        },

        getMultiAnswerClass(ans) {
            if (this.roomState === 'playing') {
                return this.selectedMultiAnswers.includes(ans.id)
                    ? 'border-violet-500 bg-violet-500/10'
                    : 'border-slate-300 dark:border-slate-700 bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800/40';
            }
            const ca = this.correctAnswers.find(c => c.id === ans.id);
            if (ca) return 'border-emerald-500 bg-emerald-500/10 text-emerald-500 font-bold';
            if (this.selectedMultiAnswers.includes(ans.id)) return 'border-red-500 bg-red-500/10 text-red-500';
            return 'border-slate-200 dark:border-slate-800 opacity-50';
        },

        getRankingClass(ans) {
            if (this.roomState === 'playing') return 'border-slate-300 dark:border-slate-700';
            const ca = this.correctAnswers.find(c => c.id === ans.id);
            const idx = this.rankingList.findIndex(i => i.id === ans.id);
            return (ca && parseInt(ca.match_order) === idx + 1)
                ? 'border-emerald-500 bg-emerald-500/10 text-emerald-500'
                : 'border-red-500 bg-red-500/10 text-red-500';
        },

        getAssociationClass(ans) {
            if (this.roomState === 'playing') return 'border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300';
            const ca = this.correctAnswers.find(c => c.id === ans.id);
            const val = this.associationAnswers[ans.id];
            return (ca && val === ca.association_pair)
                ? 'border-emerald-500 bg-emerald-500/10 text-emerald-500 font-bold'
                : 'border-red-500 bg-red-500/10 text-red-500';
        }
    };
}
</script>
