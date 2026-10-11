export interface CommissionProposee {
  id: number;
  titre: string;
}

/** Données transmises par le gabarit Twig. */
export interface DonneesInitiales {
  commissions: CommissionProposee[];
  /** Par type de note ; un type absent n'a pas de délai. */
  delaisEnvoiJours: Record<string, number>;
  /** Brouillon à reprendre. */
  noteId: number | null;
}

/** Première étape. */
export interface Demande {
  commissionId: number | null;
  objet: string;
  /** AAAA-MM-JJ */
  dateDepense: string;
}

export interface Ligne {
  /** Généré ici ; sert d'expenseId au justificatif de la ligne. */
  id: string;
  montant: number | null;
  description: string;
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

export interface NoteDeFrais {
  id: number;
  status: string;
  type: "sortie" | "formation" | "autre";
  commission: { id: number; title: string } | null;
  objet: string | null;
  dateDepense: string | null;
  motPourResponsable: string | null;
  details: string | null;
  piecesJointes: PieceJointe[];
}

export interface Toastr {
  error: (message: string) => void;
  success: (message: string) => void;
  info: (message: string) => void;
}
