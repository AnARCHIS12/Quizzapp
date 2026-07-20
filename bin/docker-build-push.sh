#!/bin/bash

# Exit on any error
set -e

# Change directory to the root of the project
cd "$(dirname "$0")/.."

# Load environment variables from .env if it exists
if [ -f .env ]; then
    export $(grep -v '^#' .env | xargs)
fi

# Ensure registry image variable is defined
if [ -z "$DOCKER_IMAGE_APP" ]; then
    echo "========================================================================="
    echo " ATTENTION : La variable DOCKER_IMAGE_APP n'est"
    echo " pas définies ou décommentées dans votre fichier .env !"
    echo ""
    echo " Veuillez d'abord configurer votre .env avec votre image cible :"
    echo "   DOCKER_IMAGE_APP=votre-pseudo-dockerhub/quizzapp-app:latest"
    echo "========================================================================="
    exit 1
fi

echo "========================================================================="
echo " Commencer le build de l'image applicative :"
echo " - App : $DOCKER_IMAGE_APP"
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

# Build and push app image
echo "-> Build & Push App image..."
docker buildx build --platform linux/amd64,linux/arm64 -f Dockerfile.app -t "$DOCKER_IMAGE_APP" --push .

echo ""
echo "========================================================================="
echo " SUCCÈS : L'image multi-plateforme (AMD64 + ARM64) a été publiée !"
echo "========================================================================="
