import { Accommodation, Other, ExpenseDetails } from '../types/api';
import { TransportType, AllTransports } from '../types/transports';

interface SectionItem {
  label: string;
  value: string | number;
}

export const formatTransport = (transport: AllTransports): SectionItem[] => {
  const transportLabels: Record<TransportType, string> = {
    [TransportType.PERSONAL_VEHICLE]: "Véhicule personnel",
    [TransportType.CLUB_MINIBUS]: "Minibus du club",
    [TransportType.RENTAL_MINIBUS]: "Minibus de location",
    [TransportType.PUBLIC_TRANSPORT]: "Transport en commun"
  };

  const items: SectionItem[] = [
    { label: "Type", value: transportLabels[transport.type as TransportType] }
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

export const calculateTotal = (details: ExpenseDetails): number => {
    let transportTotal = 0;
    const transport = details.transport;

  switch (transport.type) {
    case TransportType.PERSONAL_VEHICLE:
      transportTotal = transport.tollFee;
      break;
    case TransportType.CLUB_MINIBUS:
      transportTotal = transport.tollFee + transport.fuelExpense;
      break;
    case TransportType.RENTAL_MINIBUS:
      transportTotal = transport.tollFee + transport.fuelExpense + transport.rentalPrice;
      break;
    case TransportType.PUBLIC_TRANSPORT:
      transportTotal = transport.ticketPrice;
      break;
  }

  const accommodationsTotal = details.accommodations.reduce((sum: number, acc: Accommodation) => sum + acc.price, 0);
  const othersTotal = details.others.reduce((sum: number, other: Other) => sum + other.price, 0);

  return transportTotal + accommodationsTotal + othersTotal;
};