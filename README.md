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
