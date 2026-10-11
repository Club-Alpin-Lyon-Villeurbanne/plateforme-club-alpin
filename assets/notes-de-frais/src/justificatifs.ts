// Mêmes règles que l'envoi côté serveur (ExpenseAttachmentController).
const EXTENSIONS = ["jpg", "jpeg", "png", "pdf"];
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
