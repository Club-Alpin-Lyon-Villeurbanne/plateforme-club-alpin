#!/bin/bash

# Script de test local pour le backup database
# À exécuter localement pour vérifier que tout fonctionne

echo "=== Test du script de backup en mode local ==="
echo ""

# Variables d'environnement nécessaires pour le test
echo "Variables d'environnement requises :"
echo "- APP_ID : ID de votre app Clever Cloud"
echo "- MYSQL_ADDON_UUID : ID de l'addon MySQL (addon_79f99c0a-7f78-4f73-b7b5-aa4a323a02eb)"
echo "- B2_ACCOUNT_ID : Account ID Backblaze"
echo "- B2_ACCOUNT_KEY : Account Key Backblaze"
echo "- B2_BUCKET : Nom du bucket B2"
echo "- RESTIC_PASSWORD : Mot de passe pour chiffrer les backups"
echo "- HEALTHCHECK_BACKUP_DB : (optionnel) UUID healthchecks.io"
echo ""

# Vérifier que clever CLI est installé
if ! command -v clever &> /dev/null; then
    echo "❌ Clever CLI n'est pas installé"
    echo "Installation : npm install -g clever-tools"
    exit 1
fi

echo "✅ Clever CLI est installé"

# Vérifier que jq est installé
if ! command -v jq &> /dev/null; then
    echo "❌ jq n'est pas installé"
    echo "Installation : brew install jq (macOS) ou apt-get install jq (Linux)"
    exit 1
fi

echo "✅ jq est installé"

# Demander les variables si non définies
if [[ -z "${MYSQL_ADDON_UUID:-}" ]]; then
    read -p "MYSQL_ADDON_UUID (addon_79f99c0a-7f78-4f73-b7b5-aa4a323a02eb) : " MYSQL_ADDON_UUID
    MYSQL_ADDON_UUID=${MYSQL_ADDON_UUID:-addon_79f99c0a-7f78-4f73-b7b5-aa4a323a02eb}
fi

if [[ -z "${B2_BUCKET:-}" ]]; then
    read -p "B2_BUCKET : " B2_BUCKET
fi

if [[ -z "${B2_ACCOUNT_ID:-}" ]]; then
    read -p "B2_ACCOUNT_ID : " B2_ACCOUNT_ID
fi

if [[ -z "${B2_ACCOUNT_KEY:-}" ]]; then
    read -s -p "B2_ACCOUNT_KEY (hidden) : " B2_ACCOUNT_KEY
    echo ""
fi

if [[ -z "${RESTIC_PASSWORD:-}" ]]; then
    read -s -p "RESTIC_PASSWORD (hidden) : " RESTIC_PASSWORD
    echo ""
fi

# Exporter les variables pour le script
export MYSQL_ADDON_UUID
export B2_BUCKET
export B2_ACCOUNT_ID
export B2_ACCOUNT_KEY
export RESTIC_PASSWORD
export APP_HOME="${APP_HOME:-$HOME}"
export ROOT="$(pwd)"

echo ""
echo "=== Test 1: Lister les backups ==="
echo "Commande : clever database backups ${MYSQL_ADDON_UUID} -F json"
echo ""

backups_json=$(clever database backups "${MYSQL_ADDON_UUID}" -F json 2>/dev/null)
if [[ -z "${backups_json}" ]]; then
    echo "❌ Impossible de récupérer la liste des backups"
    exit 1
fi

echo "✅ Liste des backups récupérée"
echo "Nombre de backups : $(echo "${backups_json}" | jq '. | length')"
echo ""

# Afficher le dernier backup
latest_backup_id=$(echo "${backups_json}" | jq -r '.[-1].backupId')
latest_backup_date=$(echo "${backups_json}" | jq -r '.[-1].creationDate')

echo "Dernier backup :"
echo "  - ID : ${latest_backup_id}"
echo "  - Date : ${latest_backup_date}"
echo ""

echo "=== Test 2: Télécharger le dernier backup ==="
echo "Commande : clever database backups download --output test-backup.sql.gz ${MYSQL_ADDON_UUID} ${latest_backup_id}"
echo ""

read -p "Voulez-vous télécharger le backup ? (y/N) : " download_confirm
if [[ "${download_confirm}" == "y" ]]; then
    if clever database backups download \
        --output "test-backup.sql.gz" \
        "${MYSQL_ADDON_UUID}" \
        "${latest_backup_id}"; then
        echo "✅ Backup téléchargé : test-backup.sql.gz"
        ls -lh test-backup.sql.gz
        
        # Nettoyer
        read -p "Supprimer le fichier test ? (y/N) : " delete_confirm
        if [[ "${delete_confirm}" == "y" ]]; then
            rm -f test-backup.sql.gz
        fi
    else
        echo "❌ Échec du téléchargement"
    fi
fi

echo ""
echo "=== Test 3: Vérifier Restic ==="
echo ""

# Créer un répertoire temporaire pour le test
TEMP_TEST_DIR="/tmp/restic-test-$$"
mkdir -p "${TEMP_TEST_DIR}"

# Télécharger Restic si nécessaire
RESTIC_VERSION="0.18.0"
RESTIC_URL="https://github.com/restic/restic/releases/download/v${RESTIC_VERSION}/restic_${RESTIC_VERSION}_darwin_amd64.bz2"
if [[ "$(uname)" == "Linux" ]]; then
    RESTIC_URL="https://github.com/restic/restic/releases/download/v${RESTIC_VERSION}/restic_${RESTIC_VERSION}_linux_amd64.bz2"
fi

if ! command -v restic &> /dev/null; then
    echo "Installation de Restic ${RESTIC_VERSION}..."
    cd "${TEMP_TEST_DIR}"
    wget -q -O restic.bz2 "${RESTIC_URL}"
    bunzip2 restic.bz2
    chmod +x restic
    RESTIC_PATH="${TEMP_TEST_DIR}/restic"
else
    RESTIC_PATH="restic"
    echo "✅ Restic est déjà installé"
fi

"${RESTIC_PATH}" version

echo ""
echo "=== Test 4: Connexion à B2 ==="
echo ""

export B2_ACCOUNT_ID="${B2_ACCOUNT_ID}"
export B2_ACCOUNT_KEY="${B2_ACCOUNT_KEY}"
export RESTIC_PASSWORD="${RESTIC_PASSWORD}"

repo="b2:${B2_BUCKET}:database-backups"

echo "Test de connexion au repository : ${repo}"
if "${RESTIC_PATH}" snapshots --repo "${repo}" --quiet >/dev/null 2>&1; then
    echo "✅ Repository existe et est accessible"
    
    # Afficher les derniers snapshots
    echo ""
    echo "Derniers snapshots :"
    "${RESTIC_PATH}" snapshots --repo "${repo}" --latest 3 2>/dev/null || true
else
    echo "⚠️  Repository n'existe pas ou n'est pas accessible"
    read -p "Voulez-vous initialiser le repository ? (y/N) : " init_confirm
    if [[ "${init_confirm}" == "y" ]]; then
        if "${RESTIC_PATH}" init --repo "${repo}"; then
            echo "✅ Repository initialisé"
        else
            echo "❌ Échec de l'initialisation"
        fi
    fi
fi

# Nettoyer
rm -rf "${TEMP_TEST_DIR}"

echo ""
echo "=== Test 5: Exécution du script complet (dry-run) ==="
echo ""

read -p "Voulez-vous exécuter le script de backup complet ? (y/N) : " run_confirm
if [[ "${run_confirm}" == "y" ]]; then
    # Exécuter le script
    bash clevercloud/crons/backup-database.sh
fi

echo ""
echo "=== Tests terminés ==="