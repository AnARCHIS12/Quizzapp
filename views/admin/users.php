<div class="space-y-8">
    <!-- Header breadcrumb -->
    <div>
        <a href="/admin" class="text-xs font-bold text-violet-500 hover:underline">&larr; Retour au tableau de bord</a>
        <h1 class="text-3xl font-extrabold tracking-tight mt-1">Gestion des Utilisateurs</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Consultez et modifiez les rôles des utilisateurs enregistrés.</p>
    </div>

    <!-- Users table -->
    <div class="glass-card rounded-2xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                        <th class="p-4">Utilisateur</th>
                        <th class="p-4">Adresse e-mail</th>
                        <th class="p-4">Date d'inscription</th>
                        <th class="p-4">Rôle Actuel</th>
                        <th class="p-4 text-right">Modifier le Rôle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all">
                            <td class="p-4 flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-full bg-violet-500 text-white font-extrabold flex items-center justify-center" x-text="String(<?php echo json_encode($u['username']); ?>).substring(0,2).toUpperCase()"></div>
                                <span class="font-bold"><?php echo \App\Core\View::escape($u['username']); ?></span>
                            </td>
                            <td class="p-4"><?php echo \App\Core\View::escape($u['email']); ?></td>
                            <td class="p-4"><?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?></td>
                            <td class="p-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?php echo (int)$u['role_id'] === 1 ? 'bg-amber-500/10 text-amber-500 border border-amber-500/20' : 'bg-violet-500/10 text-violet-500 border border-violet-500/20'; ?>">
                                    <?php echo $u['role_name']; ?>
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <form action="/admin/users/role" method="POST" class="inline-flex items-center space-x-2">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <select name="role_id" onchange="this.form.submit()"
                                            class="px-2.5 py-1 text-xs border border-slate-300 dark:border-slate-700 bg-transparent rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-transparent outline-none transition-all">
                                        <option value="1" <?php echo (int)$u['role_id'] === 1 ? 'selected' : ''; ?>>admin</option>
                                        <option value="2" <?php echo (int)$u['role_id'] === 2 ? 'selected' : ''; ?>>user</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
