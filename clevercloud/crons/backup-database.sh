#!/bin/bash -l

# Script de sauvegarde de la base de données MySQL vers Backblaze B2
# Exécuté quotidiennement via cron Clever Cloud

set -euo pipefail

# Configuration
TEMP_DIR="/tmp/backup-$$"
RESTIC_VERSION="0.18.0"
RESTIC_PATH="${APP_HOME}/bin/restic"

# Logging
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

# Nettoyage en cas d'erreur
cleanup() {
    rm -rf "${TEMP_DIR}"
}
trap cleanup EXIT

# Installation de Restic si nécessaire
if [[ ! -f "${RESTIC_PATH}" ]]; then
    log "Installing Restic..."
    mkdir -p "${APP_HOME}/bin"
    cd "${APP_HOME}/bin"
    wget -q -O restic.bz2 "https://github.com/restic/restic/releases/download/v${RESTIC_VERSION}/restic_${RESTIC_VERSION}_linux_amd64.bz2"
    bunzip2 restic.bz2
    chmod +x restic
    log "Restic installed"
fi

# Vérifications des variables
if [[ -z "${MYSQL_ADDON_UUID:-}" ]] || [[ -z "${B2_BUCKET:-}" ]] || [[ -z "${RESTIC_PASSWORD:-}" ]]; then
    log "ERROR: Missing required environment variables"
    exit 1
fi

# Export pour Restic
export B2_ACCOUNT_ID="${B2_ACCOUNT_ID}"
export B2_ACCOUNT_KEY="${B2_ACCOUNT_KEY}"
export RESTIC_PASSWORD="${RESTIC_PASSWORD}"

cd "${APP_HOME}"

# Initialiser le repository si nécessaire
REPO="b2:${B2_BUCKET}:database-backups"
if ! "${RESTIC_PATH}" snapshots --repo "${REPO}" --quiet >/dev/null 2>&1; then
    log "Initializing repository..."
    "${RESTIC_PATH}" init --repo "${REPO}"
fi

# Récupérer le dernier backup
log "Fetching latest backup from Clever Cloud..."
mkdir -p "${TEMP_DIR}"
cd "${TEMP_DIR}"

# Lister les backups et prendre le dernier
BACKUPS_JSON=$(clever database backups "${MYSQL_ADDON_UUID}" -F json)
BACKUP_ID=$(echo "${BACKUPS_JSON}" | jq -r '.[-1].backupId')

if [[ -z "${BACKUP_ID}" ]] || [[ "${BACKUP_ID}" == "null" ]]; then
    log "ERROR: No backup found"
    exit 1
fi

# Télécharger le backup
log "Downloading backup ${BACKUP_ID}..."
clever database backups download \
    --output "backup.sql.gz" \
    "${MYSQL_ADDON_UUID}" \
    "${BACKUP_ID}"

# Décompresser pour améliorer la déduplication
log "Decompressing backup..."
gunzip backup.sql.gz

# Sauvegarder avec Restic
log "Backing up to B2..."
"${RESTIC_PATH}" backup \
    --repo "${REPO}" \
    --quiet \
    --tag "mysql" \
    --host "clever-cloud" \
    backup.sql

# Appliquer la politique de rétention
log "Applying retention policy..."
"${RESTIC_PATH}" forget \
    --repo "${REPO}" \
    --quiet \
    --prune \
    --keep-daily 30 \
    --keep-weekly 52 \
    --keep-yearly 5

log "Backup completed successfully"

# Ping healthchecks.io si configuré
if [[ -n "${HEALTHCHECK_BACKUP_DB:-}" ]]; then
    curl -fsS "https://hc-ping.com/${HEALTHCHECK_BACKUP_DB}" &
fi