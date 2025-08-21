# Intégration Email Marketing

## Vue d'ensemble

Synchronisation automatique des nouveaux adhérents avec MailerLite et Mailchimp pour l'envoi de mails de bienvenue.

## Fonctionnement

La synchronisation se fait automatiquement chaque nuit via le cron FFCAM :
1. Import des membres depuis le fichier FFCAM
2. Collecte des nouveaux membres
3. Envoi en batch vers MailerLite/Mailchimp
4. Déclenchement automatique des mails de bienvenue

## Configuration

Dans `.env.local` :

```bash
# MailerLite
MAILERLITE_ENABLED=true
MAILERLITE_API_KEY=your_api_key_here
MAILERLITE_WELCOME_GROUP_ID=159667990712813289

# Mailchimp  
MAILCHIMP_ENABLED=true
MAILCHIMP_API_KEY=your_api_key_here
MAILCHIMP_LIST_ID=your_list_id_here
```

Actif uniquement pour Lyon. Chambéry et Clermont gardent les valeurs par défaut (désactivé).

## Champs synchronisés

**MailerLite** : name, last_name, caf_number, city, postal_code, registration_date  
**Mailchimp** : FNAME, LNAME, CAFNUM, CITY, ZIP

## Limitations

- MailerLite : max 100 membres par batch
- Mailchimp : max 500 membres par batch  
- Les membres manuels, nomades et sans email sont ignorés