# Intégration MailerLite pour les mails de bienvenue

## Vue d'ensemble

Cette fonctionnalité permet d'envoyer automatiquement des emails de bienvenue aux nouveaux adhérents via MailerLite. Les nouveaux membres sont ajoutés à un groupe spécifique qui déclenche une automation d'envoi de mail de bienvenue.

## Configuration

### 1. Variables d'environnement

Ajouter dans votre fichier `.env.local` :

```env
# Activer l'intégration (true/false)
MAILERLITE_ENABLED=true

# Clé API MailerLite
MAILERLITE_API_KEY=votre_cle_api_ici
```

### 2. Obtenir la clé API MailerLite

1. Connectez-vous à [MailerLite](https://dashboard.mailerlite.com)
2. Allez dans **Integrations** > **API**
3. Copiez votre clé API

### 3. Configuration du groupe

Le groupe "Nouveaux adhérents" est déjà configuré avec l'ID `159667990712813289` dans `config/services.yaml`.

## Fonctionnement

### Synchronisation automatique

La synchronisation se fait automatiquement chaque nuit via le cron FFCAM :
1. Import des membres depuis le fichier FFCAM
2. Collecte des nouveaux membres
3. Si l'adhérent a un email, il est ajouté au groupe MailerLite
4. MailerLite déclenche automatiquement l'envoi du mail de bienvenue

## Données synchronisées

Pour chaque adhérent, les informations suivantes sont envoyées à MailerLite :

- **Email** (obligatoire)
- **Prénom** (`name`)
- **Nom** (`last_name`)
- **Numéro CAF** (`caf_number`)
- **Ville** (`city`)
- **Code postal** (`postal_code`)
- **Date d'adhésion** (`registration_date`)

## Import en masse

L'intégration utilise l'endpoint d'import en masse de MailerLite pour optimiser les performances :
- Les adhérents sont traités par batches de 100
- Un délai est respecté entre chaque batch pour éviter le rate limiting
- Le progrès de l'import est suivi en temps réel

## Monitoring

### Logs

Les opérations sont loggées dans les logs Symfony :

```bash
# Voir les logs en temps réel
tail -f var/log/prod.log | grep MailerLite
```

### Statistiques de synchronisation

Les logs affichent des statistiques détaillées après chaque synchronisation :
- Nombre d'adhérents importés (nouveaux)
- Nombre d'adhérents mis à jour (existants)
- Nombre d'échecs
- Nombre d'adhérents ignorés (sans email)

## Sécurité

- Les adhérents désinscrits de MailerLite ne sont pas réinscrits (`resubscribe: false`)
- Seuls les adhérents avec email valide sont synchronisés
- Les erreurs MailerLite n'affectent pas la création des adhérents dans la base

## Dépannage

### L'intégration ne fonctionne pas

1. Vérifier que `MAILERLITE_ENABLED=true` dans `.env.local`
2. Vérifier que la clé API est correcte
3. Consulter les logs pour les erreurs

### Certains adhérents ne sont pas synchronisés

- Vérifier qu'ils ont une adresse email
- Vérifier qu'ils ne sont pas des adhérents manuels ou nomades
- Vérifier les logs pour des erreurs spécifiques

### Rate limiting

Si vous avez beaucoup d'adhérents à synchroniser, l'API MailerLite peut imposer des limites. La synchronisation par batch avec délais est déjà implémentée pour minimiser ce problème.

## Tests

Pour tester l'intégration :

1. Créer un adhérent test avec email
2. Attendre la synchronisation automatique nocturne (ou déclencher manuellement le cron FFCAM)
3. Vérifier dans MailerLite que l'adhérent apparaît dans le groupe
4. Vérifier que le mail de bienvenue est envoyé