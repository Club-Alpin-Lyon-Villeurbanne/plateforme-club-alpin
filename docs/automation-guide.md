# Guide Complet d'Automatisation des Tests Fonctionnels

## 🎯 Objectif

Ce guide couvre l'implémentation d'une suite de tests automatisés complète pour la plateforme Club Alpin avec Playwright, couvrant tous les workflows fonctionnels critiques.

---

## 📋 Table des matières

1. [Architecture de test](#architecture)
2. [Configuration Playwright](#configuration)
3. [Fixtures et données de test](#fixtures)
4. [Patterns et bonnes pratiques](#patterns)
5. [Intégration CI/CD](#cicd)
6. [Rapports et monitoring](#rapports)

---

## <a name="architecture"></a>🏗️ Architecture de Test

### Structure des répertoires

```
e2e/
├── comprehensive-test-suite.spec.ts    # Suite complète (ce fichier)
├── helpers/
│   ├── auth.ts                         # Authentification réutilisable
│   ├── data-fixtures.ts                # Données de test
│   ├── page-objects.ts                 # Page Object Models
│   └── api-helpers.ts                  # Helpers API
├── fixtures/
│   ├── test-users.json                 # Utilisateurs de test
│   ├── test-events.json                # Sorties de test
│   └── test-articles.json              # Articles de test
├── spec/
│   ├── auth.spec.ts
│   ├── articles.spec.ts
│   ├── events.spec.ts
│   ├── expenses.spec.ts
│   ├── users.spec.ts
│   ├── content.spec.ts
│   ├── search.spec.ts
│   ├── security.spec.ts
│   ├── api.spec.ts
│   └── smoke.spec.ts
└── config/
    └── test-config.ts                  # Configuration centralisée
```

### Catégories de tests

| Type | Objectif | Parallélisable | Durée |
|------|----------|:-:|:---:|
| **Smoke** | Vérifications basiques | ✅ | < 1min |
| **Fonctionnel** | Workflows complets | ⚠️ | 3-5min |
| **Critiques** | Données sensibles | ❌ | 2-3min |
| **Performance** | Charge/lenteur | ❌ | 5-10min |
| **Sécurité** | Accès/XSS/CSRF | ❌ | 3-5min |

---

## <a name="configuration"></a>⚙️ Configuration Playwright

### playwright.config.ts - Configuration recommandée

```typescript
import { defineConfig, devices } from '@playwright/test';

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';

export default defineConfig({
  // Test directory
  testDir: './e2e',
  testMatch: '**/*.spec.ts',
  
  // Timeouts
  timeout: 30 * 1000,
  expect: {
    timeout: 5000,
  },
  
  // Global timeout
  globalTimeout: 30 * 60 * 1000,
  
  // Retries
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : 4,
  
  // Reporters
  reporter: [
    ['html'],
    ['json', { outputFile: 'test-results/results.json' }],
    ['junit', { outputFile: 'test-results/junit.xml' }],
    ['list'],
  ],
  
  // Output directory
  outputDir: 'test-results/output',
  
  // Shared settings for all browsers
  use: {
    baseURL: BASE_URL,
    
    // Screenshots
    screenshot: 'only-on-failure',
    
    // Videos
    video: 'retain-on-failure',
    
    // Trace
    trace: 'on-first-retry',
    
    // Action timeout
    actionTimeout: 10 * 1000,
  },
  
  // Web server
  webServer: {
    command: 'npm run dev',
    url: BASE_URL,
    reuseExistingServer: !process.env.CI,
    timeout: 120 * 1000,
  },
  
  // Projects/browsers
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    
    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },
    
    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },
    
    // Mobile testing (optionnel)
    {
      name: 'Mobile Chrome',
      use: { ...devices['Pixel 5'] },
    },
  ],
});
```

### Variables d'environnement (.env.test)

```bash
# URL
BASE_URL=http://127.0.0.1:8000

# Utilisateurs de test
TEST_ADMIN_EMAIL=admin@test-clubalpinlyon.fr
TEST_ADMIN_PASSWORD=test
TEST_ENCADRANT_EMAIL=encadrant@test-clubalpinlyon.fr
TEST_ENCADRANT_PASSWORD=test
TEST_USER_EMAIL=user@test-clubalpinlyon.fr
TEST_USER_PASSWORD=test

# API
API_TIMEOUT=10000
API_BASE_URL=http://127.0.0.1:8000/api

# Timeouts
PAGE_LOAD_TIMEOUT=30000
NAVIGATION_TIMEOUT=30000

# Reportage
SCREENSHOT_ON_FAILURE=true
VIDEO_ON_FAILURE=true
TRACE_ON_FIRST_RETRY=true

# CI/CD
CI=false
HEADLESS=true
```

---

## <a name="fixtures"></a>📦 Fixtures et Données de Test

### helpers/auth.ts

```typescript
import { Page } from '@playwright/test';

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';

export async function login(
  page: Page,
  email: string,
  password: string
): Promise<void> {
  await page.goto(`${BASE_URL}/login`);
  
  // Remplir le formulaire
  await page.fill('input[name="email"], input[type="email"]', email);
  await page.fill('input[name="password"], input[type="password"]', password);
  
  // Soumettre
  await page.click('button[type="submit"]');
  
  // Attendre la redirection
  await page.waitForNavigation({ waitUntil: 'networkidle' });
}

export async function logout(page: Page): Promise<void> {
  await page.locator('#toolbar-user').hover();
  await page.getByRole('link', { name: /Déconnexion|Logout/i }).click();
  await page.waitForNavigation();
}

export async function isLoggedIn(page: Page): Promise<boolean> {
  try {
    await page.locator('#toolbar-user').waitFor({ timeout: 1000 });
    return true;
  } catch {
    return false;
  }
}
```

### helpers/page-objects.ts - Page Object Model

```typescript
import { Page, Locator } from '@playwright/test';

export class BasePage {
  readonly page: Page;
  readonly baseUrl = process.env.BASE_URL || 'http://127.0.0.1:8000';

  constructor(page: Page) {
    this.page = page;
  }

  async goto(path: string) {
    await this.page.goto(`${this.baseUrl}${path}`);
  }

  async waitForLoadState() {
    await this.page.waitForLoadState('networkidle');
  }
}

export class LoginPage extends BasePage {
  get emailInput(): Locator {
    return this.page.locator('input[type="email"], input[name="email"]');
  }

  get passwordInput(): Locator {
    return this.page.locator('input[type="password"], input[name="password"]');
  }

  get submitButton(): Locator {
    return this.page.locator('button[type="submit"]');
  }

  async login(email: string, password: string) {
    await this.goto('/login');
    await this.emailInput.fill(email);
    await this.passwordInput.fill(password);
    await this.submitButton.click();
    await this.waitForLoadState();
  }
}

export class EventsPage extends BasePage {
  get eventsList(): Locator {
    return this.page.locator('a[href*="/sortie/"], a[href*="/evt/"]');
  }

  get createEventButton(): Locator {
    return this.page.getByRole('link', { name: /Créer|Create/i });
  }

  async navigateToAgenda() {
    await this.goto('/agenda.html');
    await this.waitForLoadState();
  }

  async getEventCount(): Promise<number> {
    return await this.eventsList.count();
  }
}

export class ArticlesPage extends BasePage {
  get articlesList(): Locator {
    return this.page.locator('a[href*="/article/"]');
  }

  get createArticleButton(): Locator {
    return this.page.getByRole('link', { name: /rédiger|write|create/i });
  }

  async getArticleCount(): Promise<number> {
    return await this.articlesList.count();
  }
}
```

### helpers/data-fixtures.ts

```typescript
export const TEST_USERS = {
  admin: {
    email: process.env.TEST_ADMIN_EMAIL || 'admin@test-clubalpinlyon.fr',
    password: process.env.TEST_ADMIN_PASSWORD || 'test',
    role: 'ROLE_ADMIN',
  },
  encadrant: {
    email: process.env.TEST_ENCADRANT_EMAIL || 'encadrant@test-clubalpinlyon.fr',
    password: process.env.TEST_ENCADRANT_PASSWORD || 'test',
    role: 'ROLE_ENCADRANT',
  },
  user: {
    email: process.env.TEST_USER_EMAIL || 'user@test-clubalpinlyon.fr',
    password: process.env.TEST_USER_PASSWORD || 'test',
    role: 'ROLE_USER',
  },
};

export const TEST_DATA = {
  event: {
    title: `Test Event ${Date.now()}`,
    description: 'Test event description',
    maxParticipants: 15,
    location: 'Test Location',
  },
  article: {
    title: `Test Article ${Date.now()}`,
    content: 'Test article content',
  },
  expense: {
    amount: 50,
    description: 'Test expense',
  },
};

export function generateUniqueTitle(prefix: string = 'test'): string {
  return `${prefix} ${Date.now()}`;
}

export function generateFutureDate(daysFromNow: number = 7): Date {
  const date = new Date();
  date.setDate(date.getDate() + daysFromNow);
  return date;
}

export function formatDateTimeLocal(date: Date): string {
  const yyyy = date.getFullYear();
  const mm = String(date.getMonth() + 1).padStart(2, '0');
  const dd = String(date.getDate()).padStart(2, '0');
  const hh = String(date.getHours()).padStart(2, '0');
  const min = String(date.getMinutes()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}T${hh}:${min}`;
}
```

---

## <a name="patterns"></a>🎯 Patterns et Bonnes Pratiques

### 1. Utiliser les roles pour l'accessibilité

✅ **BON** - Utiliser les roles
```typescript
await page.getByRole('button', { name: /Enregistrer/i }).click();
await page.getByRole('link', { name: /Mon compte/i }).click();
await page.getByRole('textbox', { name: /Titre/i }).fill('test');
```

❌ **MAUVAIS** - Sélecteurs fragiles
```typescript
await page.locator('.btn-primary').click();
await page.click('a.user-menu');
await page.fill('input.title-field', 'test');
```

### 2. Attendre les éléments correctement

✅ **BON**
```typescript
await expect(page.locator('.success-message')).toBeVisible({ timeout: 5000 });
await page.waitForLoadState('networkidle');
const count = await page.locator('a[href*="/sortie/"]').count();
```

❌ **MAUVAIS**
```typescript
await page.waitForTimeout(1000); // Timeouts fixes = flaky tests
await page.click('.element'); // Pas d'attente d'apparition
```

### 3. Gestion des erreurs

✅ **BON**
```typescript
const errorMsg = page.locator('.alert-danger');
if (await errorMsg.count() > 0) {
  console.error('Error found:', await errorMsg.textContent());
}

try {
  await page.locator('#optional-element').click({ timeout: 2000 });
} catch (error) {
  console.log('Optional element not found, continuing...');
}
```

❌ **MAUVAIS**
```typescript
// Laisser les tests échouer sans message clair
await page.click('.element-that-might-not-exist');
```

### 4. Tests parallèles avec dépendances

```typescript
// Tests indépendants - peuvent être parallèles
test.describe('Smoke tests', () => {
  test.describe.configure({ mode: 'parallel' });
  
  test('page loads', async ({ page }) => { /* ... */ });
  test('navigation works', async ({ page }) => { /* ... */ });
});

// Tests dépendants - séquentiels
test.describe('Critical workflow', () => {
  test.describe.configure({ mode: 'serial' });
  
  test('1. Create event', async ({ page }) => { /* ... */ });
  test('2. Enroll participant', async ({ page }) => { /* ... */ });
  test('3. Unenroll participant', async ({ page }) => { /* ... */ });
});
```

### 5. Fixtures personnalisées

```typescript
import { test as base } from '@playwright/test';
import { LoginPage } from './helpers/page-objects';

type TestFixtures = {
  loggedInPage: Page;
  loginPage: LoginPage;
};

export const test = base.extend<TestFixtures>({
  loggedInPage: async ({ page }, use) => {
    const loginPage = new LoginPage(page);
    await loginPage.login('admin@test-clubalpinlyon.fr', 'test');
    await use(page);
  },
  
  loginPage: async ({ page }, use) => {
    await use(new LoginPage(page));
  },
});

export { expect } from '@playwright/test';

// Utilisation
test('admin sees admin panel', async ({ loggedInPage }) => {
  await loggedInPage.goto('/admin');
  // Déjà connecté!
});
```

### 6. Assertions explicites

```typescript
// ✅ BON - Messages clairs
await expect(page).toHaveURL(/\/profil\/articles/);
await expect(page.getByText('Article créé')).toBeVisible();
await expect(page.locator('[data-testid="participant-count"]'))
  .toHaveText(/9 participants/);

// ❌ MAUVAIS - Pas assez d'info
expect(await page.locator('.success').isVisible()).toBeTruthy();
```

---

## <a name="cicd"></a>🚀 Intégration CI/CD

### GitHub Actions Workflow

```yaml
# .github/workflows/e2e-tests.yml
name: E2E Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]
  schedule:
    - cron: '0 2 * * *'  # Chaque nuit à 2h

jobs:
  e2e:
    timeout-minutes: 60
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        browser: [chromium, firefox]
        shardIndex: [1, 2, 3]
        shardTotal: [3]

    steps:
      - uses: actions/checkout@v4
      
      - name: Install dependencies
        run: npm ci && npx playwright install --with-deps
      
      - name: Setup database
        run: |
          npm run db:create
          npm run db:migrate:test
          npm run db:fixtures:load
      
      - name: Start server
        run: npm run dev &
        env:
          APP_ENV: test
      
      - name: Wait for server
        run: npx wait-on http://localhost:8000 --timeout 60000
      
      - name: Run E2E tests
        run: npx playwright test --shard=${{ matrix.shardIndex }}/${{ matrix.shardTotal }}
        env:
          BASE_URL: http://localhost:8000
          PLAYWRIGHT_BROWSERS: ${{ matrix.browser }}
      
      - name: Upload results
        if: always()
        uses: actions/upload-artifact@v4
        with:
          name: playwright-report-${{ matrix.browser }}-${{ matrix.shardIndex }}
          path: playwright-report/
          retention-days: 30
      
      - name: Upload test results
        if: always()
        uses: actions/upload-artifact@v4
        with:
          name: test-results-${{ matrix.browser }}-${{ matrix.shardIndex }}
          path: test-results/

  slack-notification:
    if: always()
    needs: e2e
    runs-on: ubuntu-latest
    steps:
      - name: Send Slack notification
        uses: slackapi/slack-github-action@v1
        with:
          payload: |
            {
              "text": "E2E Tests: ${{ job.status }}",
              "blocks": [
                {
                  "type": "section",
                  "text": {
                    "type": "mrkdwn",
                    "text": "E2E Tests: *${{ needs.e2e.result }}*"
                  }
                }
              ]
            }
        env:
          SLACK_WEBHOOK_URL: ${{ secrets.SLACK_WEBHOOK }}
```

### Package.json Scripts

```json
{
  "scripts": {
    "test:e2e": "playwright test",
    "test:e2e:debug": "playwright test --debug",
    "test:e2e:ui": "playwright test --ui",
    "test:e2e:headed": "playwright test --headed",
    "test:e2e:smoke": "playwright test smoke",
    "test:e2e:critical": "playwright test spec/events.spec.ts spec/articles.spec.ts",
    "test:e2e:single": "playwright test --grep",
    "test:e2e:report": "playwright show-report",
    "test:db:reset": "npm run db:drop && npm run db:create && npm run db:migrate:test",
    "test:setup": "npm run test:db:reset && npm run db:fixtures:load"
  }
}
```

---

## <a name="rapports"></a>📊 Rapports et Monitoring

### Interprétation du HTML Report

```bash
# Générer et ouvrir le rapport
npx playwright show-report

# Rapport détaillé par test
# - Traces vidéo
# - Screenshots de l'erreur
# - Timeline des actions
# - Console logs
```

### Analyse des résultats

```typescript
// test-results/analyze-results.js
const fs = require('fs');
const results = JSON.parse(fs.readFileSync('test-results/results.json', 'utf8'));

const summary = {
  total: results.stats.expected + results.stats.unexpected,
  passed: results.stats.expected,
  failed: results.stats.unexpected,
  skipped: results.stats.skipped,
  duration: results.stats.duration,
};

console.log('Test Results:');
console.log(`  Total: ${summary.total}`);
console.log(`  Passed: ${summary.passed} ✅`);
console.log(`  Failed: ${summary.failed} ❌`);
console.log(`  Skipped: ${summary.skipped} ⏭️`);
console.log(`  Duration: ${(summary.duration / 1000).toFixed(2)}s`);

if (summary.failed > 0) {
  console.log('\nFailed tests:');
  results.suites.forEach(suite => {
    suite.tests?.forEach(test => {
      if (test.status === 'failed') {
        console.log(`  - ${suite.title} > ${test.title}`);
        console.log(`    Error: ${test.error.message}`);
      }
    });
  });
  process.exit(1);
}
```

### Dashboard de monitoring

```typescript
// test-results/dashboard.html - Visualisation simple
import { readFileSync } from 'fs';

const results = JSON.parse(readFileSync('test-results/results.json'));
const passRate = (results.stats.expected / (results.stats.expected + results.stats.unexpected) * 100).toFixed(1);

console.log(`
┌─────────────────────────────────┐
│  Test Results Dashboard         │
├─────────────────────────────────┤
│ Pass Rate:  ${passRate}%
│ Duration:   ${(results.stats.duration / 1000).toFixed(2)}s
│ Browsers:   ${new Set(results.suites.map(s => s.tests?.[0]?.projectName)).size}
└─────────────────────────────────┘
`);
```

---

## 📈 Recommandations de Couverture

### Par priorité

| Priorité | Domaine | Tests | Couverture |
|:---:|---------|-------|:---:|
| 🔴 CRITIQUE | Auth + Sessions | 5 | 100% |
| 🔴 CRITIQUE | Sorties (workflow complet) | 15 | 90% |
| 🔴 CRITIQUE | Articles (publi/validation) | 12 | 90% |
| 🟠 HAUTE | Notes de frais | 8 | 85% |
| 🟠 HAUTE | Utilisateurs + Rôles | 8 | 80% |
| 🟡 MOYENNE | Recherche | 5 | 70% |
| 🟡 MOYENNE | Notifications | 5 | 60% |
| 🟢 BASSE | UX / Cosmétique | 3 | 40% |

### Cible par type

- **Smoke tests** : 10-15 tests
- **Fonctionnels** : 60-80 tests
- **API** : 20-30 tests
- **Performance** : 5-10 tests
- **Sécurité** : 10-15 tests

**Total recommandé : 100-150 tests**

---

## 🔍 Dépannage Courant

### Tests flaky (instables)

**Problème** : Test réussit parfois, échoue parfois

**Solutions** :
```typescript
// ❌ Causes courantes
await page.waitForTimeout(1000);  // Timeouts fixes
await page.click(selector);        // Pas d'attente
await expect(element).toBeVisible();  // Timeout court

// ✅ Solutions
await page.waitForLoadState('networkidle');
await element.waitFor({ state: 'visible' });
await expect(element).toBeVisible({ timeout: 10000 });
await element.click({ waitForElementStable: true });
```

### Erreur d'authentification

```typescript
// Vérifier les credentials
// Vérifier la session en cookie
// Vérifier les headers CSRF
await page.context().addCookies([...]);
```

### Performance lente

```typescript
// Augmenter timeout
expect.setDefaultTimeout(30000);

// Paralléliser moins
workers: 1  // ou 2

// Profiler
await page.context().tracing.start({ screenshots: true, snapshots: true });
```

---

## 📚 Ressources

- [Playwright Docs](https://playwright.dev)
- [Best Practices](https://playwright.dev/docs/best-practices)
- [Debugging](https://playwright.dev/docs/debug)
- [CI/CD Guides](https://playwright.dev/docs/ci)

---

*Dernière mise à jour : 12 janvier 2026*
