# Intégration Email Marketing

## Vue d'ensemble

Cette fonctionnalité permet de synchroniser automatiquement les nouveaux adhérents avec des services d'email marketing (MailerLite, Mailchimp) pour l'envoi automatique de mails de bienvenue.

## Architecture

L'intégration se fait via le processus de synchronisation FFCAM existant :

```
Cron Job (nuit) → FfcamFileSync → FfcamSynchronizer → MailerLite/Mailchimp
```

### Composants principaux

- **FfcamSynchronizer** : Service principal qui collecte les nouveaux membres lors de la synchronisation FFCAM
- **MailerLiteService** : Service d'intégration avec MailerLite (implémenté)
- **MailchimpService** : Service d'intégration avec Mailchimp (stub, à implémenter)
- **MailerLiteSyncCommand** : Commande pour synchroniser manuellement les membres récents

## Configuration

### 1. Variables d'environnement

Ajouter dans `.env.local` :

```bash
# Activation générale de la synchronisation email marketing
EMAIL_MARKETING_SYNC_ENABLED=true

# Configuration MailerLite
MAILERLITE_ENABLED=true
MAILERLITE_API_KEY=your_api_key_here
MAILERLITE_WELCOME_GROUP_ID=159667990712813289  # Groupe "Nouveaux adhérents"

# Configuration Mailchimp (à implémenter)
MAILCHIMP_ENABLED=false
MAILCHIMP_API_KEY=
MAILCHIMP_LIST_ID=
```

### 2. Configuration par instance

- **Lyon** : Activer `EMAIL_MARKETING_SYNC_ENABLED=true`
- **Chambéry** : Garder `EMAIL_MARKETING_SYNC_ENABLED=false` (par défaut)
- **Clermont** : Garder `EMAIL_MARKETING_SYNC_ENABLED=false` (par défaut)

## Utilisation

### Synchronisation automatique

La synchronisation se fait automatiquement chaque nuit via le cron job FFCAM :
- Les nouveaux membres sont collectés pendant l'import FFCAM
- Ils sont envoyés en batch à MailerLite/Mailchimp
- Les mails de bienvenue sont déclenchés automatiquement

### Synchronisation manuelle

Pour synchroniser manuellement les membres des 7 derniers jours :

#### MailerLite
```bash
php bin/console app:mailerlite:sync
```

#### Mailchimp
```bash
php bin/console app:mailchimp:sync
```

Ces commandes synchronisent automatiquement les nouveaux membres des 7 derniers jours si le service est activé dans la configuration.

## APIs d'intégration

### MailerLite

#### Endpoints utilisés
- `POST /subscribers` : Créer/mettre à jour un abonné
- `POST /subscribers/{id}/groups/{groupId}` : Ajouter à un groupe
- `POST /groups/{groupId}/subscribers/import` : Import en masse
- `GET /batch/{id}` : Vérifier le statut d'un import

#### Champs synchronisés
- `email` : Email de l'adhérent
- `name` : Prénom
- `last_name` : Nom
- `caf_number` : Numéro CAF
- `city` : Ville
- `postal_code` : Code postal
- `registration_date` : Date d'adhésion

### Mailchimp

#### Endpoints utilisés
- `POST /lists/{list_id}` : Import en masse de membres
- `PUT /lists/{list_id}/members/{hash}` : Ajouter/mettre à jour un membre unique

#### Champs synchronisés (merge fields)
- `FNAME` : Prénom
- `LNAME` : Nom
- `CAFNUM` : Numéro CAF
- `CITY` : Ville
- `ZIP` : Code postal
- `timestamp_signup` : Date d'adhésion

## Monitoring

### Logs

Les logs sont disponibles dans `var/log/dev.log` ou `var/log/prod.log` :

```
[info] Synchronizing 5 new members with email marketing services
[info] MailerLite sync: 3 imported, 2 updated, 0 failed
```

### Vérification

1. Vérifier que les nouveaux membres apparaissent dans MailerLite
2. Vérifier qu'ils sont dans le groupe "Nouveaux adhérents"
3. Vérifier que les mails de bienvenue sont envoyés

## Sécurité

- Les API keys sont stockées dans `.env.local` (non versionné)
- Les erreurs d'API n'interrompent pas la synchronisation FFCAM
- Les membres sans email sont ignorés silencieusement
- Les désinscrits ne sont pas réinscrits (`resubscribe: false`)

## Maintenance

### Configurer Mailchimp

L'intégration Mailchimp est maintenant complète. Pour l'activer :

1. Obtenir une clé API depuis [Mailchimp Account Settings](https://admin.mailchimp.com/account/api/)
2. Créer ou identifier la liste (audience) à utiliser
3. Ajouter dans `.env.local` :
   ```bash
   MAILCHIMP_ENABLED=true
   MAILCHIMP_API_KEY=xxxxx-us6  # Format: key-datacenter
   MAILCHIMP_LIST_ID=abc123def4
   ```
4. Tester avec `php bin/console app:mailchimp:sync --dry-run`
5. Activer en production

### Désactiver temporairement

Pour désactiver temporairement sans supprimer la configuration :

```bash
EMAIL_MARKETING_SYNC_ENABLED=false
```

## Limitations

### MailerLite
- Maximum 100 membres par batch
- Pause de 1 seconde entre les batches (rate limiting)
- Timeout de 60 secondes pour la vérification d'import

### Mailchimp
- Maximum 500 membres par batch
- Authentification Basic Auth avec API key
- Data center extrait automatiquement de l'API key

### Général
- Les membres manuels et nomades ne sont pas synchronisés
- Les membres sans email sont ignorés