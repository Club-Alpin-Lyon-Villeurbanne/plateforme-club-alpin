# Plan de Test - Migration jQuery

> Plan de test exhaustif pour valider la migration jQuery
> Date: 2025-09-20
> ⚠️ À exécuter AVANT et APRÈS chaque phase de migration

## 🎯 Stratégie de test

### Environnements de test
1. **Local** - Premier niveau de validation
2. **Staging** - Tests d'intégration complets
3. **Production** - Smoke tests post-déploiement

### Types de tests
- **Smoke tests** - Fonctionnalités critiques (5 min)
- **Tests fonctionnels** - Toutes les features (30 min)
- **Tests de régression** - Cas edge (1h)
- **Tests de performance** - Métriques (15 min)

## ✅ SMOKE TESTS (Priorité CRITIQUE)

### 1. Connexion/Navigation
- [ ] Page d'accueil charge sans erreur JS
- [ ] Login fonctionne
- [ ] Navigation menu principal OK
- [ ] Logout fonctionne
- [ ] Console sans erreur critique

### 2. Admin - Fonctions vitales
- [ ] Accès dashboard admin
- [ ] Créer un contenu test
- [ ] Modifier un contenu existant
- [ ] Uploader une image
- [ ] Sauvegarder fonctionne

### 3. Public - Fonctions vitales
- [ ] Voir liste des sorties
- [ ] Ouvrir détail sortie
- [ ] Formulaire contact fonctionne
- [ ] Recherche fonctionne
- [ ] Galerie photos s'ouvre

**✅ Si tous les smoke tests passent → Continuer**
**❌ Si un smoke test échoue → ROLLBACK IMMÉDIAT**

## 📋 TESTS FONCTIONNELS DÉTAILLÉS

### Module: Authentication
| Test | Description | Résultat | Notes |
|------|-------------|----------|-------|
| AUTH-01 | Login avec credentials valides | ⬜ | |
| AUTH-02 | Login avec credentials invalides | ⬜ | |
| AUTH-03 | Forgot password | ⬜ | |
| AUTH-04 | Logout | ⬜ | |
| AUTH-05 | Remember me | ⬜ | |

### Module: Admin - Gestion Contenus
| Test | Description | Résultat | Notes |
|------|-------------|----------|-------|
| ADM-01 | Lister tous les contenus | ⬜ | |
| ADM-02 | Rechercher un contenu | ⬜ | |
| ADM-03 | Créer nouveau contenu | ⬜ | |
| ADM-04 | Éditer contenu existant | ⬜ | |
| ADM-05 | Supprimer contenu | ⬜ | |
| ADM-06 | Drag & drop ordre pages | ⬜ | CRITIQUE |
| ADM-07 | TinyMCE editor fonctionne | ⬜ | |
| ADM-08 | Upload image dans editor | ⬜ | |
| ADM-09 | Preview contenu | ⬜ | |
| ADM-10 | Publier/Dépublier | ⬜ | |

### Module: Admin - Gestion Sorties
| Test | Description | Résultat | Notes |
|------|-------------|----------|-------|
| SRT-01 | Lister toutes les sorties | ⬜ | |
| SRT-02 | Créer nouvelle sortie | ⬜ | |
| SRT-03 | Datepicker fonctionne | ⬜ | CRITIQUE |
| SRT-04 | Timepicker fonctionne | ⬜ | CRITIQUE |
| SRT-05 | Autocomplete lieu | ⬜ | |
| SRT-06 | Ajout encadrants | ⬜ | |
| SRT-07 | Validation formulaire | ⬜ | |
| SRT-08 | Annuler une sortie | ⬜ | |
| SRT-09 | Dupliquer une sortie | ⬜ | |
| SRT-10 | Export PDF | ⬜ | |

### Module: Admin - Gestion Adhérents
| Test | Description | Résultat | Notes |
|------|-------------|----------|-------|
| ADH-01 | Lister adhérents | ⬜ | |
| ADH-02 | Recherche adhérent | ⬜ | |
| ADH-03 | Créer adhérent | ⬜ | |
| ADH-04 | Modifier adhérent | ⬜ | |
| ADH-05 | Gérer les droits | ⬜ | |
| ADH-06 | Import CSV | ⬜ | |
| ADH-07 | Export Excel | ⬜ | |

### Module: Public - Navigation
| Test | Description | Résultat | Notes |
|------|-------------|----------|-------|
| NAV-01 | Menu principal | ⬜ | |
| NAV-02 | Menu responsive mobile | ⬜ | |
| NAV-03 | Breadcrumb | ⬜ | |
| NAV-04 | Footer links | ⬜ | |
| NAV-05 | Recherche globale | ⬜ | |

### Module: Public - Galeries/Médias
| Test | Description | Résultat | Notes |
|------|-------------|----------|-------|
| GAL-01 | Ouvrir galerie photos | ⬜ | CRITIQUE |
| GAL-02 | Navigation photos (prev/next) | ⬜ | |
| GAL-03 | Zoom photo | ⬜ | |
| GAL-04 | Fermer galerie (ESC) | ⬜ | |
| GAL-05 | Carrousel partenaires | ⬜ | |
| GAL-06 | Video player | ⬜ | |

### Module: Formulaires Publics
| Test | Description | Résultat | Notes |
|------|-------------|----------|-------|
| FRM-01 | Formulaire contact | ⬜ | |
| FRM-02 | Inscription sortie | ⬜ | |
| FRM-03 | Newsletter | ⬜ | |
| FRM-04 | Validation côté client | ⬜ | |
| FRM-05 | Messages d'erreur | ⬜ | |

## 🔍 TESTS JQUERY SPÉCIFIQUES

### Méthodes critiques à tester

```javascript
// Test 1: jQuery chargé
console.assert(typeof jQuery !== 'undefined', 'jQuery not loaded');
console.assert(typeof $ !== 'undefined', '$ not defined');

// Test 2: Version correcte
console.assert(jQuery.fn.jquery === '1.12.4', 'Wrong jQuery version');

// Test 3: jQuery UI chargé
console.assert(typeof jQuery.ui !== 'undefined', 'jQuery UI not loaded');

// Test 4: Plugins critiques
console.assert(typeof jQuery.fn.datepicker === 'function', 'Datepicker missing');
console.assert(typeof jQuery.fn.sortable === 'function', 'Sortable missing');
console.assert(typeof jQuery.fn.fancybox === 'function', 'Fancybox missing');

// Test 5: Pas de méthodes dépréciées
console.assert(typeof jQuery.fn.live === 'undefined', '.live() should not exist');
console.assert(typeof jQuery.browser === 'undefined', '$.browser should not exist');
```

### Ajax à tester

```javascript
// Test Ajax basique
$.ajax({
    url: '/?ajx=test',
    method: 'GET',
    success: function(data) {
        console.log('✅ Ajax works');
    },
    error: function() {
        console.error('❌ Ajax failed');
    }
});

// Test Ajax JSON
$.getJSON('/api/test', function(data) {
    console.log('✅ getJSON works');
}).fail(function() {
    console.error('❌ getJSON failed');
});
```

### Event handlers à valider

```javascript
// Test event delegation (remplace .live())
$(document).on('click', '.test-button', function() {
    console.log('✅ Event delegation works');
});

// Test jQuery UI events
$('#test-sortable').on('sortupdate', function() {
    console.log('✅ Sortable events work');
});

// Test datepicker
$('#test-date').datepicker({
    onSelect: function(date) {
        console.log('✅ Datepicker works:', date);
    }
});
```

## 📊 TESTS DE PERFORMANCE

### Métriques à mesurer

| Métrique | Avant migration | Après migration | Objectif | Status |
|----------|-----------------|-----------------|----------|--------|
| Page Load Time | ___ ms | ___ ms | < 3000ms | ⬜ |
| JS Bundle Size | ___ KB | ___ KB | -50% | ⬜ |
| DOM Ready | ___ ms | ___ ms | < 1000ms | ⬜ |
| First Paint | ___ ms | ___ ms | < 1500ms | ⬜ |
| jQuery Parse Time | ___ ms | ___ ms | < 100ms | ⬜ |

### Commands de mesure

```bash
# Taille des bundles
du -sh public/js/*.js | sort -h

# Analyse avec Lighthouse
lighthouse https://staging.example.com --output=json

# Network waterfall
chrome://inspect -> Network tab
```

## 🔄 TESTS DE RÉGRESSION

### Cas edge à tester

1. **Double-click rapide** sur boutons submit
2. **Navigation back/forward** après Ajax
3. **Upload fichiers > 10MB**
4. **Formulaires avec 50+ champs**
5. **Drag & drop** avec 100+ items
6. **Datepicker** année 1900 et 2100
7. **Session timeout** pendant édition
8. **Connexion simultanée** 2 onglets
9. **Mode offline** puis online
10. **IE11 compatibility** (si supporté)

## 🐛 BUGS CONNUS À VÉRIFIER

### Bugs existants (ne pas régresser)
- [ ] BUG-001: Datepicker parfois derrière modal
- [ ] BUG-002: Double soumission formulaire
- [ ] BUG-003: Tri colonnes DataTables

### Nouveaux bugs potentiels
- [ ] NEW-001: _____________
- [ ] NEW-002: _____________
- [ ] NEW-003: _____________

## 📝 TEMPLATE RAPPORT DE TEST

```markdown
## Rapport de Test - [Date]

### Environnement
- Branch: refactor/jquery-consolidation-phase-1
- jQuery: 1.12.4
- jQuery UI: 1.12.1
- Navigateur: Chrome 120

### Résultats
- Smoke tests: ✅ 5/5
- Tests fonctionnels: ⚠️ 38/40
- Tests performance: ✅ Tous OK
- Tests régression: ✅ 10/10

### Issues identifiées
1. [CRITIQUE] Datepicker ne fonctionne pas sur...
2. [MINEUR] Animation menu saccadée

### Recommandation
[GO/NO-GO] pour la production

Testé par: _______
Validé par: _______
```

## ⏱️ PLANNING DES TESTS

### Phase 1 - Nettoyage (30 min)
- [ ] Smoke tests: 5 min
- [ ] Test suppression doublons: 10 min
- [ ] Vérification site: 15 min

### Phase 2 - Site public (2h)
- [ ] Smoke tests: 10 min
- [ ] Tests fonctionnels public: 1h
- [ ] Tests galeries/médias: 30 min
- [ ] Tests performance: 20 min

### Phase 3 - Admin (4h)
- [ ] Smoke tests admin: 15 min
- [ ] Tests gestion contenus: 1h
- [ ] Tests gestion sorties: 1h
- [ ] Tests gestion adhérents: 45 min
- [ ] Tests drag & drop: 30 min
- [ ] Tests régression: 30 min

### Phase 4 - Validation finale (2h)
- [ ] Tests end-to-end: 1h
- [ ] Tests cross-browser: 30 min
- [ ] Tests performance finale: 30 min

## 🚀 CRITÈRES DE SUCCÈS

### Succès si:
- ✅ 100% smoke tests passent
- ✅ > 95% tests fonctionnels passent
- ✅ 0 régression critique
- ✅ Performance améliorée ou stable
- ✅ Console sans erreur

### Échec si:
- ❌ Un smoke test échoue
- ❌ < 90% tests fonctionnels passent
- ❌ Régression critique détectée
- ❌ Performance dégradée > 20%
- ❌ Erreurs jQuery en console

---

*Plan de test v1.0 - À exécuter rigoureusement avant chaque mise en production*