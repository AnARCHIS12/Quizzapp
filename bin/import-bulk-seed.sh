#!/bin/bash
# Importe database/seed_bulk.sql dans une base EXISTANTE sans recréer le volume Docker.
#
# Usage:
#   bash bin/import-bulk-seed.sh            # ajoute les quiz manquants (INSERT IGNORE)
#   bash bin/import-bulk-seed.sh --replace  # remplace les quiz générés (id >= 1000) puis réimporte
#
# Vos utilisateurs, scores et matchs ne sont jamais supprimés.

set -e

cd "$(dirname "$0")/.."

REPLACE=false
for arg in "$@"; do
    if [ "$arg" = "--replace" ]; then
        REPLACE=true
    fi
done

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
if [ "$REPLACE" = true ]; then
    echo "Mode --replace : suppression des quiz générés (id >= 1000) avant réimport."
else
    echo "Mode ajout : INSERT IGNORE uniquement (quiz déjà importés conservés tels quels)."
fi
echo ""

count_query() {
    docker exec "$DB_CONTAINER" mysql -N -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$1" 2>/dev/null
}

echo "Avant import :"
echo "  Quiz       : $(count_query 'SELECT COUNT(*) FROM quizzes')"
echo "  Questions  : $(count_query 'SELECT COUNT(*) FROM questions')"
echo ""

if [ "$REPLACE" = true ]; then
    docker exec "$DB_CONTAINER" mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
        SET FOREIGN_KEY_CHECKS = 0;
        DELETE FROM answers WHERE question_id >= 10000;
        DELETE FROM user_question_history WHERE question_id >= 10000;
        DELETE FROM questions WHERE id >= 10000;
        DELETE FROM quizzes WHERE id >= 1000;
        SET FOREIGN_KEY_CHECKS = 1;
    "
fi

docker exec -i "$DB_CONTAINER" mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$SEED_FILE"

echo "Après import :"
echo "  Quiz       : $(count_query 'SELECT COUNT(*) FROM quizzes')"
echo "  Questions  : $(count_query 'SELECT COUNT(*) FROM questions')"
echo ""
echo "Terminé. Redémarrez l'app si besoin : docker compose -f docker-compose.prod.yml restart app"
