<template>
  <div class="tw-border-b tw-border-gray-900/10 tw-pb-6">
    <div class="tw-flex tw-items-center tw-gap-2">
      <h2 class="tw-text-lg tw-font-medium tw-leading-7 tw-text-gray-900">
        Hébergement
      </h2>
      <InfoTooltip
        text="L'hébergement comprends les nuitées, le petit déjeuner et le repas du soir."
        ariaLabel="Informations sur l'hébergement"
      />
    </div>
    <p class="tw-text-sm tw-leading-6 tw-text-gray-600">
      Le remboursement de l'hébergement se fait sur la base de
      {{ expenseReportConfig.nuiteeMaxRemboursable }}€ par jour
    </p>

    <div
      v-for="(field, idx) in accommodations"
      :key="field.key"
      class="tw-mt-5 tw-flex"
    >
      <div class="tw-flex tw-gap-5">
        <ExpenseFieldV2
          :name="`accommodations[${idx}].price`"
          :expense-id="field.value.expenseId"
          :label="`Hébergement ${idx + 1}`"
          :requires-attachment="true"
        />
        <div class="tw-flex tw-flex-col">
          <div class="tw-flex tw-min-w-full">
            <Input
              label="Commentaire"
              :name="`accommodations[${idx}].comment`"
              type="text"
              :input-width="`tw-w-fit`"
              :class="`block tw-focus:ring-2 tw-h-7 tw-w-64 tw-grow tw-rounded-md tw-py-1.5 tw-pl-2.5 tw-text-gray-900 tw-shadow-sm tw-ring-1 tw-ring-inset tw-ring-gray-300 focus:tw-outline-none focus:tw-ring-inset focus:tw-ring-indigo-600`"
            />
            <div
              @click="removeAccommodation(idx)"
              class="tw-ml-12 tw-flex tw-h-7 tw-w-7 tw-cursor-pointer tw-items-center tw-justify-center tw-self-end tw-rounded-md tw-shadow-sm tw-ring-1 tw-ring-inset tw-ring-gray-300 tw-transition-colors tw-duration-200 hover:tw-text-red-500 hover:tw-ring-red-500 focus:tw-text-red-500 focus:tw-outline-none focus:tw-ring-red-500 focus:tw-ring-opacity-50"
            >
              <div class="block tw-h-4 tw-w-4" v-html="trashIcon"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <button
      @click.prevent="addAccommodation"
      class="tw-mt-8 tw-rounded-full tw-border tw-border-dashed tw-border-slate-400/70 tw-bg-transparent tw-px-2 tw-py-1 hover:tw-cursor-pointer hover:tw-border-slate-400"
    >
      + Ajouter un hébergement
    </button>
  </div>
</template>

<script lang="ts" setup>
import ExpenseFieldV2 from "./ExpenseFieldV2.vue";
import { useAttachments } from "../composables/useAttachment";
import { Accommodation } from "../types/api";
import expenseReportConfig from "../config/expense-reports.json";
import trashIcon from "../assets/svg/trash.svg?raw";
import { useFieldArray } from "vee-validate";
import InfoTooltip from "./InfoTooltip.vue";
import Input from "./Input.vue";

const {
  remove,
  push,
  fields: accommodations,
} = useFieldArray<Accommodation>("accommodations");

const { removeAttachmentByExpenseId } = useAttachments();

const addAccommodation = () => {
  push({
    expenseId: `acc_${Date.now()}`,
    price: 0,
    comment: "",
  });
};

const removeAccommodation = (index: number) => {
  const accommodation = accommodations.value[index];
  remove(index);
  removeAttachmentByExpenseId(accommodation.value.expenseId);
};
</script>

<style scoped></style>
