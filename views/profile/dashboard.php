<div class="space-y-8" x-data="{ activeTab: 'stats' }">
    <!-- User Hero Profile -->
    <div class="glass-card rounded-2xl p-6 md:p-8 flex flex-col md:flex-row items-center md:items-start justify-between shadow-lg relative overflow-hidden">
        <div class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6 z-10">
            <!-- Avatar Frame -->
            <div class="relative">
                <img class="w-24 h-24 rounded-full border-4 border-violet-500 shadow-lg object-cover" 
                     src="<?php echo (!empty($user['avatar_url']) && (str_starts_with($user['avatar_url'], '/') || str_starts_with($user['avatar_url'], 'http'))) ? $user['avatar_url'] : 'https://api.dicebear.com/7.x/bottts/svg?seed=' . urlencode($user['username']); ?>" 
                     alt="Avatar">
                <span class="absolute bottom-0 right-0 px-2 py-0.5 text-xs font-extrabold bg-violet-600 text-white rounded-full border border-slate-900">LVL <?php echo (int)($stats['level'] ?? 1); ?></span>
            </div>
            
            <div class="text-center md:text-left space-y-2">
                <h1 class="text-3xl font-extrabold tracking-tight"><?php echo \App\Core\View::escape($user['username']); ?></h1>
                <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo \App\Core\View::escape($user['email']); ?></p>
                <div class="flex items-center justify-center md:justify-start space-x-2">
                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full <?php echo (int)$user['role_id'] === 1 ? 'bg-amber-500/10 text-amber-500 border border-amber-500/20' : 'bg-violet-500/10 text-violet-500 border border-violet-500/20'; ?>">
                        <?php echo (int)$user['role_id'] === 1 ? 'Administrateur' : 'Joueur'; ?>
                    </span>
                    <span class="text-xs text-slate-400">Inscrit le <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                </div>
            </div>
        </div>

        <!-- XP Progress Panel -->
        <div class="w-full md:w-64 mt-6 md:mt-0 bg-slate-100 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-4 z-10">
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Progression XP</span>
                <span class="text-xs font-bold text-violet-500"><?php echo (int)($stats['xp'] ?? 0); ?> / <?php echo ((int)($stats['level'] ?? 1)) * 100; ?> XP</span>
            </div>
            <!-- Progress Bar -->
            <?php 
                $level = (int)($stats['level'] ?? 1);
                $xp = (int)($stats['xp'] ?? 0);
                $targetXp = $level * 100;
                $pct = min(100, max(0, ($xp / $targetXp) * 100));
            ?>
            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3 overflow-hidden">
                <div class="bg-gradient-to-r from-violet-500 to-purple-600 h-full rounded-full transition-all duration-500" style="width: <?php echo $pct; ?>%"></div>
            </div>
            <p class="text-[10px] text-slate-400 mt-2 text-center">Plus que <?php echo ($targetXp - $xp); ?> XP pour le niveau <?php echo ($level + 1); ?> !</p>
        </div>
    </div>

    <!-- Tab Selection Navigation -->
    <div class="flex border-b border-slate-200 dark:border-slate-800">
        <button @click="activeTab = 'stats'" 
                :class="activeTab === 'stats' ? 'border-violet-500 text-violet-500 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="flex-1 py-3 border-b-2 text-center transition-all text-sm font-semibold">
            Statistiques
        </button>
        <button @click="activeTab = 'badges'" 
                :class="activeTab === 'badges' ? 'border-violet-500 text-violet-500 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="flex-1 py-3 border-b-2 text-center transition-all text-sm font-semibold">
            Badges & Succès
        </button>
        <button @click="activeTab = 'history'" 
                :class="activeTab === 'history' ? 'border-violet-500 text-violet-500 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="flex-1 py-3 border-b-2 text-center transition-all text-sm font-semibold">
            Historique des Duels
        </button>
    </div>

    <!-- STATS TAB CONTENT -->
    <div x-show="activeTab === 'stats'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Card 1 -->
        <div class="glass-card rounded-2xl p-6 shadow-md flex items-center space-x-4">
            <div class="p-3 bg-violet-500/10 text-violet-500 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Quiz Complétés</p>
                <p class="text-2xl font-bold"><?php echo (int)($stats['total_played'] ?? 0); ?></p>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="glass-card rounded-2xl p-6 shadow-md flex items-center space-x-4">
            <div class="p-3 bg-emerald-500/10 text-emerald-500 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Taux de Réussite</p>
                <p class="text-2xl font-bold"><?php echo $successRate; ?>%</p>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="glass-card rounded-2xl p-6 shadow-md flex items-center space-x-4">
            <div class="p-3 bg-amber-500/10 text-amber-500 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Temps Moyen / Rép.</p>
                <p class="text-2xl font-bold"><?php echo round((float)($stats['average_time_per_question'] ?? 0.0), 1); ?>s</p>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="glass-card rounded-2xl p-6 shadow-md flex items-center space-x-4">
            <div class="p-3 bg-rose-500/10 text-rose-500 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Réponses Correctes</p>
                <p class="text-2xl font-bold"><?php echo (int)($stats['correct_count'] ?? 0); ?></p>
            </div>
        </div>
    </div>

    <!-- BADGES TAB CONTENT -->
    <div x-show="activeTab === 'badges'" class="space-y-6">
        <h2 class="text-xl font-bold tracking-tight">Vos Succès débloqués</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-6">
            <?php 
                // Define global available achievements list to map unlocked ones
                $allAchievements = [
                    ['id' => 1, 'name' => 'Premier pas', 'description' => 'Complétez votre premier quiz.', 'image' => 'badge_first_quiz.png'],
                    ['id' => 2, 'name' => 'Passionné', 'description' => 'Complétez 10 quiz.', 'image' => 'badge_10_quizzes.png'],
                    ['id' => 3, 'name' => 'Expert', 'description' => 'Complétez 50 quiz.', 'image' => 'badge_50_quizzes.png'],
                    ['id' => 4, 'name' => 'Nouveau Niveau', 'description' => 'Atteignez le niveau 5.', 'image' => 'badge_level_5.png'],
                    ['id' => 5, 'name' => 'Maître du Quiz', 'description' => 'Atteignez le niveau 10.', 'image' => 'badge_level_10.png'],
                    ['id' => 6, 'name' => 'Sans Faute', 'description' => 'Obtenez un score parfait de 100% sur un quiz.', 'image' => 'badge_perfect_score.png']
                ];

                $unlockedIds = array_column($achievements, 'id');
            ?>

            <?php foreach ($allAchievements as $ach): ?>
                <?php 
                    $isUnlocked = in_array($ach['id'], $unlockedIds, true);
                ?>
                <div class="glass-card rounded-2xl p-4 flex flex-col items-center text-center shadow-md relative <?php echo !$isUnlocked ? 'opacity-40 grayscale' : 'border-violet-500/30'; ?>">
                    <!-- Badge Icon Circle -->
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br <?php echo $isUnlocked ? 'from-violet-500 to-purple-600 shadow-md' : 'from-slate-300 to-slate-400 dark:from-slate-700 dark:to-slate-800'; ?> flex items-center justify-center text-white mb-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <p class="text-xs font-bold"><?php echo $ach['name']; ?></p>
                    <p class="text-[10px] text-slate-400 mt-1"><?php echo $ach['description']; ?></p>
                    <?php if ($isUnlocked): ?>
                        <span class="absolute -top-1 -right-1 flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- HISTORY TAB CONTENT -->
    <div x-show="activeTab === 'history'" class="glass-card rounded-2xl shadow-md overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-lg font-bold tracking-tight">Historique récent des Duels Privés</h2>
        </div>
        
        <?php if (empty($history)): ?>
            <div class="p-8 text-center text-slate-500 dark:text-slate-400 text-sm">
                <svg class="w-12 h-12 mx-auto text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Aucune partie jouée pour le moment. Créez un salon de duel et défiez vos amis !
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                            <th class="p-4">Date</th>
                            <th class="p-4">Quiz</th>
                            <th class="p-4 text-center">Joueurs</th>
                            <th class="p-4 text-center">Votre Score</th>
                            <th class="p-4">Vainqueur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        <?php foreach ($history as $h): ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                <td class="p-4"><?php echo date('d/m/Y H:i', strtotime($h['created_at'])); ?></td>
                                <td class="p-4 font-bold"><?php echo \App\Core\View::escape($h['quiz_title']); ?></td>
                                <td class="p-4 text-center"><?php echo $h['total_players']; ?></td>
                                <td class="p-4 text-center font-bold text-violet-500"><?php echo $h['score']; ?> pts</td>
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-emerald-500/10 text-emerald-500">
                                        <svg class="w-3.5 h-3.5 text-emerald-500 fill-current" viewBox="0 0 24 24"><path d="M18 2h-12c-1.1 0-2 .9-2 2v3c0 2.2 1.8 4 4 4h1v3c0 2.2-1.8 4-4 4v2h14v-2c-2.2 0-4-1.8-4-4v-3h1c2.2 0 4-1.8 4-4v-3c0-1.1-.9-2-2-2zm-10 6c-1.1 0-2-.9-2-2v-2h2v4zm10-2v2c0 1.1-.9 2-2 2v-4h2z"/></svg>
                                        <span><?php echo \App\Core\View::escape($h['winner_name']); ?></span>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
