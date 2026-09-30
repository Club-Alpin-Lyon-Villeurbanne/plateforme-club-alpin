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

## Liste d'attente

Une sortie peut avoir un nombre de places en liste d'attente (`waitingSeat`). Quand il n'y a plus de place, le bouton d'inscription propose de « rejoindre la liste d'attente » tant qu'il en reste.

## Désinscription

Un participant peut se désinscrire lui-même (droit `evt_unjoin`, `ParticipantAnnulationVoter`). Les encadrants sont prévenus par e-mail.

## Rappel de licence

Chaque jour à 7 h 30, pour les sorties qui commencent dans **7 jours**, les participants dont la licence est à renouveler reçoivent un rappel, et l'encadrement est prévenu (`license-renew-reminder-cron`).

## Bilan carbone

Le bilan carbone de la sortie est calculé à partir de la distance (service d'itinéraire OSRM) et du **nombre maximum de places**. Il est recalculé uniquement à la création ou à la modification de la sortie, pas à chaque inscription. Pas de bilan pour une sortie à l'étranger. Si le calcul de distance échoue lors d'une modification, l'ancienne distance est conservée.
