import { test, expect } from "@playwright/test";

const CONTENU = "test de contenu de page";

test("test", async ({ page }) => {
  // Login
  await page.goto("http://127.0.0.1:8000/");
  await page.locator("#toolbar-user").hover();
  await page.getByRole("textbox", { name: "Votre e-mail" }).click();
  await page
    .getByRole("textbox", { name: "Votre e-mail" })
    .fill("test@clubalpinlyon.fr");
  await page.getByRole("textbox", { name: "Votre e-mail" }).press("Tab");
  await page.getByRole("textbox", { name: "Votre mot de passe" }).fill("test");
  await page.getByRole("button", { name: "Connexion" }).click();
  await page.waitForTimeout(1000);

  // Rédiger un article
  await page.locator("#toolbar-user").hover();
  await page.getByRole("link", { name: "• rédiger un article" }).click();
  await page.locator('select[name="commission_article"]').selectOption("1");
  await page
    .getByRole("textbox", { name: "ex : Escalade du Grand Som," })
    .click();
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

  await page.locator('input[id="topubly_article"]').press("Enter");

  // Vérification de la création de l'article
  // Vérifie qu'aucune erreur n'est visible sur la page
  const errorDiv = page.locator(".erreur");
  if (await errorDiv.isVisible()) {
    const errorText = await errorDiv.textContent();
    console.error("Erreur détectée sur la page:", errorText);
  }
  await page.getByText("Votre article a bien été").click();
  await page.getByRole("button", { name: "continuer" }).click();
  await page
    .getByRole("link", { name: "• 1 validation / gestion des" })
    .click();
  const page1Promise = page.waitForEvent("popup");
  await page.getByRole("link", { name: "test e2e creation article" }).click();
  const page1 = await page1Promise;
  await page1.getByRole("button", { name: "Autoriser & publier" }).click();
  await page1.getByText("Note : Cet article est publi").click();
  await page1.getByText("Opération effectuée avec succ").click();
  await page1.getByRole("link", { name: "Close" }).click();
  await expect(
    page1.getByText(
      "Note : Cet article est publié sur le site et visible par les adhérents !"
    )
  ).toBeVisible();
});
