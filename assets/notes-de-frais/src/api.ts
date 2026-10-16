import axios, { isAxiosError, type InternalAxiosRequestConfig } from "axios";
import type { NoteDeFrais, NoteDeSortie, PieceJointe, Refus } from "./types";

const MESSAGE_SESSION_EXPIREE =
  "Votre session a expiré. Reconnectez-vous dans un autre onglet, puis réessayez : votre saisie reste sur cette page.";
// Rien n'est saisi sur une page qui lit : la recharger mène à la connexion.
const SESSION_EXPIREE_EN_LECTURE = "Votre session a expiré : rechargez la page pour vous reconnecter.";

/** Refus de l'API (FieldPermissionValidator) quand la note est partie depuis un autre onglet. */
export const NOTE_DEJA_ENVOYEE = "Cette note a déjà été envoyée : elle ne peut plus être modifiée.";

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

// Le jeton expire au bout d'une heure, le formulaire peut rester ouvert plus longtemps.
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

export interface PageDeNotes {
  notes: NoteDeFrais[];
  pages: number;
}

/** Notes du demandeur, brouillons compris, des plus récentes aux plus anciennes ; toutes si aucun type n'est demandé. */
export async function listerMesNotes(page: number, type: NoteDeFrais["type"] | null): Promise<{ notes: (NoteDeFrais | NoteDeSortie)[]; pages: number; total: number }> {
  const reponse = await api.get<{ data: (NoteDeFrais | NoteDeSortie)[]; meta: { pages: number; total: number } }>("/notes-de-frais", {
    params: { inclure_brouillons: true, page, ...(type ? { type } : {}) },
  });

  return { notes: reponse.data.data, pages: reponse.data.meta.pages, total: reponse.data.meta.total };
}

/** Notes que l'utilisateur peut traiter comme responsable : en attente, ou déjà traitées. */
export async function listerNotesAValider(page: number, traitees: boolean): Promise<PageDeNotes & { total: number }> {
  const reponse = await api.get<{ data: NoteDeFrais[]; meta: { pages: number; total: number } }>("/notes-de-frais/a-valider", {
    params: { page, ...(traitees ? { status: ["preapproved", "approved", "rejected", "accounted"] } : {}) },
    paramsSerializer: { indexes: false },
  });

  return { notes: reponse.data.data, pages: reponse.data.meta.pages, total: reponse.data.meta.total };
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
  return statutDuRefus(erreur) === 401;
}

/** La note a changé depuis sa lecture : ailleurs, ou en même temps par une autre requête. */
export function conflit(erreur: unknown): boolean {
  return statutDuRefus(erreur) === 409;
}

export function statutDuRefus(erreur: unknown): number | undefined {
  return isAxiosError(erreur) ? erreur.response?.status : undefined;
}

/** Une liste qui ne se charge pas. */
export function refusDeLecture(erreur: unknown): string {
  return sessionExpiree(erreur) ? SESSION_EXPIREE_EN_LECTURE : messagesDErreur(erreur)[0];
}

/** Une note qui ne se lit pas : pas le droit, inconnue, ou autre erreur. */
export function erreurDeLecture(erreur: unknown): string {
  if (sessionExpiree(erreur)) {
    return SESSION_EXPIREE_EN_LECTURE;
  }
  const statut = statutDuRefus(erreur);
  if (statut === 403) {
    return "Vous n'avez pas accès à cette note de frais.";
  }
  if (statut === 404) {
    return "Cette note de frais n'existe pas.";
  }

  return "Impossible de charger cette note.";
}

/** Refus de l'API, avec le champ en cause quand c'est une violation de validation. */
export function refusDeLApi(erreur: unknown): Refus[] {
  if (sessionExpiree(erreur)) {
    return [{ champ: null, message: MESSAGE_SESSION_EXPIREE }];
  }

  const donnees = isAxiosError(erreur) ? erreur.response?.data : null;
  if (donnees?.violations?.length) {
    return donnees.violations.map((violation: { propertyPath?: string; message: string }) => ({
      champ: violation.propertyPath || null,
      message: violation.message,
    }));
  }
  // Une erreur serveur (5xx) n'a pour détail que « Internal Server Error », en anglais.
  if (donnees?.detail && (statutDuRefus(erreur) ?? 500) < 500) {
    return [{ champ: null, message: donnees.detail }];
  }

  return [{ champ: null, message: "Une erreur s'est produite. Réessayez dans un instant." }];
}

/** Messages de refus de l'API, à afficher tels quels. */
export function messagesDErreur(erreur: unknown): string[] {
  return refusDeLApi(erreur).map((refus) => refus.message);
}
