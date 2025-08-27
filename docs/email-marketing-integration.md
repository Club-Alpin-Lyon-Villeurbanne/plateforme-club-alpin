# Intégration Email Marketing - MailerLite

## Vue d'ensemble

Synchronisation automatique des nouveaux adhérents avec MailerLite pour l'envoi de mails de bienvenue.

## Fonctionnement

La synchronisation se fait automatiquement lors de la création et confirmation d'utilisateurs :
1. Création d'un nouvel utilisateur via le formulaire legacy
2. Confirmation d'un compte utilisateur
3. Synchronisation automatique avec MailerLite
4. Déclenchement automatique des mails de bienvenue

## Configuration

Dans `.env.local` :

```bash
# MailerLite
MAILERLITE_API_KEY=your_api_key_here
MAILERLITE_WELCOME_GROUP_ID=159667990712813289
```

Actif uniquement pour Lyon. Chambéry et Clermont gardent les valeurs par défaut (désactivé).

## Champs synchronisés

**MailerLite** : name, last_name, caf_number, city, postal_code, registration_date

## Limitations

- MailerLite : max 100 membres par batch
- Les membres manuels, nomades et sans email sont ignorés
- Service optionnel : si les variables d'environnement ne sont pas configurées, la synchronisation est désactivée silencieusement