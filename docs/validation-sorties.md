# Validation des sorties

## Cycle de validation

Une sortie passe par deux étapes de validation indépendantes :

1. **Validation publication** (`evt_validate`) — un responsable de commission (généralement, rôle à choisir dans la matrice des droits) approuve la sortie pour qu'elle soit visible publiquement (`STATUS_PUBLISHED_VALIDE`).
2. **Validation légale** (`evt_legal_accept` / `evt_legal_refuse`) — une personne habilitée confirme que la sortie est conforme pour être reconnue comme sortie officielle du CAF.

Ces deux étapes sont indépendantes : une sortie peut être publiée sans être validée légalement.

## Matrice des droits

Les droits `evt_validate`, `evt_legal_accept` et `evt_legal_refuse` s'administrent dans la matrice des droits (`/admin/`). Chaque droit peut être assigné à un ou plusieurs rôles, avec ou sans restriction par commission.

La page de validation légale (`/validation-des-sorties.html`) n'est accessible qu'aux utilisateurs dont au moins un rôle possède `evt_legal_accept` ou `evt_legal_refuse`.

## Comportement si aucun rôle n'a `evt_legal_accept`

Si aucun rôle n'est configuré avec le droit `evt_legal_accept` dans la matrice, les sorties sont **automatiquement validées légalement** au moment de leur publication, sans envoi d'email à l'organisateur.

Ce cas se produit typiquement lorsque le club ne souhaite pas de circuit de validation légale distinct et considère que publication vaut validation. La responsabilité légale est déléguée au rôle associé à la publication.

La vérification porte sur la configuration de la matrice (table `caf_usertype_attr`), pas sur l'existence d'utilisateurs affectés à un rôle.

## Modification d'une sortie déjà publiée

Lors de l'enregistrement d'une sortie publiée (`SortieController::edit`), la sortie **repasse en attente de validation** (`STATUS_PUBLISHED_UNSEEN`) si au moins un de ces éléments a changé :

- le nombre maximum de places (`ngensMax`) ;
- le lieu (`place`) ;
- l'activation de la billetterie HelloAsso (`hasPaymentForm`) ou son montant (`paymentAmount`) ;
- la liste des encadrants ou des initiateurs ;
- l'indicateur « sortie à l'étranger ».

Pour toute autre modification (description, horaires, matériel…), la sortie reste publiée et un e-mail de mise à jour est envoyé.

## Qui peut faire quoi sur une sortie

Les droits se règlent dans la matrice des droits ; la logique est dans `src/Security/Voter/`.

| Action | Conditions | Voter |
|---|---|---|
| Modifier | Sortie non terminée. Auteur de la sortie, ou droit `evt_validate` sur la commission, ou `evt_validate_all`. | `SortieUpdateVoter` |
| Annuler | Sortie publiée, non annulée, non terminée. Auteur ou encadrant de la sortie avec `evt_cancel_own`, ou `evt_cancel` sur la commission, ou `evt_cancel_any`. Un motif est obligatoire. | `SortieAnnulationVoter` |
| Désannuler | Sortie annulée et non terminée, mêmes droits que l'annulation. | `SortieAnnulationVoter` |
| Supprimer | Sortie en brouillon, jamais publiée, sans note de frais. Auteur, ou `evt_delete` sur la commission. | `SortieDeleteVoter` |
| Dupliquer | Droit `evt_create` sur la commission **et** participation à la sortie d'origine (quel que soit le rôle). Deux modes : avec le groupe (participants repris) ou vide. | `DuplicateSortieVoter` |

Une sortie **terminée** ne peut plus être modifiée ni annulée, par personne.

## Annulation d'une sortie publiée

À l'annulation d'une sortie publiée, les participants inscrits (`inscrit`), inscrits manuellement (`manuel`) et bénévoles (`benevole`) sont **désinscrits**. Tous les participants, encadrement compris, reçoivent un e-mail avec le motif. L'encadrement reste rattaché à la sortie.

## Rappels

Chaque jour à 5 h 54, les responsables de commission reçoivent un rappel listant les sorties en attente de validation (`event-to-publish-reminder-cron`).
