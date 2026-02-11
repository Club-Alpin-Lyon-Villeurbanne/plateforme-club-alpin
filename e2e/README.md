# Tests E2E avec Playwright

Tests end-to-end automatisés pour l'application Club Alpin Lyon.

## 🚀 Installation

```bash
# Installer les dépendances
npm install

# Installer les navigateurs Playwright
npx playwright install
```

## ⚙️ Configuration

1. Copier le fichier de configuration d'exemple :
```bash
cp .env.test.example .env.test
```

2. Ajuster les valeurs dans `.env.test` si nécessaire (URL, credentials)

## 🧪 Lancer les tests

```bash
# Tous les tests (mode headless)
npm run test:e2e

# Mode UI interactif (recommandé pour le développement)
npm run test:e2e:ui

# Mode headed (voir les navigateurs)
npm run test:e2e:headed

# Mode debug
npm run test:e2e:debug

# Voir le rapport
npm run test:e2e:report
```

## 📁 Structure

```
e2e/
├── helpers/
│   └── auth.ts                  # Helper pour l'authentification
├── login.spec.ts                # Tests de connexion (2 tests)
├── article-creation.spec.ts     # Tests création d'articles
├── article-publication.spec.ts  # Tests publication d'articles
├── sortie-creation.spec.ts      # Tests création de sorties
└── README.md
```

## ✅ Tests actuels

- **Authentification** (2 tests)
  - Connexion valide
  - Connexion avec identifiants invalides
- **Articles** (2 tests)
  - Création d'un article
  - Publication d'un article
- **Sorties** (1 test)
  - Création d'une sortie famille

## 🔜 Tests à venir

- Modération d'articles
- Modification/annulation de sorties
- Notes de frais
- Gestion des utilisateurs

## 📝 Écrire de nouveaux tests

Créer un nouveau fichier `*.spec.ts` dans le dossier `e2e/` :

```typescript
import { test, expect } from '@playwright/test';
import { login } from './helpers/auth';

test.describe('Nom du groupe', () => {
  test('Description du test', async ({ page }) => {
    await login(page, 'email@example.com', 'password');

    // Votre test ici
    await expect(page).toHaveURL(/expected-url/);
  });
});
```

## 🐛 Debugging

- Utiliser `await page.pause()` pour mettre un breakpoint
- Lancer avec `--debug` pour le mode pas à pas
- Screenshots et vidéos sont automatiquement générés en cas d'échec

## 📚 Documentation

- [Playwright Documentation](https://playwright.dev/)
- [Best Practices](https://playwright.dev/docs/best-practices)
- [API Reference](https://playwright.dev/docs/api/class-playwright)
