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

## Fonctionnalités

### Notes de frais
Système permettant aux encadrants de soumettre leurs frais de sortie et à la comptabilité de les valider.

### Synchronisation des adhérents
Processus automatique qui met à jour la base de données avec les nouveaux adhérents de la FFCAM.

### Validation de publication / validation légale
Deux étapes indépendantes : la publication rend la sortie visible, la validation légale la reconnaît comme sortie officielle du club. Voir [Validation des sorties](validation-sorties.md).

### Pré-inscription
Demande d'inscription en attente de validation par l'encadrement (statut « non confirmé »). Voir [Inscriptions aux sorties](inscriptions-sorties.md).

### Liste d'attente
Places supplémentaires proposées quand une sortie est complète (`waitingSeat`).

### Carte découverte
Adhésion FFCAM de courte durée, avec une date de début et de fin, synchronisée par un fichier séparé.

### Filiation / affiliés
Lien familial déclaré à la FFCAM (référent familial) qui permet d'inscrire ses proches à une sortie.

### Nomade
Ancien nom des personnes ajoutées manuellement à une sortie par l'encadrement : licenciés d'un autre club (profil 3) ou personnes extérieures, par exemple un formateur (profil 4). La colonne `nomade_user` n'est plus utilisée ; le créateur est conservé dans `nomade_parent_user`.

### À renouveler
Compte dont la licence a expiré (après la tolérance du 30 septembre) : il ne peut plus s'inscrire aux sorties.

### Payeur non reconnu
Paiement HelloAsso dont l'e-mail ne correspond à aucun compte du site. Voir [Hello Asso](hello-asso.md).

### Moulinette
Nom courant des traitements automatiques (synchronisations FFCAM et Google, envois d'alertes). Voir [Tâches planifiées](taches-planifiees.md).

## Outils

### ClickUp
Plateforme de gestion de projet utilisée pour suivre les tickets et les tâches.

### compta-club
Application séparée (NextJS) utilisée par la comptabilité pour valider les notes de frais.

### Loxya
Plateforme externe de réservation du matériel du club.

### Metabase
Outil de tableaux de bord statistiques branché sur la base du site.

### Sentry
Outil de monitoring des erreurs en production.

### Github Actions
Système d'automatisation pour les tests et les déploiements.

### Clever Cloud
Plateforme d'hébergement utilisée pour les environnements de staging et de production. 