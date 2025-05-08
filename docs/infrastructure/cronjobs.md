# Tâches planifiées (Cronjobs)

Les tâches récurrentes sont gérées directement depuis le code en s'appuyant sur le module de cronjobs fourni par Clever Cloud.

## Configuration

Les tâches planifiées sont stockées dans le répertoire `clevercloud/crons`. Pour plus d'informations, consultez la [documentation Clever Cloud sur les cronjobs](https://developers.clever-cloud.com/doc/administrate/cron/).

## Tâches principales

1. **Vérification de la validité des adhésions**
   - Vérifie la validité des adhésions via des fichiers FFCAM

2. **Sauvegarde des images**
   - Gère la sauvegarde des images du site

3. **Rappels de validation des sorties**
   - Envoie des rappels pour la validation des sorties 