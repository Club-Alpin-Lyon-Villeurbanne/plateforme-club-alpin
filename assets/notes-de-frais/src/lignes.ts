import type { Ligne } from "./types";

export function nouvelleLigne(): Ligne {
  return {
    id: `ligne-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`,
    montant: null,
    description: "",
  };
}

/** Champ `details` d'une note hors sortie : {"lignes": [{id, montant, description}]}. */
export function lireLignes(details: string | null): Ligne[] {
  const lignes = details ? JSON.parse(details).lignes : null;

  return Array.isArray(lignes) ? lignes : [];
}

export function ecrireLignes(lignes: Ligne[]): string {
  return JSON.stringify({
    lignes: lignes.map(({ id, montant, description }) => ({
      id,
      // Un champ nombre vidé donne une chaîne vide.
      montant: typeof montant === "number" && !Number.isNaN(montant) ? montant : null,
      description,
    })),
  });
}

export function total(lignes: Ligne[]): number {
  return lignes.reduce((somme, ligne) => somme + (typeof ligne.montant === "number" && !Number.isNaN(ligne.montant) ? ligne.montant : 0), 0);
}
