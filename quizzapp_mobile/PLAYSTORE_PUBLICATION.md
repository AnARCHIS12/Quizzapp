# Guide de Publication sur Google Play Store - QuizzApp

Ce guide detaille les etapes et les textes pour soumettre l'application QuizzApp sur la console Google Play.

---

## 1. Prerequis

1. Un compte Google Play Console (acces developpeur valide).
2. L'instance du serveur QuizzApp deployee (exemple : https://quizzapp.revlibertaire.com).

---

## 2. Generation de la cle de signature (Keystore)

Google Play requiert la signature de chaque version d'application.

Un script d'automatisation est present dans le depot :

```bash
cd /home/anar/Bureau/Quizzapp
./bin/generate-playstore-keystore.sh
```

Le script :
1. Demande un mot de passe pour la cle.
2. Genere le fichier Keystore dans ~/quizzapp-release-key.jks.
3. Configure automatiquement quizzapp_mobile/android/key.properties.

Conservez une copie de securite du fichier ~/quizzapp-release-key.jks et de son mot de passe.

---

## 3. Compilation de l'Android App Bundle (.aab)

Pour generer le package de production (.aab) requis par Google Play :

```bash
cd /home/anar/Bureau/Quizzapp/quizzapp_mobile
/home/anar/flutter/bin/flutter build appbundle --release
```

Le fichier genere se trouve a l'emplacement suivant :
`build/app/outputs/bundle/release/app-release.aab`

Pour tester directement un APK standalone sur smartphone :
```bash
/home/anar/flutter/bin/flutter build apk --release
```

---

## 4. Fiche Play Store (Textes officiels)

### Nom de l'application (max 30 caracteres)
```
QuizzApp : Duel & Quiz IA
```

### Description courte (max 80 caracteres)
```
Defiez vos amis en duel en temps reel avec des questions generees par IA.
```

### Description complete (max 4000 caracteres)
```
Testez vos connaissances dans des duels intenses en temps reel.

QuizzApp propose un jeu de quiz multijoueur enrichi par la generation de questions via Intelligence Artificielle. Chaque duel est unique.

CARACTERISTIQUES :

DUELS MULTIJOUEURS EN TEMPS REEL
- Creez un salon prive et partagez le code d'acces avec un ami.
- Rejoignez une salle instantanement via un code unique a 6 caracteres.
- Selection alternee : chaque joueur selectionne 3 categories thematiques (6 categories pour un total de 18 questions par duel).
- Compte a rebours de 20 secondes par question.
- Calcul et synchronisation des scores en direct avec explications pedagoqiques.

QUESTIONS INEDITES PAR IA
- Integration de modeles de langage (Mistral, Groq LLaMA, OpenRouter) generant 18 questions ciblees a chaque partie.
- Aucun doublon de question pendant le duel.

LIBERTE ET AUTO-HEBERGEMENT
- Connexion au serveur officiel ou saisie de l'URL de votre propre serveur QuizzApp auto-heberge.
- Respect de la vie privee : aucune publicite, aucun traceur.

STATISTIQUES ET PROGRESSION
- Gain de points d'experience (XP) et montee en niveau apres chaque partie.
- Historique complet des duels joues.

INTERFACE
- Theme sombre adapte a la lecture nocturne.
- Consommation de donnees optimisee.
```

---

## 5. Ressources graphiques

| Ressource | Format et dimensions | Role |
|---|---|---|
| Icone de l'application | 512 x 512 px (PNG 32-bit avec alpha) | Logo de l'application sur le Store |
| Graphique promotionnel | 1024 x 500 px (JPG ou PNG) | Banniere de presentation en haut de la fiche |
| Captures d'ecran mobile | Min 2, max 8 (format 9:16 ou 16:9) | Apercu des ecrans : accueil, selection, duel, profil |

---

## 6. Formulaires obligatoires Google Play Console

### A. Politique de confidentialite
URL :
`https://quizzapp.revlibertaire.com/privacy`

### B. Categorie et classification
- Type : Jeu
- Categorie : Jeux de reflexion / Quiz (Trivia)
- Tranche d'age : 13 ans et plus
- Annonces publicitaires : Non (l'application ne contient aucune publicite)

### C. Securite des donnees (Data Safety)
- L'application collecte-t-elle des donnees ? Oui.
- Les donnees sont-elles chiffrees en transit ? Oui (chiffrement HTTPS / WSS).
- L'utilisateur peut-il demander la suppression de ses donnees ? Oui (depuis les parametres de profil).
- Donnees collectees :
  1. Informations personnelles : Nom d'utilisateur et adresse e-mail (finalite : gestion du compte et authentification).
  2. Activite dans l'application : Scores et historique des duels (finalite : fonctionnement du jeu).
- Partage des donnees avec des tiers : Non.
- Utilisation a des fins publicitaires : Non.

---

## 7. Mises a jour de l'application

1. Mettez a jour le numero de version dans `quizzapp_mobile/pubspec.yaml` (exemple : `version: 1.0.1+2`).
2. Recompilez le bundle :
   ```bash
   flutter build appbundle --release
   ```
3. Chargez le fichier `.aab` dans l'onglet Production ou Test de la console Google Play.
