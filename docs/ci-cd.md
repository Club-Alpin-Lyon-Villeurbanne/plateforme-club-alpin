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

#### Production Lyon (automatique)

| Workflow | Déclencheur | Cible |
|----------|-------------|-------|
| `lyon-production-deploy.yml` | Push sur `production` | clubalpinlyon.fr |

Le déploiement en production Lyon est **automatique** à chaque push sur `production`.

#### Production autres clubs (manuel)

| Workflow | Cible |
|----------|-------|
| `chambery-production-deploy.yml` | Production Chambéry |
| `clermont-production-deploy.yml` | Production Clermont |

Ces clubs déploient **manuellement** via GitHub Actions (workflow_dispatch).

#### Branches autorisées

| Branche | Staging | Prod Lyon | Prod autres |
|---------|---------|-----------|-------------|
| `main` | ✅ Auto | ❌ | ❌ |
| `production` | ❌ | ✅ Auto | ✅ Manuel |
| `hotfix-prod-*` | ❌ | ✅ Manuel | ✅ Manuel |

#### Comment déployer en production

**Lyon** : Merger dans `production` → déploiement automatique

**Chambéry / Clermont** (manuel) :
1. Aller dans **Actions** > **Deploy on Production - Chambéry** (ou Clermont)
2. Cliquer sur **Run workflow**
3. Sélectionner la branche (`production` ou `hotfix-prod-*`)

**Hotfix urgent** :
1. Créer une branche `hotfix-prod-*` depuis `production`
2. Déclencher manuellement le workflow du club concerné

Le déploiement :
- Pousse le code sur Clever Cloud
- Envoie une notification Slack (succès ou échec)
- Met à jour la variable `*_LAST_DEPLOYED_SHA` pour tracker les déploiements

#### Bonnes pratiques

- **Protéger la branche `production`** : Activer la protection dans GitHub Settings pour éviter les pushs directs non autorisés
- **Tester sur staging** : Toujours valider sur clubalpinlyon.top avant de merger dans `production`

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