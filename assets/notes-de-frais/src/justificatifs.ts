// Mêmes règles que l'envoi côté serveur (ExpenseAttachmentController).
export const EXTENSIONS_D_IMAGE = ["jpg", "jpeg", "png"];
const EXTENSIONS = [...EXTENSIONS_D_IMAGE, "pdf"];
const TAILLE_MAX = 8_000_000;

export const FORMATS_ACCEPTES = ".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf";

export function refusDuFichier(fichier: File): string | null {
  const extension = fichier.name.split(".").pop()?.toLowerCase() ?? "";
  if (!EXTENSIONS.includes(extension)) {
    return "Format non accepté : JPG, PNG ou PDF.";
  }
  if (fichier.size > TAILLE_MAX) {
    return "Fichier trop lourd : 8 Mo au plus.";
  }

  return null;
}
