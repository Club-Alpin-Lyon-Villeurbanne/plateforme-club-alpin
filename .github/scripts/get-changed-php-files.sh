#!/bin/bash

# Script pour obtenir les fichiers PHP modifiés et les filtrer selon la config PHPStan
# Usage: ./get-changed-php-files.sh <base-sha>
#
# Outputs:
#   - files: Liste des fichiers à analyser
#   - has_changes: true/false

BASE_SHA=$1

if [ -z "$BASE_SHA" ]; then
    echo "Error: BASE_SHA is required as first argument"
    exit 1
fi

# Récupérer les fichiers PHP modifiés
CHANGED_FILES=$(git diff --name-only --diff-filter=ACMRTUXB "$BASE_SHA" | grep -E '\.php$' | grep -E '^(src|tests|legacy)/' || true)

# Filtrer les fichiers exclus par PHPStan
FILTERED_FILES=""
for file in $CHANGED_FILES; do
    # Exclure les fichiers/dossiers configurés dans phpstan.neon
    # Cette liste doit correspondre à excludePaths dans phpstan.neon
    if echo "$file" | grep -qE '^(legacy/pages/|legacy/includes/|legacy/index\.php|legacy/app/ajax/pages_reorder\.php|legacy/app/ajax/get_content_html\.php|legacy/admin/ftp\.php|var/cache/)'; then
        echo "Excluding from PHPStan: $file" >&2
    else
        if [ -f "$file" ]; then
            FILTERED_FILES="$FILTERED_FILES $file"
        fi
    fi
done

# Retirer les espaces en début/fin
FILTERED_FILES=$(echo "$FILTERED_FILES" | xargs)

# Définir les outputs pour GitHub Actions
if [ -n "$GITHUB_OUTPUT" ]; then
    echo "files=$FILTERED_FILES" >> "$GITHUB_OUTPUT"
    if [ -z "$FILTERED_FILES" ]; then
        echo "No relevant PHP files changed for PHPStan (all files are excluded or don't exist)" >&2
        echo "has_changes=false" >> "$GITHUB_OUTPUT"
    else
        echo "PHP files to analyze: $FILTERED_FILES" >&2
        echo "has_changes=true" >> "$GITHUB_OUTPUT"
    fi
else
    # Mode standalone pour tests locaux
    if [ -z "$FILTERED_FILES" ]; then
        echo "No relevant PHP files to analyze"
        echo "has_changes=false"
    else
        echo "files=$FILTERED_FILES"
        echo "has_changes=true"
    fi
fi