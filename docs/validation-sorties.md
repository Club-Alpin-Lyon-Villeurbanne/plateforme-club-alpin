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
