# CI/CD - Tests et Qualité du Code

## Vue d'ensemble

Le projet utilise GitHub Actions pour automatiser les tests et la qualité du code. Les workflows s'exécutent automatiquement sur chaque Pull Request et push sur `main`.

## Workflows

### 1. Code Quality (`code-quality.yaml`)
- **PHP-CS-Fixer** : Vérifie le formatage du code PHP
- **PHPStan** : Analyse statique du code PHP

### 2. Tests (`tests.yaml`)
- Tests unitaires et d'intégration avec PHPUnit
- Génération du coverage

## Test local avant de pusher

### Méthode 1 : Scripts de test rapide

```bash
# Tester les changements de votre branche actuelle vs main
make ci-test-local

# Ou directement
./scripts/test-ci-locally.sh

# Tester vs une autre branche
make ci-test-branch BRANCH=develop
```

### Méthode 2 : Utiliser `act` pour exécuter GitHub Actions localement

[Act](https://github.com/nektos/act) permet d'exécuter les GitHub Actions localement dans Docker.

#### Installation

```bash
# macOS
brew install act

# Linux
curl https://raw.githubusercontent.com/nektos/act/master/install.sh | sudo bash

# Windows (avec Chocolatey)
choco install act-cli
```

#### Utilisation

```bash
# Lister les workflows disponibles
act -l

# Simuler une Pull Request (exécute tous les jobs PR)
act pull_request

# Exécuter un workflow spécifique
act -W .github/workflows/code-quality.yaml

# Exécuter un job spécifique
act -W .github/workflows/code-quality.yaml -j phpstan

# Mode dry-run (voir ce qui sera exécuté sans le faire)
act -W .github/workflows/code-quality.yaml -j phpstan --dryrun

# Avec secrets locaux (créer un fichier .env.local)
act pull_request --secret-file .env.local

# Utiliser une image Docker spécifique (recommandé)
act -P ubuntu-latest=catthehacker/ubuntu:act-latest
```

#### Exemples pratiques

```bash
# Tester le workflow PHP-CS-Fixer sur vos changements
act -W .github/workflows/code-quality.yaml -j php-cs

# Tester PHPStan
act -W .github/workflows/code-quality.yaml -j phpstan

# Simuler une PR complète
act pull_request -P ubuntu-latest=catthehacker/ubuntu:act-latest
```

## Scripts utilitaires

### `.github/scripts/get-changed-php-files.sh`

Script pour PHPStan qui détecte les fichiers PHP modifiés et exclut automatiquement ceux configurés dans `phpstan.neon`.

```bash
# Usage
./.github/scripts/get-changed-php-files.sh <base-sha>

# Exemples
./.github/scripts/get-changed-php-files.sh HEAD
./.github/scripts/get-changed-php-files.sh origin/main
./.github/scripts/get-changed-php-files.sh $(git merge-base main HEAD)
```

Le script filtre automatiquement les dossiers exclus par PHPStan (`legacy/pages/`, `legacy/includes/`, etc.).

## Résolution de problèmes

### PHPStan échoue avec "No files found to analyse"

Cela arrive quand tous les fichiers modifiés sont dans les dossiers exclus (`legacy/pages/`, `legacy/includes/`, etc.).
C'est normal et le workflow affiche maintenant un message approprié.

### PHP-CS-Fixer échoue

Pour corriger localement :
```bash
# Voir les problèmes
make php-cs-check

# Corriger automatiquement
make php-cs-fix
```

### Tests locaux avec les vrais conteneurs Docker

```bash
# Démarrer l'environnement complet
make init

# Exécuter les tests
make tests

# Exécuter PHPStan
make phpstan

# Nettoyer
make down
```

## Configuration

### Fichiers de configuration

- `.php-cs-fixer.dist.php` : Règles de formatage PHP
- `phpstan.neon` : Configuration PHPStan (niveau, exclusions, etc.)
- `.github/workflows/` : Définitions des workflows GitHub Actions

### Exclusions PHPStan

Les dossiers suivants sont exclus de l'analyse PHPStan :
- `legacy/pages/`
- `legacy/includes/`
- `legacy/index.php`
- `legacy/app/ajax/pages_reorder.php`
- `legacy/app/ajax/get_content_html.php`
- `legacy/admin/ftp.php`
- `var/cache/`

Pour modifier ces exclusions, éditez `phpstan.neon` et `.github/scripts/get-changed-php-files.sh`.