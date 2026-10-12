import type { Decision, NoteDeFrais, Personne } from "./types";

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
  return `Le délai de ${delaiJours} jours est dépassé : cette note ne peut plus être envoyée.`;
}

/** « , le 13/09/2026 » après le type d'une note hors sortie ; rien tant que la date manque. */
export function leJourDeLaDepense(dateDepense: string | null): string {
  return dateDepense ? `, le ${dateFr(jour(dateDepense))}` : "";
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

/** Sous la commission choisie : à qui la note sera transmise. */
export function phraseValideurs(commission: { valideurs: Personne[]; demandeurResponsable: boolean }): string {
  const noms = enumeration(commission.valideurs.map(nomComplet));
  if (commission.valideurs.length > 1) {
    return `Sera transmise aux responsables de la commission : ${noms}.`;
  }
  if (commission.valideurs.length === 1) {
    return `Sera transmise au responsable de la commission : ${noms}.`;
  }

  // Sans affirmer qu'il n'y a pas de responsable : il peut en avoir un sans adresse mail, ou qui doit renouveler.
  return commission.demandeurResponsable
    ? "Sera transmise directement à la trésorerie : un responsable ne valide pas sa propre note."
    : "Sera transmise directement à la trésorerie.";
}

/** Lien saisi par le demandeur, seulement s'il est en http ou https ; sinon il s'affiche en texte. */
export function lienWeb(adresse: string | null): string | null {
  if (!adresse?.trim()) {
    return null;
  }
  try {
    const url = new URL(adresse.trim());

    return url.protocol === "http:" || url.protocol === "https:" ? url.href : null;
  } catch {
    return null;
  }
}

/** Après l'envoi : qui a reçu la note à valider. La trésorerie, elle, ne reçoit pas de mail. */
export function phrasePrevenus(valideurs: Personne[]): string {
  if (!valideurs.length) {
    return "Elle est transmise à la trésorerie.";
  }
  const noms = enumeration(valideurs.map(nomComplet));

  return valideurs.length > 1 ? `${noms} ont été prévenus.` : `${noms} a été prévenu·e.`;
}

// Phrases de l'avancement d'une note hors sortie, pour toutes les pages : le niveau de chaque
// décision vient de l'API, il n'est jamais recalculé ici.

/** La note attend la trésorerie : validée par un responsable, ou envoyée sans valideur. */
export function chezLaTresorerie(note: Pick<NoteDeFrais, "status" | "valideurs">): boolean {
  return note.status === "preapproved" || (note.status === "submitted" && note.valideurs.length === 0);
}

/** Un responsable est nommé ; la trésorerie signe de son rôle, pas du nom de la personne qui la tient. */
export function auteurDeLaDecision(decision: Decision): string {
  return decision.niveau === "tresorerie" ? "la trésorerie" : nomComplet(decision.auteur);
}

/** Sous une note dans une liste : « Validée par X le 12/10, transmise à la trésorerie. », « Refusée par la trésorerie le 12/10. » */
export function phraseDeLaDecision(decision: Decision): string {
  const par = `par ${auteurDeLaDecision(decision)} le ${jourMoisDe(decision.date)}`;
  if (decision.statut === "preapproved") {
    return `Validée ${par}, transmise à la trésorerie.`;
  }

  return decision.statut === "approved" ? `Validée ${par}.` : `Refusée ${par}.`;
}

/** Motif d'un refus, précédé de son auteur : « La trésorerie : « … » ». */
export function motifDuRefus(decision: Decision): string {
  const auteur = auteurDeLaDecision(decision);

  return `${auteur.charAt(0).toUpperCase()}${auteur.slice(1)} : « ${decision.message ?? ""} »`;
}

export interface DecisionSurLaPage {
  texte: string;
  /** Motif du refus, affiché entre guillemets. */
  message: string | null;
  /** « Corrigée et renvoyée le … », « Comptabilisée. » */
  suite: string | null;
}

/**
 * Page d'une note : la décision prise, aux deux niveaux. Une note renvoyée après un refus rappelle
 * ce refus ; une note comptabilisée le dit.
 */
export function decisionSurLaPage(note: NoteDeFrais): DecisionSurLaPage | null {
  const derniere = note.derniereDecision;
  if (!derniere) {
    return null;
  }
  const par = `par ${auteurDeLaDecision(derniere)} le ${dateFrDe(derniere.date)}`;
  if (derniere.statut === "preapproved") {
    return { texte: `Validée ${par}.`, message: null, suite: null };
  }

  // La validation du responsable date du dernier envoi : la décision qui la suit est celle de la trésorerie.
  const validation = note.validationResponsable;
  const debut = validation ? `Validée par ${nomComplet(validation.auteur)} le ${dateFrDe(validation.date)}, puis ` : "";

  if (derniere.statut === "approved") {
    return { texte: debut ? `${debut}${par}.` : `Validée ${par}.`, message: null, suite: note.status === "accounted" ? "Comptabilisée." : null };
  }

  return {
    texte: `${debut ? `${debut}refusée` : "Refusée"} ${par} :`,
    message: derniere.message,
    suite: note.status === "submitted" && note.dateEnvoi ? `Corrigée et renvoyée le ${dateFrDe(note.dateEnvoi)}.` : null,
  };
}

/** Page d'une note qui attend une décision qu'on ne peut pas prendre soi-même : qui décidera. */
export function enAttenteDe(note: NoteDeFrais): string | null {
  if (chezLaTresorerie(note)) {
    return "En attente de la trésorerie.";
  }

  return note.status === "submitted" ? `En attente de la décision de ${enumeration(note.valideurs.map(nomComplet), "ou")}.` : null;
}
