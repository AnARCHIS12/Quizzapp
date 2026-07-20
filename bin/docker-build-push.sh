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

# Build using docker compose
docker compose build php websocket

echo ""
echo "========================================================================="
echo " Build complété. Connexion au registre d'images (si nécessaire)..."
echo "========================================================================="
echo ""

# Ask user to make sure they are logged in if they push to a remote registry
echo "Pour envoyer les images sur Docker Hub ou votre registre privé,"
echo "assurez-vous d'avoir exécuté : docker login"
echo ""

# Push using docker compose
docker compose push php websocket

echo ""
echo "========================================================================="
echo " SUCCÈS : Les images ont été poussées sur le registre !"
echo "========================================================================="
