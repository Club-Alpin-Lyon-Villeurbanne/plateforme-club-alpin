<template>
  <div class="tw-border-b tw-border-gray-900/10 tw-pb-6">
    <div class="tw-flex tw-items-center tw-gap-2">
      <h2 class="tw-text-lg tw-font-medium tw-leading-7 tw-text-gray-900">
        Transport
      </h2>
      <InfoTooltip
        text="Sélectionnez le type de transport."
        ariaLabel="Informations sur le transport"
      />
    </div>
    <p class="tw-text-sm tw-leading-6 tw-text-gray-600">
      Si vous avez plusieurs justificatifs, vous pouvez les fusionner en un seul
      avant de les joindre.
    </p>
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
        <div 
          v-if="valueType === TransportType.PERSONAL_VEHICLE || valueType === TransportType.CLUB_MINIBUS"
          class="tw-mt-1 tw-text-xs tw-text-gray-500"
        >
          Indemnité kilométrique : {{ valueType === TransportType.PERSONAL_VEHICLE ? config.tauxKilometriqueVoiture : config.tauxKilometriqueMinibus }}€/km
        </div>
      </div>

      <div>
        <div
          v-if="valueType === TransportType.PERSONAL_VEHICLE"
        >
          <div class="tw-grid tw-grid-cols-2 tw-gap-x-8 tw-gap-y-4">
            <ExpenseField
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
        </div>
        <div
          class="tw-grid tw-grid-cols-2 tw-gap-x-8 tw-gap-y-4"
          v-else-if="valueType === TransportType.RENTAL_MINIBUS"
        >
          <ExpenseField
            name="transport.tollFee"
            expense-id="tollFee"
            label="Péage (€)"
            :requires-attachment="true"
          />
          <ExpenseField
            name="transport.fuelExpense"
            expense-id="fuelExpense"
            label="Carburant (€)"
            :requires-attachment="true"
          />
          <ExpenseField
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
          v-if="valueType === TransportType.CLUB_MINIBUS"
        >
          <div class="tw-grid tw-grid-cols-2 tw-gap-x-8 tw-gap-y-4">
            <ExpenseField
              name="transport.tollFee"
              expense-id="tollFee"
              label="Péage (€)"
              :requires-attachment="true"
            />
            <ExpenseField
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
        </div>
        <div
          class="tw-grid tw-grid-cols-2 tw-gap-x-8 tw-gap-y-4"
          v-if="valueType === TransportType.PUBLIC_TRANSPORT"
        >
          <ExpenseField
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
import ExpenseField from "./ExpenseField.vue";

import Input from "./Input.vue";
import InfoTooltip from "./InfoTooltip.vue";
import { useField } from "vee-validate";
import { onMounted } from "vue";
import config from "../config/expense-reports.json";
const { value: valueType } = useField<TransportType>("transport.type");

const transportOptions = [
  { value: TransportType.PERSONAL_VEHICLE, label: "Véhicule personnel" },
  { value: TransportType.CLUB_MINIBUS, label: "Minibus du club" },
  { value: TransportType.RENTAL_MINIBUS, label: "Minibus de location" },
  { value: TransportType.PUBLIC_TRANSPORT, label: "Transport en commun" },
];

onMounted(() => {
  if (!valueType.value) {
    valueType.value = TransportType.PERSONAL_VEHICLE;
  }
});
</script>
