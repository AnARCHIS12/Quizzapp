<div class="space-y-8">
    <div>
        <h1 class="text-3xl font-extrabold tracking-tight">Panneau d'Administration</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Supervisez l'activité globale du site, gérez les contenus et administrez la sécurité.</p>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
        <div class="glass-card rounded-2xl p-6 shadow-sm">
            <span class="text-xs text-slate-400 font-bold uppercase tracking-wider block mb-1">Joueurs</span>
            <span class="text-2xl font-extrabold" x-text="<?php echo $stats['users']; ?>"></span>
        </div>
        <div class="glass-card rounded-2xl p-6 shadow-sm">
            <span class="text-xs text-slate-400 font-bold uppercase tracking-wider block mb-1">Catégories</span>
            <span class="text-2xl font-extrabold" x-text="<?php echo $stats['categories']; ?>"></span>
        </div>
        <div class="glass-card rounded-2xl p-6 shadow-sm">
            <span class="text-xs text-slate-400 font-bold uppercase tracking-wider block mb-1">Quiz</span>
            <span class="text-2xl font-extrabold" x-text="<?php echo $stats['quizzes']; ?>"></span>
        </div>
        <div class="glass-card rounded-2xl p-6 shadow-sm">
            <span class="text-xs text-slate-400 font-bold uppercase tracking-wider block mb-1">Questions</span>
            <span class="text-2xl font-extrabold" x-text="<?php echo $stats['questions']; ?>"></span>
        </div>
        <div class="glass-card rounded-2xl p-6 shadow-sm">
            <span class="text-xs text-slate-400 font-bold uppercase tracking-wider block mb-1">Parties (Duels)</span>
            <span class="text-2xl font-extrabold" x-text="<?php echo $stats['matches']; ?>"></span>
        </div>
    </div>

    <!-- Content Split grids: Database Tools and Logging -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- 1. Database management Utilities (Import/Export/Backup) -->
        <div class="space-y-6">
            <div class="glass-card rounded-2xl p-6 shadow-md space-y-6">
                <h3 class="text-lg font-bold tracking-tight">Outils de base de données</h3>
                
                <!-- Backup -->
                <div class="space-y-2">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Sauvegarde</h4>
                    <p class="text-xs text-slate-500">Générez un fichier SQL complet contenant la structure et les données.</p>
                    <form action="/admin/backup" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <button type="submit" class="w-full text-center py-2 px-4 rounded-xl font-bold bg-violet-600 hover:bg-violet-500 text-white text-xs shadow transition-all">
                            Exporter un Backup SQL
                        </button>
                    </form>
                </div>

                <!-- Export JSON -->
                <div class="space-y-2 border-t border-slate-100 dark:border-slate-800 pt-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Exportation Quiz JSON</h4>
                    <p class="text-xs text-slate-500">Téléchargez l'intégralité des quiz sous format JSON standardisé.</p>
                    <form action="/admin/export" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <button type="submit" class="w-full text-center py-2 px-4 rounded-xl font-bold bg-violet-500/10 text-violet-500 hover:bg-violet-500/20 text-xs border border-violet-500/20 transition-all">
                            Exporter en JSON
                        </button>
                    </form>
                </div>

                <!-- Import JSON -->
                <div class="space-y-2 border-t border-slate-100 dark:border-slate-800 pt-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Importation Quiz JSON</h4>
                    <p class="text-xs text-slate-500">Uploadez un fichier JSON conforme pour alimenter les quiz en base de données.</p>
                    <form action="/admin/import" method="POST" enctype="multipart/form-data" class="space-y-3">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="file" name="json_file" accept=".json" required
                               class="w-full text-xs text-slate-500 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-[10px] file:font-semibold file:bg-violet-500/10 file:text-violet-500 hover:file:bg-violet-500/20 file:cursor-pointer">
                        <button type="submit" class="w-full py-2 px-4 rounded-xl font-bold bg-amber-500 hover:bg-amber-400 text-white text-xs shadow transition-all">
                            Importer JSON
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="glass-card rounded-2xl p-6 shadow-md space-y-3">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Liens de gestion</h3>
                <a href="/admin/users" class="block py-2 px-3 rounded-lg text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-800/40 text-violet-500 transition-colors">
                    👥 Gestion des Utilisateurs & Rôles
                </a>
            </div>
        </div>

        <!-- 2. Security Logs and Registrations -->
        <div class="md:col-span-2 space-y-6">
            <!-- Recent Registrations -->
            <div class="glass-card rounded-2xl shadow-md overflow-hidden">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-lg font-bold tracking-tight">Derniers utilisateurs inscrits</h3>
                </div>
                <div class="divide-y divide-slate-200 dark:divide-slate-800 text-xs">
                    <?php foreach ($recentUsers as $ru): ?>
                        <div class="p-4 flex items-center justify-between hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-full bg-violet-500 text-white font-extrabold flex items-center justify-center" x-text="String(<?php echo json_encode($ru['username']); ?>).substring(0,2).toUpperCase()"></div>
                                <div>
                                    <span class="font-bold block"><?php echo \App\Core\View::escape($ru['username']); ?></span>
                                    <span class="text-[10px] text-slate-400"><?php echo \App\Core\View::escape($ru['email']); ?></span>
                                </div>
                            </div>
                            <span class="text-[10px] text-slate-400"><?php echo date('d/m/Y H:i', strtotime($ru['created_at'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Activity Logs -->
            <div class="glass-card rounded-2xl shadow-md overflow-hidden">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-lg font-bold tracking-tight">Journalisation de sécurité (Logs)</h3>
                </div>
                <div class="divide-y divide-slate-200 dark:divide-slate-800 text-[11px] font-mono">
                    <?php foreach ($recentLogs as $rl): ?>
                        <div class="p-3 flex items-start justify-between hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all gap-4">
                            <div class="space-y-1">
                                <span class="text-violet-500 font-bold">[<?php echo $rl['username'] ? \App\Core\View::escape($rl['username']) : 'Invité'; ?>]</span>
                                <span class="text-slate-600 dark:text-slate-300"><?php echo \App\Core\View::escape($rl['action']); ?></span>
                                <span class="block text-[9px] text-slate-400">IP: <?php echo \App\Core\View::escape($rl['ip_address']); ?> | Agent: <?php echo \App\Core\View::escape(substr($rl['user_agent'], 0, 50)); ?>...</span>
                            </div>
                            <span class="text-slate-400 shrink-0"><?php echo date('d/m H:i:s', strtotime($rl['created_at'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
