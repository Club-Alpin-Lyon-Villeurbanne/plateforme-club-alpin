#!/bin/bash

# Utilitaires communs pour tous les scripts cron
# À sourcer dans chaque script pour bénéficier du monitoring healthchecks.io et autres fonctions utiles

# Configuration du comportement par défaut
set -euo pipefail

# Fonction pour envoyer un ping à healthchecks.io
# Usage: healthcheck_ping "UUID" ["start"|"fail"|""] ["message"]
healthcheck_ping() {
    local uuid="$1"
    local status="${2:-}"
    local message="${3:-}"
    
    # Si pas d'UUID, on ne fait rien (monitoring désactivé)
    if [[ -z "${uuid}" ]]; then
        return 0
    fi
    
    local url="https://hc-ping.com/${uuid}"
    
    # Ajouter le suffixe selon le status
    case "${status}" in
        start)
            url="${url}/start"
            ;;
        fail)
            url="${url}/fail"
            ;;
    esac
    
    # Envoyer le ping
    if [[ -n "${message}" ]]; then
        # Avec message (via POST)
        echo "${message}" | curl -fsS --retry 3 --data-binary @- "${url}" >/dev/null 2>&1 &
    else
        # Sans message (via GET)
        curl -fsS --retry 3 "${url}" >/dev/null 2>&1 &
    fi
}

# Fonction pour initialiser le monitoring d'un cron
# Usage: init_healthcheck "HEALTHCHECK_ENV_VAR_NAME" "script_name"
init_healthcheck() {
    local env_var_name="$1"
    local script_name="$2"
    
    # Récupérer l'UUID depuis la variable d'environnement
    HEALTHCHECK_UUID="${!env_var_name:-}"
    
    if [[ -z "${HEALTHCHECK_UUID}" ]]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] WARNING: ${env_var_name} not set, healthcheck monitoring disabled for ${script_name}"
        return 1
    fi
    
    # Configurer les trap pour gérer les erreurs
    trap 'healthcheck_error_handler' ERR
    trap 'healthcheck_exit_handler' EXIT
    
    # Marquer le début de l'exécution
    healthcheck_ping "${HEALTHCHECK_UUID}" "start"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Healthcheck monitoring enabled for ${script_name}"
    
    # Variable pour tracker si on a déjà envoyé un signal d'erreur
    HEALTHCHECK_ERROR_SENT=0
    
    return 0
}

# Handler pour les erreurs
healthcheck_error_handler() {
    if [[ "${HEALTHCHECK_ERROR_SENT}" -eq 0 ]] && [[ -n "${HEALTHCHECK_UUID:-}" ]]; then
        HEALTHCHECK_ERROR_SENT=1
        local error_msg="Script failed at line $LINENO with exit code $?"
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: ${error_msg}"
        healthcheck_ping "${HEALTHCHECK_UUID}" "fail" "${error_msg}"
    fi
}

# Handler pour la sortie du script
healthcheck_exit_handler() {
    local exit_code=$?
    
    # Si on n'a pas d'UUID, on ne fait rien
    if [[ -z "${HEALTHCHECK_UUID:-}" ]]; then
        return
    fi
    
    # Si le script se termine avec succès et qu'on n'a pas déjà envoyé un signal d'erreur
    if [[ ${exit_code} -eq 0 ]] && [[ "${HEALTHCHECK_ERROR_SENT}" -eq 0 ]]; then
        healthcheck_ping "${HEALTHCHECK_UUID}" ""
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Healthcheck success signal sent"
    elif [[ ${exit_code} -ne 0 ]] && [[ "${HEALTHCHECK_ERROR_SENT}" -eq 0 ]]; then
        # Si on sort avec une erreur mais qu'on n'a pas encore envoyé le signal
        healthcheck_ping "${HEALTHCHECK_UUID}" "fail" "Script exited with code ${exit_code}"
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Healthcheck failure signal sent (exit code: ${exit_code})"
    fi
}

# Fonction wrapper pour exécuter une commande avec logging et gestion d'erreur
# Usage: run_with_monitoring "command" "description"
run_with_monitoring() {
    local command="$1"
    local description="$2"
    
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ${description}..."
    
    if eval "${command}"; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] ${description} completed successfully"
        return 0
    else
        local exit_code=$?
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: ${description} failed with exit code ${exit_code}"
        return ${exit_code}
    fi
}

# Export des fonctions pour qu'elles soient disponibles dans les scripts qui sourcent ce fichier
export -f healthcheck_ping
export -f init_healthcheck
export -f healthcheck_error_handler  
export -f healthcheck_exit_handler
export -f run_with_monitoring