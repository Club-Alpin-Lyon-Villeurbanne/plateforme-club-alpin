# Déploiement

## Environnements

### Staging

- **URL** : [www.clubalpinlyon.top](https://www.clubalpinlyon.top)
- Déploiement automatique à chaque push sur `main`
- Hébergé sur [Clever Cloud](https://www.clever-cloud.com/)

### Production

- **URL** : [www.clubalpinlyon.fr](https://www.clubalpinlyon.fr)
- Déploiement automatique via GitHub Actions à chaque push sur `production`
- Hébergé sur [Clever Cloud](https://www.clever-cloud.com/)

### Infrastructure technique

- Base de données MySQL 8.0 (hébergée et managée par Clever Cloud)
- Variables d'environnement gérées dans la console Clever Cloud
- filesystem fourni par Clever Cloud

## Workflows GitHub Actions

### Qualité de code

- **code-quality.yaml** : Lint et analyse statique sur chaque PR

### Déploiement

| Workflow | Déclencheur | Cible |
|----------|-------------|-------|
| Production Lyon | Push sur `production` | clubalpinlyon.fr |
| Autres clubs | Manuel (workflow_dispatch) | Chambéry, etc. |

#### Branches et déploiements

| Branche | Staging (Clever Cloud) | Prod Lyon (GitHub Actions) | Prod autres (GitHub Actions) |
|---------|------------------------|----------------------------|------------------------------|
| `main` | ✅ Auto | ❌ | ❌ |
| `production` | ❌ | ✅ Auto | ✅ Manuel |
| `hotfix-prod-*` | ❌ | ✅ Manuel | ✅ Manuel |

## Procédure de déploiement en production

```bash
git checkout production
git pull
git merge --ff-only main
git push
```

Le `--ff-only` garantit un historique linéaire et échoue si `production` a divergé de `main`.

### Hotfix urgent

1. Créer une branche `hotfix-prod-*` depuis `production`
2. Déclencher manuellement le workflow du club concerné

### Bonnes pratiques

- **Protéger la branche `production`** : Activer la protection dans GitHub Settings pour éviter les pushs directs non autorisés
- Toujours valider sur staging avant de déployer en production

## Changer de version de PHP

La version mineure de PHP (8.5 aujourd'hui) est fixée à la fois dans le dépôt et sur Clever Cloud. Les deux doivent changer ensemble : un build Clever dont la version de PHP ne correspond pas à `composer.json` échoue au `composer install`, et l'application reste sur son instance précédente.

Dans le dépôt, en une seule PR :

- `composer.json` : `require.php`, puis `composer update --lock`. `composer why-not php 8.6.0` liste les dépendances qui bloquent la nouvelle version.
- `docker/Dockerfile` : `PHP_VERSION`, et les versions des extensions si elles ne compilent pas.
- `.github/actions/prepare-for-quality/action.yml` : `php-version`.
- `README.md` : le badge et la ligne « Backend ».

Sur Clever Cloud, c'est la variable `CC_PHP_VERSION` de chaque application (prod de chaque club et staging). Lyon et le staging la lisent dans un configuration provider partagé, Clermont dans le sien, et Chambéry la porte directement sur l'application. Il faut la modifier là où elle est définie, sinon on crée un doublon. Elle ne prend effet qu'au build suivant. La changer au moment du déploiement qui apporte la nouvelle version : à la fusion dans `main` pour le staging, à la mise en production de chaque club pour la prod. Le log de build n'affiche que la version mineure. La version exacte se lit avec `clever ssh --alias <app> --instance any --command 'php -v'`.

En local, `make docker-start` reconstruit l'image quand le Dockerfile change.

## Token Clever Cloud

> **Note importante** : Un seul token Clever Cloud est partagé entre tous les clubs utilisant cette plateforme.

Le déploiement en production via GitHub Actions nécessite un token d'authentification Clever Cloud avec les contraintes suivantes :

- **Expiration** : Le token expire après 1 an et doit être renouvelé
- **Type de compte** : Token nominatif lié à un compte personnel
- **Impact** : Une expiration ou révocation du token empêche tous les déploiements automatiques pour tous les clubs

### Renouvellement du token

1. Se connecter à [Clever Cloud Console](https://console.clever-cloud.com/)
2. Aller dans Profile > Tokens
3. Créer un nouveau token
4. Mettre à jour les secrets `CLEVER_TOKEN` et `CLEVER_SECRET` dans GitHub

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

## Test local avec `act`

```bash
# Installation
brew install act  # macOS
curl https://raw.githubusercontent.com/nektos/act/master/install.sh | sudo bash  # Linux

# Utilisation
act pull_request  # Simuler une PR
```
