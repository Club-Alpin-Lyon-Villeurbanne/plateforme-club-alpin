import { test, expect, Page } from '@playwright/test';
import { login } from './helpers/auth';

// Variables globales
const BASE_URL = 'http://127.0.0.1:8000/';
const USER_EMAIL = 'encadrant@test-clubalpinlyon.fr';
const USER_PASSWORD = 'test';
const RDV_LIEU = 'bron';
const NGENS_MAX = '9';
const JOIN_START_DAYS = '9';
const JOIN_MAX = '9';
const HEURE = '08:00';
const CONTENU = 'test de contenu de page';
const TITRE = `test création de sortie ${Date.now()}`;
const DATE_SORTIE = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toLocaleDateString('fr-FR');

// Prepare an ISO datetime-local value for the sortie start (YYYY-MM-DDTHH:MM)
const START_DATETIME = (() => {
  const d = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000);
  d.setHours(8, 0, 0, 0);
  // build a local datetime string without seconds: YYYY-MM-DDTHH:MM
  const yyyy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  const hh = String(d.getHours()).padStart(2, '0');
  const min = String(d.getMinutes()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}T${hh}:${min}`;
})();

// Fonction utilitaire pour créer une sortie
const creerSortie = async (page: Page) => {
  await page.locator('#toolbar-user').hover();
  await page.getByRole('link', { name: '• proposer une sortie' }).click();
  await page.getByRole('link', { name: '> Créer une sortie Sorties familles' }).click();

  // Fill fields using Symfony form labels / names from EventType
  await page.getByLabel('Titre').fill(TITRE);

  // check first encadrant checkbox (name ends with [encadrants][] or similar)
  const encadrantCheckbox = page.locator('input[name$="[encadrants][]"]').first();
  if (await encadrantCheckbox.count() > 0) {
    await encadrantCheckbox.check();
  }

  // Lieu de rendez-vous
  const rdvField = page.getByLabel(/Lieu de rendez-vous covoiturage/i);
  if (await rdvField.count() > 0) {
    await rdvField.fill(RDV_LIEU);
  }

  // Place marker on the map (button with id #codeAddress)
  const codeAddressBtn = page.locator('#codeAddress');
  if (await codeAddressBtn.count() > 0) {
    await expect(codeAddressBtn).toBeVisible({ timeout: 2000 });
    await codeAddressBtn.click();
    await page.waitForTimeout(1000);
  }

  // Date/time fields — EventType uses a single datetime input for startDate
  const startDateField = page.getByLabel(/Date et heure de RDV/i);
  if (await startDateField.count() > 0) {
    await startDateField.fill(START_DATETIME);
  }

  // Number of participants
  const ngensField = page.getByLabel(/Nombre maximum de personnes/i);
  if (await ngensField.count() > 0) {
    await ngensField.fill(NGENS_MAX);
  }

  // Join start date — use same start date for simplicity
  const joinStartField = page.getByLabel(/Les inscriptions démarrent le/i);
  if (await joinStartField.count() > 0) {
    await joinStartField.fill(START_DATETIME);
  }

  // Join max
  const joinMaxField = page.getByLabel(/Inscriptions maximum via internet/i);
  if (await joinMaxField.count() > 0) {
    await joinMaxField.fill(JOIN_MAX);
  }

  // Description (CKEditor5) — fill underlying textarea and update editable area
  const descriptionField = page.getByLabel(/Description complète|Description/i).first();
  if (await descriptionField.count() > 0) {
    await descriptionField.fill(CONTENU);
    await page.evaluate((content) => {
      const editable = document.querySelector('.ck-editor__editable');
      if (editable) editable.innerHTML = '<p>' + content + '</p>';
    }, CONTENU);
  }

  // check required checkboxes (images and editorial agreement)
  const imagesCheckbox = page.locator('input[name$="[imagesAuthorized]"]');
  if (await imagesCheckbox.count() > 0) await imagesCheckbox.check();
  const agreeCheckbox = page.locator('input[name$="[agreeEdito]"]');
  if (await agreeCheckbox.count() > 0) await agreeCheckbox.check();

  // submit using the publish button
  const publishBtn = page.getByRole('button', { name: /ENREGISTRER ET DEMANDER LA PUBLICATION/i });
  await expect(publishBtn).toBeVisible({ timeout: 2000 });
  await publishBtn.click();
  await page.waitForTimeout(1000);
};

test('création de sortie famille', async ({ page }) => {
  await login(page, USER_EMAIL, USER_PASSWORD);
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