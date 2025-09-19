# jQuery Version Map - État des lieux complet

> Document de référence pour la consolidation jQuery
> Date: 2025-09-20
> Branch: refactor/jquery-consolidation-phase-1

## 📊 Vue d'ensemble

- **Versions jQuery identifiées**: 7 versions différentes
- **Fichiers jQuery totaux**: 39 fichiers
- **Taille totale jQuery**: ~5MB
- **Code legacy utilisant jQuery**: 207 occurrences dans 30+ fichiers PHP

## 🔴 ÉTAT CRITIQUE - Deux systèmes incompatibles

### Système 1: Admin Legacy
- **Fichier**: `/legacy/includes/generic/header-admin.php`
- **jQuery**: 1.5.2 (mai 2011 - 14 ans!)
- **jQuery UI**: 1.8.16
- **Problèmes**: Utilise `.live()` et `$.browser` (supprimés dans jQuery 1.9)

### Système 2: Site Public/Modern
- **Fichier**: `/templates/javascripts.html.twig` et `/legacy/includes/generic/header.php`
- **jQuery**: 1.8.3 (depuis Google CDN)
- **jQuery UI**: Multiple versions (1.10.2, 1.11.2)
- **État**: Plus moderne mais fragmentation des versions UI

## 📦 Inventaire détaillé des versions

### jQuery Core

| Version | Fichier | Taille | Usage | Criticité |
|---------|---------|--------|-------|-----------|
| 1.5.2 | `/public/js/jquery-1.5.2.min.js` | 84K | Admin Legacy | CRITIQUE |
| 1.6.2 | `/public/js/libs/jquery-1.6.2.min.js` | 89K | Non utilisé? | À VÉRIFIER |
| 1.6.2 | `/public/js/libs/jquery-1.6.2.js` | 231K | Non utilisé? | À VÉRIFIER |
| 1.8.3 | `/public/js/jquery-1.8.min.js` | 91K | Site public | ACTIF |
| ?.?.? | `/public/tools/datatables/media/js/jquery.js` | 92K | DataTables | DOUBLON |
| ?.?.? | `/public/tools/jquery-ui-1.11.2/external/jquery/jquery.js` | 267K | jQuery UI 1.11.2 | DOUBLON |
| ?.?.? | 5 copies identiques dans `/public/tools/datatables/extras/*/` | 212K×5 | DataTables extras | DOUBLONS |

### jQuery UI

| Version | Fichier | Taille | Components | Usage |
|---------|---------|--------|------------|-------|
| 1.8.16 | `/public/js/jquery-ui-1.8.16.full.min.js` | 206K | Full | Admin Legacy |
| 1.8.18 | `/public/css/ui-cupertino/jquery-ui-1.8.18.custom.css` | CSS only | - | Admin theme |
| 1.10.2 | `/public/js/jquery-ui-1.10.2.custom.min.js` | 223K | Custom build | Site public |
| 1.10.2 | `/public/js/jquery-ui-1.10.2.datepicker-slider-autocomplete.min.js` | 81K | Partial | Formulaires |
| 1.11.2 | `/public/tools/jquery-ui-1.11.2/` | 458K+234K | Full | Non clair |

## 🔌 Plugins et leurs dépendances

### Plugins critiques

| Plugin | Version | Fichier | Requiert jQuery | État |
|--------|---------|---------|-----------------|------|
| Fancybox | 1.3.4 | `/public/tools/_fancybox/` | 1.3-1.6 | BLOQUANT (vieille version) |
| Fancybox | 2.1.5 | `/public/tools/fancybox/` | 1.6+ | OK |
| DataTables | ? | `/public/tools/datatables/` | 1.7+ | OK |
| Timepicker Addon | ? | `/public/js/jquery-ui-timepicker-addon.js` | 1.6+ & UI 1.8+ | OK |
| jCrop | ? | `/public/js/jquery.Jcrop.min.js` | 1.3+ | OK |
| SlidesJS | ? | `/public/js/slidesjs/` | 1.7+ | OK |

### Plugins obsolètes à supprimer

| Plugin | Fichier | Raison |
|--------|---------|--------|
| pngFix | `/public/js/jquery.pngFix.pack.js` | Pour IE6! |
| Modernizr | `/public/js/libs/modernizr-2.0.6.min.js` | Version 2011 |

## ⚠️ Méthodes dépréciées à corriger

### Statistiques des problèmes

- **`.live()`**: 75 occurrences (supprimé jQuery 1.9)
- **`$.browser`**: Multiples occurrences (supprimé jQuery 1.9)
- **`.bind()`**: À migrer vers `.on()` (déprécié jQuery 3.0)

### Fichiers les plus affectés

1. `/legacy/pages/admin-contenus.php` - 32 occurrences jQuery
2. `/legacy/pages/admin-traductions.php` - 27 occurrences
3. `/legacy/pages/admin-pages-libres.php` - 21 occurrences
4. `/legacy/pages/commission-edit.php` - 15 occurrences

## 🎯 Composants jQuery UI utilisés

### Composants actifs confirmés

- **Datepicker**: Massivement utilisé dans les formulaires
- **Timepicker**: Extension pour sélection d'heure
- **Sortable**: Admin pages, gestion commissions
- **Autocomplete**: Recherche lieux, adhérents
- **Dialog**: Popups TinyMCE

### Fichiers utilisant jQuery UI

- `/legacy/pages/gestion-des-commissions.php` - Sortable
- `/legacy/pages/admin-pages.php` - Sortable
- `/legacy/pages/admin-traductions.php` - Sortable
- `/legacy/pages/admin-contenus.php` - Sortable
- `/templates/sortie/formulaire.html.twig` - Datepicker/Timepicker (via classes CSS)

## 🗑️ Doublons à supprimer immédiatement

### Suppression sans risque (1MB+ à gagner)

```bash
# 5 copies identiques de jQuery dans DataTables extras
/public/tools/datatables/extras/AutoFill/media/docs/media/js/jquery.js
/public/tools/datatables/extras/ColReorder/media/docs/media/js/jquery.js
/public/tools/datatables/extras/ColVis/media/docs/media/js/jquery.js
/public/tools/datatables/extras/FixedColumns/docs/media/js/jquery.js
/public/tools/datatables/extras/Scroller/media/docs/media/js/jquery.js
```

## 📝 Plan de migration recommandé

### Phase 1: Nettoyage immédiat (sans risque)
1. Supprimer les 5 doublons DataTables extras
2. Supprimer pngFix (IE6)
3. Documenter les usages de jQuery 1.6.2

### Phase 2: Harmonisation site public
1. Migrer jQuery 1.8.3 → 1.12.4 (dernière v1.x)
2. Consolider jQuery UI vers 1.12.1
3. Tests exhaustifs

### Phase 3: Refactoring admin
1. Remplacer tous les `.live()` par `.on()`
2. Remplacer `$.browser` par feature detection
3. Migrer jQuery 1.5.2 → 1.8.3 → 1.12.4

### Phase 4: Consolidation finale
1. Une seule version jQuery 1.12.4
2. Une seule version jQuery UI 1.12.1
3. Mise à jour des plugins obsolètes

## 🧪 Plan de test

### Tests critiques admin
- [ ] Connexion/déconnexion
- [ ] Drag & drop pages
- [ ] Tri commissions
- [ ] Upload fichiers
- [ ] Éditeur TinyMCE

### Tests critiques site public
- [ ] Formulaire sortie (datepicker/timepicker)
- [ ] Galeries photos (Fancybox)
- [ ] Carrousel partenaires
- [ ] Autocomplétion recherche

## 📊 Métriques de succès

| Métrique | Avant | Objectif Phase 1 | Objectif Final |
|----------|-------|------------------|----------------|
| Versions jQuery | 7 | 2 | 1 |
| Fichiers jQuery | 39 | 10 | 3 |
| Taille totale | ~5MB | ~2MB | <500KB |
| Méthodes dépréciées | 75+ | 75+ | 0 |

## 🚨 Risques identifiés

### Risque ÉLEVÉ
- Admin legacy fortement couplé à jQuery 1.5.2
- Fancybox 1.3.4 incompatible avec jQuery moderne
- `.live()` utilisé massivement (75 occurrences)

### Risque MOYEN
- jQuery UI versions multiples
- Timepicker addon compatibilité
- DataTables configuration

### Risque FAIBLE
- Site public déjà sur jQuery 1.8
- Plugins modernes compatibles
- CDN avec fallback local

## 📅 Timeline estimée

- **Phase 0** (Préparation): 1 jour ✅ EN COURS
- **Phase 1** (Nettoyage): 1 jour
- **Phase 2** (Site public): 2-3 jours
- **Phase 3** (Admin refactoring): 5-7 jours
- **Phase 4** (Consolidation): 2-3 jours
- **Tests & validation**: 2-3 jours

**Total estimé**: 13-18 jours

---

*Ce document sera mis à jour au fur et à mesure de l'avancement du projet.*