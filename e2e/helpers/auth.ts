import { Page } from '@playwright/test';

const BASE_URL = process.env.BASE_URL || 'http://localhost:8000';

export const login = async (page: Page, email: string, password: string) => {
  // Aller à la page de login
  await page.goto(`${BASE_URL}/login`);
  await page.waitForLoadState('networkidle');
  
  // Remplir email et password avec les bons noms de champs
  await page.fill('input[name="_username"]', email);
  await page.fill('input[name="_password"]', password);
  
  // Soumettre le formulaire
  await page.click('input[name="connect-button"]');
  
  // Attendre la redirection et le chargement de la page
  await page.waitForLoadState('networkidle');
}; 