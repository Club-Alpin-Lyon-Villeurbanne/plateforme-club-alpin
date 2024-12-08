import { Accommodation, Other, ExpenseDetails } from '../types/api';
import { TransportType, AllTransports } from '../types/transports';
import expenseReportConfig from "../config/expense-reports.json";

interface SectionItem {
  label: string;
  value: string | number;
  isTotal?: boolean;
}

// Formattage des montants
export const formatEuros = (amount: number): string => {
  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency: "EUR",
  }).format(amount);
};

// Calculs
export const calculateTransportTotal = (transport: AllTransports): number => {
  switch (transport.type) {
    case TransportType.PERSONAL_VEHICLE:
      const distanceTotal = (transport.distance || 0) * expenseReportConfig.tauxKilometriqueVoiture;
      return distanceTotal + (transport.tollFee || 0) / expenseReportConfig.divisionPeage;

    case TransportType.CLUB_MINIBUS:
      if (transport.passengerCount === 0) return 0;
      const minibusTotal = ((transport.distance || 0) * expenseReportConfig.tauxKilometriqueMinibus 
        + (transport.fuelExpense || 0) 
        + (transport.tollFee || 0)) / transport.passengerCount;
      return minibusTotal;

    case TransportType.RENTAL_MINIBUS:
      if (transport.passengerCount === 0) return 0;
      return (transport.rentalPrice + transport.fuelExpense + transport.tollFee) / transport.passengerCount;

    case TransportType.PUBLIC_TRANSPORT:
      return transport.ticketPrice;

    default:
      return 0;
  }
};

export const calculateAccommodationTotals = (accommodations: Accommodation[]): { total: number; reimbursable: number } => {
  return accommodations.reduce(
    (acc, accommodation) => {
      const price = accommodation.price || 0;
      acc.total += price;
      acc.reimbursable += Math.min(price, expenseReportConfig.nuiteeMaxRemboursable);
      return acc;
    },
    { total: 0, reimbursable: 0 }
  );
};

// Formatage pour affichage
export const formatTransport = (transport: AllTransports): SectionItem[] => {
  const transportLabels: Record<TransportType, string> = {
    [TransportType.PERSONAL_VEHICLE]: "Véhicule personnel",
    [TransportType.CLUB_MINIBUS]: "Minibus du club",
    [TransportType.RENTAL_MINIBUS]: "Minibus de location",
    [TransportType.PUBLIC_TRANSPORT]: "Transport en commun"
  };

  const items: SectionItem[] = [
    { label: "Type", value: transportLabels[transport.type] }
  ];

  switch (transport.type) {
    case TransportType.PERSONAL_VEHICLE:
      items.push(
        { label: "Distance", value: `${transport.distance} km` },
        { label: "Péage", value: `${transport.tollFee} €` }
      );
      break;

    case TransportType.CLUB_MINIBUS:
      items.push(
        { label: "Distance", value: `${transport.distance} km` },
        { label: "Péage", value: `${transport.tollFee} €` },
        { label: "Carburant", value: `${transport.fuelExpense} €` },
        { label: "Nombre de passagers", value: transport.passengerCount }
      );
      break;

    case TransportType.RENTAL_MINIBUS:
      items.push(
        { label: "Péage", value: `${transport.tollFee} €` },
        { label: "Carburant", value: `${transport.fuelExpense} €` },
        { label: "Location", value: `${transport.rentalPrice} €` },
        { label: "Nombre de passagers", value: transport.passengerCount }
      );
      break;

    case TransportType.PUBLIC_TRANSPORT:
      items.push(
        { label: "Prix du ticket", value: `${transport.ticketPrice} €` }
      );
      break;
  }

  return items;
};

export const formatAccommodations = (accommodations: Accommodation[]): SectionItem[] => {
  return accommodations.map((acc, index) => ({
    label: `Hébergement ${index + 1}`,
    value: `${acc.price} € ${acc.comment ? `- ${acc.comment}` : ''}`
  }));
};

export const formatOthers = (others: Other[]): SectionItem[] => {
  return others.map((other, index) => ({
    label: `Dépense ${index + 1}`,
    value: `${other.price} € ${other.comment ? `- ${other.comment}` : ''}`
  }));
};

export const calculateTotal = (details: ExpenseDetails): { total: number; reimbursable: number } => {
  const transportTotal = calculateTransportTotal(details.transport);
  const accommodations = calculateAccommodationTotals(details.accommodations);
  const othersTotal = details.others.reduce((sum, other) => sum + (other.price || 0), 0);

  return {
    total: transportTotal + accommodations.total + othersTotal,
    reimbursable: transportTotal + accommodations.reimbursable + othersTotal,
  };
};