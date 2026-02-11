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
const START_DATETIME = futureDatetime(7);
const CONTENU = 'test de contenu de page';

test('creation de sortie famille', async ({ page }) => {
  await page.goto('/creer-une-sortie?commission=sorties-familles');

  // Title
  await page.getByLabel('Titre').fill(TITRE);

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

  // Date/time
  const startDateField = page.getByLabel(/Date et heure de RDV/i);
  if (await startDateField.count() > 0) {
    await startDateField.fill(START_DATETIME);
  }

  // Max participants
  const ngensField = page.getByLabel(/Nombre maximum de personnes/i);
  if (await ngensField.count() > 0) {
    await ngensField.fill('9');
  }

  // Registration start date
  const joinStartField = page.getByLabel(/Les inscriptions démarrent le/i);
  if (await joinStartField.count() > 0) {
    await joinStartField.fill(START_DATETIME);
  }

  // Max online registrations
  const joinMaxField = page.getByLabel(/Inscriptions maximum via internet/i);
  if (await joinMaxField.count() > 0) {
    await joinMaxField.fill('9');
  }

  // Description via CKEditor
  await fillCKEditor(page, CONTENU);

  // Required checkboxes
  const imagesCheckbox = page.locator('input[name$="[imagesAuthorized]"]');
  if (await imagesCheckbox.count() > 0) await imagesCheckbox.check();
  const agreeCheckbox = page.locator('input[name$="[agreeEdito]"]');
  if (await agreeCheckbox.count() > 0) await agreeCheckbox.check();

  // Submit
  const publishBtn = page.getByRole('button', { name: /ENREGISTRER ET DEMANDER LA PUBLICATION/i });
  await publishBtn.click();

  // Verify: no errors and the sortie appears in the agenda
  await expect(page.locator('.erreur')).toBeHidden({ timeout: 3_000 });
  await expect(page.locator('#agenda')).toContainText(TITRE);
});
