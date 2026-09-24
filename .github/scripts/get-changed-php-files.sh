#!/bin/bash

# Script pour obtenir les fichiers PHP modifiés et les filtrer selon la config PHPStan
# Usage: ./get-changed-php-files.sh <base-sha>
#
# Outputs (GITHUB_OUTPUT) :
#   - has_changes: true/false
#   - files_path:  chemin d'un fichier contenant les chemins séparés par des NUL
#
# La liste n'est volontairement PAS exposée comme une chaîne : un nom de fichier est
# contrôlé par l'auteur de la PR et peut contenir n'importe quoi — espaces, `;`, `$(…)`,
# retours à la ligne. Interpolée dans un `run:` de workflow, une telle valeur s'exécute
# sur le runner (CWE-78) ; écrite telle quelle dans GITHUB_OUTPUT, elle permet d'injecter
# d'autres sorties. Les chemins transitent donc par un fichier NUL-séparé, que l'appelant
# relit avec `mapfile -d ''` avant de les passer comme arguments distincts.

set -euo pipefail

BASE_SHA=${1:-}

if [ -z "$BASE_SHA" ]; then
    echo "Error: BASE_SHA is required as first argument"
    echo "Usage: $0 <base-sha>"
    echo "Example: $0 HEAD"
    exit 1
fi

FILES_PATH=${CHANGED_PHP_FILES_PATH:-${RUNNER_TEMP:-/tmp}/changed-php-files.nul}
: > "$FILES_PATH"

count=0

while IFS= read -r -d '' file; do
    case "$file" in
        *.php) ;;
        *) continue ;;
    esac

    case "$file" in
        src/* | tests/* | legacy/*) ;;
        *) continue ;;
    esac

    # Exclure les fichiers/dossiers configurés dans phpstan.dist.neon
    # Cette liste doit correspondre à excludePaths dans phpstan.dist.neon
    case "$file" in
        legacy/pages/* | legacy/includes/* | legacy/index.php | \
        legacy/app/ajax/pages_reorder.php | legacy/app/ajax/get_content_html.php | \
        legacy/admin/ftp.php | var/cache/*)
            printf 'Excluding from PHPStan: %s\n' "$file" >&2
            continue
            ;;
    esac

    # Un fichier supprimé par la PR n'est plus analysable
    [ -f "$file" ] || continue

    printf '%s\0' "$file" >> "$FILES_PATH"
    count=$(( count + 1 ))
    printf 'PHP file to analyze: %s\n' "$file" >&2
done < <(git diff --name-only -z --diff-filter=ACMRTUXB "$BASE_SHA")

if [ "$count" -gt 0 ]; then
    has_changes=true
else
    has_changes=false
fi

if [ -n "${GITHUB_OUTPUT:-}" ]; then
    {
        printf 'has_changes=%s\n' "$has_changes"
        printf 'files_path=%s\n' "$FILES_PATH"
    } >> "$GITHUB_OUTPUT"

    if [ "$has_changes" = false ]; then
        echo "No relevant PHP files changed for PHPStan (all files are excluded or don't exist)" >&2
    else
        printf '%d PHP file(s) to analyze\n' "$count" >&2
    fi
else
    # Mode standalone pour tests locaux
    printf 'has_changes=%s\n' "$has_changes"
    printf 'files_path=%s\n' "$FILES_PATH"
    printf 'count=%d\n' "$count"
fi
