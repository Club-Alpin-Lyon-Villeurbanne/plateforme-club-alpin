<template>
  <div class="tw-border-b tw-border-gray-900/10 tw-pb-6 tw-text-sm">
    <h2 class="tw-text-lg tw-font-medium tw-leading-7 tw-text-gray-900">
      Résumé des dépenses
    </h2>

    <ul class="tw-ml-4">
      <li>
        Hébergement : {{ expenseSummary.accommodationTotal }} 
        (dont {{ expenseSummary.accommodationReimbursable }} remboursable)
      </li>
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
import { calculateTransportTotal, calculateAccommodationTotals, calculateTotal, formatEuros } from "../utils/expenseReportUtils";

const details = defineModel<ExpenseDetails>("modelValue");

const expenseSummary = computed(() => {
  if (!details.value) {
    return {
      transportation: formatEuros(0),
      accommodationTotal: formatEuros(0),
      accommodationReimbursable: formatEuros(0),
      otherExpenses: formatEuros(0),
      totalReimbursable: formatEuros(0),
    };
  }

  const transportTotal = calculateTransportTotal(details.value.transport);
  const accommodation = calculateAccommodationTotals(details.value.accommodations);
  const othersTotal = details.value.others.reduce((sum, other) => sum + (other.price || 0), 0);
  const totals = calculateTotal(details.value);

  return {
    transportation: formatEuros(transportTotal),
    accommodationTotal: formatEuros(accommodation.total),
    accommodationReimbursable: formatEuros(accommodation.reimbursable),
    otherExpenses: formatEuros(othersTotal),
    totalReimbursable: formatEuros(totals.reimbursable),
  };
});
</script>