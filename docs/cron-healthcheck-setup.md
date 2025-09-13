# Configuration du monitoring des crons avec Healthchecks.io

## Vue d'ensemble

Tous les scripts cron sont maintenant équipés d'un système de monitoring automatique via healthchecks.io. Ce système permet de :
- Détecter les crons qui ne s'exécutent pas
- Être alerté en cas d'échec d'un cron
- Avoir une vue d'ensemble de la santé de tous les crons

## Fonctionnement

Chaque script cron source le fichier `clevercloud/crons/common.sh` qui fournit les fonctions de monitoring. Les scripts envoient automatiquement :
- Un ping `/start` au début de l'exécution
- Un ping de succès à la fin si tout s'est bien passé
- Un ping `/fail` en cas d'erreur

## Variables d'environnement requises

Pour activer le monitoring d'un cron, définissez la variable d'environnement correspondante avec l'UUID fourni par healthchecks.io :

| Script | Variable d'environnement | Description |
|--------|-------------------------|-------------|
| sync-members.sh | HEALTHCHECK_SYNC_MEMBERS | Synchronisation des membres FFCAM |
| save-images.sh | HEALTHCHECK_SAVE_IMAGES | Sauvegarde des images |
| send-reminders.sh | HEALTHCHECK_SEND_REMINDERS | Envoi des rappels |
| clean-alerts.sh | HEALTHCHECK_CLEAN_ALERTS | Nettoyage des alertes |
| google-groups-sync.sh | HEALTHCHECK_GOOGLE_GROUPS_SYNC | Synchronisation Google Groups |
| backup-database.sh | HEALTHCHECK_BACKUP_DB | Backup de la base de données |

## Configuration sur Clever Cloud

1. Connectez-vous à votre compte Clever Cloud
2. Accédez aux variables d'environnement de votre application
3. Ajoutez les variables avec les UUID de vos checks healthchecks.io

Exemple :
```bash
HEALTHCHECK_SYNC_MEMBERS=uuid-1234-5678-9abc
HEALTHCHECK_SAVE_IMAGES=uuid-2345-6789-abcd
HEALTHCHECK_SEND_REMINDERS=uuid-3456-789a-bcde
HEALTHCHECK_CLEAN_ALERTS=uuid-4567-89ab-cdef
HEALTHCHECK_GOOGLE_GROUPS_SYNC=uuid-5678-9abc-def0
HEALTHCHECK_BACKUP_DB=uuid-6789-abcd-ef01
```

## Configuration sur Healthchecks.io

Pour chaque cron :

1. Créez un nouveau check sur healthchecks.io
2. Configurez le schedule selon votre cron.json :
   - sync-members.sh : Daily at 07:03
   - save-images.sh : Every hour at :57
   - send-reminders.sh : Daily at 05:54
   - clean-alerts.sh : Daily at 08:54
   - google-groups-sync.sh : Daily at 06:36
   - backup-database.sh : Daily at 04:15

3. Configurez les alertes (email, Slack, etc.)
4. Copiez l'UUID et ajoutez-le comme variable d'environnement

## Fonctions disponibles dans common.sh

### init_healthcheck
Initialise le monitoring pour un script
```bash
init_healthcheck "HEALTHCHECK_ENV_VAR_NAME" "script-name"
```

### run_with_monitoring
Exécute une commande avec logging et gestion d'erreur
```bash
run_with_monitoring "command" "description"
```

### healthcheck_ping
Envoie manuellement un ping à healthchecks.io
```bash
healthcheck_ping "UUID" ["start"|"fail"|""] ["message"]
```

## Désactiver temporairement le monitoring

Si une variable d'environnement n'est pas définie, le monitoring est automatiquement désactivé pour ce cron avec un warning dans les logs.

## Logs

Les scripts loggent automatiquement :
- L'activation du monitoring au démarrage
- Les erreurs avec numéro de ligne et code de sortie
- L'envoi des signaux de succès/échec

Format des logs :
```
[2025-09-13 04:15:00] Healthcheck monitoring enabled for backup-database
[2025-09-13 04:15:30] Healthcheck success signal sent
```

## Troubleshooting

### Le cron ne ping pas healthchecks.io
- Vérifiez que la variable d'environnement est bien définie
- Vérifiez que l'UUID est correct
- Consultez les logs du cron pour voir si le monitoring est activé

### Fausses alertes
- Vérifiez que le schedule sur healthchecks.io correspond à cron.json
- Augmentez la grace period si le cron prend plus de temps que prévu

### Erreurs non détectées
- Assurez-vous que le script utilise `set -euo pipefail` (inclus dans common.sh)
- Vérifiez que les commandes critiques retournent bien un code d'erreur en cas d'échec