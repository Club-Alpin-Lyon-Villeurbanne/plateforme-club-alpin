import { test, expect } from '@playwright/test';
import { STORAGE_STATE } from './helpers/storage-state';
import { fillCKEditor } from './helpers/ckeditor';

test("Publication d'un article (creation + moderation)", async ({ browser }) => {
  const articleTitle = 'test e2e publication article ' + Date.now();

  // --- Step 1: Create an article as redacteur ---
  const redacteurContext = await browser.newContext({ storageState: STORAGE_STATE.redacteur });
  const redacteurPage = await redacteurContext.newPage();

  await redacteurPage.goto('/');
  await redacteurPage.locator('#toolbar-user').hover();
  await redacteurPage.getByRole('link', { name: /rédiger un article/ }).click();

  await redacteurPage.locator('#article_commission').selectOption({ index: 1 });
  await redacteurPage.locator('input[name="article[articleType]"][value="article"]').check();
  await redacteurPage.locator('#article_titre').fill(articleTitle);
  await redacteurPage.locator('#article_agreeEdito').check();
  await redacteurPage.locator('#article_imagesAuthorized').check();
  await fillCKEditor(redacteurPage, 'Contenu pour test de publication');

  await redacteurPage.getByRole('button', { name: /ENREGISTRER ET DEMANDER LA PUBLICATION/i }).click();
  await expect(redacteurPage).toHaveURL(/\/profil\/articles\.html/);

  await redacteurContext.close();

  // --- Step 2: Publish the article as admin (has article_validate_all) ---
  const adminContext = await browser.newContext({ storageState: STORAGE_STATE.admin });
  const adminPage = await adminContext.newPage();

  await adminPage.goto('/gestion-des-articles.html');

  const autoriserPublierBtn = adminPage
    .locator('input[type="submit"][value="Autoriser & publier"]')
    .first();
  await expect(autoriserPublierBtn).toBeVisible({ timeout: 5_000 });
  await autoriserPublierBtn.click();

  await expect(adminPage.getByText(/Opération effectuée avec succ/i)).toBeVisible({ timeout: 5_000 });

  await adminContext.close();
});
