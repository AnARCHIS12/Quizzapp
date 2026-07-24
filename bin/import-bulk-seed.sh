#!/bin/bash
# Importe database/seed_bulk.sql dans une base EXISTANTE sans recréer le volume Docker.
# Utilise INSERT IGNORE : vos utilisateurs, scores et matchs ne sont pas supprimés.

set -e

cd "$(dirname "$0")/.."

if [ -f .env ]; then
    set -a
    # shellcheck disable=SC1091
    source .env
    set +a
fi

DB_CONTAINER="${DB_CONTAINER:-quizzapp_db}"
DB_NAME="${DB_NAME:-quizzapp}"
DB_USER="${DB_USER:-quizzapp_user}"
DB_PASS="${DB_PASS:-}"
SEED_FILE="${SEED_FILE:-database/seed_bulk.sql}"

if [ ! -f "$SEED_FILE" ]; then
    echo "Fichier introuvable : $SEED_FILE"
    echo "Générez-le avec : php bin/generate_bulk_seed.php"
    exit 1
fi

if ! docker ps --format '{{.Names}}' | grep -qx "$DB_CONTAINER"; then
    echo "Conteneur MySQL '$DB_CONTAINER' introuvable ou arrêté."
    echo "Démarrez d'abord : docker compose -f docker-compose.prod.yml up -d db"
    exit 1
fi

echo "Import bulk seed dans '$DB_NAME' (conteneur $DB_CONTAINER)..."
echo "Aucune suppression de données : INSERT IGNORE uniquement."
echo ""

count_query() {
    docker exec "$DB_CONTAINER" mysql -N -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$1" 2>/dev/null
}

echo "Avant import :"
echo "  Quiz       : $(count_query 'SELECT COUNT(*) FROM quizzes')"
echo "  Questions  : $(count_query 'SELECT COUNT(*) FROM questions')"
echo ""

docker exec -i "$DB_CONTAINER" mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$SEED_FILE"

echo "Après import :"
echo "  Quiz       : $(count_query 'SELECT COUNT(*) FROM quizzes')"
echo "  Questions  : $(count_query 'SELECT COUNT(*) FROM questions')"
echo ""
echo "Terminé. Redémarrez l'app si besoin : docker compose -f docker-compose.prod.yml restart app"
