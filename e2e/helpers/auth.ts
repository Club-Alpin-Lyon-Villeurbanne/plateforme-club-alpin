import { Page } from '@playwright/test';

/**
 * Login via the UI form. Only used in the login spec itself — other tests
 * should use storageState from auth.setup.ts instead.
 */
export const loginViaUI = async (page: Page, email: string, password: string) => {
  await page.goto('/login');
  await page.locator('input[name="_username"]').fill(email);
  await page.locator('input[name="_password"]').fill(password);
  await page.locator('input[name="connect-button"]').click();
  await page.waitForLoadState('networkidle');
};
