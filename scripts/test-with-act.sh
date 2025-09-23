#!/bin/bash

# Script pour tester les GitHub Actions localement avec act
# Prérequis: installer act (brew install act ou https://github.com/nektos/act)

echo "🚀 Test des GitHub Actions avec act"
echo "===================================="
echo ""
echo "Pour installer act:"
echo "  macOS: brew install act"
echo "  Linux: curl https://raw.githubusercontent.com/nektos/act/master/install.sh | sudo bash"
echo ""

# Vérifier si act est installé
if ! command -v act &> /dev/null; then
    echo "❌ act n'est pas installé"
    exit 1
fi

# Tester le workflow code-quality pour une PR
echo "Test du workflow code-quality (simulation PR)..."
act pull_request -W .github/workflows/code-quality.yaml \
    --secret-file .env.local \
    --platform ubuntu-latest=catthehacker/ubuntu:act-latest \
    -j phpstan

# Pour tester un workflow spécifique
# act -W .github/workflows/code-quality.yaml -j phpstan --dryrun