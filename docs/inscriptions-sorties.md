# Inscriptions aux sorties

Logique principale : `SortieController::join`, `src/Security/Voter/UserJoinSortieVoter.php`, `src/Service/UserLicenseHelper.php`.

## Conditions pour s'inscrire

Un adhérent peut demander à s'inscrire si :

- la sortie est **publiée** (validation de publication faite) ;
- les inscriptions sont **ouvertes** (date d'ouverture dépassée) ;
- la sortie n'a **pas encore commencé** ;
- il a coché « J'ai lu les conditions… » ;
- sa licence est valide : son compte n'est pas marqué « à renouveler » (voir [Synchronisation des adhérents](synchronisation.md#validité-des-licences)).

Un adhérent peut aussi inscrire ses **affiliés** (liens de filiation FFCAM), et s'inscrire comme **bénévole**.

## Statut de l'inscription

- **Sans acceptation automatique** : l'inscription est une **pré-inscription** (statut « non confirmé »). L'encadrement la valide ou la refuse.
- **Avec acceptation automatique** (`autoAccept`) : l'inscription est validée tant que le nombre de participations validées (encadrement compris) plus les nouvelles inscriptions ne dépasse pas le nombre maximum de places. Au-delà, elle reste en pré-inscription (pas de refus automatique). Sans nombre maximum, tout est accepté.

Statuts de participation : non confirmé, validé, refusé, absent.
Rôles : inscrit, manuel, bénévole, encadrant, co-encadrant, stagiaire, bénévole d'encadrement.

## Jauge et liste d'attente

Deux plafonds distincts sont fixés sur la sortie :

- la **jauge** : le nombre de places de la sortie (`ngensMax`, recopié dans `join_max_evt` à l'enregistrement), **sans compter** la liste d'attente ;
- la **liste d'attente** : un nombre de places supplémentaires (`waitingSeat`, optionnel), ouvertes une fois la jauge atteinte. Les places restantes valent `waitingSeat` moins le nombre d'inscriptions non confirmées.

Quand la jauge est atteinte, le bouton d'inscription propose de « rejoindre la liste d'attente » tant qu'il en reste des places. Quand la liste d'attente est pleine, le bouton d'inscription n'est plus affiché, sauf pour un adhérent déjà inscrit qui a des affiliés : il peut encore les pré-inscrire. Ce plafond n'existe qu'à l'affichage de la page : `SortieController::join` ne le vérifie pas.

## Désinscription

Un participant peut se désinscrire lui-même, et un référent peut désinscrire ses affiliés (droit `evt_unjoin`, `ParticipantAnnulationVoter`, `SortieController::removeParticipant`).

## Qui est prévenu par e-mail

| Événement | E-mail | Destinataires |
|---|---|---|
| Demande d'inscription | `sortie-demande-inscription` | l'**organisateur** de la sortie (son auteur) **et** l'encadrement validé, selon le réglage de chacun dans « Mes alertes » (voir ci-dessous) |
| Désinscription d'un participant validé ou non confirmé | `sortie-desinscription` | l'**organisateur** de la sortie **et** l'encadrement validé, sauf la personne qui vient d'annuler |

« Encadrement validé » = participations validées avec le rôle encadrant, co-encadrant ou stagiaire (`Evt::getEncadrants()` par défaut). Le **bénévole d'encadrement** n'en fait pas partie (il est seulement dans `EventParticipation::ROLES_ENCADREMENT_ETENDU`). Ces destinataires sont donnés par `Evt::getOrganisateurEtEncadrants()`, qui ne compte qu'une fois un organisateur qui encadre aussi.

### Réglage « Recevoir un e-mail pour chaque demande d'inscription »

Chaque personne choisit dans **Mes alertes** (`/profil/alertes`, colonne `caf_user.alert_toutes_demandes_inscription`) si elle reçoit un e-mail pour chaque demande d'inscription aux sorties qu'elle organise ou encadre. Le réglage vaut pour toutes ses sorties.

- **Coché** (par défaut, et pour tous les comptes existants) : un e-mail par demande, comme avant.
- **Décoché** : un e-mail seulement pour les demandes accompagnées d'un message de l'adhérent.

Un e-mail dont la demande contient un message a un objet qui commence par `[Message]`, quel que soit le réglage. Le réglage ne change rien aux e-mails de désinscription.

Sur la fiche d'une sortie à venir et non annulée, l'organisateur et l'encadrement voient une cloche à côté du menu « Actions » : 🔔 s'ils reçoivent chaque demande, 🔕 sinon. Son infobulle rappelle le réglage et la cloche mène à « Mes alertes ». Le pied de l'e-mail de demande d'inscription y renvoie aussi.

## Rappel de licence

Chaque jour à 7 h 30, pour les sorties qui commencent dans **7 jours**, les participants dont la licence ne couvre pas la sortie reçoivent un rappel, et l'organisateur et l'encadrement sont prévenus (`license-renew-reminder-cron`).

## Bilan carbone

Le bilan carbone de la sortie est calculé à partir de la distance (service d'itinéraire OSRM), du mode de transport, du nombre de véhicules et du **nombre maximum de places**. Il est recalculé uniquement à la création ou à la modification de la sortie, pas à chaque inscription. Pas de bilan pour une sortie à l'étranger. Si le calcul de distance échoue lors d'une modification, l'ancienne distance est conservée.
