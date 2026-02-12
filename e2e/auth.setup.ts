import { test as setup } from '@playwright/test';
import { STORAGE_STATE } from './helpers/storage-state';

const USERS = [
  {
    role: 'admin',
    email: process.env.TEST_USER_ADMIN_EMAIL || 'admin@test-clubalpinlyon.fr',
    password: process.env.TEST_USER_ADMIN_PASSWORD || 'test',
    file: STORAGE_STATE.admin,
  },
  {
    role: 'redacteur',
    email: process.env.TEST_USER_REDACTEUR_EMAIL || 'redacteur@test-clubalpinlyon.fr',
    password: process.env.TEST_USER_REDACTEUR_PASSWORD || 'test',
    file: STORAGE_STATE.redacteur,
  },
  {
    role: 'encadrant',
    email: process.env.TEST_USER_ENCADRANT_EMAIL || 'encadrant@test-clubalpinlyon.fr',
    password: process.env.TEST_USER_ENCADRANT_PASSWORD || 'test',
    file: STORAGE_STATE.encadrant,
  },
  {
    role: 'resp.comm',
    email: process.env.TEST_USER_RESP_COMM_EMAIL || 'resp.comm@test-clubalpinlyon.fr',
    password: process.env.TEST_USER_RESP_COMM_PASSWORD || 'test',
    file: STORAGE_STATE.respComm,
  },
];

for (const user of USERS) {
  setup(`authenticate as ${user.role}`, async ({ page }) => {
    await page.goto('/login');
    await page.locator('input[name="_username"]').fill(user.email);
    await page.locator('input[name="_password"]').fill(user.password);
    await page.locator('input[name="connect-button"]').click();
    await page.waitForURL((url) => !url.toString().includes('/login'));
    await page.context().storageState({ path: user.file });
  });
}
