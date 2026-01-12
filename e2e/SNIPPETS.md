# 📌 Snippets Playwright Prêts à Utiliser

Ce fichier contient des **snippets réutilisables** que vous pouvez copier-coller directement dans vos tests.

---

## 🔐 AUTHENTIFICATION

### Fonction Login Réutilisable
```typescript
// helpers/auth.ts
import { Page } from '@playwright/test';

export async function login(
  page: Page,
  email: string,
  password: string
): Promise<void> {
  await page.goto('/login');
  await page.fill('input[name="email"], input[type="email"]', email);
  await page.fill('input[name="password"], input[type="password"]', password);
  await page.click('button[type="submit"]');
  await page.waitForNavigation({ waitUntil: 'networkidle' });
}

export async function logout(page: Page): Promise<void> {
  await page.locator('#toolbar-user').hover();
  await page.getByRole('link', { name: /Déconnexion|Logout/i }).click();
  await page.waitForNavigation();
}

export async function isLoggedIn(page: Page): Promise<boolean> {
  try {
    await page.locator('#toolbar-user').waitFor({ timeout: 2000 });
    return true;
  } catch {
    return false;
  }
}
```

### Test Login Simple
```typescript
test('Login avec credentials valides', async ({ page }) => {
  await login(page, 'user@test.fr', 'password123');
  
  // Vérifier que logged in
  expect(await isLoggedIn(page)).toBeTruthy();
});
```

---

## 📰 ARTICLES

### Créer un Article avec Upload Image
```typescript
test('Créer un article avec image', async ({ page }) => {
  await login(page, 'redacteur@test.fr', 'test');
  
  // Naviguer vers création
  await page.locator('#toolbar-user').hover();
  await page.getByRole('link', { name: /rédiger/i }).click();
  
  // Sélectionner commission
  await page.locator('#article_commission').selectOption({ index: 1 });
  
  // Type d'article
  await page.locator('input[value="article"]').check();
  
  // Remplir formulaire
  const title = `Article ${Date.now()}`;
  await page.locator('#article_titre').fill(title);
  
  // Upload image
  const fileInput = page.locator('input[type="file"]').first();
  if (await fileInput.count() > 0) {
    await fileInput.setInputFiles('./test-image.jpg');
  }
  
  // Cocher cases obligatoires
  await page.locator('#article_agreeEdito').check();
  await page.locator('#article_imagesAuthorized').check();
  
  // Remplir contenu
  await page.locator('#article_cont').fill('Contenu du test');
  
  // Soumettre
  await page.getByRole('button', { 
    name: /ENREGISTRER ET DEMANDER LA PUBLICATION/i 
  }).click();
  
  await expect(page).toHaveURL(/\/profil\/articles/);
  await expect(page.locator('.flash-success')).toBeVisible();
});
```

### Valider un Article (Modérateur)
```typescript
test('Valider et publier un article', async ({ page }) => {
  await login(page, 'modérateur@test.fr', 'test');
  
  // Aller à la modération
  await page.goto('/gestion-des-articles.html');
  
  // Trouver le premier article à valider
  const validateBtn = page.locator(
    'input[type="submit"][value="Autoriser & publier"]'
  ).first();
  
  await expect(validateBtn).toBeVisible();
  await validateBtn.click();
  
  await page.waitForLoadState('networkidle');
  await expect(page.getByText(/succès|success/i)).toBeVisible();
});
```

---

## 🏔️ SORTIES (ÉVÉNEMENTS)

### Créer une Sortie Complète
```typescript
test('Créer une sortie complet', async ({ page }) => {
  await login(page, 'encadrant@test.fr', 'test');
  
  // Date pour la sortie (7 jours à partir de maintenant)
  const eventDate = new Date();
  eventDate.setDate(eventDate.getDate() + 7);
  eventDate.setHours(8, 0, 0, 0);
  
  const dateTimeString = formatToDateTime(eventDate);
  
  // Naviguer vers création
  await page.locator('#toolbar-user').hover();
  await page.getByRole('link', { name: /proposer une sortie/i }).click();
  await page.getByRole('link', { name: /Sorties familles/i }).click();
  
  // Remplir les champs
  await page.getByLabel('Titre').fill(`Sortie Test ${Date.now()}`);
  
  // Sélectionner encadrant
  await page.locator('input[name$="[encadrants][]"]').first().check();
  
  // Lieu de RDV
  const rdvInput = page.getByLabel(/Lieu de rendez-vous/i);
  if (await rdvInput.count() > 0) {
    await rdvInput.fill('Bron');
  }
  
  // Placer sur la carte
  const mapBtn = page.locator('#codeAddress');
  if (await mapBtn.count() > 0) {
    await mapBtn.click();
    await page.waitForTimeout(1000);
  }
  
  // Date/heure RDV
  await page.getByLabel(/Date et heure de RDV/i).fill(dateTimeString);
  
  // Nombre de participants
  await page.getByLabel(/Nombre maximum/i).fill('15');
  
  // Inscriptions
  await page.getByLabel(/démarrent le/i).fill(dateTimeString);
  await page.getByLabel(/inscriptions maximum/i).fill('15');
  
  // Description
  await page.getByLabel(/Description/i).first().fill('Sortie test');
  
  // Cases obligatoires
  await page.locator('input[name$="[imagesAuthorized]"]').check();
  await page.locator('input[name$="[agreeEdito]"]').check();
  
  // Soumettre
  await page.getByRole('button', { 
    name: /ENREGISTRER ET DEMANDER LA PUBLICATION/i 
  }).click();
  
  await page.waitForNavigation();
  await expect(page).toHaveURL(/\/sortie\/|\/evt\//);
});

// Helper pour formater date
function formatToDateTime(date: Date): string {
  const yyyy = date.getFullYear();
  const mm = String(date.getMonth() + 1).padStart(2, '0');
  const dd = String(date.getDate()).padStart(2, '0');
  const hh = String(date.getHours()).padStart(2, '0');
  const min = String(date.getMinutes()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}T${hh}:${min}`;
}
```

### S'Inscrire à une Sortie
```typescript
test('S\'inscrire à une sortie', async ({ page }) => {
  await login(page, 'user@test.fr', 'test');
  
  // Aller à l'agenda
  await page.goto('/agenda.html');
  
  // Trouver la première sortie
  const eventLink = page.locator('a[href*="/sortie/"], a[href*="/evt/"]').first();
  await eventLink.click();
  
  // S'inscrire
  const enrollBtn = page.getByRole('button', { name: /S\'inscrire|Enroll/i });
  
  if (await enrollBtn.count() > 0) {
    await enrollBtn.click();
    
    // Confirmer si popup
    const confirmBtn = page.getByRole('button', { name: /Confirmer|Confirm/i });
    if (await confirmBtn.count() > 0) {
      await confirmBtn.click();
    }
    
    await expect(page.locator('.flash-success')).toBeVisible();
  }
});
```

### Se Désinscrire d'une Sortie
```typescript
test('Se désinscrire d\'une sortie', async ({ page }) => {
  await login(page, 'user@test.fr', 'test');
  
  // Mes sorties
  await page.locator('#toolbar-user').hover();
  await page.getByRole('link', { name: /Mes sorties/i }).click();
  
  // Se désinscrire
  const unrollBtn = page.getByRole('button', { name: /désinscrire/i }).first();
  
  if (await unrollBtn.count() > 0) {
    await unrollBtn.click();
    
    const confirmBtn = page.getByRole('button', { name: /Confirmer/i });
    if (await confirmBtn.count() > 0) {
      await confirmBtn.click();
    }
    
    await expect(page.locator('.flash-success')).toBeVisible();
  }
});
```

---

## 💰 NOTES DE FRAIS

### Créer une Note de Frais
```typescript
test('Créer une note de frais', async ({ page }) => {
  await login(page, 'encadrant@test.fr', 'test');
  
  // Aller à une sortie
  await page.goto('/agenda.html');
  const eventLink = page.locator('a[href*="/sortie/"]').first();
  await eventLink.click();
  
  // Ajouter note de frais
  const addBtn = page.getByRole('button', { name: /note|expense/i });
  if (await addBtn.count() > 0) {
    await addBtn.click();
    await page.waitForLoadState('networkidle');
    
    // Remplir montant
    const amountInput = page.locator('input[type="number"]').first();
    if (await amountInput.count() > 0) {
      await amountInput.fill('50');
    }
    
    // Ajouter description
    const descInput = page.locator('textarea').first();
    if (await descInput.count() > 0) {
      await descInput.fill('Carburant pour la sortie');
    }
    
    // Soumettre
    await page.getByRole('button', { name: /Créer|Create/i }).click();
    
    await expect(page.locator('.flash-success')).toBeVisible();
  }
});
```

### Soumettre une Note de Frais
```typescript
test('Soumettre une note de frais', async ({ page }) => {
  await login(page, 'encadrant@test.fr', 'test');
  
  // Mes notes
  await page.locator('#toolbar-user').hover();
  await page.getByRole('link', { name: /notes|expense/i }).click();
  
  // Ouvrir une note brouillon
  const draftLink = page.locator('a:has-text("Brouillon")').first();
  if (await draftLink.count() > 0) {
    await draftLink.click();
    
    // Soumettre
    const submitBtn = page.getByRole('button', { name: /Soumettre|Submit/i });
    if (await submitBtn.count() > 0) {
      await submitBtn.click();
      
      const confirmBtn = page.getByRole('button', { name: /Confirmer/i });
      if (await confirmBtn.count() > 0) {
        await confirmBtn.click();
      }
      
      await expect(page.locator('.flash-success')).toBeVisible();
    }
  }
});
```

---

## 👥 GESTION UTILISATEURS

### Créer un Utilisateur (Admin)
```typescript
test('Créer un nouvel utilisateur', async ({ page }) => {
  await login(page, 'admin@test.fr', 'test');
  
  // Aller à gestion utilisateurs
  await page.goto('/admin/');
  await page.getByRole('link', { name: /utilisateurs|users/i }).click();
  
  // Ajouter utilisateur
  const addBtn = page.getByRole('button', { name: /Ajouter|Add/i });
  if (await addBtn.count() > 0) {
    await addBtn.click();
    await page.waitForLoadState('networkidle');
    
    // Remplir formulaire
    const newEmail = `user-${Date.now()}@test.fr`;
    await page.fill('input[type="email"]', newEmail);
    await page.fill('input[name*="first"], input[name*="firstname"]', 'Test');
    await page.fill('input[name*="last"], input[name*="lastname"]', 'User');
    
    // Sélectionner rôle
    const roleSelect = page.locator('select[name*="role"]');
    if (await roleSelect.count() > 0) {
      await roleSelect.selectOption('ROLE_ENCADRANT');
    }
    
    // Soumettre
    await page.getByRole('button', { name: /Créer|Create|Enregistrer/i }).click();
    
    await expect(page.locator('.flash-success')).toBeVisible();
  }
});
```

### Modifier Rôles d'un Utilisateur
```typescript
test('Modifier les rôles d\'un utilisateur', async ({ page }) => {
  await login(page, 'admin@test.fr', 'test');
  
  // Aller à utilisateurs
  await page.goto('/admin/users');
  
  // Trouver et cliquer sur un utilisateur
  const userLink = page.locator('a[href*="/admin/user/"]').first();
  if (await userLink.count() > 0) {
    await userLink.click();
    await page.waitForLoadState('networkidle');
    
    // Cocher/décocher rôles
    const roleCheckbox = page.locator('input[type="checkbox"][value="ROLE_ENCADRANT"]');
    if (await roleCheckbox.count() > 0) {
      if (!await roleCheckbox.isChecked()) {
        await roleCheckbox.check();
      }
    }
    
    // Sauvegarder
    await page.getByRole('button', { name: /Enregistrer|Save/i }).click();
    
    await expect(page.locator('.flash-success')).toBeVisible();
  }
});
```

---

## 🔍 RECHERCHE

### Rechercher une Sortie
```typescript
test('Rechercher une sortie', async ({ page }) => {
  await page.goto('/agenda.html');
  
  // Utiliser filtre de commission
  const commissionSelect = page.locator('select[name*="commission"]');
  if (await commissionSelect.count() > 0) {
    await commissionSelect.selectOption({ index: 1 });
  }
  
  // Trier par date
  const sortSelect = page.locator('select[name*="sort"]');
  if (await sortSelect.count() > 0) {
    await sortSelect.selectOption('date_asc');
  }
  
  // Vérifier résultats
  const eventsList = page.locator('a[href*="/sortie/"]');
  expect(await eventsList.count()).toBeGreaterThan(0);
});
```

### Rechercher un Article
```typescript
test('Rechercher un article', async ({ page }) => {
  await page.goto('/articles.html');
  
  // Chercher mot-clé
  const searchInput = page.locator('input[type="search"]');
  if (await searchInput.count() > 0) {
    await searchInput.fill('alpinisme');
    await page.keyboard.press('Enter');
    await page.waitForLoadState('networkidle');
  }
  
  // Vérifier résultats
  const articles = page.locator('a[href*="/article/"]');
  expect(await articles.count()).toBeGreaterThan(0);
});
```

---

## 🔒 SÉCURITÉ

### Vérifier Refus d'Accès (403)
```typescript
test('Refuser accès avec rôle insuffisant', async ({ page }) => {
  // Login avec rôle limité
  await login(page, 'user@test.fr', 'test');
  
  // Tenter accès admin
  await page.goto('/admin/', { waitUntil: 'domcontentloaded' });
  
  // Vérifier pas d'accès
  const adminPanel = page.locator('[role="main"]');
  expect(page.url()).not.toMatch(/\/admin\/$/);
});
```

### Vérifier Validation CSRF
```typescript
test('Token CSRF présent dans formulaires', async ({ page }) => {
  await page.goto('/login');
  
  // Vérifier présence du token
  const csrfToken = page.locator('input[name="_token"], input[name="_csrf_token"]');
  await expect(csrfToken).toBeVisible();
  
  // Vérifier qu'il a une valeur
  const tokenValue = await csrfToken.inputValue();
  expect(tokenValue?.length).toBeGreaterThan(0);
});
```

---

## ✅ SMOKE TESTS

### Vérifier Page Charge
```typescript
test('Page d\'accueil charge correctement', async ({ page }) => {
  await page.goto('/');
  
  // Vérifier titre
  await expect(page).toHaveTitle(/.+/);
  
  // Vérifier navigation
  const nav = page.locator('nav, header, [role="navigation"]');
  await expect(nav).toBeVisible();
  
  // Vérifier contenu principal
  const main = page.locator('main, [role="main"]');
  expect(await main.count()).toBeGreaterThan(0);
});
```

### Vérifier Menu Navigation
```typescript
test('Menu principal fonctionne', async ({ page }) => {
  await page.goto('/');
  
  // Cliquer premier lien du menu
  const navLinks = page.locator('nav a, header a');
  const link = navLinks.first();
  
  const href = await link.getAttribute('href');
  if (href && !href.includes('http')) {
    await link.click();
    
    // Vérifier navigation
    expect(page.url()).not.toBe('http://localhost:8000/');
  }
});
```

### Vérifier Footer Contact
```typescript
test('Footer contient infos de contact', async ({ page }) => {
  await page.goto('/');
  
  const footer = page.locator('footer, [role="contentinfo"]');
  await expect(footer).toBeVisible();
  
  // Vérifier email ou téléphone
  const contact = page.locator('footer a[href*="mailto:"], footer a[href*="tel:"]');
  expect(await contact.count()).toBeGreaterThan(0);
});
```

---

## 📊 API TESTS

### Tester Endpoint GET
```typescript
test('API GET /sorties retourne données', async ({ page }) => {
  const response = await page.request.get('/api/sorties');
  
  expect(response.ok()).toBeTruthy();
  
  const data = await response.json();
  expect(data['hydra:member'] || data.data || data).toBeDefined();
});
```

### Tester Endpoint POST
```typescript
test('Créer note via API', async ({ page }) => {
  const response = await page.request.post('/api/notes-de-frais', {
    data: {
      event: 123,
      amount: 50,
      description: 'Test expense'
    }
  });
  
  expect(response.status()).toBe(201);
  
  const data = await response.json();
  expect(data.id).toBeDefined();
});
```

---

## 🛠️ HELPERS UTILES

### Attendre et Cliquer
```typescript
async function waitAndClick(page: Page, selector: string) {
  await page.waitForSelector(selector);
  await page.click(selector);
}

// Utilisation
await waitAndClick(page, '.btn-submit');
```

### Remplir Formulaire CKEditor
```typescript
async function fillCKEditor(page: Page, content: string) {
  // Remplir textarea
  await page.locator('.ck-editor__editable').fill(content);
  
  // Mettre à jour l'éditeur
  await page.evaluate((text) => {
    const editable = document.querySelector('.ck-editor__editable');
    if (editable) editable.innerHTML = `<p>${text}</p>`;
  }, content);
}

// Utilisation
await fillCKEditor(page, 'Mon contenu');
```

### Attendre Élément Stable
```typescript
async function waitForElementStable(page: Page, selector: string, timeout = 5000) {
  try {
    await page.locator(selector).waitFor({ state: 'visible', timeout });
    await page.locator(selector).evaluate(el => el.offsetHeight);
  } catch (error) {
    throw new Error(`Element ${selector} ne s'affiche pas`);
  }
}

// Utilisation
await waitForElementStable(page, '.modal');
```

### Vérifier Message de Succès
```typescript
async function expectSuccess(page: Page) {
  const successMsg = page.locator(
    '.flash-success, .alert-success, .toast-success, [role="alert"]:has-text("succès")'
  );
  
  await expect(successMsg).toBeVisible({ timeout: 5000 });
}

// Utilisation
await expectSuccess(page);
```

### Vérifier Message d'Erreur
```typescript
async function expectError(page: Page, errorText?: string) {
  const errorMsg = page.locator('.flash-danger, .alert-danger, .erreur');
  await expect(errorMsg).toBeVisible();
  
  if (errorText) {
    await expect(errorMsg).toContainText(errorText);
  }
}

// Utilisation
await expectError(page, 'Email invalide');
```

---

## 💾 Données de Test Réutilisables

### helpers/fixtures.ts
```typescript
export const TEST_DATA = {
  users: {
    admin: {
      email: 'admin@test-clubalpinlyon.fr',
      password: 'test',
      name: 'Admin Test'
    },
    encadrant: {
      email: 'encadrant@test-clubalpinlyon.fr',
      password: 'test',
      name: 'Encadrant Test'
    },
    user: {
      email: 'user@test-clubalpinlyon.fr',
      password: 'test',
      name: 'User Test'
    }
  },
  
  event: {
    title: () => `Test Event ${Date.now()}`,
    description: 'Description du test',
    location: 'Bron',
    maxParticipants: 15
  },
  
  article: {
    title: () => `Test Article ${Date.now()}`,
    content: 'Contenu test de article'
  },
  
  expense: {
    amount: 50,
    description: 'Dépense de test'
  }
};

// Utilisation
import { TEST_DATA } from './fixtures';
await login(page, TEST_DATA.users.admin.email, TEST_DATA.users.admin.password);
await page.fill('input[name="title"]', TEST_DATA.event.title());
```

---

**Vous pouvez copier ces snippets directement dans vos fichiers .spec.ts !**
