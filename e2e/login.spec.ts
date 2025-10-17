import { test, expect } from '@playwright/test';
import { login } from './helpers/auth';

test('Authentification', async ({ page }) => {
  await login(page, 'admin@test-clubalpinlyon.fr', 'test');
  await page.locator('#toolbar-user').hover();
  await page.getByRole('link', { name: 'Mon compte' }).click();
  await expect(page.getByText('Votre pseudonyme :')).toBeVisible();
});