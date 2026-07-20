<div class="w-full max-w-2xl mx-auto my-2 sm:my-6 px-1 sm:px-0" x-data="quizPlayer()">
    <!-- Sticky top bar on mobile -->
    <div class="flex justify-between items-center mb-3 sm:mb-6 px-1">
        <a href="/" class="flex items-center gap-1.5 text-xs font-bold text-violet-500 hover:text-violet-400 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            <span class="hidden sm:inline">Quitter</span>
        </a>
        <button @click="toggleFavorite()" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold transition-all">
            <svg class="w-4 h-4 transition-colors duration-200" :class="isFav ? 'text-rose-500 fill-current' : 'text-slate-400 dark:text-slate-500'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
            <span class="hidden sm:inline" x-text="isFav ? 'Favori' : 'Favoris'"></span>
        </button>
    </div>

    <!-- 1. LOADING SCREEN -->
    <div x-show="state === 'loading'" class="glass-card rounded-2xl sm:rounded-3xl p-8 sm:p-12 text-center shadow-xl space-y-4 sm:space-y-6">
        <div class="flex justify-center">
            <svg class="w-12 h-12 text-violet-500 animate-pulse" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
        </div>
        <h2 class="text-xl sm:text-2xl font-extrabold">Préparation du quiz...</h2>
        <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm">Chargement des questions en cours.</p>
    </div>

    <!-- 2. GAME PANEL -->
    <div x-show="state === 'playing' || state === 'feedback'" x-cloak class="glass-card rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 shadow-xl space-y-4 sm:space-y-6 relative overflow-hidden">
        <!-- Progress Bar and Score -->
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Q <span x-text="currentIndex + 1"></span>/<span x-text="questions.length"></span></span>
            <span class="text-xs font-bold text-violet-500">Score : <span x-text="score"></span> pts</span>
        </div>
        
        <!-- Timer bar -->
        <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
            <div class="bg-gradient-to-r from-violet-500 to-purple-600 h-full rounded-full transition-all duration-100" :style="'width: ' + timerPercent + '%'"></div>
        </div>

        <!-- Question media (if any) -->
        <template x-if="currentQuestion.media_url">
            <div class="rounded-xl overflow-hidden max-h-48 sm:max-h-64 border border-slate-200 dark:border-slate-800 flex justify-center items-center bg-slate-100 dark:bg-slate-900/50">
                <template x-if="currentQuestion.type === 'image'">
                    <img :src="currentQuestion.media_url" alt="Illustration" class="max-h-48 sm:max-h-60 object-contain rounded-xl">
                </template>
                <template x-if="currentQuestion.type === 'audio'">
                    <audio controls class="w-full max-w-md my-4">
                        <source :src="currentQuestion.media_url" type="audio/mpeg">
                    </audio>
                </template>
                <template x-if="currentQuestion.type === 'video'">
                    <video controls class="max-h-48 sm:max-h-60 object-contain rounded-xl">
                        <source :src="currentQuestion.media_url" type="video/mp4">
                    </video>
                </template>
            </div>
        </template>

        <!-- Question text -->
        <div>
            <h2 class="text-base sm:text-xl md:text-2xl font-bold tracking-tight leading-snug" x-text="currentQuestion.question_text"></h2>
        </div>

        <!-- Answer Forms based on type -->
        <div class="space-y-2 sm:space-y-3">
            
            <!-- Type A: MCQ, True/False -->
            <template x-if="['mcq', 'true_false', 'image', 'audio', 'video'].includes(currentQuestion.type)">
                <div class="grid grid-cols-1 gap-2 sm:gap-3">
                    <template x-for="ans in currentQuestion.answers" :key="ans.id">
                        <button @click="selectAnswer(ans.id)" :disabled="state === 'feedback'"
                                :class="getAnswerClass(ans)"
                                class="w-full text-left p-3 sm:p-4 rounded-xl border font-semibold text-sm transition-all flex items-center justify-between gap-2 min-h-[48px]">
                            <span x-text="ans.answer_text" class="leading-snug"></span>
                            <span x-show="selectedAnswer === ans.id" class="text-violet-500 flex-shrink-0">✓</span>
                        </button>
                    </template>
                </div>
            </template>

            <!-- Type B: Multi-choice -->
            <template x-if="currentQuestion.type === 'multi_choice'">
                <div class="grid grid-cols-1 gap-2 sm:gap-3">
                    <template x-for="ans in currentQuestion.answers" :key="ans.id">
                        <button @click="toggleMultiAnswer(ans.id)" :disabled="state === 'feedback'"
                                :class="getMultiAnswerClass(ans)"
                                class="w-full text-left p-3 sm:p-4 rounded-xl border font-semibold text-sm transition-all flex items-center justify-between gap-2 min-h-[48px]">
                            <span x-text="ans.answer_text" class="leading-snug"></span>
                            <span x-show="selectedMultiAnswers.includes(ans.id)" class="text-violet-500 flex-shrink-0">✓</span>
                        </button>
                    </template>
                </div>
            </template>

            <!-- Type C: Ranking -->
            <template x-if="currentQuestion.type === 'ranking'">
                <div class="space-y-2">
                    <p class="text-xs text-slate-400">Classez les éléments dans le bon ordre :</p>
                    <div class="space-y-2">
                        <template x-for="(ans, index) in rankingList" :key="ans.id">
                            <div :class="getRankingClass(ans)" 
                                 class="p-3 sm:p-4 rounded-xl border font-semibold text-sm flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/20 gap-2">
                                <span x-text="ans.answer_text" class="leading-snug flex-1"></span>
                                <div class="flex space-x-1 flex-shrink-0" x-show="state === 'playing'">
                                    <button @click="moveRanking(index, -1)" :disabled="index === 0" class="p-2 rounded-lg bg-slate-200 dark:bg-slate-700 hover:bg-violet-500 hover:text-white disabled:opacity-30 text-xs">▲</button>
                                    <button @click="moveRanking(index, 1)" :disabled="index === rankingList.length - 1" class="p-2 rounded-lg bg-slate-200 dark:bg-slate-700 hover:bg-violet-500 hover:text-white disabled:opacity-30 text-xs">▼</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Type D: Association -->
            <template x-if="currentQuestion.type === 'association'">
                <div class="space-y-3">
                    <p class="text-xs text-slate-400">Associez chaque élément à son équivalent :</p>
                    <div class="grid grid-cols-1 gap-2 sm:gap-3">
                        <template x-for="ans in currentQuestion.answers" :key="ans.id">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-3 sm:p-4 rounded-xl border border-slate-300 dark:border-slate-700 gap-2">
                                <span class="font-bold text-sm" x-text="ans.answer_text"></span>
                                <select x-model="associationAnswers[ans.id]" :disabled="state === 'feedback'"
                                        :class="getAssociationClass(ans)"
                                        class="w-full sm:w-56 px-3 py-2 rounded-lg border bg-white dark:bg-slate-900 font-medium text-xs outline-none">
                                    <option value="" class="dark:bg-slate-900">-- Choisir --</option>
                                    <template x-for="opt in associationOptions" :key="opt">
                                        <option :value="opt" class="dark:bg-slate-900" x-text="opt"></option>
                                    </template>
                                </select>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

        </div>

        <!-- Feedback Panel -->
        <div x-show="state === 'feedback'" class="p-3 sm:p-4 rounded-2xl border bg-slate-50 dark:bg-slate-800/40 border-violet-500/20 space-y-2">
            <h4 class="text-xs font-bold uppercase tracking-wider text-violet-500">Explication</h4>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed" x-text="currentQuestion.explanation"></p>
        </div>

        <!-- Submit Button — full width on mobile -->
        <div class="flex justify-end pt-2">
            <button x-show="state === 'playing'" @click="validateQuestion()" 
                    class="w-full sm:w-auto py-3 px-6 font-bold text-white bg-violet-600 hover:bg-violet-500 rounded-xl transition-all shadow-md text-sm">
                Valider ma réponse
            </button>
            <button x-show="state === 'feedback'" @click="nextQuestion()" 
                    class="w-full sm:w-auto py-3 px-6 font-bold text-white bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 rounded-xl transition-all shadow-md text-sm">
                <span x-text="currentIndex < questions.length - 1 ? 'Question Suivante ›' : 'Terminer le Quiz'"></span>
            </button>
        </div>
    </div>

    <!-- 4. RESULTS SCREEN -->
    <div x-show="state === 'finished'" x-cloak class="glass-card rounded-2xl sm:rounded-3xl p-6 sm:p-8 md:p-12 text-center shadow-xl space-y-6 sm:space-y-8 relative overflow-hidden">
        <div class="absolute -top-10 -left-10 w-40 h-40 bg-emerald-500/5 rounded-full blur-2xl animate-float"></div>
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-violet-500/5 rounded-full blur-2xl animate-float" style="animation-delay: 2s;"></div>

        <div class="space-y-2 sm:space-y-4">
            <div class="flex justify-center">
                <svg class="w-12 h-12 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-6.75a1.125 1.125 0 00-1.125 1.125v3.375m9 0h-9M9 10.5c0-2.25 1.875-4.0307 4.125-4.125M9 10.5H5.625c-.621 0-1.125-.504-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H9M15 10.5c0-2.25-1.875-4.0307-4.125-4.125M15 10.5h3.375c.621 0 1.125-.504 1.125-1.125V7.875c0-.621-.504-1.125-1.125-1.125H15M9 3.75h6"/></svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold">Quiz Terminé !</h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">Récapitulatif de vos performances.</p>
        </div>

        <!-- Score Ring -->
        <div class="w-28 h-28 sm:w-36 sm:h-36 rounded-full border-8 border-violet-500 flex flex-col items-center justify-center mx-auto shadow-md">
            <span class="text-3xl sm:text-4xl font-extrabold text-violet-500" x-text="score"></span>
            <span class="text-[9px] sm:text-[10px] text-slate-400 uppercase tracking-widest mt-1">points</span>
        </div>

        <!-- Metric badges -->
        <div class="grid grid-cols-3 gap-2 sm:gap-4 max-w-sm mx-auto">
            <div class="p-2 sm:p-3 bg-slate-50 dark:bg-slate-800/30 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-800">
                <span class="text-[10px] sm:text-xs text-slate-400 block mb-0.5">Précision</span>
                <span class="text-base sm:text-lg font-bold" x-text="Math.round((correctCount / questions.length) * 100) + '%'"></span>
            </div>
            <div class="p-2 sm:p-3 bg-slate-50 dark:bg-slate-800/30 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-800">
                <span class="text-[10px] sm:text-xs text-slate-400 block mb-0.5">XP</span>
                <span class="text-base sm:text-lg font-bold text-amber-500" x-text="'+' + xpEarned"></span>
            </div>
            <div class="p-2 sm:p-3 bg-slate-50 dark:bg-slate-800/30 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-800">
                <span class="text-[10px] sm:text-xs text-slate-400 block mb-0.5">Niveau</span>
                <span class="text-base sm:text-lg font-bold text-violet-500" x-text="'Niv ' + userLevel"></span>
            </div>
        </div>

        <!-- Level Up Notification -->
        <div x-show="levelUp" class="p-3 sm:p-4 rounded-xl border border-amber-500/20 bg-amber-500/10 text-amber-500 text-xs sm:text-sm max-w-sm mx-auto animate-bounce">
            🎉 <strong>Bravo !</strong> Vous êtes monté niveau <span x-text="userLevel"></span> !
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <button @click="restartQuiz()" class="px-6 py-3 rounded-xl font-bold text-white bg-violet-600 hover:bg-violet-500 shadow-md hover:shadow-lg transition-all text-sm w-full sm:w-auto">
                Rejouer
            </button>
            <a href="/" class="px-6 py-3 rounded-xl font-bold text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all text-sm w-full sm:w-auto text-center">
                Retour aux catégories
            </a>
        </div>
    </div>
</div>

<!-- Player Alpine JS controller -->
<script>
function quizPlayer() {
    return {
        quizId: <?php echo (int)$quiz['id']; ?>,
        timeLimit: <?php echo (int)$quiz['time_limit']; ?>,
        questions: <?php echo $questionsJson; ?>,
        isFav: <?php echo $isFavorited ? 'true' : 'false'; ?>,
        
        state: 'playing',
        currentIndex: 0,
        currentQuestion: {},
        score: 0,
        correctCount: 0,
        timeSpent: 0,

        timerValue: 0,
        timerPercent: 100,
        timerInterval: null,

        selectedAnswer: null,
        selectedMultiAnswers: [],
        rankingList: [],
        associationAnswers: {},
        associationOptions: [],

        xpEarned: 0,
        userLevel: 1,
        levelUp: false,

        init() {
            this.loadQuestion();
        },

        loadQuestion() {
            this.currentQuestion = this.questions[this.currentIndex];
            this.selectedAnswer = null;
            this.selectedMultiAnswers = [];
            
            if (this.currentQuestion.type === 'ranking') {
                this.rankingList = [...this.currentQuestion.answers];
                this.rankingList.sort(() => Math.random() - 0.5);
            }

            if (this.currentQuestion.type === 'association') {
                this.associationAnswers = {};
                this.currentQuestion.answers.forEach(a => {
                    this.associationAnswers[a.id] = "";
                });
                this.associationOptions = this.currentQuestion.answers.map(a => a.association_pair);
                this.associationOptions.sort(() => Math.random() - 0.5);
            }

            this.startTimer();
        },

        startTimer() {
            clearInterval(this.timerInterval);
            this.timerValue = this.timeLimit;
            this.timerPercent = 100;

            this.timerInterval = setInterval(() => {
                this.timerValue -= 0.1;
                this.timeSpent += 0.1;
                this.timerPercent = (this.timerValue / this.timeLimit) * 100;

                if (this.timerValue <= 0) {
                    clearInterval(this.timerInterval);
                    this.validateQuestion(true);
                }
            }, 100);
        },

        selectAnswer(id) {
            if (this.state !== 'playing') return;
            this.selectedAnswer = id;
        },

        toggleMultiAnswer(id) {
            if (this.state !== 'playing') return;
            const index = this.selectedMultiAnswers.indexOf(id);
            if (index > -1) {
                this.selectedMultiAnswers.splice(index, 1);
            } else {
                this.selectedMultiAnswers.push(id);
            }
        },

        moveRanking(index, direction) {
            const targetIndex = index + direction;
            if (targetIndex < 0 || targetIndex >= this.rankingList.length) return;
            const temp = this.rankingList[index];
            this.rankingList[index] = this.rankingList[targetIndex];
            this.rankingList[targetIndex] = temp;
        },

        validateQuestion(timedOut = false) {
            clearInterval(this.timerInterval);
            this.state = 'feedback';
            let correct = false;

            if (!timedOut) {
                switch (this.currentQuestion.type) {
                    case 'mcq':
                    case 'true_false':
                    case 'image':
                    case 'audio':
                    case 'video':
                        const ans = this.currentQuestion.answers.find(a => a.id === this.selectedAnswer);
                        correct = ans ? (parseInt(ans.is_correct) === 1) : false;
                        break;

                    case 'multi_choice':
                        const correctIds = this.currentQuestion.answers
                            .filter(a => parseInt(a.is_correct) === 1)
                            .map(a => a.id)
                            .sort();
                        const selectedIds = [...this.selectedMultiAnswers].sort();
                        correct = JSON.stringify(correctIds) === JSON.stringify(selectedIds);
                        break;

                    case 'ranking':
                        correct = true;
                        this.rankingList.forEach((item, index) => {
                            if (parseInt(item.match_order) !== (index + 1)) {
                                correct = false;
                            }
                        });
                        break;

                    case 'association':
                        correct = true;
                        this.currentQuestion.answers.forEach(item => {
                            if (this.associationAnswers[item.id] !== item.association_pair) {
                                correct = false;
                            }
                        });
                        break;
                }
            }

            if (correct) {
                this.correctCount++;
                const basePoints = this.currentQuestion.points;
                const bonus = Math.round(basePoints * 0.5 * (this.timerValue / this.timeLimit));
                this.score += basePoints + bonus;
            }
        },

        nextQuestion() {
            if (this.currentIndex < this.questions.length - 1) {
                this.currentIndex++;
                this.state = 'playing';
                this.loadQuestion();
            } else {
                this.finishQuiz();
            }
        },

        async finishQuiz() {
            this.state = 'loading';
            
            try {
                const response = await fetch('/api/quiz/submit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?php echo $csrf_token; ?>'
                    },
                    body: JSON.stringify({
                        quiz_id: this.quizId,
                        score: this.score,
                        correct_count: this.correctCount,
                        total_questions: this.questions.length,
                        time_spent: this.timeSpent
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    this.xpEarned = data.xp_earned;
                    this.userLevel = data.new_level;
                    this.levelUp = data.level_up;
                }
            } catch (err) {
                console.error("Erreur d'enregistrement du score", err);
            }

            this.state = 'finished';
        },

        restartQuiz() {
            this.currentIndex = 0;
            this.score = 0;
            this.correctCount = 0;
            this.timeSpent = 0;
            this.state = 'playing';
            this.loadQuestion();
        },

        async toggleFavorite() {
            try {
                const response = await fetch('/api/quiz/favorite', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?php echo $csrf_token; ?>'
                    },
                    body: JSON.stringify({ quiz_id: this.quizId })
                });
                const data = await response.json();
                if (data.success) {
                    this.isFav = (data.status === 'added');
                }
            } catch (err) {
                console.error(err);
            }
        },

        getAnswerClass(ans) {
            if (this.state === 'playing') {
                return this.selectedAnswer === ans.id 
                    ? 'border-violet-500 bg-violet-500/10' 
                    : 'border-slate-300 dark:border-slate-700 bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800/40';
            }
            if (parseInt(ans.is_correct) === 1) {
                return 'border-emerald-500 bg-emerald-500/10 text-emerald-500 font-bold';
            }
            if (this.selectedAnswer === ans.id && parseInt(ans.is_correct) !== 1) {
                return 'border-red-500 bg-red-500/10 text-red-500';
            }
            return 'border-slate-200 dark:border-slate-800 opacity-50';
        },

        getMultiAnswerClass(ans) {
            if (this.state === 'playing') {
                return this.selectedMultiAnswers.includes(ans.id)
                    ? 'border-violet-500 bg-violet-500/10'
                    : 'border-slate-300 dark:border-slate-700 bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800/40';
            }
            const isCorrect = parseInt(ans.is_correct) === 1;
            const isSelected = this.selectedMultiAnswers.includes(ans.id);
            if (isCorrect) {
                return 'border-emerald-500 bg-emerald-500/10 text-emerald-500 font-bold';
            }
            if (isSelected && !isCorrect) {
                return 'border-red-500 bg-red-500/10 text-red-500';
            }
            return 'border-slate-200 dark:border-slate-800 opacity-50';
        },

        getRankingClass(ans) {
            if (this.state === 'playing') {
                return 'border-slate-300 dark:border-slate-700';
            }
            const currentIndex = this.rankingList.findIndex(item => item.id === ans.id);
            const isOrderCorrect = parseInt(ans.match_order) === (currentIndex + 1);
            return isOrderCorrect
                ? 'border-emerald-500 bg-emerald-500/10 text-emerald-500'
                : 'border-red-500 bg-red-500/10 text-red-500';
        },

        getAssociationClass(ans) {
            if (this.state === 'playing') {
                return 'border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300';
            }
            const val = this.associationAnswers[ans.id];
            const isCorrect = val === ans.association_pair;
            return isCorrect
                ? 'border-emerald-500 bg-emerald-500/10 text-emerald-500 font-bold'
                : 'border-red-500 bg-red-500/10 text-red-500';
        }
    };
}
</script>
