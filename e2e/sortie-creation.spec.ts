import { test, expect, Page } from '@playwright/test';

// Variables globales
const BASE_URL = 'http://127.0.0.1:8000/';
const USER_EMAIL = 'test@clubalpinlyon.fr';
const USER_PASSWORD = 'test';
const RDV_LIEU = 'bron';
const NGENS_MAX = '9';
const JOIN_START_DAYS = '9';
const JOIN_MAX = '9';
const HEURE = '08:00';
const CONTENU = 'test de contenu de page';
const TITRE = `test création de sortie ${Date.now()}`;
const DATE_SORTIE = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toLocaleDateString('fr-FR');

// Fonction utilitaire pour login
const login = async (page: Page) => {
  await page.goto(BASE_URL);
  await page.locator('#toolbar-user').hover();
  await page.getByRole('textbox', { name: 'Votre e-mail' }).click();
  await page.getByRole('textbox', { name: 'Votre e-mail' }).fill(USER_EMAIL);
  await page.getByRole('textbox', { name: 'Votre e-mail' }).press('Tab');
  await page.getByRole('textbox', { name: 'Votre mot de passe' }).fill(USER_PASSWORD);
  await page.getByRole('button', { name: 'Connexion' }).click();
  await page.waitForTimeout(1000);
};

// Fonction utilitaire pour créer une sortie
const creerSortie = async (page: Page) => {
  await page.locator('#toolbar-user').hover();
  await page.getByRole('link', { name: '• proposer une sortie' }).click();
  await page.getByRole('link', { name: '> Créer une sortie Sorties familles' }).click();

  await page.locator('input[name="titre_evt"]').fill(TITRE);
  await page.locator('#encadrant-1').check();
  await page.locator('input[name="rdv_evt"]').fill(RDV_LIEU);
  const codeAddressBtn = page.locator('input[name="codeAddress"][value="Placer le point sur la carte"]');
  await expect(codeAddressBtn).toBeVisible({ timeout: 2000 });
  await codeAddressBtn.click();
  await page.waitForTimeout(1000);
  await page.locator('input[name="tsp_evt_day"]').fill(DATE_SORTIE);
  await page.locator('input[name="tsp_evt_hour"]').fill(HEURE);
  await page.getByRole('button', { name: 'même jour ?' }).click();
  await page.locator('input[name="ngens_max_evt"]').fill(NGENS_MAX);
  await page.locator('input[name="join_start_evt_days"]').fill(JOIN_START_DAYS);
  await page.locator('input[name="join_max_evt"]').fill(JOIN_MAX);
  // Correction du typage de window.tinymce
  await page.evaluate((content) => {
    const w = window as typeof window & { tinymce?: any };
    if (typeof w.tinymce !== 'undefined') {
      const editor = w.tinymce.get('mce_editor_0');
      if (editor) {
        editor.setContent(content);
      }
    } else {
      throw new Error('TinyMCE n\'est pas disponible sur cette page');
    }
  }, CONTENU);
  await page.getByRole('button', { name: 'Enregistrer' }).click();
  await page.waitForTimeout(1000);
};

test('création de sortie famille', async ({ page }) => {
  await login(page);
  await creerSortie(page);
  await page.waitForTimeout(1000);

  // Vérifie qu'aucune erreur n'est visible sur la page
  const errorDiv = page.locator('.erreur');
  if (await errorDiv.isVisible()) {
    const errorText = await errorDiv.textContent();
    console.error('Erreur détectée sur la page:', errorText);
  }
  await expect(errorDiv).toBeHidden({ timeout: 1000 });

  await expect(page.locator('#agenda')).toContainText(TITRE);
});