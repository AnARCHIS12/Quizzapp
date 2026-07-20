#!/bin/bash

# Exit on any error
set -e

# Change directory to the root of the project
cd "$(dirname "$0")/.."

# Load environment variables from .env if it exists
if [ -f .env ]; then
    export $(grep -v '^#' .env | xargs)
fi

# Ensure registry image variables are defined
if [ -z "$DOCKER_IMAGE_PHP" ] || [ -z "$DOCKER_IMAGE_WS" ]; then
    echo "========================================================================="
    echo " ATTENTION : Les variables DOCKER_IMAGE_PHP et DOCKER_IMAGE_WS ne sont"
    echo " pas définies ou décommentées dans votre fichier .env !"
    echo ""
    echo " Veuillez d'abord configurer votre .env avec vos noms d'images cibles :"
    echo "   DOCKER_IMAGE_PHP=votre-pseudo-dockerhub/quizzapp-php:latest"
    echo "   DOCKER_IMAGE_WS=votre-pseudo-dockerhub/quizzapp-websocket:latest"
    echo "========================================================================="
    exit 1
fi

echo "========================================================================="
echo " Commencer le build des images :"
echo " - PHP-FPM : $DOCKER_IMAGE_PHP"
echo " - WebSockets : $DOCKER_IMAGE_WS"
echo "========================================================================="
echo ""

# Set up Docker Buildx builder for multi-platform (AMD64 + ARM64)
echo "Configuration du builder multi-plateforme (Docker Buildx)..."
docker buildx create --use --name quizzapp-builder 2>/dev/null || docker buildx use quizzapp-builder || true

echo ""
echo "Pour envoyer les images sur Docker Hub ou votre registre privé,"
echo "assurez-vous d'avoir exécuté : docker login"
echo ""

echo "Lancement du build multi-plateforme (linux/amd64, linux/arm64) et push automatique..."

# Build and push PHP image
echo "-> Build & Push PHP-FPM image..."
docker buildx build --platform linux/amd64,linux/arm64 -t "$DOCKER_IMAGE_PHP" --push .

# Build and push WebSocket image
echo "-> Build & Push WebSocket image..."
docker buildx build --platform linux/amd64,linux/arm64 -t "$DOCKER_IMAGE_WS" --push .

echo ""
echo "========================================================================="
echo " SUCCÈS : Les images multi-plateformes (AMD64 + ARM64) ont été publiées !"
echo "========================================================================="
