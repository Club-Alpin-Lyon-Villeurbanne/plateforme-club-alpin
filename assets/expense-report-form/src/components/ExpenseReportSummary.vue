<template>
    <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-6">
      <div class="tw-mb-8">
        <h2 class="tw-text-xl tw-font-medium tw-mb-4">Récapitulatif de votre note de frais</h2>
        <div class="tw-bg-blue-50 tw-p-4 tw-rounded-lg">
          <p class="tw-text-blue-700">Votre note de frais est en attente de vérification par la comptabilité.</p>
          <p class="tw-text-blue-700">Vous serez informé(e) de son acceptation ou de son refus par email.</p>
        </div>
      </div>
  
      <ExpenseReportSection
        title="Transport"
        :items="formatTransport(expenseReport.details.transport)"
      />
  
      <ExpenseReportSection
        v-if="expenseReport.details.accommodations.length"
        title="Hébergements"
        :items="formatAccommodations(expenseReport.details.accommodations)"
      />
  
      <ExpenseReportSection
        v-if="expenseReport.details.others.length"
        title="Autres dépenses"
        :items="formatOthers(expenseReport.details.others)"
      />
  
      <div class="tw-border-t tw-border-gray-200 tw-pt-4">
        <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
          <span class="tw-font-medium">Total</span>
          <span class="tw-text-lg tw-font-semibold">
            {{ calculateTotal(expenseReport.details) }} €
          </span>
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
  
      <div class="tw-mt-6 tw-rounded-lg tw-bg-amber-50 tw-p-4">
        <p class="tw-text-sm tw-text-amber-800">
          Si vous avez fait une erreur et souhaitez modifier votre note de frais, merci d'en faire la demande auprès de la comptabilité: 
          <a href="mailto:comptabilite@clubalpinlyon.fr" class="tw-font-medium tw-underline hover:tw-text-amber-900">
            comptabilite@clubalpinlyon.fr</a>
          en indiquant le nom et la date de la sortie.
        </p>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ExpenseReport } from '../types/api';
  import ExpenseReportSection from './ExpenseReportSection.vue';
  import {
    formatTransport,
    formatAccommodations,
    formatOthers,
    calculateTotal
  } from '../utils/expenseReportUtils';
  
  defineProps<{
    expenseReport: ExpenseReport;
  }>();
  </script>