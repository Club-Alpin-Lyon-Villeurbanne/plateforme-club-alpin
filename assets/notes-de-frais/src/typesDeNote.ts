/** Les deux types d'une note hors sortie ; « autre » s'affiche « Achat ou autre dépense ». */
export type TypeHorsSortie = "autre" | "formation";

interface LibellesDuType {
  /** Bouton radio de la première étape. */
  choix: string;
  /** « Achat pour Escalade », « Formation du 13/09/2026 ». */
  libelle: string;
  date: string;
  exempleObjet: string;
}

// Seul endroit où les pages écrivent les libellés des types.
export const TYPES: Record<TypeHorsSortie, LibellesDuType> = {
  autre: { choix: "Achat ou autre dépense", libelle: "Achat", date: "Date de l'achat", exempleObjet: "ex : Dégaines pour la SAE" },
  formation: { choix: "Formation", libelle: "Formation", date: "Date de la formation", exempleObjet: "ex : Formation initiateur SAE, Grenoble" },
};

export function libellesDuType(type: string): LibellesDuType {
  return TYPES[type as TypeHorsSortie] ?? TYPES.autre;
}
