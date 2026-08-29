<?php
$title = "Suppression de Compte et Donnees - QuizzApp";
ob_start();
?>

<div class="max-w-4xl mx-auto px-4 py-12">
    <div class="bg-indigo-950/60 backdrop-blur-xl border border-indigo-800/40 rounded-3xl p-8 sm:p-12 shadow-2xl text-white">
        <div class="mb-6">
            <h1 class="text-3xl font-black bg-clip-text text-transparent bg-gradient-to-r from-violet-400 to-indigo-300">
                Demande de Suppression de Compte et Donnees
            </h1>
            <p class="text-xs text-indigo-300/60 mt-1">
                Application : QuizzApp (QuizzApp Mobile) | Developpeur : AnARCHIS / Liberchat
            </p>
        </div>

        <div class="border-t border-indigo-800/40 my-6"></div>

        <div class="space-y-8 text-indigo-100/90 leading-relaxed text-sm sm:text-base">
            <section>
                <h2 class="text-lg font-bold text-violet-300 mb-2">1. Procedure de suppression du compte</h2>
                <p class="mb-3">
                    Vous disposez de deux methodes simples et directes pour supprimer definitivement votre compte QuizzApp et l'ensemble des donnees associees :
                </p>
                <div class="grid sm:grid-cols-2 gap-4 my-4">
                    <div class="bg-indigo-900/40 p-4 rounded-2xl border border-indigo-800/40">
                        <h3 class="font-bold text-white mb-2">Option A : Depuis l'application mobile ou web</h3>
                        <ol class="list-decimal pl-5 space-y-1 text-sm text-indigo-200">
                            <li>Connectez-vous a votre compte QuizzApp.</li>
                            <li>Accedez a la rubrique <strong>Profil</strong> ou <strong>Parametres</strong>.</li>
                            <li>Cliquez sur le bouton rouge <strong>Supprimer mon compte</strong>.</li>
                            <li>Confirmez votre choix pour executer la suppression immediate.</li>
                        </ol>
                    </div>
                    <div class="bg-indigo-900/40 p-4 rounded-2xl border border-indigo-800/40">
                        <h3 class="font-bold text-white mb-2">Option B : Par demande directe</h3>
                        <p class="text-sm text-indigo-200 mb-2">
                            Si vous n'avez plus acces a l'application, vous pouvez envoyer votre demande de suppression par email a l'administrateur avec votre nom d'utilisateur :
                        </p>
                        <a href="mailto:contact@revlibertaire.com?subject=Demande%20de%20suppression%20de%20compte%20QuizzApp" class="inline-block text-xs font-bold text-violet-300 underline mt-1">
                            contact@revlibertaire.com
                        </a>
                    </div>
                </div>
            </section>

            <section>
                <h2 class="text-lg font-bold text-violet-300 mb-2">2. Types de donnees supprimees</h2>
                <p class="mb-2">Lorsque vous supprimez votre compte, les donnees suivantes sont integralement et definitivement effacees de notre base de donnees :</p>
                <ul class="list-disc pl-6 space-y-1.5 text-indigo-200">
                    <li><strong>Identifiants de compte</strong> : Nom d'utilisateur (pseudonyme) et adresse e-mail.</li>
                    <li><strong>Informations de securite</strong> : Mot de passe hache (BCrypt), secrets et jetons d'authentification.</li>
                    <li><strong>Statistiques de jeu</strong> : Niveau atteint, points d'experience (XP), nombre de parties jouees, reponses correctes.</li>
                    <li><strong>Historique multijoueur</strong> : Enregistrements des duels et scores associes.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-bold text-violet-300 mb-2">3. Duree et modalites de conservation</h2>
                <p>
                    <strong>Suppression immediate et definitive</strong> : La suppression de vos donnees est effective instantanement lors de la confirmation dans l'application, ou traitee sous 48 heures ouvrables en cas de demande par email.
                </p>
                <p class="mt-2">
                    <strong>Aucune donnee conservee</strong> : Aucune copie ou donnee personnelle de l'utilisateur n'est conservee apres la suppression du compte.
                </p>
            </section>
        </div>

        <div class="mt-10 pt-6 border-t border-indigo-800/40 flex justify-between items-center flex-wrap gap-4">
            <a href="/privacy" class="text-xs text-violet-300 underline">
                Consulter la Politique de Confidentialite
            </a>
            <a href="/" class="px-6 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 font-bold text-white text-sm shadow-lg transition-all">
                Retour a l'accueil
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
