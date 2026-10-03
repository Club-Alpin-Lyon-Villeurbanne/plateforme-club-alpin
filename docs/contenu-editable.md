# Contenu éditable sans dev

Ce document recense ce qui se modifie **depuis l'interface du site**, sans intervention du dev (liste A), et les textes qui, bien qu'ils ressemblent à du contenu, sont **écrits dans le code** (liste D). Il sert à orienter une demande vers l'équipe contenu ou vers le dev ; l'agent de tri des demandes entrantes (ClickUp) s'y réfère.

> **À tenir à jour** dans la PR qui ajoute, retire ou déplace un texte éditable ou un texte codé en dur : `templates/`, `src/Form/`, `legacy/`, `assets/`, `templates/email/`, ou un nouvel appel à `easy_include`.
>
> État établi à partir du commit `57d8bf6a` (13/09/2026).

## Principe

Un élément est **éditable sans dev** s'il se modifie **depuis l'interface du site** (rôle *Gestionnaire contenu* `ROLE_CONTENT_MANAGER`, admin, ou droits de commission ou d'article). Un texte ou un lien **écrit dans le code** (templates Twig, formulaires PHP, composants Vue, e-mails) demande une intervention du dev.

## Liste A – éditable sans dev (équipe contenu)

| # | Élément | Comment on le reconnaît dans une demande | Où ça se modifie | Dans le code |
|---|---|---|---|---|
| A1 | **Pages libres** : guide de l'encadrement, guide des responsables de commission, que faire en cas d'accident, organisation des sorties, tutos vidéos, secourisme PSC, pages d'activité (VTT…), tarifs / adhésion… | URL `/pages/<nom>.html` ; « la page X », « le guide », ancre ou table des matières cassée, lien mort dans une page | Admin > Pages libres | `caf_page` + bloc `main-pagelibre-<id>` (`CmsPageController`, `legacy/pages/admin-pages-libres*.php`) |
| A2 | **Menus de navigation principaux** (les 4 menus déroulants du bandeau public) | « menu », « lien dans le menu … » (ex. comptes rendus CoDir) | Bouton « Modifier » sur le menu | `easy_include('nav-menu-1'…'nav-menu-4')` dans `header.html.twig` |
| A3 | **Blocs de texte intégrés aux pages du site** | Texte d'introduction ou d'explication sur : local du club, matériel, minibus, mot de passe perdu, signalement, nomades, profil (infos, statuts, filiation, e-mail secondaire, coordonnées FFCAM), fiche de sortie (adresse, contacts spécifiques), formalités d'inscription, alertes licence, alerte bénévoles, statut légal de sortie, méthodologie du bilan carbone, gestion et validation des sorties et des articles, compétences (staff, profil complet, sortie), pied de page, colonne de droite, création de compte, page 404 | Bouton « Modifier » sur le bloc | `easy_include('<code>')` → table `caf_content_html` (codes en annexe) |
| A4 | **Présentation d'une commission** (colonne de droite de la page d'accueil de la commission) | « présentation de la commission X » | Bouton « Modifier » sur le bloc | `easy_include('presentation-<code>')` |
| A5 | **Nom, visuels et pictos d'une commission** | « renommer la commission », « changer l'image / le picto » | Gestion des commissions (droit `comm_edit`) | `legacy/pages/commission-edit.php` |
| A6 | **Partenaires / avantages adhérents** (privés et publics) | « ajouter / modifier un partenaire », logo, remise, tableau des avantages | Admin > Partenaires | table `caf_partenaires` (`PartnerController`, `admin-partenaires`) |
| A7 | **Textes courts du site** : titres et descriptions des pages pour les moteurs de recherche, libellés « accueil », « contact », titre du logo… | « titre de la page », « description Google » | Admin > Contenus | table `caf_content_inline` (`admin-contenus`) |
| A8 | **Fichiers déposés** (PDF des statuts, règlement intérieur, fiche d'inscription, documents téléchargeables) | « remplacer le PDF », « mettre la nouvelle version du document » | Admin > gestionnaire de fichiers, ou upload dans l'éditeur | `legacy/admin/ftp*.php`, `MediaUploadController` |
| A9 | **Articles** (texte, mise en forme, commission de rattachement) | URL `/article/…`, « revoir l'article », « déplacer des articles vers la commission X » | Édition de l'article (auteur ou validateur) | `ArticleType` (champ commission modifiable) |

## Liste D – codé en dur (dev : bug ou évolution)

| # | Élément | Exemple de demande | Dans le code |
|---|---|---|---|
| D1 | **Menu personnel de l'adhérent connecté** (rubriques Sorties / Outils / Articles / Adhérents / Commissions / Statistiques), y compris les liens vers le guide de l'encadrement, le guide des responsables, les tutos vidéos, le Drive partagé | « Ajout de liens dans Mes Outils », « nouvelles pages dans le menu vert » | `templates/header.html.twig` (l. ~135-380) |
| D2 | **Libellés et aides des formulaires** : création ou modification de sortie et d'article (dont l'encart « ligne éditoriale »), note de frais (« total remboursable »…) | « Changer le lien ligne éditoriale », « reformulation du formulaire de note de frais » | `src/Form/EventType.php`, `src/Form/ArticleType.php`, `assets/expense-report-form/` |
| D3 | **Listes de matériel par défaut des sorties** (Rando raquettes, Randonnée montagne…) | « Mise à jour de la liste matériel Rando raquettes » | `src/Form/EventType.php` (~l. 600-630) |
| D4 | **E-mails automatiques** (confirmations, notifications, notes de frais) | « le mail de confirmation dit… » | `templates/email/` |
| D5 | **Comportement de l'éditeur de texte** (iframes, ouverture des liens dans un nouvel onglet, markdown, images collées) | « l'éditeur ne garde pas… » | code front / sanitizer |
| D6 | **Mise en page, ergonomie, arborescence, structure des pages** (couleurs des commissions, page contact repensée…) | « mieux identifier les commissions », « réflexion sur la page contact » | templates / CSS |

## Annexe – codes des blocs éditables (`easy_include`)

`adresse-fiche-sortie`, `alerte-benevoles`, `alerte-licence-obsolete`, `alerte-licence-renouveler`, `bloc-partenaires`, `commission-staff-skills`, `commission_config_fields`, `complement-contacts-specifiques-fiche`, `event-skills`, `explication-nomades`, `formalites-gestion-des-inscrits`, `formalites-gestion-des-inscrits-evt-passe`, `formalites-inscription`, `full-profile-skills`, `gestion-des-articles-main`, `gestion-des-sorties-main`, `validation-des-sorties-main`, `info-inscription-licence-obsolete`, `info-inscription-non-connecte`, `info-inscription-passee`, `infos-profil-coordonnees-perso-ffcam`, `infos-profil-email-secondaire`, `infos-profil-filiation-enfants`, `infos-profil-filiation-parent`, `infos-profil-statuts`, `inscrire-filiation-select`, `local-club-description`, `main-pagelibre-<id>`, `mainfooter-1..3`, `mainmenu-connection`, `mainmenu-creer-mon-compte`, `materiel-access`, `materiel-explanation`, `methodologie-bilan-carbone`, `minivan-presentation`, `minivan-reservation`, `nav-menu-1..4`, `password-lost-confirm`, `password-lost-intro`, `presentation-<commission|general>`, `profil-infos`, `profil-sorties-next`, `profil-sorties-prev`, `signalement-intro`, `status-legal-<statut>`, `user_create`, `user_update`.

Côté legacy : `404`, `commission-add-bigimg`, `commission-add-nom`, `commission-add-pictos`, `info-activer-commission`, `info-desactiver-commission`, `infos-supprimer-mon-commentaire`, `infos-supprimer-any-commentaire`.
