<template>
  <div class="tw-border-b tw-border-gray-900/10 tw-pb-6 tw-text-sm">
    <h2 class="tw-text-lg tw-font-medium tw-leading-7 tw-text-gray-900">
      Résumé des dépenses
    </h2>

    <ul class="tw-ml-4">
      <li>Hébergement : {{ expenseSummary.accommodationTotal }}</li>
      <li>Transport : {{ expenseSummary.transportation }}</li>
      <li>Autres dépenses : {{ expenseSummary.otherExpenses }}</li>
    </ul>
    <p class="tw-mt-2">
      <span class="tw-font-semibold">Total remboursable : </span>
      <span class="refund-amount">{{ expenseSummary.totalReimbursable }}</span>
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { ExpenseDetails } from "../types/api";
import {
  MinibusClub,
  MinibusRental,
  PersonalVehicle,
  PublicTransport,
  TransportType,
} from "../types/transports";
import expenseReportConfig from "../config/expense-reports.json";

const details = defineModel<ExpenseDetails>("modelValue");

const calculatePersonalVehicleTotal = (transport: PersonalVehicle): number => {
  const distanceTotal =
    (transport.distance || 0) * expenseReportConfig.tauxKilometriqueVoiture;
  const tollTotal =
    (transport.tollFee || 0) / expenseReportConfig.divisionPeage;
  return distanceTotal + tollTotal;
};

const calculatePublicTransportTotal = (transport: PublicTransport): number => {
  return transport.ticketPrice || 0;
};

const calculateMinibusRentalTotal = (transport: MinibusRental): number => {
  let total = 0;
  if (transport.passengerCount !== 0) {
    total += (transport.rentalPrice || 0) / transport.passengerCount;
    total += (transport.fuelExpense || 0) / transport.passengerCount;
    total += (transport.tollFee || 0) / transport.passengerCount;
  }
  return total;
};

const calculateClubMinibusTotal = (transport: MinibusClub): number => {
  const {
    distance,
    fuelExpense: fuel,
    tollFee: toll,
    passengerCount: passengers,
  } = transport;
  let total = 0;
  if (passengers !== 0) {
    total +=
      ((distance || 0) * expenseReportConfig.tauxKilometriqueMinibus) /
      passengers;
    total += (fuel || 0) / passengers;
    total += (toll || 0) / passengers;
  }
  return total;
};

const transportationTotal = computed(() => {
  if (!details.value?.transport) return 0;

  const transport = details.value.transport;

  switch (transport.type) {
    case TransportType.PERSONAL_VEHICLE:
      return calculatePersonalVehicleTotal(transport as PersonalVehicle);
    case TransportType.PUBLIC_TRANSPORT:
      return calculatePublicTransportTotal(transport as PublicTransport);
    case TransportType.RENTAL_MINIBUS:
      return calculateMinibusRentalTotal(transport as MinibusRental);
    case TransportType.CLUB_MINIBUS:
      return calculateClubMinibusTotal(transport as MinibusClub);
    default:
      return 0;
  }
});

const accommodationTotal = computed(
  (): { total: number; reimbursable: number } => {
    if (!details.value?.accommodations) return { total: 0, reimbursable: 0 };

    return details.value.accommodations.reduce(
      (acc, accommodation) => {
        const price = accommodation.price || 0;
        acc.total += price;
        acc.reimbursable += Math.min(
          price,
          expenseReportConfig.nuiteeMaxRemboursable,
        );
        return acc;
      },
      { total: 0, reimbursable: 0 },
    );
  },
);

const otherExpensesTotal = computed((): number => {
  if (!details.value?.others) return 0;

  return details.value.others.reduce((total, expense) => {
    return total + (expense.price || 0);
  }, 0);
});

const grandTotal = computed((): { total: number; reimbursable: number } => {
  return {
    total:
      transportationTotal.value +
      accommodationTotal.value.total +
      otherExpensesTotal.value,
    reimbursable:
      transportationTotal.value +
      accommodationTotal.value.reimbursable +
      otherExpensesTotal.value,
  };
});

const formatEuros = (amount: number): string => {
  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency: "EUR",
  }).format(amount);
};

const expenseSummary = computed(() => {
  return {
    transportation: formatEuros(transportationTotal.value),
    accommodationTotal: formatEuros(accommodationTotal.value.total),
    accommodationReimbursable: formatEuros(
      accommodationTotal.value.reimbursable,
    ),
    otherExpenses: formatEuros(otherExpensesTotal.value),
    total: formatEuros(grandTotal.value.total),
    totalReimbursable: formatEuros(grandTotal.value.reimbursable),
  };
});
</script>

<style scoped></style>
