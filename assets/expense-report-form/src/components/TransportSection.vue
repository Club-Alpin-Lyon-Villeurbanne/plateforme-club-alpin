<template>
  <div class="tw-border-b tw-border-gray-900/10 tw-pb-6">
    <h2 class="tw-text-lg tw-font-medium tw-leading-7 tw-text-gray-900">
      Transport
    </h2>
    <div class="tw-mt-5 tw-flex tw-gap-10">
      <div class="tw-relative">
        <label class="tw-block tw-text-sm tw-leading-6 tw-text-gray-900"
          >Type de transport</label
        >
        <div class="tw-mt-0.5">
          <select
            name="transport.type"
            class="tw-h-7 tw-w-44 tw-appearance-none tw-rounded-md tw-bg-white tw-py-1.5 tw-pl-2.5 tw-pr-2 tw-text-gray-900 tw-shadow-sm tw-outline-none tw-ring-1 tw-ring-inset tw-ring-gray-300 focus:tw-ring-indigo-600"
            v-model="valueType"
          >
            <option
              v-for="option in transportOptions"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
        </div>
      </div>

      <div>
        <div
          class="tw-grid tw-grid-cols-2 tw-gap-x-8 tw-gap-y-4"
          v-if="valueType === TransportType.PERSONAL_VEHICLE"
        >
          <!-- <div>
          Taux d’indemnité kilométrique à
          {{ expenseReportConfig.tauxKilometriqueVoiture }}€/km
        </div> -->
          <ExpenseFieldV2
            name="transport.tollFee"
            expense-id="tollFee"
            label="Péage (€)"
            :requires-attachment="true"
          />
          <Input
            name="transport.distance"
            label="Distance (km)"
            type="number"
          />
        </div>
        <div
          class="tw-grid tw-grid-cols-2 tw-gap-x-8 tw-gap-y-4"
          v-else-if="valueType === TransportType.RENTAL_MINIBUS"
        >
          <ExpenseFieldV2
            name="transport.tollFee"
            expense-id="tollFee"
            label="Péage (€)"
            :requires-attachment="true"
          />
          <ExpenseFieldV2
            name="transport.fuelExpense"
            expense-id="fuelExpense"
            label="Carburant (€)"
            :requires-attachment="true"
          />
          <ExpenseFieldV2
            name="transport.rentalPrice"
            expense-id="rentalPrice"
            label="Location (€)"
            :requires-attachment="true"
          />
          <Input
            name="transport.passengerCount"
            label="Passagers"
            type="number"
            :default-value="9"
          />
        </div>
        <div
          class="tw-grid tw-grid-cols-2 tw-gap-x-8 tw-gap-y-4"
          v-if="valueType === TransportType.CLUB_MINIBUS"
        >
          <!-- <div>
          Taux d’indemnité kilométrique à
          {{ expenseReportConfig.tauxKilometriqueMinibus }}€/km
        </div> -->
          <ExpenseFieldV2
            name="transport.tollFee"
            expense-id="tollFee"
            label="Péage (€)"
            :requires-attachment="true"
          />
          <ExpenseFieldV2
            name="transport.fuelExpense"
            expense-id="fuelExpense"
            label="Carburant (€)"
            :requires-attachment="true"
          />
          <Input
            name="transport.distance"
            label="Distance (km)"
            type="number"
          />
          <Input
            name="transport.passengerCount"
            label="Passagers"
            type="number"
            :default-value="9"
          />
        </div>
        <div
          class="tw-grid tw-grid-cols-2 tw-gap-x-8 tw-gap-y-4"
          v-if="valueType === TransportType.PUBLIC_TRANSPORT"
        >
          <ExpenseFieldV2
            name="transport.ticketPrice"
            expense-id="ticketPrice"
            label="Prix du ticket (€)"
            :requires-attachment="true"
          />
          <Input
              :name="`transport.comment`"
              label="Commentaire"
              type="text"
            />
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { TransportType } from "../types/transports";
import ExpenseFieldV2 from "./ExpenseFieldV2.vue";

import Input from "./Input.vue";
import { useField } from "vee-validate";
import { onMounted } from "vue";

const { value: valueType } = useField<TransportType>("transport.type");

const transportOptions = [
  { value: TransportType.PERSONAL_VEHICLE, label: "Véhicule personnel" },
  { value: TransportType.CLUB_MINIBUS, label: "Minibus du club" },
  { value: TransportType.RENTAL_MINIBUS, label: "Minubus de location" },
  { value: TransportType.PUBLIC_TRANSPORT, label: "Transport en commun" },
];

onMounted(() => {
  if (!valueType.value) {
    valueType.value = TransportType.PERSONAL_VEHICLE;
  }
});
</script>
