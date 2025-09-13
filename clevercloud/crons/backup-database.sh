#!/bin/bash -l

# Script de sauvegarde automatique de la base de données MySQL vers Backblaze B2
# Utilise Restic pour le chiffrement et la gestion des backups
# Exécuté quotidiennement via cron Clever Cloud

set -euo pipefail

# Configuration
readonly SCRIPT_NAME="backup-database"
readonly RESTIC_VERSION="0.18.0"
readonly RESTIC_URL="https://github.com/restic/restic/releases/download/v${RESTIC_VERSION}/restic_${RESTIC_VERSION}_linux_amd64.bz2"
readonly RESTIC_PATH="${APP_HOME}/bin/restic"
readonly TEMP_DIR="${APP_HOME}/tmp/backup-$$"
readonly LOG_PREFIX="[$(date '+%Y-%m-%d %H:%M:%S')]"

# Fonction de logging avec timestamp
log() {
    echo "${LOG_PREFIX} $1"
}

# Fonction de gestion d'erreur avec notification healthchecks.io
error_exit() {
    log "ERROR: $1"
    # Envoyer le ping d'échec à healthchecks.io si configuré
    if [[ -n "${HEALTHCHECK_BACKUP_DB:-}" ]]; then
        curl -fsS --retry 3 "https://hc-ping.com/${HEALTHCHECK_BACKUP_DB}/fail" &
    fi
    cleanup
    exit 1
}

# Fonction de nettoyage des fichiers temporaires
cleanup() {
    log "Cleaning up temporary files..."
    if [[ -d "${TEMP_DIR}" ]]; then
        rm -rf "${TEMP_DIR}"
    fi
}

# Installation de Restic si nécessaire
install_restic() {
    if [[ ! -f "${RESTIC_PATH}" ]] || [[ "$("${RESTIC_PATH}" version 2>/dev/null | grep -oP 'restic \K[0-9.]+' || echo '')" != "${RESTIC_VERSION}" ]]; then
        log "Installing Restic ${RESTIC_VERSION}..."
        mkdir -p "${APP_HOME}/bin"
        cd "${APP_HOME}/bin"
        
        # Télécharger Restic
        wget -q -O restic.bz2 "${RESTIC_URL}" || error_exit "Failed to download Restic"
        
        # Décompresser et rendre exécutable
        bunzip2 -f restic.bz2 || error_exit "Failed to extract Restic"
        chmod +x restic
        
        log "Restic ${RESTIC_VERSION} installed successfully"
    else
        log "Restic ${RESTIC_VERSION} already installed"
    fi
}

# Initialisation du repository Restic si nécessaire
init_restic_repo() {
    export B2_ACCOUNT_ID="${B2_ACCOUNT_ID}"
    export B2_ACCOUNT_KEY="${B2_ACCOUNT_KEY}"
    export RESTIC_PASSWORD="${RESTIC_PASSWORD}"
    
    local repo="b2:${B2_BUCKET}:database-backups"
    
    # Vérifier si le repository existe déjà
    if ! "${RESTIC_PATH}" snapshots --repo "${repo}" --quiet >/dev/null 2>&1; then
        log "Initializing Restic repository..."
        "${RESTIC_PATH}" init --repo "${repo}" || error_exit "Failed to initialize Restic repository"
        log "Repository initialized successfully"
    fi
}

# Téléchargement du backup depuis Clever Cloud
download_backup() {
    log "Fetching latest MySQL backup from Clever Cloud..."
    mkdir -p "${TEMP_DIR}"
    cd "${TEMP_DIR}"
    
    # Récupérer la liste des backups en JSON
    local backups_json=$(clever database backups "${MYSQL_ADDON_UUID}" -F json 2>/dev/null)
    if [[ -z "${backups_json}" ]]; then
        error_exit "Failed to fetch backup list from Clever Cloud"
    fi
    
    # Extraire l'ID du dernier backup (le plus récent - dernier élément du tableau)
    local backup_id=$(echo "${backups_json}" | jq -r '.[-1].backupId' 2>/dev/null)
    if [[ -z "${backup_id}" ]] || [[ "${backup_id}" == "null" ]]; then
        error_exit "No backup found in Clever Cloud"
    fi
    
    local backup_date=$(echo "${backups_json}" | jq -r '.[-1].creationDate' 2>/dev/null)
    log "Found backup: ${backup_id} from ${backup_date}"
    
    # Télécharger le backup
    local backup_file="backup-${backup_id}.sql.gz"
    log "Downloading backup to ${backup_file}..."
    
    clever database backups download \
        --output "${backup_file}" \
        "${MYSQL_ADDON_UUID}" \
        "${backup_id}" || error_exit "Failed to download backup from Clever Cloud"
    
    if [[ ! -f "${backup_file}" ]]; then
        error_exit "Backup file not found after download"
    fi
    
    log "Downloaded backup: ${backup_file}"
    echo "${backup_file}"
}

# Décompression du backup pour optimiser la déduplication
decompress_backup() {
    local compressed_file="$1"
    local decompressed_file="${compressed_file%.gz}"
    
    log "Decompressing backup for better deduplication..."
    gunzip -k "${compressed_file}" || error_exit "Failed to decompress backup"
    
    # Supprimer le fichier compressé pour économiser l'espace
    rm -f "${compressed_file}"
    
    log "Backup decompressed: ${decompressed_file}"
    echo "${decompressed_file}"
}

# Sauvegarde vers Restic
backup_to_restic() {
    local backup_file="$1"
    local repo="b2:${B2_BUCKET}:database-backups"
    local backup_date=$(date '+%Y-%m-%d')
    
    log "Starting backup to Restic repository..."
    
    # Créer le backup avec tags pour faciliter la gestion
    "${RESTIC_PATH}" backup \
        --repo "${repo}" \
        --quiet \
        --tag "daily" \
        --tag "date:${backup_date}" \
        --tag "mysql" \
        --tag "clever-cloud" \
        --tag "addon:${MYSQL_ADDON_UUID}" \
        --host "clever-cloud-mysql" \
        "${backup_file}" || error_exit "Failed to backup to Restic"
    
    log "Backup completed successfully"
}

# Application de la politique de rétention
apply_retention_policy() {
    local repo="b2:${B2_BUCKET}:database-backups"
    
    log "Applying retention policy..."
    
    # Politique: 30 jours quotidien, 52 semaines hebdo, 5 ans annuel
    "${RESTIC_PATH}" forget \
        --repo "${repo}" \
        --quiet \
        --prune \
        --keep-daily 30 \
        --keep-weekly 52 \
        --keep-yearly 5 \
        --tag "mysql" || error_exit "Failed to apply retention policy"
    
    log "Retention policy applied successfully"
}

# Vérification d'intégrité hebdomadaire (dimanche)
check_repository_integrity() {
    local repo="b2:${B2_BUCKET}:database-backups"
    local day_of_week=$(date '+%u')
    
    # 7 = dimanche
    if [[ "${day_of_week}" == "7" ]]; then
        log "Running weekly integrity check (Sunday)..."
        
        # Vérifier un sous-ensemble des données pour optimiser le temps
        "${RESTIC_PATH}" check \
            --repo "${repo}" \
            --quiet \
            --read-data-subset=10% || error_exit "Repository integrity check failed"
        
        log "Integrity check completed successfully"
    else
        log "Skipping integrity check (not Sunday)"
    fi
}

# Fonction principale
main() {
    # Configuration des trap pour gérer les interruptions
    trap 'error_exit "Script interrupted"' INT TERM
    trap cleanup EXIT
    
    log "========================================="
    log "Starting database backup process..."
    log "========================================="
    
    # Notification de début à healthchecks.io si configuré
    if [[ -n "${HEALTHCHECK_BACKUP_DB:-}" ]]; then
        curl -fsS --retry 3 "https://hc-ping.com/${HEALTHCHECK_BACKUP_DB}/start" &
        log "Healthcheck monitoring enabled"
    fi
    
    # Se déplacer dans le répertoire de l'application
    cd "${APP_HOME}"
    
    # Vérification des variables d'environnement requises
    if [[ -z "${APP_ID:-}" ]] || [[ -z "${MYSQL_ADDON_UUID:-}" ]]; then
        error_exit "Missing required Clever Cloud environment variables (APP_ID or MYSQL_ADDON_UUID)"
    fi
    
    if [[ -z "${B2_ACCOUNT_ID:-}" ]] || [[ -z "${B2_ACCOUNT_KEY:-}" ]] || [[ -z "${B2_BUCKET:-}" ]]; then
        error_exit "Missing required Backblaze B2 environment variables"
    fi
    
    if [[ -z "${RESTIC_PASSWORD:-}" ]]; then
        error_exit "Missing RESTIC_PASSWORD environment variable"
    fi
    
    
    # Étapes du backup
    install_restic
    
    init_restic_repo
    
    # Télécharger le backup depuis Clever Cloud
    local compressed_backup=$(download_backup)
    
    # Se déplacer dans le répertoire temporaire pour les opérations
    cd "${TEMP_DIR}"
    
    # Décompresser pour optimiser la déduplication
    local decompressed_backup=$(decompress_backup "${compressed_backup}")
    
    # Sauvegarder vers Restic
    backup_to_restic "${decompressed_backup}"
    
    # Appliquer la politique de rétention
    apply_retention_policy
    
    # Vérifier l'intégrité (dimanche uniquement)
    check_repository_integrity
    
    log "========================================="
    log "Database backup process completed successfully"
    log "========================================="
    
    # Notification de succès à healthchecks.io si configuré
    if [[ -n "${HEALTHCHECK_BACKUP_DB:-}" ]]; then
        curl -fsS --retry 3 "https://hc-ping.com/${HEALTHCHECK_BACKUP_DB}" &
        log "Healthcheck success signal sent"
    fi
    
    # Le cleanup sera fait automatiquement via trap EXIT
}

# Exécution du script
main "$@"