#!/bin/bash

# Script pour tester localement les changements CI/CD
# Usage: ./scripts/test-ci-locally.sh [branch-to-compare]

set -e

echo "🧪 Test CI/CD local"
echo "=================="

# Couleurs pour output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Branch de comparaison (par défaut: main)
BASE_BRANCH=${1:-main}
CURRENT_BRANCH=$(git branch --show-current)

echo -e "${YELLOW}Branch actuelle:${NC} $CURRENT_BRANCH"
echo -e "${YELLOW}Branch de base:${NC} $BASE_BRANCH"
echo ""

# 1. Récupérer les fichiers PHP modifiés (simule le workflow)
echo -e "${YELLOW}📁 Détection des fichiers PHP modifiés...${NC}"
CHANGED_FILES=$(git diff --name-only $BASE_BRANCH..HEAD | grep -E '\.php$' | grep -E '^(src|tests|legacy)/' || true)

if [ -z "$CHANGED_FILES" ]; then
    echo -e "${GREEN}✅ Aucun fichier PHP modifié${NC}"
else
    echo "Fichiers détectés:"
    echo "$CHANGED_FILES" | sed 's/^/  - /'
    echo ""

    # 2. Utiliser le script commun pour filtrer les fichiers
    echo -e "${YELLOW}🔍 Filtrage des fichiers exclus par PHPStan...${NC}"

    # Utiliser le script centralisé
    SCRIPT_OUTPUT=$(./.github/scripts/get-changed-php-files.sh $(git merge-base $BASE_BRANCH HEAD) 2>&1)

    # Extraire les valeurs
    FILTERED_FILES=$(echo "$SCRIPT_OUTPUT" | grep "^files=" | cut -d= -f2-)
    HAS_CHANGES=$(echo "$SCRIPT_OUTPUT" | grep "^has_changes=" | cut -d= -f2)

    # Afficher les exclusions (messages stderr du script)
    EXCLUSIONS=$(echo "$SCRIPT_OUTPUT" | grep "^Excluding from PHPStan:")
    if [ ! -z "$EXCLUSIONS" ]; then
        echo -e "${YELLOW}Fichiers exclus:${NC}"
        echo "$EXCLUSIONS" | sed 's/Excluding from PHPStan: /  ❌ /'
    fi

    if [ "$HAS_CHANGES" = "false" ] || [ -z "$FILTERED_FILES" ]; then
        echo -e "${GREEN}✅ Tous les fichiers sont exclus, PHPStan ne sera pas exécuté${NC}"
    else
        echo -e "${GREEN}Fichiers à analyser:${NC}"
        echo "$FILTERED_FILES" | tr ' ' '\n' | sed 's/^/  ✓ /'
        echo ""

        # 3. Exécuter PHPStan sur les fichiers filtrés
        echo -e "${YELLOW}🔬 Exécution de PHPStan...${NC}"
        if make phpstan-files FILES="$FILTERED_FILES"; then
            echo -e "${GREEN}✅ PHPStan: OK${NC}"
        else
            echo -e "${RED}❌ PHPStan: ERREUR${NC}"
            exit 1
        fi
    fi
fi

echo ""
echo -e "${YELLOW}🎨 Test PHP-CS-Fixer...${NC}"

# 4. Tester PHP-CS-Fixer sur les fichiers modifiés
if [ ! -z "$CHANGED_FILES" ]; then
    echo "Vérification du formatage..."

    # Créer un fichier temporaire avec la liste des fichiers
    TEMP_FILE=$(mktemp)
    echo "$CHANGED_FILES" > $TEMP_FILE

    # Vérifier chaque fichier
    HAS_ERRORS=0
    while IFS= read -r file; do
        if [ -f "$file" ]; then
            echo -n "  Checking $file... "
            if make php-cs-check args="$file" > /dev/null 2>&1; then
                echo -e "${GREEN}OK${NC}"
            else
                echo -e "${RED}NEEDS FIXING${NC}"
                HAS_ERRORS=1
            fi
        fi
    done < $TEMP_FILE

    rm $TEMP_FILE

    if [ $HAS_ERRORS -eq 1 ]; then
        echo -e "${YELLOW}⚠️  Des fichiers ont besoin d'être formatés${NC}"
        echo "Exécutez: make php-cs-fix"
    else
        echo -e "${GREEN}✅ PHP-CS-Fixer: OK${NC}"
    fi
else
    echo -e "${GREEN}✅ Aucun fichier PHP à vérifier${NC}"
fi

echo ""
echo -e "${GREEN}🎉 Test CI local terminé!${NC}"