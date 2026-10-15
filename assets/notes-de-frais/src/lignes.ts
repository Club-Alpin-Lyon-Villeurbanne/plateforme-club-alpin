import { euros } from "./format";
import type { TypeHorsSortie } from "./typesDeNote";
import type { Ligne } from "./types";

/** Une ligne de voiture se saisit en kilomètres, les autres en euros. */
export const NATURE_EN_KILOMETRES = "voiture";
/** Une ligne « Autre » se précise par une description, exigée à l'envoi. */
export const NATURE_A_PRECISER = "autre";

export function nouvelleLigne(type: TypeHorsSortie): Ligne {
  return {
    id: `ligne-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`,
    montant: null,
    description: "",
    // Pas de nature par défaut : laissée sur « Autre », une nuit ou un péage échapperait à son plafond.
    ...(type === "formation" ? { nature: null } : {}),
  };
}

/** Champ `details` d'une note hors sortie : {"lignes": [{id, montant, description, nature, distance}]}. */
export function lireLignes(details: string | null): Ligne[] {
  const lignes = details ? JSON.parse(details).lignes : null;

  return Array.isArray(lignes) ? lignes.map((ligne) => ({ ...ligne, description: ligne.description ?? "" })) : [];
}

/** « 12,50 », « 12.5 », « 1 234,56 € » ; null si le texte n'est pas un nombre. */
export function lireNombre(texte: string): number | null {
  const chiffres = texte.replace(/[\s€]/g, "").replace(",", ".");

  return /^-?(\d+\.?\d*|\.\d+)$/.test(chiffres) ? Number(chiffres) : null;
}

function nombre(valeur: unknown): number | null {
  return typeof valeur === "number" && !Number.isNaN(valeur) ? valeur : null;
}

export function ecrireLignes(lignes: Ligne[]): string {
  return JSON.stringify({
    lignes: lignes.map(({ id, montant, description, nature, distance }) => ({
      id,
      montant: nombre(montant),
      description,
      ...(nature === undefined ? {} : { nature }),
      ...(distance === undefined ? {} : { distance: nombre(distance) }),
    })),
  });
}

/** Pour l'affichage pendant la saisie : kilomètres × taux pour la voiture, montant saisi sinon. */
export function montantDeLaLigne(ligne: Ligne, tauxKilometrique: number): number {
  if (ligne.nature === NATURE_EN_KILOMETRES) {
    const distance = nombre(ligne.distance);

    return distance === null ? 0 : Math.round(distance * tauxKilometrique * 100) / 100;
  }

  return nombre(ligne.montant) ?? 0;
}

export function total(lignes: Ligne[], tauxKilometrique: number): number {
  return lignes.reduce((somme, ligne) => somme + montantDeLaLigne(ligne, tauxKilometrique), 0);
}

/** « 220 km × 0,40 € ». */
export function calculDeLaVoiture(distance: number, tauxKilometrique: number): string {
  return `${distance.toLocaleString("fr-FR")} km × ${euros(tauxKilometrique)}`;
}
