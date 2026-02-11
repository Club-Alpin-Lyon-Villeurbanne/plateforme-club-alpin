import { test, expect } from '@playwright/test';
import { STORAGE_STATE } from './helpers/storage-state';
import { fillCKEditor } from './helpers/ckeditor';

test.use({ storageState: STORAGE_STATE.redacteur });

const CONTENU = 'test de contenu de page';

test("Creation d'un article", async ({ page }) => {
  await page.goto('/');
  await page.locator('#toolbar-user').hover();
  await page.getByRole('link', { name: /rédiger un article/ }).click();

  // Commission — pick first available option (not the placeholder)
  await page.locator('#article_commission').selectOption({ index: 1 });

  // Article type
  await page.locator('input[name="article[articleType]"][value="article"]').check();

  // Title
  await page.locator('#article_titre').fill('test e2e creation article ' + Date.now());

  // Required checkboxes
  await page.locator('#article_agreeEdito').check();
  await page.locator('#article_imagesAuthorized').check();

  // Content via CKEditor helper
  await fillCKEditor(page, CONTENU);

  // Submit
  const publishBtn = page.getByRole('button', { name: /ENREGISTRER ET DEMANDER LA PUBLICATION/i });
  await publishBtn.click();

  // Verify redirect to articles list with success flash
  await expect(page).toHaveURL(/\/profil\/articles\.html/);
  await expect(page.locator('.info, .flash-success, .flash--success, .alert-success')).toBeVisible();
});
