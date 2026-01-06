# Infrastructure

## Environnements

Nous disposons de deux environnements hébergés sur [Clever Cloud](https://www.clever-cloud.com/) :

- **Staging** : [www.clubalpinlyon.top](https://www.clubalpinlyon.top)
  - Déploiement automatique par Clever Cloud (à chaque push sur `main`)
  - Environnement de test
- **Production** : [www.clubalpinlyon.fr](https://www.clubalpinlyon.fr)
  - Déploiement automatique via GitHub Actions (à chaque push sur `production`)
  - Environnement final

## Architecture

- Serveur web
- Base de données MySQL 8.0 (hébergée et managée par Clever Cloud)
- Variables d'environnement gérées dans la console Clever Cloud

## Tâches Automatisées

Les tâches récurrentes sont gérées via le module de cronjobs de Clever Cloud.
Configuration dans le répertoire `clevercloud/crons`.

Tâches principales :
- Vérification des adhésions FFCAM
- Sauvegarde des images
- Rappels de validation des sorties

Documentation : https://developers.clever-cloud.com/doc/administrate/cron/ 