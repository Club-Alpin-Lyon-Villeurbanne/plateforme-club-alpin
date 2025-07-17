import { test, expect } from "@playwright/test";
import { login } from "./helpers/auth";

const CONTENU = "test de contenu de page";

test("Creation d'un article", async ({ page }) => {
  // Login
  await login(page, "test@clubalpinlyon.fr", "test");

  // Rédiger un article
  await page.locator("#toolbar-user").hover();
  await page.getByRole("link", { name: "• rédiger un article" }).click();
  await page.locator('select[name="commission_article"]').selectOption("1");

  await page
    .getByRole("textbox", { name: "ex : Escalade du Grand Som," })
    .fill("test e2e creation article" + new Date().getTime());
  await page.getByRole("button", { name: "Choose File" }).click();
  await page
    .getByRole("button", { name: "Choose File" })
    .setInputFiles("./assets/images/alltricks.jpg");
  // Correction du typage de window.tinymce
  await page.evaluate((content) => {
    const w = window as typeof window & { tinymce?: any };
    if (typeof w.tinymce !== "undefined") {
      const editor = w.tinymce.get("mce_editor_0");
      if (editor) {
        editor.setContent(content);
      }
    } else {
      throw new Error("TinyMCE n'est pas disponible sur cette page");
    }
  }, CONTENU);

  await page.locator('input[id="topubly_article"]').click();
  // Clique sur le bouton pour sauvegarder l'article
  const saveArticleBtn = page.locator("a.biglink", {
    hasText: "ENREGISTRER CET ARTICLE DANS « MES ARTICLES »",
  });
  await expect(saveArticleBtn).toBeVisible({ timeout: 2000 });
  await saveArticleBtn.click();

  // Vérification de la création de l'article
  // Vérifie qu'aucune erreur n'est visible sur la page
  const errorDiv = page.locator(".erreur");
  if (await errorDiv.isVisible()) {
    const errorText = await errorDiv.textContent();
    console.error("Erreur détectée sur la page:", errorText);
  }
  await page.getByText("Votre article a bien été").click();
  await page.getByRole("button", { name: "continuer" }).click();
});



