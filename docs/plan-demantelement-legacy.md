# Plan de démantèlement du legacy

Analyse détaillée (juin 2026) du code legacy restant et plan de migration vers Symfony, basé sur la lecture du code. Complète l'[audit technique](audit-2026-06.md).

## Mécanisme actuel

Toute URL `*.html` non interceptée par une route Symfony tombe sur les catch-all `legacy_p1`..`legacy_p4` (`src/Controller/LegacyController.php`), qui exécutent `legacy/index.php`. Celui-ci lit la table `caf_page` (existence + droits), puis `require legacy/pages/{slug}.php`. **Une page legacy n'est accessible que si une ligne existe dans `caf_page`.** Le pont base de données est `src/Utils/MysqliHandler.php` (mysqli brut, parallèle à Doctrine), exposé via l'alias `legacy_mysqli_handler` et le helper `App\Legacy\LegacyContainer`.

`src/Legacy/LegacyRouteLoader.php` enregistre en plus chaque `.php` du dossier `legacy/` (hors `scripts/`, `config/`...) comme route de priorité `-10`.

## État des lieux : 6 854 LOC / 69 fichiers

La migration des gros morceaux structurants (droits, articles, users, **affichage** des commissions et pages) est **déjà faite**. Les entités Doctrine cibles existent (`Page`, `ContentHtml`, `ContentInline`, `ContentInlineGroup`, `Commission`, `Comment`, `Partenaire`, `LogAdmin`). Le reliquat est borné et essentiellement constitué de **back-offices d'administration**.

---

## Découvertes clés

- **Le bloc FTP est résiduel** : photos profil, images articles, logos partenaires sont déjà migrés vers VichUploader / `MediaUploadController` (`/upload-image`, `/upload-file`) ou un upload maison (partenaires). Seul point d'entrée vivant : l'iframe « tiroir » de `editElt.php`. → Décommissionnement, pas rebuild.
- **`lpfibr.php` + `valums/admin-upload.php` = code mort** (~430 LOC) : pointent vers l'ancien TinyMCE, remplacé par CKEditor 5.
- **L'entité `Groupe` (`caf_groupe`) est morte** : orpheline, remplacée par le référentiel FFCAM ; `Evt::$groupe` annoté « plus utilisé ». À supprimer.
- **Bugs à ne pas reproduire** : `pages_reorder.php` écrit dans `caf_pdt` au lieu de `caf_page` (réordonnancement cassé aujourd'hui) ; `operations.page_new.php` écrit des colonnes inexistantes (mort) ; `traductions_save.php` sans appelant (mort).
- **`profil.php` est mort** : `require profil-{$p2}.php` alors qu'aucun `profil-*.php` n'existe ; hors menu.

## Risque dual-write (MysqliHandler)

`MysqliHandler` écrit en SQL brut sur 7 tables **aussi gérées par Doctrine** → risque d'identity map périmée si lecture Doctrine + écriture legacy dans la même requête :

`caf_commission`, `caf_content_inline`, `caf_content_inline_group`, `caf_comment`, `caf_article`, `caf_page`, `caf_content_html`.

**Règle d'ordre : supprimer le pont DB *après* avoir migré les écritures de chaque table, jamais avant.**

---

## Plan par phases

### Phase 0 — Gains immédiats (~0,5 j, risque quasi nul)

À faire en premier : valide la mécanique de suppression.

1. Supprimer `legacy/includes/recherche.php` (doublon de `templates/right-column.html.twig`, 0 référence). Test : le bloc recherche s'affiche toujours sur une page commission.
2. Supprimer `legacy/pages/profil.php` + retirer la ligne `caf_page` id 13. Test : `/profil/mon-compte` OK, `/profil.html` renvoie une erreur propre.
3. Supprimer les **9 alias `legacy_*` morts** de `config/services.yaml` : `legacy_csrf_token_manager`, `legacy_message_bus`, `legacy_fs`, `legacy_hasher_factory`, `legacy_logger`, `legacy_mailer`, `legacy_user_repository`, `legacy_member_merger`, `legacy_user_right_service`. Test : `cache:clear` + `debug:container` + smoke test.
4. Supprimer le code mort CMS : `operations.page_new.php`, retirer `traductions_save` de la whitelist `index.php`.

**Quick wins sécurité (en parallèle, indépendants)** : `public/ftp/.htaccess` (`engine off` + deny `.php/.phar/.phtml`), confiner `$_GET['dossier']` via `realpath()` dans les endpoints upload, passer les `operation=delete` FTP en POST + CSRF.

### Phase 1 — Petites migrations indépendantes (~4–6 j)

5. **admin-log** (`pages/admin-log.php` + `includes/admin-log.php`) → action Symfony + DataTable (entité `LogAdmin` existe) ; repointer `menuAdmin.php:16`. Fusionner les deux fichiers quasi identiques.
6. **Liste partenaires** (`admin-partenaires.php`) → le CRUD est déjà migré (`PartnerController`), seule la liste reste ; ajouter une action liste + template ; retirer de la whitelist (`menuAdmin.php`, `AdminController.php:77`).
7. **comment-del** + `operations.comment_hide` → action Symfony soft-delete (status=2) + confirmation ; repointer `templates/components/comment.html.twig:9`.
8. **commission-consulter** → fusionner avec `participants_by_commission` (`/encadrement-par-commission`, doublon partiel) ; repointer `gestion-des-commissions.php`.
9. **stats.php** : la vue `commissions` est un doublon obsolète ; porter la vue `nbvues` (vues/commentaires par article) si encore utile, sinon supprimer ; retirer la ligne `caf_page` id 39.

### Phase 2 — Commissions admin (~8–10 j)

Détail dans le dossier ci-dessous. Migre les écritures `caf_commission` (création, édition titre/images, visibilité, réordonnancement) + gestion des images `public/ftp/commission/{id}/`. Supprime `caf_groupe` (mort). À la disparition des pages commission, `right-type-agenda.php` devient orphelin → supprimer.

Liens menu à rebrancher : `templates/header.html.twig:336,344` (`/gestion-des-commissions.html`, `/commission-add.html`).

### Phase 3 — CMS contenu/pages (~14–21 j, le cœur)

Détail dans le dossier ci-dessous. Migre l'éditeur de blocs HTML versionnés (`editElt`), les contenus inline (`admin-contenus`), le CRUD des pages libres (`admin-pages-libres*`). Branche l'insertion d'image CKEditor sur `/upload-image` existant → permet de **supprimer tout le bloc FTP** (iframe `editElt:143` + `admin/ftp*.php` + `valums/ftp.php` + `ftp.js`).

### Phase 4 — Menu admin Symfony

Recréer le menu admin (`legacy/admin/menuAdmin.php`, construit dynamiquement depuis `caf_page.menuadmin_page`) côté Symfony **avant** de retirer `index.php`, sinon les gestionnaires perdent leur navigation.

### Phase 5 — Coupe du pont (~3–4 j)

Quand plus aucune page legacy n'est servie : supprimer `index.php`, `includer.php`, `includes/generic/*` (header, header-admin, footer, top), `bigfond.php`, `lbxMsg.php`, `404.php`, `app/*`, puis `LegacyController`, `LegacyRouteLoader`, `MysqliHandler`, `LegacyContainer` et les alias restants. Test avant chaque coupe : `debug:router` (aucun consommateur) + parcours fonctionnel + suite de tests.

---

## Estimation globale

| Phase | Charge (j-h) |
|---|---|
| 0 — Gains immédiats | 0,5 |
| 1 — Petites migrations | 4–6 |
| 2 — Commissions admin | 8–10 |
| 3 — CMS contenu (cœur) | 14–21 |
| 4 + 5 — Menu admin + coupe du pont | 3–4 |
| **Total** | **≈ 30–43 j-h** |

≈ **6 à 8,5 semaines à temps plein**. En contexte bénévole au rythme actuel : **~4 à 8 mois calendaires**. Le cœur (CMS + commissions) concentre ~75 % de la charge.

---

## Annexe — Dossiers détaillés par bloc

### Bloc CMS contenu/pages (~2 230 LOC)

**Sous-fonctions** : (A) éditeur de blocs HTML inline versionnés `editElt.php` (CKEditor 5, versionnement par INSERT dans `caf_content_html` + purge `CONTENT_MAX_VERSIONS`), (B) contenus textuels inline `admin-contenus.php` (UPDATE en place sur `caf_content_inline`, métas/menus), (C) arborescence des pages fixes (drag&drop, marqué « en développement »), (D) pages libres CMS `admin-pages-libres*` (CRUD complet, contenu = bloc `main-pagelibre-{id}`).

**Tables** : `caf_content_html` (versionné), `caf_content_inline`, `caf_content_inline_group`, `caf_page`. Toutes MyISAM (pas de FK ni transaction → suppressions en cascade manuelles).

**Déjà migré** : affichage (`CmsPageController::view`, `App\Legacy\ContentHtml::getEasyInclude` + filtre Twig `easy_include`, `CmsContentService`). Entités complètes à 100 %.

**Manque** : tout le back-office (contrôleurs, forms, repositories d'écriture, templates admin, JS d'édition inline).

**Front legacy lourd** : communication par iframe + double encodage HTML-entity (héritage ISO-8859-1, ~250 LOC de mappings dans `fonctionsAdmin.js`) → disparaît en passant à UTF-8/JsonResponse, mais vérifier la non-régression des accents/caractères spéciaux. CKEditor 5 déjà en place ailleurs (articles, sorties) → réutilisable.

**Risques** : perte de l'historique éditorial si on UPDATE au lieu d'INSERT (reproduire fidèlement le versionnement) ; régression d'encodage ; conventions de code implicites (`main-pagelibre-{id}`, `meta-title-{code}`, `mainmenu-{code}`) à centraliser dans des constantes ; `parent_page=54` hardcodé à paramétrer.

**Ne pas migrer** : `page_new` (mort), `traductions_save` (mort), `pages_reorder` tel quel (cassé, cible `caf_pdt`), arborescence pages fixes (faible valeur, « en développement »).

### Bloc commissions admin (~1 300 LOC)

**Écrans** : liste/gestion (`gestion-des-commissions.php`, drag&drop réordonnancement), création (`commission-add.php`, upload bigfond + 3 pictos), édition (`commission-edit.php`, titre + groupes de niveaux), consultation encadrants (`commission-consulter.php`), activer/désactiver (`commission-edit-vis.php`).

**Tables** : `caf_commission` (id, ordre, vis, code, title) — 100 % couvert par l'entité `Commission`. `caf_groupe` → **mort, à supprimer**. Lecture `caf_user`/`caf_usertype`/`caf_user_attr` (déjà modélisés).

**Déjà migré** : `commission_index`, `commission_configuration`, `commission_brevet`, pages publiques (`commission_homepage`, `commission_agenda`), `responsables`.

**Manque** : CRUD admin (création, édition titre/images, visibilité, réordonnancement), uploads images.

**Images** : `public/ftp/commission/{id}/{bigfond.jpg,picto.png,picto-light.png,picto-dark.png}`, dossier 0 = défauts. Couplage filesystem dur, cache-busting `?ac=time()`.

**Risques** : ne jamais permettre l'édition du `code` (référencé par convention dans `caf_evt`, `caf_user_attr LIKE 'commission:code'`) ; impact de `vis=false` sur agenda/articles à vérifier ; injection SQL dans `operations.groupe_edit.php` (disparaît avec la suppression de `caf_groupe`).

**Décision produit** : `commission-consulter` partiellement redondant avec `participants_by_commission` — clarifier si une fiche par commission est nécessaire.

### Bloc FTP / upload (~1 370 LOC)

**Nature** : explorateur de fichiers web sur `public/ftp/` (filesystem local, pas de FTP distant). Monté en iframe dans `editElt.php`.

**Déjà migré (90 %)** : profils (`/upload-image` + Vich), articles (idem), partenaires (`PartnerController`, upload maison + CSRF). Abstraction moderne : VichUploader (`config/packages/vich_uploader.yaml`, entité `MediaUpload`, `MediaUploadController`).

**Code mort** : `lpfibr.php` (338) + `valums/admin-upload.php` (92) — ancien file browser TinyMCE.

**Failles** (tant que le bloc vit) : path traversal (`admin-upload.php:10`, `ftp.php:28` garde-fou `..` désactivé), CSRF GET (`ftp-deletefile.php:51`, `ftp-deletedir.php:62` suppression récursive), pas de restriction d'exécution PHP sous `public/ftp/`, validation par extension seule (pas de MIME).

**Recommandation : décommissionner** (option c, 0,5–2 j). Brancher l'insertion d'image des blocs CKEditor sur `/upload-image` (existant), retirer l'iframe et les `admin/ftp*.php`. **Ne pas toucher au contenu de `public/ftp/`** (URL `/ftp/...` en dur dans les contenus historiques + flux RSS).
