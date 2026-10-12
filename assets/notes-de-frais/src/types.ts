import type { TypeHorsSortie } from "./typesDeNote";

/** Un responsable de la commission qui valide la note. */
export interface Valideur {
  id: number;
  prenom: string;
  nom: string;
}

export interface CommissionProposee {
  id: number;
  titre: string;
  /** Calculés pour l'utilisateur connecté, lui-même exclu ; vide : la trésorerie validera. */
  valideurs: Valideur[];
  /** L'utilisateur y a un rôle d'encadrement : l'API accepterait qu'il y demande une formation. */
  formationPossible: boolean;
  /** L'utilisateur est responsable de cette commission : sans autre valideur, il en est le seul. */
  demandeurResponsable: boolean;
}

/** Transmis par le gabarit pour afficher le calcul d'une ligne de voiture ; l'API fait foi pour les montants. */
export interface BaremeFormation {
  tauxKilometrique: number;
  /** Libellé de chaque nature, dans l'ordre du menu. */
  natures: Record<string, string>;
}

/** Données transmises par le gabarit Twig. */
export interface DonneesInitiales {
  commissions: CommissionProposee[];
  /** Par type de note ; un type absent n'a pas de délai. */
  delaisEnvoiJours: Record<string, number>;
  formation: BaremeFormation;
  /** Brouillon à reprendre. */
  noteId: number | null;
}

/** Première étape. */
export interface Demande {
  type: TypeHorsSortie;
  commissionId: number | null;
  objet: string;
  /** AAAA-MM-JJ */
  dateDepense: string;
  /** Formation seulement ; vide si aucun. */
  lienFormation: string;
}

export interface Ligne {
  /** Généré ici ; sert d'expenseId au justificatif de la ligne. */
  id: string;
  /** En euros ; null pour une voiture, qui se déclare en kilomètres. */
  montant: number | null;
  description: string;
  /** Formation seulement ; null tant qu'elle n'est pas choisie. */
  nature?: string | null;
  /** Kilomètres d'une voiture. */
  distance?: number | null;
}

/** Une ligne telle que le serveur la présente, au taux kilométrique figé pour une note envoyée. */
export interface LigneAffichee {
  id: string | null;
  /** Null pour un achat. */
  nature: string | null;
  /** Nature d'une formation, description d'un achat. */
  libelle: string | null;
  /** « 220 km × 0,40 € » pour la voiture, la description pour « Autre ». */
  detail: string | null;
  montant: number;
}

export interface PieceJointe {
  id: number;
  expenseId: string;
  fileUrl: string;
}

export interface Justificatif {
  fileUrl: string;
  /** Nom du fichier choisi, inconnu pour un justificatif déjà enregistré. */
  nom: string | null;
}

export interface Personne {
  prenom: string;
  nom: string;
}

/** Dernière décision sur une note : validation par un responsable (preapproved) ou par la trésorerie, ou renvoi. */
export interface Decision {
  statut: "preapproved" | "approved" | "rejected";
  /** Qui a décidé, établi par le serveur au moment de la décision ; null pour une décision plus ancienne que ce champ. */
  niveau: "responsable" | "tresorerie" | null;
  auteur: Personne;
  /** Date et heure ISO 8601. */
  date: string;
  /** Message du renvoi, ou mot laissé avec une validation ; null sinon. */
  message: string | null;
}

export interface NoteDeFrais {
  id: number;
  status: string;
  type: "sortie" | "formation" | "autre";
  utilisateur: Personne & { id: number };
  commission: { id: number; title: string } | null;
  objet: string | null;
  dateDepense: string | null;
  lienFormation: string | null;
  motPourResponsable: string | null;
  /**
   * Null tant que le demandeur n'a pas choisi : true pour un remboursement, false pour un don.
   * Absent pour le responsable de la note, que ce choix ne doit pas influencer.
   */
  refundRequired?: boolean | null;
  details: string | null;
  piecesJointes: PieceJointe[];
  /** Calculés par le serveur, figés à l'envoi. */
  totalDeclare: number;
  totalRemboursable: number;
  lignesAffichees: LigneAffichee[];
  /** Messages à afficher tels quels. */
  avertissements: string[];
  /** Null pour un brouillon. */
  dateEnvoi: string | null;
  /** Vide : la note va directement à la trésorerie. */
  valideurs: Valideur[];
  derniereDecision: Decision | null;
  /** Validation par un responsable depuis le dernier envoi, conservée après la décision de la trésorerie. */
  validationResponsable: { auteur: Personne; date: string } | null;
  /** L'utilisateur connecté peut valider ou renvoyer la note maintenant. */
  peutDecider: boolean;
}

/** Note d'une sortie, telle que « Mes notes de frais » la lit : elle se remplit sur la page de sa sortie. */
export interface NoteDeSortie {
  id: number;
  status: string;
  type: "sortie";
  sortie: { id: number; code: string; titre: string; startDate: string; commission: { title: string } | null };
  commentaireStatut: string | null;
  /** Calculés par le serveur avec les taux en vigueur. */
  totalDeclare: number;
}

export interface DonneesMesNotes {
  delaisEnvoiJours: Record<string, number>;
}

export interface DonneesAValider {
  /** Notes déjà traitées plutôt que notes en attente. */
  traitees: boolean;
}

export interface DonneesDetail {
  noteId: number;
  /** L'utilisateur connecté a fait la note. */
  estDemandeur: boolean;
}

export interface Refus {
  /** Chemin de la violation (`details`, `refundRequired`…), null pour un refus général. */
  champ: string | null;
  message: string;
}

export interface Toastr {
  error: (message: string) => void;
  success: (message: string) => void;
  info: (message: string) => void;
}
