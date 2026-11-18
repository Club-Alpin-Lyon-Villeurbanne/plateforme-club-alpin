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
Personnes qui valident une note de frais hors sortie sur le site, avant la trésorerie, qui la valide ensuite dans compta-club : les responsables de la commission de la note (rôle `responsable-commission`), sauf le demandeur et ceux dont le compte est supprimé ou verrouillé, qui doivent renouveler leur adhésion ou qui n'ont pas d'adresse mail. S'il n'en reste aucun, la note va directement à la trésorerie.

## Fonctionnalités

### Notes de frais
Remboursement des frais engagés pour le club. Deux circuits :
- note de sortie : frais d'une sortie, déposés par l'encadrant sur la page de la sortie et validés par la trésorerie dans compta-club ;
- note hors sortie : formation ou achat fait pour le club, sans sortie. La note est validée par les [valideurs](#valideurs), puis par la trésorerie dans compta-club.

Voir [Notes de frais](notes-de-frais.md).

### Synchronisation des adhérents
Processus automatique qui met à jour la base de données avec les nouveaux adhérents de la FFCAM.

### Validation de publication / validation légale
Deux étapes indépendantes : la publication rend la sortie visible, la validation légale la reconnaît comme sortie officielle du club. Voir [Validation des sorties](validation-sorties.md).

### Pré-inscription
Demande d'inscription en attente de validation par l'encadrement (statut « non confirmé »). Voir [Inscriptions aux sorties](inscriptions-sorties.md).

### Jauge
Nombre de places d'une sortie (`ngensMax`), sans compter les places en liste d'attente. Voir [Inscriptions aux sorties](inscriptions-sorties.md#jauge-et-liste-dattente).

### Liste d'attente
Places supplémentaires (nombre fixé sur la sortie, `waitingSeat`) ouvertes une fois la jauge atteinte. Quand la liste d'attente est pleine, le bouton d'inscription n'est plus affiché.

### Carte découverte
Adhésion FFCAM de courte durée, avec une date de début et de fin, synchronisée par un fichier séparé.

### Filiation / affiliés
Lien familial déclaré à la FFCAM (référent familial) qui permet d'inscrire ses proches à une sortie.

### Nomade
Ancien nom des personnes ajoutées manuellement à une sortie par l'encadrement : licenciés d'un autre club (profil 3) ou personnes extérieures, par exemple un formateur (profil 4). La colonne `nomade_user` reste renseignée pour les licenciés d'un autre club, qu'elle exclut du blocage des licences expirées ; le créateur est conservé dans `nomade_parent_user`.

### À renouveler
Compte dont la licence a expiré (après la tolérance du 30 septembre) : il ne peut plus s'inscrire aux sorties.

### Payeur non reconnu
Paiement HelloAsso qui ne correspond à aucun participant de la sortie, ni par l'e-mail, ni par le prénom et le nom. Voir [Hello Asso](hello-asso.md).

### Moulinette
Nom courant des traitements automatiques (synchronisations FFCAM et Google, envois d'alertes). Voir [Tâches planifiées](taches-planifiees.md).

## Cursus FFCAM

Le cursus d'un adhérent comporte deux filières : « pratiquants » (niveaux de pratique) et « cadres » (brevets). Voir [Schéma formations et compétences](schema-formations-competences.md).

### Brevet
Diplôme fédéral de la filière « cadres », délivré par activité. Deux degrés d'initiateur : 1er degré (brevet socle de l'activité) et 2e degré (spécialité dans l'activité). Le 3e degré est le brevet d'instructeur, qui forme les initiateurs. L'extranet FFCAM affiche pour chaque brevet quatre dates : obtention, migration, recyclage, formation continue.

### Formation validée
Stage FFCAM suivi et validé par un adhérent (par exemple PSC1 ou une unité de formation « Neige et avalanches »), avec sa date et le numéro de la session.

### Niveau de pratique
Niveau d'un adhérent dans une activité, filière « pratiquants » : INITIE, puis PERFECTIONNE, et SPECIALISE dans certaines activités seulement. Exemple : « INITIE en alpinisme ». Le niveau PERFECTIONNE dans l'activité est le prérequis pour se former au brevet d'initiateur 1er degré. En escalade sur glace / dry tooling, le cursus d'initiateur demande le niveau SPECIALISE.

### Groupe de compétences (GDC)
Subdivision d'un niveau de pratique. Chaque niveau est découpé en 4 chapitres, eux-mêmes découpés en parties ; chaque partie est un groupe de compétences, repéré par niveau-chapitre.partie (par exemple INI-2.1 ou PER-3.3). Un GDC est attesté par un initiateur ou un instructeur au niveau INITIE ; aux niveaux PERFECTIONNE et SPECIALISE, par un instructeur, un professionnel agréé ou un initiateur accrédité. Quand tous les GDC d'un niveau sont validés, le niveau est obtenu.

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