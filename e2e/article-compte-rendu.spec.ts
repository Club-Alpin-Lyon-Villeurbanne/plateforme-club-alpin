import { test, expect, Page } from '@playwright/test';
import { STORAGE_STATE } from './helpers/storage-state';

test.use({ storageState: STORAGE_STATE.redacteur });

const blocSortieLiee = (page: Page) => page.locator('#sortie-liee');
const selectSortie = (page: Page) => page.locator('#sortie-liee select');
const messageCommission = (page: Page) => page.locator('#sortie-liee-commission-requise');

async function choisirCommission(page: Page) {
  const reponse = page.waitForResponse((r) => r.url().includes('/sorties-par-commission'));
  await page.locator('#article_commission').selectOption({ index: 1 });
  await reponse;
}

test('Compte rendu : type choisi avant la commission', async ({ page }) => {
  await page.goto('/article/new');
  await expect(blocSortieLiee(page)).toBeHidden();

  await page.locator('input[name="article[articleType]"][value="cr"]').check();
  await expect(messageCommission(page)).toBeVisible();
  await expect(selectSortie(page)).toBeHidden();

  await choisirCommission(page);
  await expect(messageCommission(page)).toBeHidden();
  await expect(selectSortie(page)).toBeVisible();
  await expect(selectSortie(page)).toHaveAttribute('required', '');
  await expect(selectSortie(page)).toHaveAttribute('name', 'form[evt]');
});

test('Compte rendu : commission choisie avant le type', async ({ page }) => {
  await page.goto('/article/new');

  await choisirCommission(page);
  await expect(blocSortieLiee(page)).toBeHidden();

  await page.locator('input[name="article[articleType]"][value="cr"]').check();
  await expect(selectSortie(page)).toBeVisible();
  await expect(selectSortie(page)).toHaveAttribute('required', '');

  // revenir à « un article » masque le champ et lève son caractère obligatoire
  await page.locator('input[name="article[articleType]"][value="article"]').check();
  await expect(blocSortieLiee(page)).toBeHidden();
  await expect(selectSortie(page)).not.toHaveAttribute('required', '');
});
