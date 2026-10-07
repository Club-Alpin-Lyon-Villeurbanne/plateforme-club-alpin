# Tâches planifiées (Cronjobs)

Les tâches récurrentes sont gérées directement depuis le code en s'appuyant sur le module de cronjobs fourni par Clever Cloud.

## Configuration

Les tâches planifiées sont configurées dans le fichier `clevercloud/cron.json` et les scripts associés sont stockés dans le répertoire `clevercloud/crons`. Pour plus d'informations, consultez la [documentation Clever Cloud sur les cronjobs](https://developers.clever-cloud.com/doc/administrate/cron/).

Les horaires sont ceux du serveur. Certains scripts ne s'exécutent qu'en production (test de `DEPLOY_ENV` dans le script).

## Liste des tâches

| Horaire | Script | Commande | Rôle | Production uniquement |
|---|---|---|---|---|
| Tous les jours 5 h 54 | `send-reminders.sh` | `event-to-publish-reminder-cron` | Rappel aux responsables de commission des sorties en attente de validation | Oui |
| Tous les jours 6 h 16 | `sync-nomads.sh` | `ffcam-discovery-file-sync` | Synchronisation des cartes découverte depuis le fichier FFCAM (voir [Synchronisation](synchronisation.md#cartes-découverte)) | Oui |
| Tous les jours 6 h 36 | `google-groups-sync.sh` | `google-groups-sync --execute` | Synchronisation des groupes Google et des accès aux Drive partagés à partir des rôles sur le site | Oui |
| Tous les jours 7 h 03 | `sync-members.sh` | `ffcam-file-sync` | Synchronisation des adhérents depuis le fichier FFCAM : création, mise à jour, fusion des doublons, blocage des licences expirées (voir [Synchronisation](synchronisation.md)) | Oui |
| Tous les jours 7 h 28 | `anonymize-users.sh` | `user-anonymization-cron` | Anonymisation RGPD des comptes dont la dernière adhésion date d'avant la saison N-2 | Non |
| Tous les jours 7 h 30 | `license-renew-reminder.sh` | `license-renew-reminder-cron` | Rappel de renouvellement de licence aux participants des sorties qui commencent dans 7 jours, et information de l'encadrement | Oui |
| Tous les jours 7 h 45 | `mailerlite-accueil-sync.sh nouveaux` | `mailerlite-accueil-sync --circuit nouveaux --execute` | Inscription des adhérents de la saison aux circuits d'accueil MailerLite | Oui |
| Tous les jours 8 h 54 | `clean-alerts.sh` | `clean-alerts` | Suppression des notifications expirées | Non |
| Le 1er septembre à 2 h 45 | `auto-renew-special-accounts.sh` | `auto-renew-special-accounts` | Remise au 1er septembre de la date d'adhésion des comptes spéciaux (`SPECIAL_ACCOUNTS_IDS`) | Non |

## Groupes Google et Drive partagés

`google-groups-sync` calcule les membres des groupes Google (listes de diffusion) à partir des rôles sur le site, puis les accès aux Drive partagés :

- groupe de commission : accès en écriture (`writer`) au Drive de la commission ;
- responsables de commission : accès « organisateur de fichiers » (`fileOrganizer`) ;
- Drive communs à tous : accès en commentaire (`commenter`).

Un accès manquant alors que le rôle est correct sur le site arrive donc au passage suivant (le lendemain matin).
