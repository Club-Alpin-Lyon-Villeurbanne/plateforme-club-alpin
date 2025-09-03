<template>
    <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-6">
      <div class="tw-mb-8">
        <h2 class="tw-text-xl tw-font-medium tw-mb-4">Récapitulatif de votre note de frais</h2>
        <div v-if="expenseReport.status === ExpenseStatus.SUBMITTED" class="tw-bg-blue-50 tw-p-4 tw-rounded-lg">
          <p class="tw-text-blue-700">Votre note de frais est en attente de vérification par la comptabilité.</p>
          <p class="tw-text-blue-700">Vous serez informé(e) de son acceptation ou de son refus par email.</p>
        </div>
        <div v-else-if="expenseReport.status === ExpenseStatus.APPROVED" class="tw-bg-green-50 tw-p-4 tw-rounded-lg">
          <p class="tw-text-green-700">Votre note de frais a été acceptée.</p>
          <p class="tw-text-green-700">Elle sera prochainement intégrée dans l'outil de comptabilité.</p>
        </div>
        <div v-else-if="expenseReport.status === ExpenseStatus.ACCOUNTED" class="tw-bg-purple-50 tw-p-4 tw-rounded-lg">
          <p class="tw-text-purple-700">Votre note de frais a été intégrée dans l'outil de comptabilité et n'est donc plus modifiable.</p>
        </div>
      </div>
  
      <ExpenseReportSection
        title="Transport"
        :items="formatTransport(expenseReport.details.transport)"
      />
  
      <ExpenseReportSection
        v-if="expenseReport.details.accommodations.length"
        title="Hébergements"
        :items="[
          ...formatAccommodations(expenseReport.details.accommodations),
          { label: 'Total', value: expenseSummary.accommodationTotal, isTotal: true },
          { label: 'Montant remboursable', value: expenseSummary.accommodationReimbursable, isTotal: true }
        ]"
      />
  
      <ExpenseReportSection
        v-if="expenseReport.details.others.length"
        title="Autres dépenses"
        :items="formatOthers(expenseReport.details.others)"
      />
  
      <div class="tw-border-t tw-border-gray-200 tw-pt-4">
        <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
          <span class="tw-font-medium">Total</span>
          <span class="tw-text-lg tw-font-semibold">{{ expenseSummary.total }}</span>
        </div>
        <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
          <span class="tw-font-medium">Total remboursable</span>
          <span class="tw-text-lg tw-font-semibold">{{ expenseSummary.totalReimbursable }}</span>
        </div>
        <div class="tw-bg-gray-50 tw-p-4 tw-rounded-lg tw-text-sm">
          <p v-if="expenseReport.refundRequired">
            ✓ Remboursement demandé
          </p>
          <p v-else>
            ✓ Don au club (un reçu fiscal vous sera envoyé en fin d'année)
          </p>
        </div>
      </div>
  
      <div v-if="expenseReport.status === ExpenseStatus.SUBMITTED || expenseReport.status === ExpenseStatus.APPROVED" class="tw-mt-6 tw-rounded-lg tw-bg-amber-50 tw-p-4">
        <p class="tw-text-sm tw-text-amber-800">
          Si vous avez fait une erreur et souhaitez modifier votre note de frais, merci d'en faire la demande auprès de la comptabilité: 
          <a href="mailto:comptabilite@clubalpinlyon.fr" class="tw-font-medium tw-underline hover:tw-text-amber-900">
            comptabilite@clubalpinlyon.fr
          </a>
          en indiquant le nom et la date de la sortie.
        </p>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { computed } from 'vue';
  import { ExpenseReport, ExpenseStatus } from '../types/api';
  import ExpenseReportSection from './ExpenseReportSection.vue';
  import { 
    formatTransport, 
    formatAccommodations, 
    formatOthers, 
    calculateTotal, 
    calculateAccommodationTotals,
    formatEuros 
  } from '../utils/expenseReportUtils';
  
  const props = defineProps<{
    expenseReport: ExpenseReport;
  }>();
  
  const expenseSummary = computed(() => {
    const totals = calculateTotal(props.expenseReport.details);
    const accommodation = calculateAccommodationTotals(props.expenseReport.details.accommodations);
  
    return {
      total: formatEuros(totals.total),
      totalReimbursable: formatEuros(totals.reimbursable),
      accommodationTotal: formatEuros(accommodation.total),
      accommodationReimbursable: formatEuros(accommodation.reimbursable),
    };
  });
  </script>