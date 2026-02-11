# Tests E2E avec Playwright

Tests end-to-end pour la plateforme Club Alpin Lyon. Ces tests se concentrent sur ce que les tests PHPUnit ne couvrent pas : interactions JavaScript (CKEditor5, maps), workflows multi-pages, et enchainements multi-roles.

## Setup local

```bash
# 1. L'app doit tourner en mode dev avec la base initialisee
make init
make database-init

# 2. Installer les deps Node et Playwright
npm install
npx playwright install chromium

# 3. Copier la config de test
cp .env.test.example .env.test

# 4. Lancer les tests
npm run test:e2e
```

## Lancer les tests

```bash
npm run test:e2e            # Headless (CI)
npm run test:e2e:headed     # Voir le navigateur
npm run test:e2e:ui         # Interface Playwright
npm run test:e2e:debug      # Mode debug pas-a-pas
npm run test:e2e:report     # Ouvrir le dernier rapport HTML
```

Ou via Make :

```bash
make test-e2e
make test-e2e-headed
make test-e2e-report
```

## Architecture

```
e2e/
├── auth.setup.ts            # Setup : authentifie chaque role et sauvegarde les sessions
├── .auth/                   # Sessions sauvegardees (gitignored)
│   ├── admin.json
│   ├── redacteur.json
│   ├── encadrant.json
│   └── resp-comm.json
├── helpers/
│   ├── auth.ts              # loginViaUI() — uniquement pour le test de login
│   ├── ckeditor.ts          # fillCKEditor() — saisie realiste dans CKEditor5
│   └── storage-state.ts     # Chemins STORAGE_STATE (importe par les specs)
├── login.spec.ts            # Tests de connexion (2 tests)
├── article-creation.spec.ts # Creation d'article
├── article-publication.spec.ts # Workflow creation + moderation (multi-roles)
├── sortie-creation.spec.ts  # Creation de sortie famille
└── README.md
```

### Authentification via storageState

Les tests n'appellent pas `login()` a chaque fois. Le projet `auth-setup` se connecte une fois pour chaque role et sauvegarde les cookies dans `e2e/.auth/`. Chaque spec declare son role :

```typescript
import { STORAGE_STATE } from './helpers/storage-state';
test.use({ storageState: STORAGE_STATE.redacteur });
```

Exception : `login.spec.ts` utilise `storageState: { cookies: [], origins: [] }` car il teste le formulaire de login lui-meme.

## Ecrire un nouveau test

```typescript
import { test, expect } from '@playwright/test';
import { STORAGE_STATE } from './helpers/storage-state';

test.use({ storageState: STORAGE_STATE.admin });

test('description du test', async ({ page }) => {
  await page.goto('/ma-page');
  // ...assertions...
});
```

Regles :
- Pas de `waitForTimeout()` — utiliser des attentes explicites (`waitFor()`, `toBeVisible()`, etc.)
- Pas d'URL hardcodees — `page.goto('/')` utilise le `baseURL` du config
- Pas de credentials dans le code — utiliser `storageState`
- Un test doit etre independant : il cree ses propres donnees et ne depend pas de l'ordre d'execution

## Debugging

- `await page.pause()` pour un breakpoint interactif
- `--debug` pour le mode pas-a-pas
- Screenshots et videos sont generes automatiquement en cas d'echec

## Documentation

- [Playwright Documentation](https://playwright.dev/)
- [Best Practices](https://playwright.dev/docs/best-practices)
