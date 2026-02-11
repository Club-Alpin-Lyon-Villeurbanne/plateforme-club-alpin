import { test, expect } from '@playwright/test';
import { login } from './helpers/auth';

const TEST_USER_EMAIL = process.env.TEST_USER_ADMIN_EMAIL || 'admin@test-clubalpinlyon.fr';
const TEST_USER_PASSWORD = process.env.TEST_USER_ADMIN_PASSWORD || 'test';

test.describe('Authentification', () => {
  test('Connexion et accès au profil', async ({ page }) => {
    await login(page, TEST_USER_EMAIL, TEST_USER_PASSWORD);

    // Navigation vers le profil
    await page.locator('#toolbar-user').hover();
    await page.getByRole('link', { name: 'Mon compte' }).click();

    // Vérification accès au profil
    await expect(page.getByText('Votre pseudonyme :')).toBeVisible();
  });

  test('Connexion avec identifiants invalides', async ({ page }) => {
    await login(page, TEST_USER_EMAIL, 'mauvaismdp');

    // Doit rester sur la page de login
    await expect(page).toHaveURL(/login/);

    // Message d'erreur visible
    await expect(page.locator('.alert-danger, .erreur')).toBeVisible();
  });
});
