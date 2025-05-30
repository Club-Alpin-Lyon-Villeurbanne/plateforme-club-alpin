import { Page } from '@playwright/test';

export const login = async (page: Page, email: string, password: string) => {
  await page.goto('http://127.0.0.1:8000/');
  await page.locator('#toolbar-user').hover();
  await page.getByRole('textbox', { name: 'Votre e-mail' }).fill(email);
  await page.getByRole('textbox', { name: 'Votre mot de passe' }).fill(password);
  await page.getByRole('button', { name: 'Connexion' }).click();
  await page.waitForTimeout(1000);
}; 