<?php
$title = "Politique de Confidentialite - QuizzApp";
ob_start();
?>

<div class="max-w-4xl mx-auto px-4 py-12">
    <div class="bg-indigo-950/60 backdrop-blur-xl border border-indigo-800/40 rounded-3xl p-8 sm:p-12 shadow-2xl text-white">
        <div class="mb-6">
            <h1 class="text-3xl font-black bg-clip-text text-transparent bg-gradient-to-r from-violet-400 to-indigo-300">Politique de Confidentialite</h1>
            <p class="text-xs text-indigo-300/60 mt-1">Derniere mise a jour : 29 Aout 2026 - QuizzApp & QuizzApp Mobile</p>
        </div>

        <div class="border-t border-indigo-800/40 my-6"></div>

        <div class="space-y-8 text-indigo-100/90 leading-relaxed text-sm sm:text-base">
            <section>
                <h2 class="text-lg font-bold text-violet-300 mb-2">1. Presentation generale</h2>
                <p>
                    L'application <strong>QuizzApp</strong> (accessible via le web et l'application mobile Android) est une plateforme de quiz et duels multijoueurs en temps reel.
                    La protection de votre vie privee est une priorite. Nous collectons le strict minimum de donnees necessaires au bon fonctionnement de la plateforme de jeu.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-violet-300 mb-2">2. Donnees collectees</h2>
                <p class="mb-3">Lors de votre inscription et utilisation de la plateforme, les donnees suivantes sont traitees :</p>
                <ul class="list-disc pl-6 space-y-1.5 text-indigo-200">
                    <li><strong>Pseudonyme (Nom d'utilisateur)</strong> : pour vous identifier lors des parties et classements.</li>
                    <li><strong>Adresse e-mail</strong> : pour la validation du compte et la reinitialisation de mot de passe.</li>
                    <li><strong>Mot de passe</strong> : stocke sous forme hachee cryptographiquement (BCrypt), jamais en clair.</li>
                    <li><strong>Statistiques de jeu</strong> : points XP, niveau, parties jouees, scores et historique des duels.</li>
                </ul>
                <p class="mt-3 text-xs text-indigo-300/80 bg-indigo-900/40 p-3 rounded-xl border border-indigo-800/30">
                    QuizzApp ne collecte aucune donnee de geolocalisation, liste de contacts, donnees biometriques ou identifiant publicitaire.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-violet-300 mb-2">3. Modele Auto-Heberge et Fédere (Application Mobile)</h2>
                <p>
                    L'application mobile Android permet aux utilisateurs de choisir l'instance serveur QuizzApp de leur choix. Vos donnees de profil et de jeu sont transmises et stockees uniquement sur le serveur configure dans les parametres de votre application.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-violet-300 mb-2">4. Traitement de l'Intelligence Artificielle</h2>
                <p>
                    Les questions de quiz dynamiques sont generees via des API d'IA (Mistral AI, Groq, OpenRouter). Aucune donnee utilisateur n'est transmise aux fournisseurs d'IA. Seules les categories thematiques selectionnees sont envoyees pour generer les questions.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-violet-300 mb-2">5. Vos droits et Suppression de compte (RGPD)</h2>
                <p>
                    Conformement aux reglementations sur la protection des donnees (RGPD), vous disposez d'un droit d'acces, de rectification et de suppression de vos donnees personnelles.
                    Vous pouvez supprimer definitivement votre compte et l'integralite de vos statistiques a tout moment depuis les parametres de votre profil dans l'application.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-violet-300 mb-2">6. Contact</h2>
                <p>
                    Pour toute question relative a cette politique de confidentialite ou a vos donnees personnelles, vous pouvez contacter l'administrateur a l'adresse de support configuree sur votre instance.
                </p>
            </section>
        </div>

        <div class="mt-10 pt-6 border-t border-indigo-800/40 flex justify-center">
            <a href="/" class="px-6 py-3 rounded-xl bg-violet-600 hover:bg-violet-500 font-bold text-white shadow-lg transition-all">
                Retour a l'accueil
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
