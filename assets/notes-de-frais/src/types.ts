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

export interface Personne {
  prenom: string;
  nom: string;
}

/** Dernière validation ou dernier renvoi d'une note. */
export interface Decision {
  statut: "approved" | "rejected";
  auteur: Personne;
  /** Date et heure ISO 8601. */
  date: string;
  /** Message du renvoi ; null pour une validation. */
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
  /** Messages à afficher tels quels. */
  avertissements: string[];
  /** Null pour un brouillon. */
  dateEnvoi: string | null;
  /** Vide : la note va directement à la trésorerie. */
  valideurs: Valideur[];
  derniereDecision: Decision | null;
  /** L'utilisateur connecté peut valider ou renvoyer la note maintenant. */
  peutDecider: boolean;
}

export interface DonneesMesNotes {
  delaisEnvoiJours: Record<string, number>;
}

export interface DonneesAValider {
  /** Demandes déjà traitées plutôt que demandes en attente. */
  traitees: boolean;
  /** La trésorerie voit à part les demandes qui attendent un responsable. */
  gestionnaire: boolean;
}

export interface DonneesDetail {
  noteId: number;
  utilisateurId: number | null;
  gestionnaire: boolean;
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
