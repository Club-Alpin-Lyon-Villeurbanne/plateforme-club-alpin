# Inscriptions aux sorties

Logique principale : `SortieController::joinSortie`, `src/Security/Voter/UserJoinSortieVoter.php`, `src/Service/UserLicenseHelper.php`.

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

- la **jauge** : le nombre de places de la sortie (`join_max_evt`), **sans compter** la liste d'attente ;
- la **liste d'attente** : un nombre de places supplémentaires (`waitingSeat`, optionnel), ouvertes une fois la jauge atteinte.

Quand la jauge est atteinte, le bouton d'inscription propose de « rejoindre la liste d'attente » tant qu'il en reste des places. Quand la liste d'attente est pleine, plus aucune inscription ne doit être possible.

## Désinscription

Un participant peut se désinscrire lui-même (droit `evt_unjoin`, `ParticipantAnnulationVoter`, `SortieController::removeParticipant`).

## Qui est prévenu par e-mail

| Événement | E-mail | Destinataires |
|---|---|---|
| Demande d'inscription | `sortie-demande-inscription` | l'**organisateur** de la sortie (son auteur) **et** l'encadrement validé |
| Désinscription d'un participant validé ou non confirmé | `sortie-desinscription` | **uniquement** l'encadrement validé |

« Encadrement validé » = participations validées avec le rôle encadrant, co-encadrant ou stagiaire (`Evt::getEncadrants()` par défaut). Le **bénévole d'encadrement** n'en fait pas partie (il est seulement dans `EventParticipation::ROLES_ENCADREMENT_ETENDU`).

À noter : l'organisateur reçoit les demandes d'inscription mais pas les désinscriptions, s'il n'a pas lui-même un rôle d'encadrement sur la sortie.

## Rappel de licence

Chaque jour à 7 h 30, pour les sorties qui commencent dans **7 jours**, les participants dont la licence est à renouveler reçoivent un rappel, et l'encadrement est prévenu (`license-renew-reminder-cron`).

## Bilan carbone

Le bilan carbone de la sortie est calculé à partir de la distance (service d'itinéraire OSRM) et du **nombre maximum de places**. Il est recalculé uniquement à la création ou à la modification de la sortie, pas à chaque inscription. Pas de bilan pour une sortie à l'étranger. Si le calcul de distance échoue lors d'une modification, l'ancienne distance est conservée.
