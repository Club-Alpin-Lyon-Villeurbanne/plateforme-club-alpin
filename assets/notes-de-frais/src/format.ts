import type { Personne } from "./types";

export function euros(montant: number): string {
  return montant.toLocaleString("fr-FR", { style: "currency", currency: "EUR" });
}

/** AAAA-MM-JJ lu comme un jour local, sans décalage de fuseau. */
export function jour(date: string): Date {
  const [annee, mois, quantieme] = date.split("-").map(Number);

  return new Date(annee, mois - 1, quantieme);
}

export function aujourdhui(): Date {
  const maintenant = new Date();

  return new Date(maintenant.getFullYear(), maintenant.getMonth(), maintenant.getDate());
}

export function dateFr(date: Date): string {
  return date.toLocaleDateString("fr-FR");
}

/** Dernier jour pour envoyer une dépense faite le `dateDepense` (AAAA-MM-JJ). */
export function limiteEnvoi(dateDepense: string, delaiJours: number): Date {
  const limite = jour(dateDepense.slice(0, 10));
  limite.setDate(limite.getDate() + delaiJours);

  return limite;
}

export function messageDelaiDepasse(delaiJours: number): string {
  return `Le délai de ${delaiJours} jours est dépassé : cette demande ne peut plus être envoyée.`;
}

/** JJ/MM/AAAA d'une date ISO 8601 (date et heure). */
export function dateFrDe(iso: string): string {
  return new Date(iso).toLocaleDateString("fr-FR");
}

/** JJ/MM d'une date ISO 8601 (date et heure). */
export function jourMoisDe(iso: string): string {
  return new Date(iso).toLocaleDateString("fr-FR", { day: "2-digit", month: "2-digit" });
}

export function nomComplet(personne: { prenom: string; nom: string }): string {
  return `${personne.prenom} ${personne.nom}`;
}

/** « Escalade », « Escalade et Alpinisme », « Escalade, Ski et Alpinisme ». */
export function enumeration(elements: string[], conjonction: "et" | "ou" = "et"): string {
  return elements.length < 2
    ? (elements[0] ?? "")
    : `${elements.slice(0, -1).join(", ")} ${conjonction} ${elements[elements.length - 1]}`;
}

/** Sous le total, quand un plafond du club s'applique ; les montants viennent du serveur. */
export function partRemboursable(montants: { totalDeclare: number; totalRemboursable: number }): string | null {
  return montants.totalRemboursable < montants.totalDeclare
    ? `dont ${euros(montants.totalRemboursable)} remboursables selon les plafonds du club`
    : null;
}

/** Sous la commission choisie : qui validera la note. */
export function phraseValideurs(commission: { valideurs: Personne[]; demandeurResponsable: boolean }): string {
  if (commission.valideurs.length) {
    return `Validée par ${enumeration(commission.valideurs.map(nomComplet), "ou")}.`;
  }

  return commission.demandeurResponsable
    ? "Vous êtes le seul responsable de cette commission : la trésorerie validera."
    : "Pas de responsable : la trésorerie validera.";
}

/** Après l'envoi : qui a reçu la note à valider. */
export function phrasePrevenus(valideurs: Personne[]): string {
  if (!valideurs.length) {
    return "La trésorerie a été prévenue.";
  }
  const noms = enumeration(valideurs.map(nomComplet));

  return valideurs.length > 1 ? `${noms} ont été prévenus.` : `${noms} a été prévenu·e.`;
}
