# jQuery Dependencies Map - Cartographie des dépendances

> Mapping détaillé de qui utilise quoi dans le projet
> Date: 2025-09-20

## 🎯 Qui charge quelle version de jQuery ?

### 1. Pages Admin Legacy

**Loader**: `/legacy/includes/generic/header-admin.php`
```html
<script src="/js/jquery-1.5.2.min.js"></script>
<script src="/js/jquery-ui-1.8.16.full.min.js"></script>
```

**Pages concernées**:
- `/legacy/pages/admin-*.php` (tous les fichiers admin)
- `/legacy/pages/gestion-*.php` (gestion sorties, articles, commissions)
- `/legacy/pages/adherents-*.php` (gestion adhérents)
- `/legacy/pages/stats.php`
- `/legacy/pages/profil*.php`

**Total**: ~30 fichiers PHP

### 2. Site Public (Legacy)

**Loader**: `/legacy/includes/generic/header.php`
```html
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>
<script>window.jQuery || document.write('<script src="/js/jquery-1.8.min.js">\x3C/script>')</script>
```

**Pages concernées**:
- Toutes les pages publiques legacy
- Pages authentifiées non-admin
- Formulaires publics

### 3. Site Modern (Symfony/Twig)

**Loader**: `/templates/javascripts.html.twig`
```html
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>
<script>window.jQuery || document.write('<script src="/js/jquery-1.8.min.js">\x3C/script>')</script>
```

**Templates concernés**:
- `/templates/sortie/*.html.twig`
- `/templates/article/*.html.twig`
- `/templates/login/*.html.twig`
- Toutes les pages Symfony modernes

## 🔗 Dépendances Plugin → jQuery

### Fancybox (2 versions!)

#### Fancybox 1.3.4 (Legacy)
- **Localisation**: `/public/tools/_fancybox/`
- **Requiert**: jQuery 1.3.x - 1.6.x (⚠️ BLOQUE sur vieilles versions)
- **Utilisé par**: Admin file browser, anciennes galeries
- **Méthodes jQuery utilisées**: `.browser`, `.live()`

#### Fancybox 2.1.5 (Modern)
- **Localisation**: `/public/tools/fancybox/`
- **Requiert**: jQuery 1.6+
- **Utilisé par**: Galeries modernes, lightbox
- **Compatible**: jQuery 1.8, 1.12

### DataTables

- **Localisation**: `/public/tools/datatables/`
- **Requiert**: jQuery 1.7+
- **Version embarquée**: jQuery inconnue dans `/media/js/jquery.js`
- **Utilisé par**: Tableaux admin, listes de données
- **Extras**: 5 copies de jQuery dans les sous-dossiers (À SUPPRIMER)

### jQuery UI Components

#### Datepicker/Timepicker
- **Fichier addon**: `/public/js/jquery-ui-timepicker-addon.js`
- **Requiert**: jQuery 1.6+, jQuery UI 1.8+
- **Utilisé dans**:
  - Formulaires de sortie
  - Sélecteurs de date/heure
  - Planning événements

#### Sortable
- **Requiert**: jQuery UI core
- **Utilisé dans**:
  - `/legacy/pages/admin-pages.php` - Ordre des pages
  - `/legacy/pages/gestion-des-commissions.php` - Ordre commissions
  - `/legacy/pages/admin-traductions.php` - Ordre traductions
  - `/legacy/pages/admin-contenus.php` - Ordre contenus

### Plugins mineurs

| Plugin | Fichier | Requiert | Usage |
|--------|---------|----------|-------|
| jCrop | `/public/js/jquery.Jcrop.min.js` | jQuery 1.3+ | Crop images |
| SlidesJS | `/public/js/slidesjs/jquery.slides.js` | jQuery 1.7+ | Carrousel |
| Easing | `/public/js/jquery.easing.1.3.js` | jQuery 1.1+ | Animations |
| Color | `/public/js/jquery.color.js` | jQuery 1.5+ | Animations couleur |
| pngFix | `/public/js/jquery.pngFix.pack.js` | jQuery 1.2+ | IE6 (OBSOLÈTE) |
| webkitResize | `/public/js/jquery.webkitresize.min.js` | jQuery 1.4+ | Webkit resize |

## 🚨 Fichiers utilisant des méthodes dépréciées

### Utilisation de `.live()` (supprimé jQuery 1.9)

**Top 10 fichiers**:
1. `/legacy/pages/admin-contenus.php` - Usage intensif
2. `/legacy/pages/admin-traductions.php` - Multiple occurrences
3. `/legacy/pages/admin-pages-libres.php` - Forms dynamiques
4. `/public/js/onready.js` - Event binding
5. `/public/js/cycle.js` - Carousel events
6. `/public/admin/ftp.js` - File manager
7. `/legacy/includes/join_manual.php` - Form validation
8. Fancybox 1.3.4 - Dans le plugin même
9. jQuery UI 1.8.16 - Core UI
10. DataTables TableTools - Extension

### Utilisation de `$.browser` (supprimé jQuery 1.9)

**Fichiers affectés**:
- `/public/js/jquery.pngFix.pack.js` - Detection IE
- `/public/js/jquery.webkitresize.min.js` - Detection WebKit
- `/legacy/includes/generic/alerte-navigateur.php` - Browser warning
- jQuery 1.5.2, 1.6.2 - Dans core jQuery
- Fancybox 1.3.4 - Browser detection
- jCrop - Browser hacks

## 📊 Matrice de compatibilité

| Component | jQuery 1.5 | jQuery 1.8 | jQuery 1.12 | jQuery 3.x | Notes |
|-----------|------------|------------|--------------|------------|-------|
| Admin Legacy | ✅ Actuel | ⚠️ Besoin refactor | ⚠️ Besoin refactor | ❌ Breaking | `.live()` à migrer |
| Site Public | ❌ | ✅ Actuel | ✅ Compatible | ⚠️ Tests requis | OK pour migration |
| Fancybox 1.3.4 | ✅ | ❌ | ❌ | ❌ | Bloqué sur jQuery ≤1.6 |
| Fancybox 2.1.5 | ❌ | ✅ | ✅ | ⚠️ | Compatible moderne |
| DataTables | ❌ | ✅ | ✅ | ✅ | Prêt pour upgrade |
| jQuery UI 1.8 | ✅ | ✅ | ❌ | ❌ | Version obsolète |
| jQuery UI 1.10 | ❌ | ✅ | ✅ | ❌ | OK pour jQuery 1.x |
| jQuery UI 1.12 | ❌ | ❌ | ✅ | ✅ | Target version |
| Timepicker | ⚠️ | ✅ | ✅ | ⚠️ | Tests nécessaires |

## 🎯 Stratégie de migration par composant

### Priorité 1 - Quick wins (1 jour)
```bash
# Supprimer sans risque
rm -rf public/tools/datatables/extras/*/media/docs/media/js/jquery.js
rm public/js/jquery.pngFix.pack.js  # IE6
```

### Priorité 2 - Site public (2-3 jours)
```javascript
// Migrer jQuery 1.8.3 → 1.12.4
// Ajouter jQuery Migrate pour détection
// Tester tous les templates Twig
```

### Priorité 3 - Plugins (3-5 jours)
```javascript
// Fancybox 1.3.4 → Fancybox 3.x
// jQuery UI → version unifiée 1.12.1
// DataTables → dernière version
```

### Priorité 4 - Admin refactor (5-7 jours)
```javascript
// Remplacer tous les .live() → .on()
// Supprimer $.browser
// Migrer jQuery 1.5.2 → 1.12.4
```

## 🔍 Commandes utiles pour l'audit

```bash
# Trouver tous les .live()
grep -r "\.live(" legacy/ public/js/

# Trouver tous les $.browser
grep -r "\$\.browser" legacy/ public/

# Lister les fichiers jQuery
find . -name "*jquery*.js" -type f | sort

# Analyser les versions
grep -h "jQuery v[0-9]" public/js/*.js public/tools/**/*.js

# Compter les utilisations
grep -r "\$(" legacy/ --include="*.php" | wc -l
```

## ⚡ Actions immédiates recommandées

1. **Backup** : Sauvegarder tous les fichiers JS actuels
2. **Documenter** : Noter les versions actuelles en production
3. **Tester** : Créer un environnement de test isolé
4. **Commencer petit** : Supprimer les doublons évidents
5. **Mesurer** : Benchmarker les performances avant/après

---

*Document vivant - À mettre à jour pendant la migration*