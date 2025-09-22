# Environnements

## Hébergement

Nous disposons de deux environnements hébergés sur [Clever Cloud](https://www.clever-cloud.com/) :

### Environnements disponibles

1. **Staging** ([www.clubalpinlyon.top](https://www.clubalpinlyon.top))
   - Environnement de test
   - Déploiement continu via Github Actions
   - Chaque Pull Request mergée devient rapidement disponible à tester

2. **Production** ([www.clubalpinlyon.fr](https://www.clubalpinlyon.fr))
   - Environnement final des utilisateurs
   - Déclenchement manuel et déploiement automatisé via Github Actions

## Infrastructure technique

- Serveur web
- Base de données MySQL 8.0 (hébergée et managée par Clever Cloud)
- Variables d'environnement gérées dans la console de Clever Cloud
- filesystem fourni par Clever Cloud: 1 pour le dossier `ftp` et un pour l'upload du fichier de la FFCAM.

## Gestion des déploiements

### Token d'authentification Clever Cloud

> **Note importante** : Un seul token Clever Cloud est partagé entre tous les clubs utilisant cette plateforme (Lyon, Chambéry, Clermont, etc.)

Le déploiement automatique via GitHub Actions nécessite un token d'authentification Clever Cloud avec les contraintes suivantes :

- **Expiration** : Le token expire après 1 an et doit être renouvelé avant cette date
- **Type de compte** : Token nominatif lié à un compte personnel (Clever Cloud ne propose pas de compte service)
- **Impact** : Une expiration ou révocation du token empêche tous les déploiements automatiques pour tous les clubs
- **Renouvellement** : Si le détenteur du token quitte le projet, il faut le renouveler immédiatement

#### Configuration dans GitHub

Les secrets suivants doivent être configurés dans les paramètres du repository :
- `CLEVER_TOKEN` : Token d'authentification Clever Cloud
- `CLEVER_SECRET` : Secret associé au token

#### Renouvellement du token

1. Se connecter à [Clever Cloud Console](https://console.clever-cloud.com/)
2. Aller dans Profile > Tokens
3. Créer un nouveau token et noter la date d'expiration
4. Mettre à jour les secrets `CLEVER_TOKEN` et `CLEVER_SECRET` dans GitHub
5. Documenter le changement et la nouvelle date d'expiration dans ce fichier

Pour plus d'informations : [Documentation API Clever Cloud](https://www.clever-cloud.com/doc/extend/cc-api/#tokens)