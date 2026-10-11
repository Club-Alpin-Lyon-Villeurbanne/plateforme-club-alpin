import axios, { isAxiosError } from "axios";
import type { NoteDeFrais, PieceJointe } from "./types";

const api = axios.create({
  baseURL: ((window as any).globals?.apiBaseUrl ?? "") + "/api",
  headers: { Accept: "application/json" },
});

api.interceptors.request.use((config) => {
  config.headers.Authorization = `Bearer ${localStorage.getItem("jwt")}`;
  return config;
});

export async function creerNote(corps: Record<string, unknown>): Promise<NoteDeFrais> {
  return (await api.post<NoteDeFrais>("/notes-de-frais", corps)).data;
}

export async function lireNote(id: number): Promise<NoteDeFrais> {
  return (await api.get<NoteDeFrais>(`/notes-de-frais/${id}`)).data;
}

export async function enregistrerNote(id: number, corps: Record<string, unknown>): Promise<NoteDeFrais> {
  return (
    await api.patch<NoteDeFrais>(`/notes-de-frais/${id}`, corps, {
      headers: { "Content-Type": "application/merge-patch+json" },
    })
  ).data;
}

/** Remplace le justificatif déjà envoyé pour la même ligne. */
export async function envoyerJustificatif(noteId: number, ligneId: string, fichier: File): Promise<PieceJointe> {
  const formulaire = new FormData();
  formulaire.append("file", fichier);
  formulaire.append("expenseId", ligneId);

  return (await api.post<PieceJointe>(`/notes-de-frais/${noteId}/pieces-jointes`, formulaire)).data;
}

/** Messages de refus de l'API, à afficher tels quels. */
export function messagesDErreur(erreur: unknown): string[] {
  const donnees = isAxiosError(erreur) ? erreur.response?.data : null;

  if (donnees?.violations?.length) {
    return donnees.violations.map((violation: { message: string }) => violation.message);
  }
  if (donnees?.detail) {
    return [donnees.detail];
  }

  return ["Une erreur s'est produite. Réessayez dans un instant."];
}
