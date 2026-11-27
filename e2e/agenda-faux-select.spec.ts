import { test, expect, Page } from '@playwright/test';

// L'agenda est public : pas de session nécessaire
test.use({ storageState: { cookies: [], origins: [] } });

const selecteurCommission = (page: Page) => page.locator('.faux-select').nth(0);
const selecteurMois = (page: Page) => page.locator('.faux-select').nth(1);
const options = (page: Page, index: number) =>
  page.locator('.faux-select').nth(index).locator('a:not(.up)');

test.describe('Agenda sur mobile (écran tactile)', () => {
  test.use({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });

  test('Toucher le sélecteur de commission permet de choisir une commission', async ({ page }) => {
    await page.goto('/agenda.html?month=6&year=2026');

    await selecteurCommission(page).locator('a.up').tap();
    await expect(options(page, 0).first()).toBeVisible();

    // La liste reste ouverte le temps de toucher l'option
    // La première option du sélecteur de commission est la première commission (« Toutes les sorties » est le choix courant)
    await options(page, 0).first().tap();

    await expect(page).toHaveURL(/\/agenda\/[^/]+\.html\?month=6&year=2026$/);
  });

  test('Toucher le sélecteur de mois permet de choisir un mois', async ({ page }) => {
    await page.goto('/agenda.html?month=6&year=2026');

    await selecteurMois(page).locator('a.up').tap();
    const juillet = selecteurMois(page).getByRole('link', { name: /Juillet 2026/i });
    await expect(juillet).toBeVisible();
    await juillet.tap();

    await expect(page).toHaveURL(/agenda\.html\?month=7&year=2026$/);
  });

  test('Toucher en dehors du sélecteur referme la liste sans naviguer', async ({ page }) => {
    await page.goto('/agenda.html?month=6&year=2026');

    await selecteurCommission(page).locator('a.up').tap();
    await expect(options(page, 0).first()).toBeVisible();

    await page.locator('.agenda-date').tap();

    await expect(options(page, 0).first()).toBeHidden();
    await expect(page).toHaveURL(/agenda\.html\?month=6&year=2026$/);
  });
});

test.describe('Agenda sur ordinateur', () => {
  test('Le survol ouvre la liste et la sortie de la souris la referme', async ({ page }) => {
    await page.goto('/agenda.html?month=6&year=2026');

    await selecteurCommission(page).hover();
    await expect(options(page, 0).first()).toBeVisible();

    await page.locator('.agenda-date').hover();
    await expect(options(page, 0).first()).toBeHidden();
  });

  test('Le clavier ouvre la liste et Échap la referme', async ({ page }) => {
    await page.goto('/agenda.html?month=6&year=2026');

    await selecteurMois(page).locator('a.up').focus();
    const juillet = selecteurMois(page).getByRole('link', { name: /Juillet 2026/i });
    await expect(juillet).toBeVisible();

    await page.keyboard.press('Escape');
    await expect(juillet).toBeHidden();
    await expect(selecteurMois(page).locator('a.up')).toBeFocused();
  });
});
