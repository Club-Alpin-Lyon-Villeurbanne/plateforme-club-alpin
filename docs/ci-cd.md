# CI/CD

## Workflows GitHub Actions

- **code-quality.yaml** : PHP-CS-Fixer et PHPStan
- **tests.yaml** : Tests PHPUnit avec coverage

## Test local avec `act`

```bash
# Installation
brew install act  # macOS
curl https://raw.githubusercontent.com/nektos/act/master/install.sh | sudo bash  # Linux

# Utilisation
act pull_request  # Simuler une PR
act -W .github/workflows/code-quality.yaml -j phpstan  # Job spécifique
act -l  # Lister les workflows
```

## Commandes utiles

```bash
# Formatage PHP
make php-cs-check  # Vérifier
make php-cs-fix    # Corriger

# Analyse statique
make phpstan

# Tests
make tests
```

## Scripts

**`.github/scripts/get-changed-php-files.sh`** : Détecte les fichiers PHP modifiés pour PHPStan (exclut automatiquement `legacy/pages/`, `legacy/includes/`, etc.)