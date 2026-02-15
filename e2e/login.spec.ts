import { test, expect } from '@playwright/test';
import { loginViaUI } from './helpers/auth';

// No stored session — we are testing the login flow itself
test.use({ storageState: { cookies: [], origins: [] } });

const EMAIL = process.env.TEST_USER_ADMIN_EMAIL || 'admin@test-clubalpinlyon.fr';
const PASSWORD = process.env.TEST_USER_ADMIN_PASSWORD || 'test';

test.describe('Authentification', () => {
  test('Connexion et acces au profil', async ({ page }) => {
    await loginViaUI(page, EMAIL, PASSWORD);

    await page.locator('#toolbar-user').hover();
    await page.getByRole('link', { name: 'Mon compte' }).click();

    await expect(page.getByText('Votre pseudonyme :')).toBeVisible();
  });

  test('Connexion avec identifiants invalides', async ({ page }) => {
    await loginViaUI(page, EMAIL, 'mauvaismdp');

    await expect(page).toHaveURL(/login/);
    await expect(page.locator('.alert-danger, .erreur')).toBeVisible();
  });
});
