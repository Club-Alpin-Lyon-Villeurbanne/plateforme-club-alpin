import { test, expect } from "@playwright/test";
import { login } from "./helpers/auth";

test("Publication d'un article", async ({ page }) => {
  await login(page, "resp.comm@test-clubalpinlyon.fr", "test");
  await page.locator("#toolbar-user").hover();
  await page.waitForTimeout(1000);
  await page.locator('a[href="/gestion-des-articles.html"]').click();

  page.getByRole("link", { name: /test e2e creation article/ }).first().click();
  const articlePage = await page.waitForEvent('popup');

  await articlePage.waitForTimeout(1000);
  const autoriserPublierBtn = articlePage.locator('input[type="submit"][value="Autoriser & publier"]');
  await expect(autoriserPublierBtn).toBeVisible({ timeout: 2000 });
  await autoriserPublierBtn.click();
  await articlePage.getByText("Note : Cet article est publi").click();
  await articlePage.getByText("Opération effectuée avec succ").click();
  await articlePage.getByRole("link", { name: "Close" }).click();
  await expect(
    articlePage.getByText(
      "Note : Cet article est publié sur le site et visible par les adhérents !"
    )
  ).toBeVisible();
});
