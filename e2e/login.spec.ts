import { test, expect } from '@playwright/test';
import { login } from './helpers/auth';

const BASE_URL = process.env.BASE_URL || 'http://localhost:8000';


test.describe('Authentification', () => {
  test('TC-AUTH-001 : Connexion valide', async ({ page }) => {
    await login(page, 'admin@test-clubalpinlyon.fr', 'test');
    
    // Vérifier la redirection
    await expect(page).toHaveURL(/\//);
    
    // Vérifier qu'on est bien connecté (présence d'un élément visible seulement quand connecté)
    await expect(page.locator('.connected-name')).toBeVisible();
  });
  
  test('TC-AUTH-002 : Connexion avec identifiants invalides', async ({ page }) => {
    await login(page, 'admin@test-clubalpinlyon.fr', 'mauvaismdp');
    
    await expect(page).toHaveURL(/login/);
    await expect(page.locator('.alert-danger, .erreur')).toBeVisible();
  });

  // test('TC-AUTH-003 : Déconnexion', async ({ page }) => {
  //   await login(page, 'admin@test-clubalpinlyon.fr', 'test');
    
  //   // Cliquer sur toolbar-user pour ouvrir le menu
  //   await page.locator('#toolbar-user').click();
    
  //   // Cliquer sur le lien de déconnexion
  //   await page.locator('a[href*="/logout"]').click();
    
  //   // Vérifier la redirection
  //   await expect(page).toHaveURL(/\//);
    
  //   // Vérifier qu'on est bien déconnecté (présence du lien "Mot de passe perdu ?" visible seulement quand déconnecté)
  //   await expect(page.getByText('Mot de passe perdu ?')).toBeVisible();
  // });

  // test('TC-AUTH-004 : Accès au compte utilisateur', async ({ page }) => {
  //   await login(page, 'admin@test-clubalpinlyon.fr', 'test');
  //   const toolbarUser = page.locator('a[href*="account"], button[id*="user"], [role="button"][aria-label*="profil"]');
  //   await toolbarUser.first().hover().catch(() => {});
  //   await page.getByRole('link', { name: /Mon compte|My Account|Account|Profil/i }).click().catch(() => {
  //     page.click('a[href*="account"]');
  //   });
    
  //   await expect(page.getByText(/Votre pseudonyme|Your username|Profil/i)).toBeVisible().catch(() => {
  //     expect(page.url()).toContain('account');
  //   });
  // });
});
