import axios, { isAxiosError, type InternalAxiosRequestConfig } from "axios";
import type { NoteDeFrais, PieceJointe } from "./types";

const MESSAGE_SESSION_EXPIREE =
  "Votre session a expiré. Reconnectez-vous dans un autre onglet, puis réessayez : votre saisie reste sur cette page.";

const api = axios.create({
  baseURL: ((window as any).globals?.apiBaseUrl ?? "") + "/api",
  headers: { Accept: "application/json" },
});

api.interceptors.request.use((config) => {
  config.headers.Authorization = `Bearer ${localStorage.getItem("jwt")}`;
  return config;
});

let jetonEnCours: Promise<string | null> | null = null;

/** Jeton de l'API délivré à la session du site, qui dure bien plus longtemps que lui. */
function nouveauJeton(): Promise<string | null> {
  jetonEnCours ??= axios
    .post<{ token: string }>(`${window.location.origin}/jwt`)
    .then((reponse) => {
      localStorage.setItem("jwt", reponse.data.token);
      return reponse.data.token;
    })
    .catch(() => null)
    .finally(() => (jetonEnCours = null));

  return jetonEnCours;
}

// Le jeton expire au bout d'une heure, une demande peut rester ouverte plus longtemps.
api.interceptors.response.use(undefined, async (erreur) => {
  const requete = erreur.config as (InternalAxiosRequestConfig & { rejouee?: boolean }) | undefined;
  if (!sessionExpiree(erreur) || !requete || requete.rejouee) {
    throw erreur;
  }
  if ((await nouveauJeton()) === null) {
    throw erreur;
  }
  requete.rejouee = true;

  return api(requete);
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

export function sessionExpiree(erreur: unknown): boolean {
  return isAxiosError(erreur) && erreur.response?.status === 401;
}

/** Messages de refus de l'API, à afficher tels quels. */
export function messagesDErreur(erreur: unknown): string[] {
  if (sessionExpiree(erreur)) {
    return [MESSAGE_SESSION_EXPIREE];
  }

  const donnees = isAxiosError(erreur) ? erreur.response?.data : null;
  if (donnees?.violations?.length) {
    return donnees.violations.map((violation: { message: string }) => violation.message);
  }
  if (donnees?.detail) {
    return [donnees.detail];
  }

  return ["Une erreur s'est produite. Réessayez dans un instant."];
}
