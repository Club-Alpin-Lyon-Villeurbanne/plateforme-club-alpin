import { test, expect } from '@playwright/test';
import { STORAGE_STATE } from './helpers/storage-state';
import { fillCKEditor } from './helpers/ckeditor';

test.use({ storageState: STORAGE_STATE.encadrant });

function futureDatetime(daysFromNow: number, hour = 8, minute = 0): string {
  const d = new Date(Date.now() + daysFromNow * 24 * 60 * 60 * 1000);
  d.setHours(hour, minute, 0, 0);
  const yyyy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  const hh = String(d.getHours()).padStart(2, '0');
  const min = String(d.getMinutes()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}T${hh}:${min}`;
}

const TITRE = `test creation de sortie ${Date.now()}`;
const START_DATETIME = futureDatetime(7, 8, 0);
const END_DATETIME = futureDatetime(7, 17, 0);
const CONTENU = 'test de contenu de page';

test('creation de sortie famille', async ({ page }) => {
  await page.goto('/creer-une-sortie?commission=sorties-familles');

  // Title
  await page.getByLabel('Titre').fill(TITRE);

  // Activity departure location (required)
  await page.getByLabel(/Lieu de départ/i).fill('69005 Lyon');

  // Check first encadrant checkbox
  const encadrantCheckbox = page.locator('input[name$="[encadrants][]"]').first();
  if (await encadrantCheckbox.count() > 0) {
    await encadrantCheckbox.check();
  }

  // Meeting point
  const rdvField = page.getByLabel(/Lieu de rendez-vous covoiturage/i);
  if (await rdvField.count() > 0) {
    await rdvField.fill('bron');
  }

  // Geocode — click button and wait for map marker to appear
  const codeAddressBtn = page.locator('#codeAddress');
  if (await codeAddressBtn.count() > 0) {
    await codeAddressBtn.click();
    // Wait for geocoding result (marker on map) with a graceful timeout
    await page.locator('.leaflet-marker-icon, .gm-style img[src*="marker"]')
      .first()
      .waitFor({ timeout: 5_000 })
      .catch(() => { /* geocoding may not be available in test env */ });
  }

  // Date/time (required)
  await page.getByLabel(/Date et heure de RDV/i).fill(START_DATETIME);
  await page.getByLabel(/Date et heure.*retour/i).fill(END_DATETIME);

  // Max participants (required)
  await page.getByLabel(/Nombre maximum de personnes/i).fill('9');

  // Registration start date
  const joinStartField = page.getByLabel(/Les inscriptions démarrent le/i);
  if (await joinStartField.count() > 0) {
    await joinStartField.fill(START_DATETIME);
  }

  // Description via CKEditor
  await fillCKEditor(page, CONTENU);

  // Required checkboxes
  await page.locator('input[name$="[imagesAuthorized]"]').check();
  await page.locator('input[name$="[agreeEdito]"]').check();

  // Submit
  const publishBtn = page.getByRole('button', { name: /ENREGISTRER ET DEMANDER LA PUBLICATION/i });
  await publishBtn.click();

  // Verify: no errors and the sortie appears in the agenda
  await expect(page.locator('.erreur')).toBeHidden({ timeout: 3_000 });
  await expect(page.locator('#agenda')).toContainText(TITRE);
});
