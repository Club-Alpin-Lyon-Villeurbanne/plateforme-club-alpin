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
  // On DOMContentLoaded, switchCommission() fires two AJAX calls that replace
  // #individus (encadrant checkboxes) and #commission-specific-fields with fresh
  // HTML. We use networkidle to wait for these AJAX calls to complete and their
  // .then() callbacks to update the DOM before interacting with the form.
  await page.goto('/creer-une-sortie?commission=sorties-familles', { waitUntil: 'networkidle' });

  // Title
  await page.getByLabel('Titre').fill(TITRE);

  // Activity departure location (required)
  await page.getByLabel(/Lieu de départ/i).fill('69005 Lyon');

  // Check first encadrant checkbox (loaded via AJAX)
  await page.locator('#individus input[name$="[encadrants][]"]').first().check();

  // Meeting point
  const rdvField = page.getByLabel(/Lieu de rendez-vous covoiturage/i);
  if (await rdvField.count() > 0) {
    await rdvField.fill('bron');
  }

  // Set lat/long hidden fields directly (geocoding API may not be available in CI)
  await page.locator('input[name$="[lat]"]').evaluate(
    (el: HTMLInputElement) => { el.value = '45.7578'; },
  );
  await page.locator('input[name$="[long]"]').evaluate(
    (el: HTMLInputElement) => { el.value = '4.8320'; },
  );

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

  // Commission-specific mandatory fields (loaded via AJAX)
  await page.getByLabel(/Difficulté, niveau/i).fill('Facile');
  await page.getByLabel(/Dénivelé positif/i).fill('300');
  await page.getByLabel(/Distance/i).fill('5.5');

  // Description via CKEditor
  await fillCKEditor(page, CONTENU);

  // Required checkboxes
  await page.locator('input[name$="[imagesAuthorized]"]').check();
  await page.locator('input[name$="[agreeEdito]"]').check();

  // Submit
  const publishBtn = page.getByRole('button', { name: /ENREGISTRER ET DEMANDER LA PUBLICATION/i });
  await publishBtn.click();

  // Verify: redirected to profile sorties page and the sortie appears in the agenda
  await expect(page).toHaveURL(/\/sorties\/self/);
  await expect(page.locator('#agenda')).toContainText(TITRE);
});
