# Glossaire

## Termes techniques

### FFCAM
Fédération Française des Clubs Alpins et de Montagne. C'est la fédération nationale à laquelle le club est affilié.

### Staging
Environnement de test où les nouvelles fonctionnalités sont déployées avant d'être mises en production. Accessible sur [www.clubalpinlyon.top](https://www.clubalpinlyon.top).

### Production
Environnement final utilisé par les utilisateurs. Accessible sur [www.clubalpinlyon.fr](https://www.clubalpinlyon.fr).

### Cronjob
Tâche planifiée qui s'exécute automatiquement à intervalles réguliers. Dans notre cas, géré par Clever Cloud.

## Rôles et permissions

### Admin
Rôle avec tous les droits : gestion des permissions, des rôles de président ou de responsables de commission. Accès à l'administration via https://www.clubalpinlyon.fr/admin/.

### Gestionnaire de contenu
Rôle permettant de modifier les pages et les blocs de contenu du site, sans les droits d'administration complets. Identifiants de test en local : voir le [guide d'installation](installation.md#accès).

### Valideurs
Personnes qui reçoivent une note de frais hors sortie à valider : les responsables de la commission de la note (rôle `responsable-commission`), sauf le demandeur et ceux qui n'ont pas d'adresse mail. S'il n'en reste aucun, c'est la trésorerie qui valide.

## Fonctionnalités

### Notes de frais
Remboursement des frais engagés pour le club. Deux circuits :
- note de sortie : frais d'une sortie, déposés par l'encadrant sur la page de la sortie et validés par la comptabilité dans compta-club ;
- note hors sortie : formation ou achat fait pour le club, sans sortie. La note est validée par les [valideurs](#valideurs) puis transmise à la trésorerie.

Voir [Notes de frais](notes-de-frais.md).

### Synchronisation des adhérents
Processus automatique qui met à jour la base de données avec les nouveaux adhérents de la FFCAM.

## Outils

### ClickUp
Plateforme de gestion de projet utilisée pour suivre les tickets et les tâches.

### Sentry
Outil de monitoring des erreurs en production.

### Github Actions
Système d'automatisation pour les tests et les déploiements.

### Clever Cloud
Plateforme d'hébergement utilisée pour les environnements de staging et de production. 