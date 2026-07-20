<?php
/**
 * View: Category Quizzes list
 * Displays the list of quizzes available inside a specific category,
 * along with subcategories if any, and a dynamic AI-generated quiz starter card.
 */
?>
<div class="space-y-10">
    <!-- Breadcrumb header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <a href="/" class="text-xs font-bold text-violet-500 hover:text-violet-600 dark:text-violet-400 dark:hover:text-violet-300 flex items-center space-x-1.5 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"></path></svg>
                <span>Retour aux catégories</span>
            </a>
            <h1 class="text-3xl font-extrabold tracking-tight mt-2 text-slate-800 dark:text-slate-100"><?php echo \App\Core\View::escape($category['name']); ?></h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1"><?php echo \App\Core\View::escape($category['description']); ?></p>
        </div>
    </div>

    <!-- Subcategories Section if applicable -->
    <?php if (!empty($subcategories)): ?>
        <div class="space-y-4">
            <h2 class="text-lg font-bold tracking-tight text-slate-700 dark:text-slate-300">Sous-thématiques disponibles</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($subcategories as $sub): ?>
                    <a href="/category/<?php echo \App\Core\View::escape($sub['slug']); ?>" 
                       class="glass-card rounded-2xl p-5 border border-slate-200 dark:border-slate-800 hover:border-violet-500/40 transition-all duration-300 flex flex-col justify-between group">
                        <div>
                            <h3 class="font-bold text-slate-800 dark:text-slate-100 group-hover:text-violet-500 transition-colors"><?php echo \App\Core\View::escape($sub['name']); ?></h3>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-2 line-clamp-2"><?php echo \App\Core\View::escape($sub['description']); ?></p>
                        </div>
                        <span class="text-[10px] font-bold text-violet-500 mt-4 flex items-center gap-1 group-hover:translate-x-1 transition-transform">
                            Voir la thématique &rarr;
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- AI Dynamic Quiz Starter Card -->
    <div class="glass-card rounded-3xl p-6 md:p-8 shadow-lg border border-violet-500/20 bg-gradient-to-br from-violet-600/5 to-purple-600/5 relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="absolute top-0 right-0 w-64 h-64 bg-violet-500/10 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="space-y-2 max-w-xl text-center md:text-left">
            <span class="px-2.5 py-1 text-[9px] font-black uppercase rounded-full bg-violet-500 text-white tracking-widest">
                Génération IA
            </span>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-800 dark:text-slate-100 flex items-center justify-center md:justify-start gap-2">
                <svg class="w-6 h-6 text-violet-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                <span>Quiz IA Infini & unique sur "<?php echo \App\Core\View::escape($category['name']); ?>"</span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                Le générateur de quiz par IA génère à la volée un lot de 10 questions inédites. La garantie de ne jamais tomber sur les mêmes questions d'une partie à l'autre !
            </p>
        </div>

        <a href="/quiz/dynamic/<?php echo (int)$category['id']; ?>" 
           class="px-6 py-3.5 rounded-xl font-bold text-white bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 shadow-md hover:shadow-lg transition-all text-sm w-full md:w-auto text-center flex items-center justify-center gap-2">
            <span>Lancer le Quiz IA</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
        </a>
    </div>

    <!-- Quizzes Grid -->
    <div class="space-y-4">
        <h2 class="text-lg font-bold tracking-tight text-slate-700 dark:text-slate-300">Quiz prédéfinis de la communauté</h2>
        
        <?php if (empty($quizzes)): ?>
            <div class="glass-card rounded-2xl p-12 text-center text-slate-500 dark:text-slate-400 border border-slate-200/80 dark:border-slate-800/40">
                <svg class="w-12 h-12 mx-auto text-slate-400 mb-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"></path></svg>
                Aucun quiz statique n'a encore été créé pour cette catégorie. Testez le quiz IA ci-dessus !
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($quizzes as $quiz): ?>
                    <div class="glass-card rounded-2xl p-6 flex flex-col justify-between hover:shadow-lg transition-all border border-slate-200 dark:border-slate-800/40 hover:border-violet-500/20 group">
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="px-2.5 py-1 text-[10px] font-extrabold uppercase rounded-lg bg-violet-500/10 text-violet-600 dark:text-violet-400 tracking-wider">
                                    Quiz
                                </span>
                                <span class="text-xs text-amber-600 dark:text-amber-400 font-bold flex items-center space-x-1">
                                    <svg class="w-4 h-4 text-amber-500 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"></path></svg>
                                    <span>+<?php echo (int)$quiz['xp_reward']; ?> XP</span>
                                </span>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100 group-hover:text-violet-500 transition-colors duration-200"><?php echo \App\Core\View::escape($quiz['title']); ?></h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed"><?php echo \App\Core\View::escape($quiz['description']); ?></p>
                        </div>

                        <div class="mt-8 pt-4 border-t border-slate-200/40 dark:border-slate-800/40 flex items-center justify-between">
                            <div class="flex space-x-4 text-xs text-slate-400 font-medium">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 text-slate-400 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                    <span><?php echo $quiz['question_count']; ?> Questions</span>
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 text-slate-400 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span><?php echo (int)$quiz['time_limit']; ?>s / question</span>
                                </span>
                            </div>
                            <a href="/quiz/<?php echo $quiz['id']; ?>" class="px-5 py-2 font-bold text-white bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 rounded-xl transition-all shadow-md text-sm">
                                Commencer
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
