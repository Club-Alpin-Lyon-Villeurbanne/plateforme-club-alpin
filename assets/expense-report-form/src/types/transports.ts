interface Transport {
  type: string;
}

export enum TransportType {
  PERSONAL_VEHICLE = "PERSONAL_VEHICLE",
  CLUB_MINIBUS = "CLUB_MINIBUS",
  RENTAL_MINIBUS = "RENTAL_MINIBUS",
  PUBLIC_TRANSPORT = "PUBLIC_TRANSPORT",
}

export interface PersonalVehicle extends Transport {
  type: TransportType.PERSONAL_VEHICLE;
  tollFee: number;
  distance: number;
}

export interface MinibusRental extends Transport {
  type: TransportType.RENTAL_MINIBUS;
  tollFee: number;
  fuelExpense: number;
  rentalPrice: number;
  passengerCount: number;
}

export interface MinibusClub extends Transport {
  type: TransportType.CLUB_MINIBUS;
  tollFee: number;
  fuelExpense: number;
  distance: number;
  passengerCount: number;
}

export interface PublicTransport extends Transport {
  type: TransportType.PUBLIC_TRANSPORT;
  ticketPrice: number;
}

export type AllTransports =
  | PersonalVehicle
  | MinibusRental
  | MinibusClub
  | PublicTransport;
