# Checklist de Mise en Œuvre - Tests Fonctionnels Automatisés

## 📋 AVANT DE COMMENCER

- [ ] **Environnement prêt**
  - [ ] Node.js 16+ installé
  - [ ] npm ou yarn fonctionnel
  - [ ] Docker disponible (si DB locale)
  - [ ] Git configuré

- [ ] **Accès aux ressources**
  - [ ] Accès au repo GitHub
  - [ ] Credentials de test disponibles
  - [ ] Base de test accessible
  - [ ] Serveur de développement fonctionnel

- [ ] **Compréhension du projet**
  - [ ] Architecture générale lue
  - [ ] Workflows critiques documentés
  - [ ] Rôles utilisateurs compris
  - [ ] Flux API exploré

---

## 🔧 PHASE 1 : SETUP (Jour 1)

### Installation Playwright
- [ ] Installer Playwright : `npm install -D @playwright/test`
- [ ] Installer navigateurs : `npx playwright install`
- [ ] Vérifier installation : `npx playwright --version`

### Configuration de base
- [ ] Copier `playwright.config.ts` (voir guide d'automation)
- [ ] Créer `.env.test` avec les variables d'environnement
- [ ] Créer répertoire `e2e/` s'il n'existe pas
- [ ] Créer répertoire `e2e/helpers/` pour les utilitaires
- [ ] Créer répertoire `e2e/spec/` pour les tests organisés

### Helpers initiaux
- [ ] Créer `e2e/helpers/auth.ts` avec fonction `login()`
- [ ] Créer `e2e/helpers/data-fixtures.ts` avec données de test
- [ ] Créer `e2e/helpers/page-objects.ts` avec Page Objects
- [ ] Tester les helpers avec un test simple

### Test de base
- [ ] Lancer un test simple : `npx playwright test --headed`
- [ ] Vérifier enregistrement de vidéo/screenshot
- [ ] Consulter HTML report : `npx playwright show-report`
- [ ] ✅ Valider que le setup fonctionne

---

## 🚀 PHASE 2 : TESTS CRITIQUES (Semaine 1)

### Groupe 1 : Authentification
- [ ] Implémenter TC-AUTH-001 (Connexion valide)
- [ ] Implémenter TC-AUTH-002 (Connexion invalide)
- [ ] Implémenter TC-AUTH-003 (Déconnexion)
- [ ] Implémenter TC-AUTH-004 (Accès au profil)
- [ ] Lancer suite : `npx playwright test auth`
- [ ] ✅ Tous les tests passent

### Groupe 2 : Sorties (Événements)
- [ ] Implémenter TC-EVENT-001 (Créer sortie)
  - [ ] Remplir tous les champs du formulaire
  - [ ] Vérifier page de redirection
  - [ ] Valider message de succès
- [ ] Implémenter TC-EVENT-002 (Valider sortie éditorialement)
  - [ ] Naviguer vers gestion des approbations
  - [ ] Valider changement de statut
- [ ] Implémenter TC-EVENT-003 (Valider sortie juridiquement)
  - [ ] Workflow complet de validation 2 niveaux
- [ ] Implémenter TC-EVENT-005 (S'inscrire à sortie)
- [ ] Implémenter TC-EVENT-009 (Se désinscrire)
- [ ] Lancer suite : `npx playwright test events`
- [ ] ✅ Tous les tests passent

### Groupe 3 : Articles
- [ ] Implémenter TC-ARTICLE-001 (Créer article)
  - [ ] Upload image
  - [ ] Remplir contenu CKEditor
  - [ ] Valider création
- [ ] Implémenter TC-ARTICLE-003 (Valider et publier)
  - [ ] Voir article en attente
  - [ ] Cliquer validation
  - [ ] Vérifier publication
- [ ] Implémenter TC-ARTICLE-005 (Modifier brouillon)
- [ ] Lancer suite : `npx playwright test articles`
- [ ] ✅ Tous les tests passent

**Checkpoint 1** : Minimum 15 tests critiques passants ✅

---

## 💰 PHASE 3 : FONCTIONNALITÉS COMPLÉMENTAIRES (Semaine 2)

### Groupe 4 : Notes de Frais
- [ ] Implémenter TC-EXPENSE-001 (Créer note)
  - [ ] Tester avec différents montants
  - [ ] Valider calcul automatique km
- [ ] Implémenter TC-EXPENSE-002 (Soumettre note)
- [ ] Implémenter TC-EXPENSE-004 (Ajouter pièce jointe)
- [ ] Test API : Créer via POST /api/notes-de-frais
- [ ] Lancer suite : `npx playwright test expenses`
- [ ] ✅ Tous les tests passent

### Groupe 5 : Utilisateurs & Rôles
- [ ] Implémenter TC-USER-001 (Créer user - Admin)
- [ ] Implémenter TC-USER-002 (Modifier rôles)
- [ ] Implémenter TC-USER-003 (Désactiver user)
- [ ] Implémenter TC-USER-006 (Sync FFCAM - script)
- [ ] Lancer suite : `npx playwright test users`
- [ ] ✅ Tous les tests passent

### Groupe 6 : Contenu
- [ ] Implémenter TC-CONTENT-001 (Modifier page)
- [ ] Implémenter TC-CONTENT-003 (Ajouter partenaire)
- [ ] Lancer suite : `npx playwright test content`
- [ ] ✅ Tous les tests passent

### Groupe 7 : Notifications
- [ ] Implémenter TC-NOTIF-001 (Email bienvenue)
  - [ ] Tester avec API email mock
- [ ] Implémenter TC-NOTIF-002 (Confirmation inscription)
- [ ] Implémenter TC-NOTIF-003 (Annulation sortie)
- [ ] Lancer suite : `npx playwright test notifications`
- [ ] ✅ Tous les tests passent

**Checkpoint 2** : Minimum 30 tests au total ✅

---

## 🔒 PHASE 4 : SÉCURITÉ & ACCÈS (Semaine 2-3)

### Sécurité
- [ ] Implémenter TC-SEC-001 (Pas auth = pas accès)
- [ ] Implémenter TC-SEC-002 (Rôle insuffisant = 403)
- [ ] Implémenter TC-SEC-003 (Isolation données)
- [ ] Implémenter TC-SEC-004 (Validation CSRF)
- [ ] Implémenter TC-SEC-005 (Prévention SQL injection)
- [ ] Implémenter TC-SEC-006 (Prévention XSS)
- [ ] Lancer suite : `npx playwright test security`
- [ ] ✅ Tous les tests passent

### Smoke Tests
- [ ] Implémenter TC-SMOKE-001 (Homepage charge)
- [ ] Implémenter TC-SMOKE-002 (Menu accessible)
- [ ] Implémenter TC-SMOKE-003 (Footer infos contact)
- [ ] Lancer suite : `npx playwright test smoke`
- [ ] ✅ Tous les tests passent

**Checkpoint 3** : Minimum 40 tests + couverture sécurité ✅

---

## 🔍 PHASE 5 : RECHERCHE & API (Semaine 3)

### Recherche
- [ ] Implémenter TC-SEARCH-001 (Recherche sorties)
- [ ] Implémenter TC-SEARCH-002 (Recherche articles)
- [ ] Implémenter TC-SEARCH-003 (Agenda/calendrier)
- [ ] Implémenter TC-SEARCH-004 (Flux RSS)
- [ ] Lancer suite : `npx playwright test search`
- [ ] ✅ Tous les tests passent

### API Tests
- [ ] Implémenter TC-API-001 (GET /api/sorties)
- [ ] Implémenter TC-API-002 (POST /api/notes-de-frais)
- [ ] Implémenter TC-API-003 (Webhook HelloAsso)
- [ ] Lancer suite : `npx playwright test api`
- [ ] ✅ Tous les tests passent

**Checkpoint 4** : Minimum 50 tests ✅

---

## ⚡ PHASE 6 : PERFORMANCE & AVANCÉS (Semaine 4)

### Performance (optionnel mais utile)
- [ ] Implémenter TC-PERF-001 (Search avec 1000 articles)
- [ ] Implémenter TC-PERF-002 (Agenda avec 500 sorties)
- [ ] Mesurer temps réponse
- [ ] Documenter baselines

### Fonctionnalités Avancées
- [ ] Implémenter TC-ADV-001 (Gestion commissions)
- [ ] Implémenter TC-ADV-002 (Gestion groupes)
- [ ] Implémenter TC-ADV-003 (Minibu - réservation)
- [ ] Implémenter TC-ADV-004 (Matériel - location)
- [ ] Implémenter TC-ADV-005 (Formations)
- [ ] Implémenter TC-ADV-006 (Metabase reports)

**Checkpoint 5** : Minimum 60 tests ✅

---

## 🔄 PHASE 7 : INTÉGRATION CI/CD (Semaine 4)

### GitHub Actions
- [ ] Créer `.github/workflows/e2e-tests.yml`
- [ ] Configurer déclencheurs (push, PR, schedule)
- [ ] Tester run local : `npm run test:e2e`
- [ ] Vérifier artifacts (reports, videos)
- [ ] Configurer notifications Slack (optionnel)

### Package.json Scripts
- [ ] Ajouter `test:e2e` script
- [ ] Ajouter `test:e2e:debug` script
- [ ] Ajouter `test:e2e:ui` script
- [ ] Ajouter `test:db:reset` script
- [ ] Ajouter `test:setup` script
- [ ] Tester tous les scripts

### Documentation
- [ ] Mettre à jour `docs/testing.md` (si existe)
- [ ] Ajouter instructions au README
- [ ] Documenter command d'exécution locale
- [ ] Documenter troubleshooting

**Checkpoint 6** : CI/CD opérationnel ✅

---

## 📊 PHASE 8 : OPTIMISATION & MAINTENANCE

### Parallélisation
- [ ] Tester avec `workers: 4`
- [ ] Identifier tests à rendre parallèles
- [ ] Marquer tests séquentiels si nécessaire
- [ ] Mesurer réduction de durée

### Flakiness
- [ ] Identifier tests instables (> 1 flake par 10 runs)
- [ ] Augmenter timeouts où nécessaire
- [ ] Utiliser `waitForLoadState()` à la place de `waitForTimeout()`
- [ ] Refactoriser sélecteurs fragiles

### Couverture
- [ ] Identifier gaps non testés
- [ ] Ajouter tests manquants (priorité haute)
- [ ] Vérifier 80%+ couverture workflows critiques
- [ ] Documenter exclusions intentionnelles

### Rapports
- [ ] Mettre en place dashboard (optionnel)
- [ ] Archiver rapports mensuels
- [ ] Documenter tendances (durée, flakiness)

**Checkpoint 7** : Suite stable et maintenable ✅

---

## 📈 MÉTRIQUES À SUIVRE

### Par rapport
| Métrique | Cible | Fréquence |
|----------|-------|-----------|
| Tests passants | > 95% | Chaque run |
| Durée totale | < 10 min | Hebdo |
| Flakiness | < 5% | Hebdo |
| Couverture | > 80% | Mensuel |
| Nouveaux tests | +5/semaine | Mensuel |

### Tableau de bord
```
Tests Automatisés - Semaine 1
┌─────────────────────────────┐
│ Total: 25 tests             │
│ ✅ Passants: 23 (92%)       │
│ ❌ Échoués: 2 (8%)          │
│ ⏭️  Skippés: 0              │
│ ⏱️  Durée: 4m 32s           │
│ 📊 Flakiness: 3%            │
└─────────────────────────────┘
```

---

## 🎯 PRIORITÉS SI TEMPS LIMITÉ

**Semaine 1 obligatoire** :
1. ✅ Setup complet
2. ✅ TC-AUTH-001/002/003 (authentification)
3. ✅ TC-EVENT-001/002/003 (sortie complète)
4. ✅ TC-ARTICLE-001/003 (article complet)
5. ✅ TC-SMOKE-001/002/003 (vérifications basiques)

**Semaine 2 recommandée** :
6. ✅ TC-EXPENSE-001/002 (notes de frais)
7. ✅ TC-SEC-001/002 (sécurité basique)
8. ✅ TC-NOTIF-001/002/003 (notifications)
9. ✅ CI/CD setup

**Semaine 3-4 nice to have** :
10. Tests avancés (API, performance, etc.)

---

## ✅ VALIDATION FINALE

Avant de considérer le projet **DONE** :

- [ ] **Minimum 50 tests** automatisés et passants
- [ ] **CI/CD** intégré et testé
- [ ] **Documentation** complète et à jour
- [ ] **Coverage** > 80% des workflows critiques
- [ ] **Flakiness** < 5%
- [ ] **Durée** < 15 minutes pour full suite
- [ ] **Rapports** accessibles et lisibles
- [ ] **Équipe** formée à la maintenance

---

## 📞 CONTACT & SUPPORT

### Problèmes courants

**"Les tests sont flaky"**
→ Voir `docs/automation-guide.md` section Dépannage

**"Les tests sont lents"**
→ Paralléliser tests indépendants avec `mode: 'parallel'`

**"Je ne comprends pas le test"**
→ Consulter [Playwright Docs](https://playwright.dev)

**"Comment déboguer localement"**
→ Lancer `npx playwright test --debug --headed`

---

## 📝 NOTES

- Revoir cette checklist **toutes les 2 semaines**
- Mettre à jour quand nouvelles fonctionnalités ajoutées
- Partager progrès dans issues/PRs GitHub
- Célébrer les milestones! 🎉

---

**Démarrage** : [Voir docs/automation-guide.md](automation-guide.md)  
**Cas de tests** : [Voir docs/test-cases-fonctionnels.md](test-cases-fonctionnels.md)  
**Examples** : [Voir e2e/comprehensive-test-suite.spec.ts](../e2e/comprehensive-test-suite.spec.ts)

*Dernière mise à jour : 12 janvier 2026*
