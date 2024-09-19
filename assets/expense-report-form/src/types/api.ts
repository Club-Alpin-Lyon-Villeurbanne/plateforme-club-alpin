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
  rdv: string;
  titre: string;
  tsp: string;
  tspEnd: string;
}

export interface User {
  firstname: string;
  id: number;
  lastname: string;
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
  attachments: Attachment[];
  createdAt: string;
  details: ExpenseDetails;
  event: Event;
  id: number;
  refundRequired: boolean;
  status: string;
  statusComment: string;
  user: User;
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
}
