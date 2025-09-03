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

