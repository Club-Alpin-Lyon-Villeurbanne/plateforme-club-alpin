# Notes de Frais

L'application gère deux circuits de notes de frais :
- les notes de sortie, rattachées à une sortie : saisies sur le site, validées et comptabilisées par la trésorerie dans compta-club (sections suivantes) ;
- les [notes hors sortie](#notes-hors-sortie), pour une formation ou un achat fait pour le club : validées sur le site par le responsable de la commission, puis par la trésorerie dans compta-club.

## Soumission des Notes de Frais

Interface VueJS disponible dans la page de chaque sortie :
- Template Twig pour l'envoi du récapitulatif
- API pour récupérer les informations

### Configuration des Taux

Les taux d'indemnités kilométriques sont configurés à deux endroits :
1. `assets/expense-report-form/config/expense-report.json` (client)
2. `config/services.yaml` (server)

⚠️ En cas de modification des taux, mettre à jour les deux fichiers.

## Validation des Notes de Frais

Interface distincte développée en NextJS : [compta-club](https://github.com/Club-Alpin-Lyon-Villeurbanne/compta-club). La trésorerie y valide, refuse et comptabilise toutes les notes, de sortie comme hors sortie, et télécharge leur PDF. Elle passe par l'API de la plateforme.

Les taux d'indemnités kilométriques sont également configurés dans :
https://github.com/Club-Alpin-Lyon-Villeurbanne/compta-club/blob/main/app/config.ts


## Notes hors sortie

Une note hors sortie rembourse une formation ou un achat fait pour le club, sans lien avec une sortie. Elle remplace le fichier Excel « Note de frais divers » : la demande, les validations et le choix entre remboursement et don sont désormais tracés.

### Le circuit

Deux validations, puis la comptabilisation :

1. Le demandeur fait sa demande sur le site, depuis le menu « Notes de frais » : une ligne par dépense avec son justificatif, puis le choix entre remboursement et don.
2. Le responsable de la commission valide la note ou la refuse, sur le site. Il ne voit pas le choix entre remboursement et don.
3. La trésorerie valide la note ou la refuse, dans compta-club, puis la comptabilise.

Une note sans valideur (voir les règles ci-dessous) va directement à la trésorerie dès l'envoi.

```mermaid
stateDiagram-v2
    state "Brouillon (draft)" as brouillon
    state envoi <<choice>>
    state "Chez le responsable (submitted)" as responsable
    state "Chez la trésorerie (submitted)" as tresorerie
    state "Validée par le responsable, chez la trésorerie (preapproved)" as prevalidee
    state "À corriger (rejected)" as corriger
    state "Validée par la trésorerie (approved)" as validee
    state "Comptabilisée (accounted)" as comptabilisee

    [*] --> brouillon
    brouillon --> envoi : Envoyer
    corriger --> envoi : Envoyer à nouveau
    envoi --> responsable : la note a des valideurs
    envoi --> tresorerie : sinon
    responsable --> prevalidee : le responsable valide
    responsable --> corriger : le responsable refuse, avec un message
    prevalidee --> validee : la trésorerie valide
    prevalidee --> corriger : la trésorerie refuse, avec un message
    tresorerie --> validee : la trésorerie valide
    tresorerie --> corriger : la trésorerie refuse, avec un message
    validee --> comptabilisee : la trésorerie comptabilise
    comptabilisee --> [*]
```

Règles à connaître :

- Tout adhérent connecté peut demander le remboursement d'un achat. Une formation est réservée aux adhérents qui ont un rôle d'encadrement sur la commission (responsable, encadrant, coencadrant, stagiaire ou bénévole d'encadrement).
- Les valideurs d'une note sont les responsables de sa commission, sauf le demandeur et ceux qui n'ont pas d'adresse mail. Ils sont recalculés à chaque lecture : si l'on retire son rôle à un responsable qui ne répond pas, ses notes restent aux autres responsables de la commission, ou passent à la trésorerie s'il n'en reste aucun.
- Un refus porte toujours un message. Le demandeur corrige sa note et l'envoie à nouveau : elle repasse par le responsable s'il y en a un, même quand c'est la trésorerie qui l'a refusée.
- Le demandeur ne modifie sa note qu'en brouillon ou à corriger. Celui qui décide d'une note qui a quitté son étape lit « Cette note a déjà été traitée. ».
- Le demandeur reçoit un mail à chaque étape, avec le lien « Voir ma note » : envoi, validation par le responsable, validation par la trésorerie, refus (avec le message et le lien « Corriger ma note »), comptabilisation. Chaque valideur reçoit « Note de frais à valider ». Aucun mail ne part vers la trésorerie.

### La trésorerie travaille dans compta-club

Le site n'a aucune fonction de trésorerie : ni liste, ni compteur, ni bouton. Un gestionnaire des notes de frais n'y a aucune fonction de plus qu'un autre adhérent ; ses droits sur l'API servent à compta-club.

Dans compta-club, la trésorerie :

- trouve dans « À valider » les notes qui l'attendent : celles qu'un responsable a validées, et celles qui lui arrivent directement (« Directement à la trésorerie ») ; une note encore chez son responsable n'y apparaît pas ;
- ouvre la page d'une note : validation du responsable, dépenses et justificatifs, total déclaré et total remboursable, don ou remboursement, libellé comptable ;
- approuve, refuse avec un message, comptabilise, et télécharge le PDF d'une note approuvée ou comptabilisée.

Ses décisions sont signées « la trésorerie » sur le site, dans les mails et dans compta-club. Le détail est dans la documentation de compta-club ([README](https://github.com/Club-Alpin-Lyon-Villeurbanne/compta-club#readme) et [API.md](https://github.com/Club-Alpin-Lyon-Villeurbanne/compta-club/blob/main/API.md)).

### Écrans

Le menu adhérent gagne une rubrique « Notes de frais ». L'entrée « notes de frais à valider », avec son compteur, n'apparaît qu'aux responsables de commission.

![Menu adhérent d'un responsable de commission, avec la rubrique Notes de frais](images/notes-de-frais/hors-sortie-menu.png)

Première étape de la demande : type, commission, objet et date. Sous la commission, le demandeur voit à qui sa note sera transmise : le responsable de la commission, ou la trésorerie.

![Première étape d'une demande de formation](images/notes-de-frais/hors-sortie-premiere-etape.png)

Seconde étape : une ligne par dépense avec son justificatif, le total, le choix entre remboursement et don (obligatoire pour envoyer), et un mot facultatif pour le responsable.

![Seconde étape d'une demande de formation](images/notes-de-frais/hors-sortie-seconde-etape.png)

« Mes notes de frais » : toutes les notes du demandeur, de sortie comme hors sortie, filtrables par type. Pour chacune : où elle en est (« Transmise au responsable de commission », « Transmise à la trésorerie », « Validée », « Comptabilisée », « À corriger » avec le message du refus), et les liens « Reprendre » ou « Corriger ». Le titre d'une note hors sortie ouvre sa page ; sous son montant, « dont X € remboursables » quand un plafond du club s'applique, ou « Don au club ». Une note de sortie renvoie vers la page de sa sortie.

![Page Mes notes de frais](images/notes-de-frais/hors-sortie-mes-notes.png)

« Notes de frais à valider » : les notes qui attendent le responsable dans ses commissions.

![Page Notes de frais à valider](images/notes-de-frais/hors-sortie-a-valider.png)

Page d'une note vue par le responsable : dépenses, justificatifs et mot du demandeur, puis « Refuser » ou « Valider ». Une fois la note passée, la page dit qui l'attend ou ce qui a été décidé. Le demandeur voit la même page, avec son choix (« Votre choix : remboursement, X € selon les plafonds du club. » ou « Votre choix : don au club. ») ; le responsable ne le voit pas.

![Page d'une note vue par le responsable](images/notes-de-frais/hors-sortie-detail-responsable.png)

### Configuration

- `DISPLAY_NOTES_DE_FRAIS` : à faux, le site n'affiche aucune note de frais, de sortie ou hors sortie, et les pages hors sortie répondent 404.
- `AUTHORIZED_IDS_FOR_EXPENSE_MANAGEMENT` : identifiants utilisateur des gestionnaires des notes de frais, séparés par des virgules. C'est la trésorerie, qui agit depuis compta-club.
- Rôle `responsable-commission`, donné sur une commission dans l'administration : ses titulaires valident les notes de cette commission, sauf s'ils sont supprimés, doivent renouveler leur adhésion ou n'ont pas d'adresse mail.
- `expense_report.horsSortie` dans `config/services.yaml` : délai d'envoi par type (`delaiEnvoiJours`), seuil d'avertissement (`seuilAvertissement`), taux kilométrique des formations (`formation.tauxKilometrique`) et plafonds par type (`<type>.plafonds`). Les valeurs suivent la politique de remboursement du club. Ce barème est distinct des taux des notes de sortie.

Il n'y a pas d'adresse de trésorerie à configurer.

### Pour un développeur

- Statuts : une note hors sortie utilise `preapproved` (`ExpenseReportStatusEnum::PREAPPROVED`) entre la validation du responsable et la décision de la trésorerie ; une note de sortie ne l'utilise jamais. Transitions, dans `StatusTransitionValidator` :
  - le demandeur : `draft → submitted`, `rejected → submitted` ;
  - un valideur de la note : `submitted → preapproved | rejected` ;
  - un gestionnaire (compta-club) : `preapproved → approved | rejected`, `submitted → approved | rejected` si la note n'a pas de valideur, `approved → accounted`. Un gestionnaire qui est aussi valideur agit en valideur tant que la note est `submitted`.
- Valideurs : `ValideursNoteHorsSortie` est la seule règle « qui valide en premier », avec sa variante SQL `conditionValideurs()`. Les droits (`ResponsableNoteDeFraisVoter`), la liste « à valider », le compteur du menu, la liste de compta-club, les mails et les phrases « Validée par … » en dépendent. Une liste vide : la trésorerie décide directement. Une note rattachée un jour à un code analytique plutôt qu'à une commission n'aura qu'à changer ce module et le formulaire.
- Niveau d'une décision (`responsable` ou `tresorerie`, `NiveauDeDecisionEnum`) : écrit dans l'historique des statuts au moment de la décision (`ExpenseReportStatusHistoryListener`), parce que les valideurs changent ensuite. L'API l'expose dans `derniereDecision.niveau` ; les mails et les pages le lisent sans le recalculer.
- Ce que lit compta-club :
  - `/admin/notes-de-frais` : les notes de sortie et les notes hors sortie qui attendent la trésorerie ou qu'elle a traitées, ni brouillon ni note `submitted` qui a des valideurs (`ExpenseReportExtension`). Filtres `status` (plusieurs valeurs), `type`, `refundRequired`, `user.lastname`. La recherche et la date de la sortie ne filtrent que les notes de sortie : une note hors sortie reste dans la liste de l'API quand elles sont actives (`src/Doctrine/Filter/`) ; compta-club, lui, ne demande alors que des sorties (`type=sortie`) ;
  - `GET /notes-de-frais/{id}` pour la page d'une note ;
  - les champs d'une note hors sortie, ajoutés par `NoteDeFraisNormalizer` : `valideurs`, `derniereDecision`, `validationResponsable` (validation du responsable depuis le dernier envoi), `libelleComptable` (« NOM Prénom NDF JJ/MM/AAAA objet »), `lignesAffichees`, `totalDeclare`, `totalRemboursable`, `dateEnvoi` (dernier envoi), `avertissements` (messages des contrôles). Voir la [liste](api.md#liste-des-notes-de-frais-admincomptabilité) et les [champs d'une note hors sortie](api.md#champs-dune-note-hors-sortie).
- Mails : tous ceux d'une note hors sortie partent de `NotificationsNoteHorsSortie`, une fois le changement de statut enregistré ; le listener Doctrine des notes de sortie les ignore. Ils vont au demandeur et aux valideurs.
- Contrôles : `ControlesNoteHorsSortie`. Chaque contrôle a un moment (création, chaque envoi, premier envoi, changement de commission, lecture) et un niveau : bloquant, l'API refuse ; avertissement, l'API l'expose dans `avertissements`. Ajouter un contrôle, c'est une fonction, une ligne dans `controles()` et un test API. Les pages affichent les messages de l'API : un nouveau contrôle ne les change pas.
- Plafonds : en configuration seulement. `<type>.plafonds.parNature` plafonne une nature sur chaque ligne (`portee: ligne`) ou sur son total dans la note (`portee: note`) ; `<type>.plafonds.total` plafonne le remboursable de la note.
- Montants figés : à chaque envoi, `ExpenseReportCalculator::figerNoteHorsSortie()` enregistre le total déclaré, le total remboursable et, pour une formation, le taux kilométrique. Ce que le responsable a validé reste ce que la trésorerie paie, même si le barème change ensuite. Un brouillon ou une note à corriger suit le barème en vigueur.
- Lecture de `details` : l'API refuse un montant ou une distance qui n'est pas un nombre positif d'au plus 100 000 (garde-fou technique), et deux lignes de même identifiant (`CoherenceValidator`). À la lecture, toute valeur passe par `ExpenseReportCalculator::nombre()` : un texte ou un nombre infini écrit en base compte pour 0, pour qu'aucune note, de sortie comme hors sortie, ne fasse échouer une liste.
- Écritures simultanées : la note a un verrou optimiste (colonne `version`). De deux écritures croisées (un envoi et une modification des lignes, deux décisions), la seconde reçoit un 409 « Cette note a été modifiée en même temps par une autre action. Rechargez la page. » et n'écrit rien. Le téléversement d'un justificatif verrouille la note et relit son statut avant d'écrire le fichier.
- Migrations (jouées au déploiement par `clevercloud/post_build.sh`) :
  - `Version20261011120000` : type de note, sortie facultative, commission, objet, date de la dépense, lien de la formation, mot pour le responsable, totaux figés ;
  - `Version20261011150000` : message de chaque décision dans l'historique des statuts ;
  - `Version20261011180000` : `preapproved` ajouté à la colonne `status`, une énumération MySQL (tout nouveau statut demande une migration) ;
  - `Version20261012090000` : colonne `niveau` de l'historique des statuts ;
  - `Version20261012120000` : colonne `version` de la note (verrou optimiste), à 1 pour les notes existantes.
- Code : application Vue dans `assets/notes-de-frais/`, pages dans `NoteDeFraisController`, opérations d'API sur l'entité `ExpenseReport`, services dans `src/Service/NoteDeFrais/`.
- Tests : `tests/Api/NoteHorsSortie*Test.php` et `tests/Api/NoteDeFrais*Test.php`, sur le socle `ExpenseReportApiTestCase`. En local, ces fichiers peuvent dépasser la mémoire par défaut de PHP : `php -d memory_limit=1G bin/phpunit <fichier>`. Les pages n'ont pas de test automatisé.

### Avant la mise en production

`DISPLAY_NOTES_DE_FRAIS` vaut déjà vrai à Lyon : la mise en production ouvre l'outil à Lyon. compta-club part en premier : il marche avec l'API d'aujourd'hui, qui ne lui envoie aucune note hors sortie. Dans l'ordre :

1. [ ] Vérifier la nouvelle version de compta-club contre staging, qui tourne encore sur `main`, sans les notes hors sortie (préversion Vercel ou poste local, `NEXT_PUBLIC_API_URL` sur l'API de staging) : connexion, liste « À valider », filtres, page et PDF d'une note de sortie.
2. [ ] Faire la recette de la plateforme sur staging avec de vrais rôles, mails et compta-club compris : la préversion Vercel de compta-club ou un poste local, `NEXT_PUBLIC_API_URL` sur l'API de staging. Les droits des données de démo ne reflètent pas la prod.
3. [ ] Sur une copie de la prod, lire toutes les notes existantes par la nouvelle API : `GET /admin/notes-de-frais?pagination=false&inclure_brouillons=true` en gestionnaire, puis la liste du demandeur (« Mes notes de frais », `GET /notes-de-frais?inclure_brouillons=true`). Aucune ne doit faire échouer la lecture : une seule note illisible bloquerait compta-club.
4. [ ] Vérifier que `DISPLAY_NOTES_DE_FRAIS` vaut faux à Chambéry et à Clermont.
5. [ ] Vérifier en prod les responsables de chaque commission et leur adresse mail. Une commission sans responsable joignable envoie tout à la trésorerie.
6. [ ] Décider si la formation est proposée dès maintenant : la politique de remboursement qui fixe son barème est-elle votée ?
7. [ ] Déployer compta-club, avant la plateforme, avec en une seule fois la liste, la page d'une note et le PDF des notes hors sortie : la plateforme lui enverra ces notes et le statut `preapproved`.
8. [ ] Déployer la plateforme le même jour que compta-club, et le même jour dans tous les clubs : entre les deux, les filtres « Validée par le responsable », « Formations » et « Achats » de compta-club restent vides. Vérifier ensuite que `doctrine:migrations:status` n'annonce aucune migration en attente.

Retour arrière : mieux vaut corriger en avant. Le code d'avant ne connaît pas le statut `preapproved` (une note à ce statut ferait échouer les listes) et enverrait à compta-club les notes qui attendent un responsable ; les `down()` des migrations refusent de s'exécuter dès qu'une note hors sortie ou une note `preapproved` existe.

Ne pas créer de groupe de travail pour l'instant : prévus en commissions masquées, ils ne sont pas encore proposés dans le formulaire.

Autre club : la phrase déclarée nomme le Club Alpin de Lyon et le barème est commun à tous les clubs ; à adapter avant d'activer `DISPLAY_NOTES_DE_FRAIS`.
