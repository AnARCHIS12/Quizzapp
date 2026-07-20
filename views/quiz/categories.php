<?php
/**
 * View: Categories Page
 * Displays available quiz categories with a modern premium card design, custom SVG icons, and theme-tailored color accents.
 */

if (!function_exists('getCategoryIcon')) {
    /**
     * Returns matching SVG icon path for a given category slug (clean, thin vector icons)
     */
    function getCategoryIcon(string $slug): string {
        switch ($slug) {
            case 'astronomie':
                return '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21m0 0l-.813-5.096m.813 5.096a11.95 11.95 0 01-3.078-1.323m3.078 1.323a11.947 11.947 0 003.078-1.323M9.813 15.904a7.487 7.487 0 01-1.626 0M9 21a9.01 9.01 0 01-2.287-4.423M9 21a9.01 9.01 0 002.287-4.423M9.813 15.904l3.187-6.375a1.875 1.875 0 113.596 1.2l-3.187 6.376m-3.596-1.2a7.482 7.482 0 011.626 0m3.596 1.2a7.487 7.487 0 001.626 0m0 0L19 21m0 0l-.814-5.096m.814 5.096a11.95 11.95 0 01-3.078-1.323m3.078 1.323a11.947 11.947 0 003.078-1.323M19.813 15.904a7.487 7.487 0 01-1.626 0M19 21a9.01 9.01 0 01-2.287-4.423M19 21a9.01 9.01 0 002.287-4.423m0 0l-3.187-6.375a1.875 1.875 0 113.596-1.2l3.187 6.376M12 3v1m0 8V8m-6.364-.364l.707.707M18.364 7.636l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707"></path></svg>';
            case 'geographie':
                return '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M6.115 5.19l.319 1.913A1 1 0 007.42 7.84l1.324-.083a1 1 0 01.875.437l.628 1.005a1 1 0 001.488.244l1.41-1.129a1 1 0 01.885-.159l1.49.51a1 1 0 001.244-.69l.334-1.166a1 1 0 01.536-.67l1.398-.699M12 21a9 9 0 100-18 9 9 0 000 18z"></path></svg>';
            case 'mathematiques':
                return '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>';
            case 'informatique':
                return '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5"></path></svg>';
            case 'histoire':
                return '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"></path></svg>';
            case 'sciences-nature':
                return '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v11.896m0-11.896a5.497 5.497 0 013.5 0M9.75 3.104A5.497 5.497 0 006.25 3.1a5.497 5.497 0 00-3.5 0m16.5 0a5.497 5.497 0 01-3.5 0m3.5 0v11.896m0-11.896a5.497 5.497 0 00-3.5 0m-3.5 0v11.896M9.75 15h4.5m-4.5 3h4.5m-4.5 3h4.5m-11.5-6h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2a2 2 0 012-2zm12 0h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2a2 2 0 012-2z"></path></svg>';
            case 'litterature':
                return '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"></path></svg>';
            case 'cinema':
                return '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25zM3.75 9h16.5M3.75 15h16.5M9 3.75v16.5M15 3.75v16.5"></path></svg>';
            case 'art-peinture':
                return '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-3.078 0L3.75 17.5v-11A2.25 2.25 0 016 4.25h12A2.25 2.25 0 0120.25 6.5v11l-2.702-1.378a3 3 0 00-3.078 0l-4.94 2.518zM12 10.25a1.5 1.5 0 110-3 1.5 1.5 0 010 3z"></path></svg>';
            case 'mythologie':
                return '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z"></path></svg>';
            case 'politique':
            case 'politique-generale':
                return '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3-1M6 7V20M18 7l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 1M18 7V20M4 20h16"></path></svg>';
            case 'socialisme':
                return '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"></path></svg>';
            case 'anarchisme':
                return '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18M3 12h18M12 12m-9 0a9 9 0 1118 0 9 9 0 01-18 0z"></path></svg>';
            case 'communisme':
                return '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499c.173-.42.766-.42.94 0l2.373 5.766 6.136.564c.453.042.633.616.293.929l-4.636 4.267 1.349 6.009c.1.442-.383.805-.765.566L12 18.257l-5.17 2.733c-.382.239-.865-.124-.765-.566l1.349-6.009-4.636-4.267c-.34-.313-.16-.887.293-.929l6.136-.564 2.373-5.766z"></path></svg>';
            default:
                return '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"></path></svg>';
        }
    }
}

if (!function_exists('getCategoryColorClasses')) {
    /**
     * Returns tailwind class names for custom coloring
     */
    function getCategoryColorClasses(string $slug): array {
        switch ($slug) {
            case 'astronomie':
                return [
                    'bg' => 'bg-indigo-500/10 dark:bg-indigo-500/5',
                    'text' => 'text-indigo-600 dark:text-indigo-400',
                    'border' => 'hover:border-indigo-500/40 focus-within:ring-indigo-500/40',
                    'iconBg' => 'bg-indigo-500/20 text-indigo-600 dark:text-indigo-300'
                ];
            case 'geographie':
                return [
                    'bg' => 'bg-emerald-500/10 dark:bg-emerald-500/5',
                    'text' => 'text-emerald-600 dark:text-emerald-400',
                    'border' => 'hover:border-emerald-500/40 focus-within:ring-emerald-500/40',
                    'iconBg' => 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-300'
                ];
            case 'mathematiques':
                return [
                    'bg' => 'bg-cyan-500/10 dark:bg-cyan-500/5',
                    'text' => 'text-cyan-600 dark:text-cyan-400',
                    'border' => 'hover:border-cyan-500/40 focus-within:ring-cyan-500/40',
                    'iconBg' => 'bg-cyan-500/20 text-cyan-600 dark:text-cyan-300'
                ];
            case 'informatique':
                return [
                    'bg' => 'bg-amber-500/10 dark:bg-amber-500/5',
                    'text' => 'text-amber-600 dark:text-amber-400',
                    'border' => 'hover:border-amber-500/40 focus-within:ring-amber-500/40',
                    'iconBg' => 'bg-amber-500/20 text-amber-600 dark:text-amber-300'
                ];
            case 'histoire':
                return [
                    'bg' => 'bg-rose-500/10 dark:bg-rose-500/5',
                    'text' => 'text-rose-600 dark:text-rose-400',
                    'border' => 'hover:border-rose-500/40 focus-within:ring-rose-500/40',
                    'iconBg' => 'bg-rose-500/20 text-rose-600 dark:text-rose-300'
                ];
            case 'sciences-nature':
                return [
                    'bg' => 'bg-teal-500/10 dark:bg-teal-500/5',
                    'text' => 'text-teal-600 dark:text-teal-400',
                    'border' => 'hover:border-teal-500/40 focus-within:ring-teal-500/40',
                    'iconBg' => 'bg-teal-500/20 text-teal-600 dark:text-teal-300'
                ];
            case 'litterature':
                return [
                    'bg' => 'bg-fuchsia-500/10 dark:bg-fuchsia-500/5',
                    'text' => 'text-fuchsia-600 dark:text-fuchsia-400',
                    'border' => 'hover:border-fuchsia-500/40 focus-within:ring-fuchsia-500/40',
                    'iconBg' => 'bg-fuchsia-500/20 text-fuchsia-600 dark:text-fuchsia-300'
                ];
            case 'cinema':
                return [
                    'bg' => 'bg-red-500/10 dark:bg-red-500/5',
                    'text' => 'text-red-600 dark:text-red-400',
                    'border' => 'hover:border-red-500/40 focus-within:ring-red-500/40',
                    'iconBg' => 'bg-red-500/20 text-red-600 dark:text-red-300'
                ];
            case 'art-peinture':
                return [
                    'bg' => 'bg-violet-500/10 dark:bg-violet-500/5',
                    'text' => 'text-violet-600 dark:text-violet-400',
                    'border' => 'hover:border-violet-500/40 focus-within:ring-violet-500/40',
                    'iconBg' => 'bg-violet-500/20 text-violet-600 dark:text-violet-300'
                ];
            case 'mythologie':
                return [
                    'bg' => 'bg-orange-500/10 dark:bg-orange-500/5',
                    'text' => 'text-orange-600 dark:text-orange-400',
                    'border' => 'hover:border-orange-500/40 focus-within:ring-orange-500/40',
                    'iconBg' => 'bg-orange-500/20 text-orange-600 dark:text-orange-300'
                ];
            case 'politique':
            case 'socialisme':
            case 'communisme':
                return [
                    'bg' => 'bg-red-500/10 dark:bg-red-500/5',
                    'text' => 'text-red-600 dark:text-red-400',
                    'border' => 'hover:border-red-500/40 focus-within:ring-red-500/40',
                    'iconBg' => 'bg-red-500/20 text-red-600 dark:text-red-300'
                ];
            case 'anarchisme':
                return [
                    'bg' => 'bg-slate-500/10 dark:bg-slate-500/5',
                    'text' => 'text-slate-700 dark:text-slate-300',
                    'border' => 'hover:border-slate-500/40 focus-within:ring-slate-500/40',
                    'iconBg' => 'bg-slate-500/20 text-slate-700 dark:text-slate-300'
                ];
            case 'politique-generale':
                return [
                    'bg' => 'bg-sky-500/10 dark:bg-sky-500/5',
                    'text' => 'text-sky-600 dark:text-sky-400',
                    'border' => 'hover:border-sky-500/40 focus-within:ring-sky-500/40',
                    'iconBg' => 'bg-sky-500/20 text-sky-600 dark:text-sky-300'
                ];
            default:
                return [
                    'bg' => 'bg-violet-500/10 dark:bg-violet-500/5',
                    'text' => 'text-violet-600 dark:text-violet-400',
                    'border' => 'hover:border-violet-500/40 focus-within:ring-violet-500/40',
                    'iconBg' => 'bg-violet-500/20 text-violet-600 dark:text-violet-300'
                ];
        }
    }
}
?>

<div class="space-y-6 sm:space-y-12">
    <!-- Hero Banner Section -->
    <div class="glass-card rounded-2xl sm:rounded-3xl p-5 sm:p-8 md:p-12 text-center shadow-lg relative overflow-hidden flex flex-col items-center justify-center border border-white/20 dark:border-slate-800/40">
        <!-- Floating ambient shapes -->
        <div class="absolute -top-10 -left-10 w-40 h-40 bg-violet-500/10 rounded-full blur-2xl animate-float"></div>
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-amber-500/10 rounded-full blur-2xl animate-float" style="animation-delay: 2s;"></div>

        <h1 class="text-2xl sm:text-4xl md:text-5xl font-extrabold tracking-tight max-w-2xl leading-tight text-slate-800 dark:text-slate-100">
            Développez votre esprit avec nos <span class="bg-gradient-to-r from-violet-500 via-purple-500 to-amber-500 bg-clip-text text-transparent">Quiz Thématiques</span>
        </h1>
        <p class="text-xs sm:text-sm md:text-base text-slate-500 dark:text-slate-400 mt-3 sm:mt-4 max-w-xl">
            Une base complète de quiz rigoureusement rédigés sur des thématiques variées.
        </p>
        <div class="mt-5 sm:mt-8 flex flex-col sm:flex-row items-center justify-center gap-3 w-full sm:w-auto">
            <a href="#categories-list" class="w-full sm:w-auto text-center px-6 py-3 rounded-xl font-bold text-white bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 shadow-md hover:shadow-lg transition-all text-sm">
                Parcourir les catégories
            </a>
            <a href="/duel" class="w-full sm:w-auto text-center px-6 py-3 rounded-xl font-bold text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all text-sm flex items-center justify-center gap-2">
                <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z"></path></svg>
                <span>Duel en direct</span>
            </a>
        </div>
    </div>

    <!-- Search / Filter Component -->
    <div id="categories-list" class="space-y-6" x-data="{ searchQuery: '' }">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-800 dark:text-slate-100">Catégories Disponibles</h2>
            <div class="relative w-full sm:w-72">
                <input type="text" x-model="searchQuery" placeholder="Rechercher une thématique..." 
                       class="w-full pl-10 pr-4 py-2.5 bg-white/60 dark:bg-slate-900/40 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-transparent outline-none transition-all text-sm">
                <svg class="absolute left-3 top-3 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>

        <!-- Categories Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($categories as $cat): 
                $colors = getCategoryColorClasses($cat['slug']);
                $quizCount = \App\Models\Category::getQuizCount((int)$cat['id']);
            ?>
                <div x-show="searchQuery === '' || String(<?php echo json_encode(strtolower($cat['name'])); ?>).includes(searchQuery.toLowerCase())"
                     class="glass-card rounded-2xl p-6 shadow-sm hover:shadow-xl border border-slate-200/80 dark:border-slate-800/40 <?php echo $colors['border']; ?> transition-all duration-300 flex flex-col justify-between group">
                    <div class="space-y-4">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-transform duration-300 group-hover:scale-110 <?php echo $colors['iconBg']; ?>">
                            <?php echo getCategoryIcon($cat['slug']); ?>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100 group-hover:text-violet-500 dark:group-hover:text-violet-400 transition-colors">
                            <?php echo \App\Core\View::escape($cat['name']); ?>
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed line-clamp-3">
                            <?php echo \App\Core\View::escape($cat['description']); ?>
                        </p>
                    </div>
                    <div class="mt-8 flex items-center justify-between border-t border-slate-200/40 dark:border-slate-800/40 pt-4">
                        <span class="text-xs font-semibold text-slate-400 flex items-center space-x-1">
                            <svg class="w-4 h-4 text-slate-400 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <?php echo $quizCount; ?> <?php echo $quizCount > 1 ? 'Quiz' : 'Quiz'; ?>
                        </span>
                        <a href="/category/<?php echo \App\Core\View::escape($cat['slug']); ?>" class="text-xs font-bold text-violet-500 dark:text-violet-400 hover:text-violet-600 dark:hover:text-violet-300 flex items-center space-x-1">
                            <span>Voir la catégorie</span>
                            <svg class="w-3.5 h-3.5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path></svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Quizzes section (only shown if at least one quiz has been played) -->
    <?php 
    $playedQuizzes = array_filter($quizzes, function($q) {
        return (int)($q['play_count'] ?? 0) > 0;
    });
    if (!empty($playedQuizzes)): 
    ?>
    <div class="space-y-6">
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-800 dark:text-slate-100">Quiz Populaires & Récents</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach (array_slice($playedQuizzes, 0, 4) as $quiz): ?>
                <div class="glass-card rounded-2xl p-6 flex flex-col justify-between border border-slate-200/80 dark:border-slate-800/40 hover:border-violet-500/30 transition-all duration-300 shadow-sm hover:shadow-md">
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="px-2.5 py-1 text-[10px] font-extrabold uppercase rounded-lg bg-violet-500/10 text-violet-600 dark:text-violet-400 tracking-wider">
                                <?php echo \App\Core\View::escape($quiz['category_name']); ?>
                            </span>
                            <span class="text-xs text-slate-400 flex items-center space-x-1">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span><?php echo (int)$quiz['time_limit'] * (int)$quiz['question_count']; ?>s</span>
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 mt-2"><?php echo \App\Core\View::escape($quiz['title']); ?></h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed"><?php echo \App\Core\View::escape($quiz['description']); ?></p>
                    </div>
                    
                    <div class="mt-6 pt-4 border-t border-slate-200/40 dark:border-slate-800/40 flex items-center justify-between">
                        <span class="text-xs text-slate-400 font-medium flex items-center">
                            <svg class="w-4 h-4 text-slate-400 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            <span><?php echo $quiz['question_count']; ?> questions</span>
                        </span>
                        <a href="/quiz/<?php echo $quiz['id']; ?>" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 shadow-md hover:shadow-lg transition-all duration-200">
                            Débuter
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
