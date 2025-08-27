import { AllTransports } from "./transports";

export interface Attachment {
  expenseId: string;
  fileUrl: string;
  id: number;
}
export interface Event {
  code: string;
  commission: any[];
  id: number;
  titre: string;
  dateDebut: string;
  dateFin: string;
}

export interface User {
  prenom: string;
  id: number;
  nom: string;
}

export interface Accommodation {
  expenseId: string;
  price: number;
  comment: string;
}

export interface Other {
  expenseId: string;
  comment: string;
  price: number;
}

export interface ExpenseDetails {
  transport: AllTransports;
  accommodations: Array<Accommodation>;
  others: Array<Other>;
}

export interface ExpenseReport {
  piecesJointes: Attachment[];
  dateCreation: string;
  details: ExpenseDetails;
  sortie: Event;
  id: number;
  refundRequired: boolean;
  status: string;
  commentaireStatut: string;
  utilisateur: User;
}

export interface ExpenseReportPayload {
  details: string;
  refundRequired: boolean;
  status?: string;
}

export enum ExpenseStatus {
  DRAFT = "draft",
  SUBMITTED = "submitted",
  REJECTED = "rejected",
  APPROVED = "approved",
  ACCOUNTED = "accounted",
}
