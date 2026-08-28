# ![Quizzapp Logo](Capture%20d'écran_20260720_181407.png)

[![PHP Version](https://img.shields.io/badge/PHP-8.3-blue.svg)](https://www.php.net/)
[![Docker](https://img.shields.io/badge/Docker-compatible-blue.svg)](https://www.docker.com/)
[![WebSockets](https://img.shields.io/badge/WebSockets-Ratchet-orange.svg)](http://socketo.me/)
[![AI Generation](https://img.shields.io/badge/AI-Mistral%20%7C%20Groq%20%7C%20OpenRouter-purple.svg)](https://groq.com/)
[![Flutter](https://img.shields.io/badge/App%20mobile-Flutter%20Android-blue.svg)](https://flutter.dev/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Quizzapp est une plateforme moderne de quiz en temps réel, jouable en solo ou en **duel multijoueur**. Le projet intègre une génération dynamique de questions par IA avec **chaîne de fallback multi-providers** (Mistral → Groq → OpenRouter) pour une disponibilité maximale, un serveur WebSocket Ratchet, et une **application mobile Flutter Android native**.

---

## Fonctionnalités Principales

- **Mode Solo** : Plus de 10 catégories thématiques (Astronomie, Géographie, Informatique, Mathématiques, Histoire, Politique…) avec explications détaillées.
- **Duels en Temps Réel** : Salons privés par code unique, WebSocket, sélection alternée de 3 catégories chacun → 18 questions IA par duel.
- **Questions IA — 3 providers avec failover automatique** :
  - **Mistral AI** (primary) — `mistral-small-latest`
  - **Groq** (fallback gratuit) — `llama-3.1-70b-versatile`, 14 400 req/jour
  - **OpenRouter** (fallback agrégateur) — `meta-llama/llama-3.1-8b-instruct:free`
  - Si un provider retourne 429/quota dépassé → bascule automatique sur le suivant
- **Application mobile Flutter Android** : se connecte au même backend, choisir son propre serveur
- **Sécurité de Niveau Production** : 2FA (TOTP), CSRF, XSS, injections SQL, Rate Limiting, en-têtes HTTP stricts
- **Design Premium** : Glassmorphism, transitions fluides, 100% responsive, support PWA

---

## Badges & Succès de la Communauté

| Badge | Description | Objectif |
| :--- | :--- | :--- |
| **Premier pas** | Compléter votre premier quiz | 1 quiz joué |
| **Passionné** | Compléter 10 quiz | 10 quiz joués |
| **Expert** | Compléter 50 quiz | 50 quiz joués |
| **Nouveau Niveau** | Atteindre le niveau 5 | Niveau 5 |
| **Maître du Quiz** | Atteindre le niveau 10 | Niveau 10 |
| **Sans Faute** | Score parfait (100%) sur un quiz | 1 fois |

---

## Stack Technique

| Couche | Technologie |
| :--- | :--- |
| Backend | PHP 8.3 — Architecture MVC légère (sans framework) |
| Base de données | MySQL 8.0 (utf8mb4_unicode_ci) |
| Serveur Web | Nginx (reverse proxy + gzip) |
| WebSocket (Duels) | Ratchet PHP sur ReactPHP — non-bloquant |
| IA Questions | Mistral AI + Groq + OpenRouter (failover automatique) |
| Frontend Web | Tailwind CSS + Alpine.js |
| Application Mobile | Flutter 3.x — Android natif |
| Auth Mobile | JWT Bearer (même clé que WebSocket) |
| Déploiement | Docker multi-plateforme AMD64 + ARM64 |

---

## Installation & Lancement Local

### 1. Cloner et configurer l'environnement

```bash
cp .env.example .env
```

Éditez `.env` pour configurer au minimum **un** provider IA :

```env
# Provider 1 — Mistral (payant, très bonne qualité)
MISTRAL_API_KEY=votre_cle_mistral_ici

# Provider 2 — Groq (GRATUIT, ultra-rapide, recommandé comme fallback)
# Clé gratuite sur : https://console.groq.com
GROQ_API_KEY=gsk_votre_cle_groq

# Provider 3 — OpenRouter (GRATUIT avec modèles open-source)
# Clé gratuite sur : https://openrouter.ai/keys
OPENROUTER_API_KEY=sk-or-votre_cle_openrouter
```

> **⚡ Recommandation** : mettez au moins `GROQ_API_KEY` (gratuit) pour garantir le service même si Mistral coupe vos tokens.

### 2. Démarrer l'architecture Docker

```bash
docker compose up -d --build
```

### 3. Accéder à l'application

Ouvrez votre navigateur sur **http://localhost:7777**

---

## Configuration Détaillée

### Variables d'Environnement

| Variable | Obligatoire | Description |
| :--- | :--- | :--- |
| `DB_HOST` | ✅ | Hôte MySQL (`db` en Docker) |
| `DB_PORT` | ✅ | Port MySQL (défaut `3306`) |
| `DB_NAME` | ✅ | Nom de la base (`quizzapp`) |
| `DB_USER` | ✅ | Utilisateur MySQL |
| `DB_PASS` | ✅ | Mot de passe MySQL |
| `JWT_SECRET` | ✅ | Secret JWT (changer impérativement en prod) |
| `WS_HOST` | prod | Nom de domaine pour le WebSocket |
| `WS_PORT` | opt | Port WebSocket (vide = 443 en SSL) |
| `MISTRAL_API_KEY` | opt | Clé API Mistral (provider 1) |
| `MISTRAL_MODEL` | opt | Modèle Mistral (défaut : `mistral-small-latest`) |
| `GROQ_API_KEY` | opt | Clé API Groq (provider 2 — gratuit) |
| `GROQ_MODEL` | opt | Modèle Groq (défaut : `llama-3.1-70b-versatile`) |
| `OPENROUTER_API_KEY` | opt | Clé OpenRouter (provider 3 — gratuit) |
| `OPENROUTER_MODEL` | opt | Modèle OpenRouter (défaut : `meta-llama/llama-3.1-8b-instruct:free`) |
| `APP_PORT` | opt | Port local (défaut `7777`) |
| `SMTP_HOST` | opt | Hôte SMTP (vide = log local dans `logs/mail.log`) |
| `SMTP_PORT` | opt | Port SMTP (défaut `587`) |
| `SMTP_USER` | opt | Utilisateur SMTP |
| `SMTP_PASS` | opt | Mot de passe SMTP |

### Logique de Failover IA

```
Duel lancé
     │
     ▼
  Mistral ──429/402/503──► Groq ──429/402/503──► OpenRouter
     │                       │                        │
     └── succès ─────────────┴── succès ──────────────┴── 18 questions générées
```

Si **tous** les providers échouent, un message d'erreur est loggé dans `error_log` (aucun crash du serveur WebSocket).

### Emails

- **Mode local** : `SMTP_HOST` vide → emails sauvegardés dans `logs/mail.log`
- **Mode production** : renseignez SMTP complet

### Base de Données : Réinitialisation manuelle

```bash
# Recréer la structure des tables
docker exec -i quizzapp_db mysql -uquizzapp_user -p"Qu1zzApp_S3cur3_P@ss!" quizzapp < database/migration.sql

# Injecter les données par défaut
docker exec -i quizzapp_db mysql -uquizzapp_user -p"Qu1zzApp_S3cur3_P@ss!" quizzapp < database/seed.sql
```

---

## Comptes de Test Par Défaut

| Rôle | Identifiant | Mot de passe |
| :--- | :--- | :--- |
| Administrateur | `admin` ou `admin@quizapp.com` | `admin123` |
| Joueur Standard | `joueur1` ou `joueur1@quizapp.com` | `user123` |

---

## Application Mobile Flutter Android

L'app native se connecte à **n'importe quelle instance** QuizzApp auto-hébergée (choisir son propre serveur au premier lancement, comme Element/Matrix).

### Dossier du projet

```
quizzapp_mobile/
```

### Build APK

```bash
cd quizzapp_mobile

# Installer les dépendances Flutter
flutter pub get

# Build APK release
flutter build apk --release

# APK généré :
# build/app/outputs/flutter-apk/app-release.apk
```

### API REST JSON (endpoints mobiles)

Ajoutés au backend pour l'app mobile — aucun recodage du backend :

| Méthode | Route | Auth | Description |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/ping` | — | Health check + infos serveur |
| `POST` | `/api/auth/login` | — | Connexion → JWT (30 jours) |
| `POST` | `/api/auth/register` | — | Inscription → JWT |
| `GET` | `/api/categories` | opt | Liste toutes les catégories |
| `GET` | `/api/profile` | Bearer JWT | Profil + stats + historique |
| `GET` | `/api/ws-token` | Bearer JWT | Token court pour WebSocket |

Le WebSocket (`wss://serveur/ws?token=JWT`) utilise le **même protocole JSON** que le site web.

---

## Build & Push des Images Docker (Production)

### Script automatique (multi-plateforme AMD64 + ARM64)

```bash
./bin/docker-build-push.sh
```

### Manuellement

```bash
docker buildx create --use --name quizzapp-builder || docker buildx use quizzapp-builder
docker buildx build --platform linux/amd64,linux/arm64 -t liberchat/quizzapp-app:latest --push .
```

---

## Déploiement en Production avec Dockhand

### 1. Créer un Stack → source Git

- URL : `https://github.com/AnARCHIS12/Quizzapp.git`
- Branche : `main`
- Fichier Compose : `docker-compose.prod.yml`

### 2. Variables d'environnement dans Dockhand

```env
DOCKER_IMAGE_APP=liberchat/quizzapp-app:latest
DB_HOST=db
DB_PORT=3306
DB_NAME=quizzapp
DB_USER=quizzapp_user
DB_PASS=mettez_un_mot_de_passe_robuste
WS_HOST=quiz.votre-domaine.com
WS_PORT=                              # laisser vide derrière SSL
JWT_SECRET=une_cle_tres_longue_et_aleatoire
APP_PORT=7777

# IA — au moins un des trois :
MISTRAL_API_KEY=votre_cle_mistral
GROQ_API_KEY=gsk_votre_cle_groq       # gratuit → https://console.groq.com
OPENROUTER_API_KEY=                   # facultatif

# SMTP (laisser vide pour logs locaux)
SMTP_HOST=
SMTP_PORT=587
SMTP_USER=
SMTP_PASS=
SMTP_SECURE=tls
MAIL_FROM_ADDRESS=no-reply@votre-domaine.com
MAIL_FROM_NAME=Quizzapp
```

---

## Utilisation avec un Reverse Proxy Hôte (Nginx, Apache, Traefik…)

Si les ports 80/443 sont déjà pris par un autre service :

1. Définissez `APP_PORT=7777` dans vos variables de Stack
2. Configurez le proxy hôte :

```nginx
server {
    listen 80;
    server_name quiz.mondomaine.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name quiz.mondomaine.com;

    ssl_certificate /etc/letsencrypt/live/quiz.mondomaine.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/quiz.mondomaine.com/privkey.pem;

    # HTTP principal
    location / {
        proxy_pass http://127.0.0.1:7777;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # WebSocket duels (IMPORTANT : activer Upgrade)
    location /ws {
        proxy_pass http://127.0.0.1:7777/ws;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

---

## Configuration avec Pangolin (Tunnel & Reverse Proxy)

**Pangolin** est une alternative auto-hébergée à Cloudflare Tunnel (basée sur WireGuard + SSL Let's Encrypt automatique).

1. Démarrez Quizzapp en exposant sur `APP_PORT=7777`
2. Dans Pangolin, créez un Service pointant vers `http://172.17.0.1:7777`
3. **Activez le support WebSocket** (HTTP Upgrade) dans les paramètres de la route
4. Variables :
   ```env
   WS_HOST=quiz.votre-domaine.com
   WS_PORT=          # laisser vide → port 443 standard
   ```

---

## Commandes Utiles

```bash
# Arrêter le projet
docker compose down

# Logs du serveur WebSocket (duels)
docker logs -f quizzapp_app

# Logs IA (erreurs de génération, fallback utilisé)
docker logs quizzapp_app 2>&1 | grep "QuizzApp AI"

# Tester manuellement la génération IA (provider 1 → 2 → 3)
docker exec quizzapp_app php /var/www/bin/generate_questions_async.php ABC123 5 12

# Réinitialiser la base de données
docker exec -i quizzapp_db mysql -uquizzapp_user -p"Qu1zzApp_S3cur3_P@ss!" quizzapp < database/migration.sql
docker exec -i quizzapp_db mysql -uquizzapp_user -p"Qu1zzApp_S3cur3_P@ss!" quizzapp < database/seed.sql
```

---

## Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus d'informations.
