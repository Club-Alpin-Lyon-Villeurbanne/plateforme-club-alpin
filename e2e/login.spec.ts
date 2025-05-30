import { test, expect } from '@playwright/test';

test('test', async ({ page }) => {
  await page.goto('http://127.0.0.1:8000/');
  await page.locator('#toolbar-user').hover();
  await page.getByRole('textbox', { name: 'Votre e-mail' }).click();
  await page.getByRole('textbox', { name: 'Votre e-mail' }).fill('test@clubalpinlyon.fr');
  await page.getByRole('textbox', { name: 'Votre mot de passe' }).click();
  await page.getByRole('textbox', { name: 'Votre mot de passe' }).fill('test');
  await page.getByRole('button', { name: 'Connexion' }).click();
  await page.waitForTimeout(1000);
  await page.locator('#toolbar-user').hover();
  await page.getByRole('link', { name: 'Mon compte' }).click();
  await expect(page.getByText('Votre pseudonyme :')).toBeVisible();
});