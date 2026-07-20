# ![Quizzapp Logo](Capture%20d'écran_20260720_181407.png)

[![PHP Version](https://img.shields.io/badge/PHP-8.3-blue.svg)](https://www.php.net/)
[![Docker](https://img.shields.io/badge/Docker-compatible-blue.svg)](https://www.docker.com/)
[![WebSockets](https://img.shields.io/badge/WebSockets-Ratchet-orange.svg)](http://socketo.me/)
[![AI Generation](https://img.shields.io/badge/AI-Mistral-purple.svg)](https://mistral.ai/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Quizzapp est une plateforme moderne de quiz en temps réel, permettant de jouer en solo ou de défier d'autres joueurs dans des duels. Le projet intègre une génération dynamique de questions par IA (Mistral) et un serveur de WebSocket en arrière-plan.

---

## Fonctionnalités Principales

*   **Mode Solo** : Plus de 10 catégories thématiques pré-enregistrées (Astronomie, Géographie, Informatique, Mathématiques, Histoire, Politique, etc.) avec explications détaillées après chaque question.
*   **Duels en Temps Réel** : Système de salons privés (codes uniques) géré par WebSocket avec une phase de sélection alternée des catégories (3 choix par joueur, soit 18 questions au total).
*   **Quiz IA Mistral** : Génération de questions uniques à la volée grâce à l'intégration de l'API Mistral (avec repli automatique sur la base de données locale si la clé est manquante).
*   **Sécurité de Niveau Production** : 
    *   Authentification à deux facteurs (2FA via Google Authenticator).
    *   Protection complète contre les failles CSRF, XSS et injections SQL.
    *   Limitation du taux de requêtes (Rate Limiting).
    *   En-têtes de sécurité HTTP stricts (CSP, X-Frame-Options, etc.).
*   **Design Premium & 100% Responsive** : Style moderne avec effets de flou (glassmorphism), transitions fluides et interface optimisée pour écrans mobiles (cibles tactiles conformes aux standards).
*   **Support PWA** : Manifeste de l'application et Service Worker pour la mise en cache statique et le support hors ligne.

---

## Badges & Succès de la Communauté

Le système de progression récompense les joueurs avec les succès suivants :

| Badge | Description | Type de Critère | Objectif |
| :--- | :--- | :--- | :--- |
| **Premier pas** | Complétez votre premier quiz | Quiz joués | 1 |
| **Passionné** | Complétez 10 quiz | Quiz joués | 10 |
| **Expert** | Complétez 50 quiz | Quiz joués | 50 |
| **Nouveau Niveau** | Atteignez le niveau 5 | Niveau atteint | 5 |
| **Maître du Quiz** | Atteignez le niveau 10 | Niveau atteint | 10 |
| **Sans Faute** | Obtenez un score parfait (100%) sur un quiz | Score parfait | 1 |

---

## Stack Technique

*   **Backend** : PHP 8.3 (sans framework lourd, architecture MVC légère et robuste)
*   **Base de Données** : MySQL 8.0 (encodage complet utf8mb4_unicode_ci)
*   **Serveur Web & Reverse Proxy** : Nginx
*   **Serveur de Jeu (WebSockets)** : Ratchet PHP
*   **Frontend** : Tailwind CSS (via CDN) & Alpine.js

---

## Installation & Lancement Local

La plateforme est entièrement dockerisée pour un démarrage simple et rapide.

### 1. Cloner le projet et configurer l'environnement
Copiez le fichier de configuration d'exemple et renseignez vos clés :
```bash
cp .env.example .env
```
Éditez le fichier `.env` pour y ajouter votre clé API Mistral si vous souhaitez activer la génération de questions par IA :
```env
MISTRAL_API_KEY=votre_cle_mistral_ici
```

### 2. Démarrer l'architecture
Lancez les conteneurs Docker (Base de données, PHP-FPM, WebSockets, et Nginx) :
```bash
docker compose up -d --build
```

### 3. Accéder à l'application
Ouvrez votre navigateur sur http://localhost:7777.

---

## Configuration Détaillée (Variables d'Environnement & Emails)

Le comportement de l'application est contrôlé par les variables définies dans le fichier `.env` :

### 1. Configuration des Mails (SMTP)
L'application envoie des e-mails pour la validation de compte et la réinitialisation des mots de passe.
*   **Mode Local / Débogage (Par défaut)** : Si vous laissez la variable `SMTP_HOST` vide, aucun e-mail n'est envoyé à l'extérieur. Ils sont tous interceptés et sauvegardés localement dans le fichier `logs/mail.log` pour inspection rapide.
*   **Mode Production** : Renseignez vos accès SMTP dans le fichier `.env` :
    ```env
    SMTP_HOST=smtp.votre-fournisseur.com   # Hôte SMTP
    SMTP_PORT=587                         # Port (587 TLS ou 465 SSL)
    SMTP_USER=contact@votre-domaine.com    # Utilisateur SMTP
    SMTP_PASS=mot_de_passe_securise       # Mot de passe SMTP
    SMTP_SECURE=tls                       # Sécurité (tls ou ssl)
    MAIL_FROM_ADDRESS=no-reply@domaine.com # Adresse de l'expéditeur
    MAIL_FROM_NAME=Quizzapp               # Nom de l'expéditeur
    ```

### 2. Clé Secrète JWT (Sécurité)
Il est impératif de modifier le secret utilisé pour signer les jetons d'authentification des sessions WebSocket :
```env
JWT_SECRET=une_cle_tres_longue_et_aleatoire_generer_pour_la_prod
```

### 3. Base de données : Initialisation & Réinitialisation
Au premier lancement de `docker compose up -d`, la base de données est **automatiquement** créée et peuplée grâce aux scripts d'init SQL de Docker.
Si vous souhaitez réinitialiser manuellement la base à blanc à tout moment, exécutez les commandes suivantes :
```bash
# 1. Recréer la structure des tables
docker exec -i quizzapp_db mysql -uquizzapp_user -p"Qu1zzApp_S3cur3_P@ss!" quizzapp < database/migration.sql

# 2. Injecter les données par défaut (quizzes politiques inclus)
docker exec -i quizzapp_db mysql -uquizzapp_user -p"Qu1zzApp_S3cur3_P@ss!" quizzapp < database/seed.sql
```

---

## Comptes de Test Par Défaut

*   **Administrateur** :
    *   **Identifiant** : admin ou admin@quizapp.com
    *   **Mot de passe** : admin123
*   **Joueur Standard** :
    *   **Identifiant** : joueur1 ou joueur1@quizapp.com
    *   **Mot de passe** : user123

---

## Build & Push des Images Docker (Production)

Pour publier les images sur un registre (Docker Hub ou privé), suivez ces étapes :

### 1. Décommenter et renseigner les variables d'images dans `.env`
```env
DOCKER_IMAGE_PHP=votre-pseudo-dockerhub/quizzapp-php:latest
DOCKER_IMAGE_WS=votre-pseudo-dockerhub/quizzapp-websocket:latest
```

### 2. Se connecter au registre
```bash
docker login
```

### 3. Lancer le script automatique (build + push en une commande)
```bash
./bin/docker-build-push.sh
```

Ou manuellement étape par étape :
```bash
# Build
docker compose build php websocket

# Push
docker compose push php websocket
```

### 4. Déployer sur un serveur distant
Sur le VPS de production, clonez le projet et démarrez avec les images du registre :
```bash
DOCKER_IMAGE_PHP=votre-pseudo/quizzapp-php:latest \
DOCKER_IMAGE_WS=votre-pseudo/quizzapp-websocket:latest \
docker compose up -d
```

---

## Déploiement en Production avec Dockhand

**Dockhand** est une solution d'orchestration légère et moderne alternative à Portainer. Pour installer Quizzapp via l'interface de Dockhand :

### 1. Créer un nouveau "Stack"
Dans le tableau de bord de votre instance Dockhand :
1. Allez dans l'onglet **Stacks**.
2. Cliquez sur **Create Stack** (Créer une pile).

### 2. Configurer la source Git (GitOps)
1. Choisissez **Git Repository** comme source de déploiement.
2. Entrez l'URL publique de votre dépôt : `https://github.com/AnARCHIS12/Quizzapp.git`
3. Spécifiez la branche : `main`
4. Laissez le chemin du fichier de configuration Compose par défaut (`docker-compose.yml`).

### 3. Configurer les variables d'environnement dans l'UI de Dockhand
Dans la section **Environment Variables** (ou le formulaire `.env` intégré de Dockhand), ajoutez les paires de clés/valeurs requises :
*   `DOCKER_IMAGE_PHP` : `liberchat/quizzapp-php:latest` (image d'application pré-construite)
*   `DOCKER_IMAGE_WS` : `liberchat/quizzapp-websocket:latest` (image WebSocket pré-construite)
*   `DB_HOST` : `db` (doit correspondre au nom de service de la base de données dans le compose)
*   `DB_PORT` : `3306`
*   `DB_NAME` : `quizzapp`
*   `DB_USER` : `quizzapp_user`
*   `DB_PASS` : `mettez_un_mot_de_passe_base_de_donnees_robuste`
*   `WS_PORT` : `8080`
*   `JWT_SECRET` : `mettez_une_cle_secrete_aleatoire_tres_longue_ici`
*   `MISTRAL_API_KEY` : `votre_cle_mistral_api` (facultatif)

*Pour la configuration SMTP (Emails) :*
*   `SMTP_HOST` : `smtp.votre-fournisseur.com` (laisser vide pour simuler localement dans `logs/mail.log`)
*   `SMTP_PORT` : `587`
*   `SMTP_USER` : `votre_compte_mail@domaine.com`
*   `SMTP_PASS` : `mot_de_passe_smtp`
*   `SMTP_SECURE` : `tls`
*   `MAIL_FROM_ADDRESS` : `no-reply@votre-domaine.com`
*   `MAIL_FROM_NAME` : `Quizzapp`

### 4. Déployer et démarrer
Cliquez sur **Deploy Stack** (Déployer). Dockhand va cloner le dépôt, récupérer les images Docker et instancier tous les conteneurs. Les scripts de migration de base de données se lanceront d'eux-mêmes au premier démarrage.

---

## Commandes Utiles

*   **Arrêter le projet** :
    ```bash
    docker compose down
    ```
*   **Consulter les logs du serveur WebSocket** :
    ```bash
    docker logs -f quizzapp_websocket
    ```
*   **Lancer les tests de vérification** :
    ```bash
    docker exec -it quizzapp_php php tests/test_runner.php
    ```

---

## Licence

Ce projet est sous licence MIT. Voir le fichier LICENSE pour plus d'informations.
