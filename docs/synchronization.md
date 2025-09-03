Synchronisation FFCAM — adhérents
=================================

Contexte & infrastructure
-------------------------
- Source: fichier déposé chaque nuit par l’extranet FFCAM sur le filesystem Clever Cloud (FTP activé).
- Déclenchement: cron Clever Cloud qui lance `ffcam-file-sync` (voir `src/Command/FfcamFileSync.php`).
- Traitement: `FfcamSynchronizer` parse le fichier puis crée/met à jour/fusionne les profils (`User`).

Pré‑requis
----------
- Accès FS: chemin du fichier via env (`FFCAM_FILE_PATH`), droits de lecture.
- Prod: `APP_ENV=prod`, `APP_DEBUG=0`. Logs + Sentry actifs.

Fonctionnement (résumé)
-----------------------
1) Parsing encodage ISO‑8859‑1 → UTF‑8, CSV `;`.
2) Recherche existant par numéro CAF (licence). Si trouvé → mise à jour.
3) Sinon, détection de doublon (même nom + prénom + jour de naissance, CAF différent, non supprimé) → fusion dans l’ancien profil (remplacement du `cafnum`).
4) Sinon → création d’un nouveau profil (non activé).
5) Archiver le fichier (ZIP) si insert/update > 0 + log d’admin.
6) Maintenance: marquer les comptes expirés « à renouveler » et purger des filiations trop anciennes.

Mapping (exemples utiles)
-------------------------
- `cafnum` → `User.cafnum` (unique)
- `nom`/`prenom` → `User.lastname`/`User.firstname`
- `dateNaissance` → `User.birthday` (timestamp)
- `adresse/CP/ville`, `tel/tel2`, `civ`, `cafnumParent`
- Email: non importé depuis FFCAM (la valeur du fichier n’écrase pas le compte local).

Logique métier: création, mise à jour, fusion, suppression
---------------------------------------------------------
- Création: si aucun `cafnum` existant et aucun doublon → persistance (tsInsert, valid=false, nickname généré, flags renouvellement selon fichier).
- Mise à jour: si `cafnum` existant → champs d’identité et coordonnées, flags (avec fenêtre de tolérance renouvellement fin août → fin octobre), `tsUpdate`.
- Fusion (nouvelle licence pour un membre connu): ancien profil non supprimé avec même nom/prénom et date de naissance (au jour) → remplacement du `cafnum` et mise à jour des champs; l’ID historique est conservé.
- Suppression: pas de delete dur; opérations de maintenance marquent « à renouveler » et annulent des filiations trop anciennes.

Exécution
---------
- Cron: via back‑office Clever Cloud (quotidien, nuit).
- Manuel: `bin/console ffcam-file-sync` (optionnel)

Monitoring & échec
-------------------
- Logs applicatifs (`caf_log_admin`) et Sentry (exceptions parsing/DB).
- En cas d’échec partiel: poursuivre, reporter les lignes en erreur (CSV) et alerter.

FAQ / cas courants
------------------
- Délai attendu: en général T+1 (parfois T+2 si l’export FFCAM est en retard).
- Email absent: des comptes actifs peuvent ne pas avoir d’email; requête utile:
  `SELECT COUNT(*) FROM caf_user WHERE is_deleted=0 AND valid_user=1 AND doit_renouveler_user=0 AND nomade_user=0 AND (email_user IS NULL OR TRIM(email_user)='');`
- Pas de doublon visible par recherche email: normal si le nouveau compte (potentiel) a un email vide; vérifier par `cafnum`.

Évolutions récentes
-------------------
- Détection de doublon assouplie: 
  - comparaison « au jour » de la date de naissance (00:00→23:59:59),
  - suppression du filtre « à renouveler » sur l’ancien profil (on garde `isDeleted=false` + `cafnum` différent).

