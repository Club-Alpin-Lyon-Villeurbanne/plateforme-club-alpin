# Tâches planifiées (Cronjobs)

Les tâches récurrentes sont gérées directement depuis le code en s'appuyant sur le module de cronjobs fourni par Clever Cloud.

## Configuration

Les tâches planifiées sont configurées dans le fichier `clevercloud/cron.json` et les scripts associés sont stockés dans le répertoire `clevercloud/crons`. Pour plus d'informations, consultez la [documentation Clever Cloud sur les cronjobs](https://developers.clever-cloud.com/doc/administrate/cron/).

## Tâches principales

1. **Vérification de la validité des adhésions** (`sync-members.sh`)
   - Exécution : Tous les jours à 7h03
   - Vérifie la validité des adhésions via des fichiers FFCAM

2. **Sauvegarde des images** (`save-images.sh`)
   - Exécution : Toutes les heures à XX:57
   - Gère la sauvegarde des images du site

3. **Rappels de validation des sorties** (`send-reminders.sh`)
   - Exécution : Tous les jours à 5h54
   - Envoie des rappels pour la validation des sorties

4. **Nettoyage des alertes** (`clean-alerts.sh`)
   - Exécution : Tous les jours à 8h54
   - Nettoie les alertes obsolètes

5. **Synchronisation des groupes Google** (`google-groups-sync.sh`)
   - Exécution : Tous les jours à 6h36
   - Synchronise les groupes Google avec les adhérents 

## Hors de ce dépôt

L'import FFCAM des formations, brevets, niveaux de pratique et groupes de compétences ne tourne pas chez Clever Cloud : il tourne chaque lundi matin sur GitHub Actions, dans le dépôt [ffcam-formations-scraper](https://github.com/Club-Alpin-Lyon-Villeurbanne/ffcam-formations-scraper). Voir [Formations, brevets et compétences FFCAM](schema-formations-competences.md).
