import { test, expect } from "@playwright/test";
import { login } from "./helpers/auth";

test("Publication d'un article", async ({ page }) => {
  await login(page, "resp.comm@test-clubalpinlyon.fr", "test");
  await page.locator("#toolbar-user").hover();
  await page.waitForTimeout(1000);
  await page.locator('a[href="/gestion-des-articles.html"]').click();
  // On the legacy moderation page, the "Autoriser & publier" submit exists inline
  // Find and click the first matching moderation button rather than relying on a popup preview
  const autoriserPublierBtn = page.locator('input[type="submit"][value="Autoriser & publier"]').first();
  await expect(autoriserPublierBtn).toBeVisible({ timeout: 5000 });
  await autoriserPublierBtn.click();

  // Wait for the operation to complete and show a success message on the same page
  await page.waitForLoadState('networkidle');
  await expect(page.getByText(/Opération effectuée avec succ/i)).toBeVisible({ timeout: 5000 });
});
