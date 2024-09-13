<template>
  <div class="tw-border-b tw-border-gray-900/10 tw-pb-6">
    <h2 class="tw-text-lg tw-font-medium tw-leading-7 tw-text-gray-900">
      Autres dépenses
    </h2>
    <p class="tw-text-sm tw-leading-6 tw-text-gray-600">
      Toute autre dépense que vous souhaitez vous faire rembourser
    </p>
    <div
      v-for="(field, idx) in others"
      :key="field.key"
      class="tw-mt-5 tw-flex"
    >
      <div class="tw-flex tw-gap-5">
        <ExpenseFieldV2
          :name="`others[${idx}].price`"
          :expense-id="field.value.expenseId"
          :label="`Dépense ${idx + 1}`"
          :requires-attachment="true"
        />
        <div class="tw-flex tw-flex-col">
          <label
            class="tw-text-sm tw-leading-6 tw-text-gray-900"
            :for="`others[${idx}].comment`"
            >Commentaire</label
          >
          <div class="tw-flex tw-min-w-full">
            <input
              :name="`others[${idx}].comment`"
              type="text"
              class="block tw-focus:ring-2 tw-mt-0.5 tw-h-7 tw-w-64 tw-grow tw-rounded-md tw-py-1.5 tw-pl-2.5 tw-text-gray-900 tw-shadow-sm tw-ring-1 tw-ring-inset tw-ring-gray-300 focus:tw-outline-none focus:tw-ring-inset focus:tw-ring-indigo-600"
            />
            <div
              @click="removeOther(idx)"
              class="tw-ml-12 tw-flex tw-h-7 tw-w-7 tw-cursor-pointer tw-items-center tw-justify-center tw-self-end tw-rounded-md tw-shadow-sm tw-ring-1 tw-ring-inset tw-ring-gray-300 tw-transition-colors tw-duration-200 hover:tw-text-red-500 hover:tw-ring-red-500 focus:tw-text-red-500 focus:tw-outline-none focus:tw-ring-red-500 focus:tw-ring-opacity-50"
            >
              <div class="block tw-h-4 tw-w-4" v-html="trashIcon"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <button
      @click.prevent="addOther"
      class="tw-mt-8 tw-rounded-full tw-border tw-border-dashed tw-border-slate-400/70 tw-bg-transparent tw-px-2 tw-py-1 hover:tw-cursor-pointer hover:tw-border-slate-400"
    >
      + Ajouter une dépense
    </button>
  </div>
</template>

<script lang="ts" setup>
import ExpenseFieldV2 from "./ExpenseFieldV2.vue";
import { useAttachments } from "../composables/useAttachment";
import { Other } from "../types/api";
import trashIcon from "../assets/svg/trash.svg?raw";
import { useFieldArray } from "vee-validate";

const { remove, push, fields: others } = useFieldArray<Other>("others");

const { removeAttachmentByExpenseId } = useAttachments();

const addOther = () => {
  push({
    expenseId: `oth_${Date.now()}`,
    price: 0,
    comment: "",
  });
};

const removeOther = (index: number) => {
  const other = others.value[index];
  remove(index);
  removeAttachmentByExpenseId(other.value.expenseId);
};
</script>
