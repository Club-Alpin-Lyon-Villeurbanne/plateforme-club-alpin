# CI/CD

## Workflows GitHub Actions

### Qualité de code
- **code-quality.yaml** : PHP-CS-Fixer et PHPStan
- **tests.yaml** : Tests PHPUnit avec coverage

### Déploiement

#### Staging (automatique)

| Workflow | Déclencheur | Cible |
|----------|-------------|-------|
| `staging-deploy.yml` | Push sur `main` | clubalpinlyon.top |

Le déploiement sur staging est **automatique** à chaque push sur `main`.

#### Production (manuel)

| Workflow | Cible |
|----------|-------|
| `lyon-production-deploy.yml` | clubalpinlyon.fr |
| `chambery-production-deploy.yml` | Production Chambéry |
| `clermont-production-deploy.yml` | Production Clermont |

Les déploiements en production sont déclenchés **manuellement** via GitHub Actions.

#### Branches autorisées

| Branche | Staging | Production |
|---------|---------|------------|
| `main` | ✅ Auto | ❌ |
| `production` | ❌ | ✅ Manuel |
| `hotfix-prod-*` | ❌ | ✅ Manuel |

#### Comment déployer en production

1. Merger les changements dans `production` (ou créer une branche `hotfix-prod-*`)
2. Aller dans **Actions** > **Deploy on Production - Lyon** (ou autre club)
3. Cliquer sur **Run workflow**
4. Sélectionner la branche (`production` ou `hotfix-prod-*`)
5. Cliquer sur **Run workflow**

Le déploiement :
- Pousse le code sur Clever Cloud
- Envoie une notification Slack (succès ou échec)
- Met à jour la variable `*_LAST_DEPLOYED_SHA` pour tracker les déploiements

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