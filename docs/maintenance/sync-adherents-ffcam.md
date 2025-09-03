Synchronisation des adhérents avec la FFCAM
===========================================

Objectif
--------
- Décrire le fonctionnement, la mise en place et l’exploitation de la synchro des adhérents FFCAM (import/mise à jour des profils, droits, statuts, licences).

Périmètre
---------
- Source: fichiers/flux FFCAM (format, fréquence, canal).
- Cible: entités `User` et attributs associés (licence, statut légal, filiation, etc.).
- Non couvert: historique complet des modifications (audit approfondi) — à voir selon besoin.

Pré‑requis
----------
- Accès au flux FFCAM (SFTP/API/fichier), chemin et credentials sécurisés via variables d’environnement (`FFCAM_*`).
- Environnement: `APP_ENV=prod`, `APP_DEBUG=0`. Logging et Sentry configurés.

Plan de fonctionnement
----------------------
1) Récupération du flux (horaire/quotidienne) et dépôt en dossier d’atterrissage sécurisé.
2) Validation du schéma (entêtes/colonnes attendues, encodage/CSV/UTF‑8).
3) Traitement incrémental: matching sur identifiant FFCAM/licence/email, création MAJ des champs, désactivation des comptes absents si applicable.
4) Journalisation: logs dédiés + événement Sentry en cas d’échec bloquant.

Mapping des champs (exemple à ajuster)
--------------------------------------
- `numero_licence` → `User.licence` (unique)
- `email` → `User.email` (si non vide et valide)
- `prenom`/`nom` → `User.firstname`/`User.lastname`
- `date_fin_licence` → statut licence (valide/expirée)
- `statut` → `User.statusLegal` (majeur/mineur/responsable…)
- `filiation` → liens parent/enfant (optionnel)

Sécurité et conformité
----------------------
- Données personnelles: ne pas persister le flux brut en clair; purger les fichiers après traitement.
- Tracer les accès au répertoire d’atterrissage.
- Sentry: `send_default_pii=false`; attacher uniquement des IDs techniques dans les événements.

Exécution
---------
- Manuelle: `bin/console app:ffcam:sync [--dry-run] [--limit=...] [--since=...]`
- CRON/Planification: exécution quotidienne (hors pics d’usage), monitoring via logs + Sentry.

Stratégie d’échec
-----------------
- Fichier invalide → ignorer le run, alerte Sentry (niveau warning/error) + mail Slack (si configuré).
- Échec partiel (N erreurs) → continuer, reporter les lignes KO dans un CSV `var/log/ffcam-sync-failures-YYYYMMDD.csv`.
- Rollback: traitement idempotent par ligne, pas de transaction globale lourde.

Vérifications & QA
------------------
- Comptes échantillons: licences qui expirent, nouveau membre, changement d’email, filiation.
- Tests de non‑régression: pas de création de doublons (clés licence/email).
- Post‑run: volume d’updates vs. créations vs. suppressions contrôlé.

Dépannage (troubleshooting)
---------------------------
- « Fichier ignoré »: vérifier l’encodage/UTF‑8 et séparateur.
- « Mappage introuvable »: ajouter la colonne dans le mapping de l’import.
- « Doublons de licence »: corriger côté source ou appliquer une règle de dédoublonnage contrôlée.

Annexes
-------
- Exemple de structure CSV/entêtes attendues.
- Exemple de sortie dry‑run.

Logique métier: création, mise à jour, fusion, suppression
---------------------------------------------------------

Vue d’ensemble du traitement effectué par `FfcamFileSync` + `FfcamSynchronizer`.

- Fichier manquant
  - Si le fichier attendu n’est pas présent: on journalise un warning et on applique quand même deux opérations de maintenance:
    - `blockExpiredAccounts()`: marque certains comptes comme « à renouveler » (voir ci‑dessous).
    - `removeExpiredFiliations()`: purge des filiations trop anciennes.

- Pour chaque ligne du fichier (parsing CSV ISO‑8859‑1 → UTF‑8)
  - Construction d’un objet `User` « parsé » (non persisté) à partir des colonnes (nom/prénom, anniversaire, adresse, tel, numéro de licence `cafnum`, filiation `cafnumParent`, etc.).
  - Recherche d’un utilisateur existant par numéro de licence (`findOneByLicenseNumber`).

1) Création (aucun utilisateur trouvé par `cafnum` et aucun doublon)
   - On persiste un nouvel utilisateur avec:
     - `tsInsert = now()`, `valid = false` (le compte n’est pas automatiquement « activé » côté site),
     - `nickname` généré (Nom/Prénom),
     - `doitRenouveler` et `alerteRenouveler` selon la licence (cf. parsing),
     - le reste des champs issus du fichier.

2) Mise à jour (utilisateur trouvé par `cafnum`)
   - Champs mis à jour: prénom/nom, anniversaire, civilité, filiation (`cafnumParent`), téléphones, adresse/CP/ville, pseudo, alertes renouvellement, date d’adhésion.
   - Fenêtre de tolérance (renouvellement):
     - entre le 25/08 et le 31/10 (inclus), on n’active PAS le flag `doitRenouveler` même si la licence côté FFCAM apparaît expirée; hors de cette fenêtre, on applique la valeur issue du fichier.
   - Marqueurs internes: `tsUpdate = now()`, `manuel = false`, `nomade = false`.

3) Fusion (nouvelle licence pour un membre connu)
   - Cas pris en charge quand FFCAM attribue un nouveau numéro de licence à un adhérent existant.
   - Détection d’un « doublon » via `findDuplicateUser(lastname, firstname, birthday, excludeCafnum)` avec conditions:
     - même nom/prénom/date de naissance,
     - `doitRenouveler = true` sur l’ancien compte (compte expiré/inactif),
     - non supprimé (`isDeleted = false`).
   - Action `mergeNewMember(oldCafNum, parsedUser)`:
     - On conserve l’enregistrement existant (historique, relations),
     - On met à jour ses champs et on remplace son `cafnum` par le nouveau,
     - Pas de suppression dure: on réutilise l’ID existant.
   - Note: la méthode `mergeExistingMembers($old, $new)` (non utilisée dans le run nocturne) permet aussi de fusionner deux comptes déjà persistés (le « nouveau » est alors marqué `isDeleted = true` et neutralisé).

4) Suppression / désactivation
   - Pas de suppression physique lors du run nocturne.
   - Après traitement, deux opérations de maintenance sont toujours exécutées:
     - `blockExpiredAccounts()`: positionne `doitRenouveler = true` sur les comptes (hors admin=1, non nomades, non manuels) dont:
       - `dateAdhesion <= 31/08` de l’année précédente, OU
       - `tsUpdate <= now() - 10 jours`.
     - `removeExpiredFiliations()`: met `cafnumParent = NULL` quand `tsUpdate < now() - 200 jours`.
   - Effet métier: comptes « à renouveler » (blocage d’accès/inscriptions selon vos règles), mais pas d’effacement de données.

Archiver et journaliser
-----------------------
- Si au moins 1 création ou 1 mise à jour: le fichier d’entrée est archivé en ZIP (`<nom>_YYYY-MM-DD.zip`).
- Un log applicatif est écrit dans `caf_log_admin` (INSERT/UPDATE + nom de fichier); le compteur « merged » n’est pas encore inclus dans ce log applicatif.

Points d’attention / améliorations possibles
-------------------------------------------
- « merged » dans les statistiques: l’ajouter aussi dans le log `caf_log_admin` pour lisibilité.
- Détection de doublons: élargir la heuristique (ex: email, normalisation nom/prénom avec accents) pour limiter les cas non fusionnés.
- Fenêtre de tolérance: exposer les bornes via variables d’environnement pour ajustement annuel sans code.
- Mode « dry‑run »: option console `--dry-run` pour simuler un import et ne pas persister (échantillons + CSV anomalies).
- Traçabilité: sortie CSV des lignes KO et des fusions réalisées (`var/log/ffcam-sync-*.csv`).
