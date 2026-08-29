#!/usr/bin/env bash
set -e

KEYSTORE_PATH="$HOME/quizzapp-release-key.jks"
KEY_PROPS_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")/../quizzapp_mobile/android" && pwd)/key.properties"

echo "========================================================================="
echo " 🔐 Générateur de clé de signature Google Play Store pour QuizzApp"
echo "========================================================================="

if [ -f "$KEYSTORE_PATH" ]; then
    echo "⚠️  Le fichier keystore existe déjà : $KEYSTORE_PATH"
    read -p "Voulez-vous écraser la clé existante ? (o/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Oo]$ ]]; then
        echo "Opération annulée."
        exit 0
    fi
fi

read -s -p "🔑 Entrez le mot de passe pour le Keystore et l'Alias (min 6 caractères) : " KEY_PASS
echo
read -s -p "🔑 Confirmez le mot de passe : " KEY_PASS_CONFIRM
echo

if [ "$KEY_PASS" != "$KEY_PASS_CONFIRM" ]; then
    echo "❌ Les mots de passe ne correspondent pas."
    exit 1
fi

if [ ${#KEY_PASS} -lt 6 ]; then
    echo "❌ Le mot de passe doit faire au moins 6 caractères."
    exit 1
fi

echo "⚙️ Génération du fichier Keystore RSA 2048 bits (validité 25 ans)..."
keytool -genkey -v \
    -keystore "$KEYSTORE_PATH" \
    -storepass "$KEY_PASS" \
    -alias quizzapp \
    -keypass "$KEY_PASS" \
    -keyalg RSA \
    -keysize 2048 \
    -validity 10000 \
    -dname "CN=QuizzApp, OU=Mobile, O=QuizzApp, L=Paris, ST=IDF, C=FR"

echo "📝 Écriture automatique du fichier de configuration : $KEY_PROPS_PATH"
cat > "$KEY_PROPS_PATH" << EOF
keyAlias=quizzapp
keyPassword=$KEY_PASS
storeFile=$KEYSTORE_PATH
storePassword=$KEY_PASS
EOF

chmod 600 "$KEYSTORE_PATH" "$KEY_PROPS_PATH"

echo "========================================================================="
echo " ✅ SUCCÈS : Clé générée et configurée !"
echo " Keystore : $KEYSTORE_PATH"
echo " Config   : $KEY_PROPS_PATH"
echo ""
echo " 🚀 Pour compiler le bundle Play Store (.aab) :"
echo "    cd quizzapp_mobile && flutter build appbundle --release"
echo "========================================================================="
