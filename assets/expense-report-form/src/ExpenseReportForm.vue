<template>
  <div
    class="expense-report-form tw-mb-6 tw-mr-[2rem] tw-bg-white tw-px-4 tw-font-sans tw-ring-1 tw-ring-slate-900/10"
  >
    <div
      class="tw-flex tw-flex-row tw-justify-between tw-border-b tw-border-slate-300 tw-px-4 tw-py-4"
    >
      <span class="antialiased tw-text-2xl tw-font-medium">Note de frais</span>
      <span
        v-if="expenseReport"
        :class="`${badge.bgColor} ${badge.fontColor}`"
        class="tw-rounded-full tw-px-4 tw-py-2 tw-font-bold tw-capitalize"
        >{{ badge.label }}</span
      >
    </div>
    <div class="tw-py-4">
      <div v-if="isLoading">Chargement de la note de frais...</div>
      <div v-else-if="expenseReport">
        <div v-if="expenseReport.status === ExpenseStatus.SUBMITTED || 
                     expenseReport.status === ExpenseStatus.APPROVED || 
                     expenseReport.status === ExpenseStatus.ACCOUNTED">
          <ExpenseReportSummary :expense-report="expenseReport" />
        </div>
        <div v-else>
          <div
            v-if="expenseReport.statusComment"
            class="tw-mb-4 tw-rounded-lg tw-border-x-4 tw-border-red-500 tw-bg-red-50 tw-p-2"
          >
            <div class="tw-text-red-700">
              <p class="tw-font-semibold tw-italic">
                {{ expenseReport.statusComment }}
              </p>
            </div>
          </div>
          <form class="tw-space-y-6 tw-px-4">
            <TransportSection />
            <AccommodationSection />
            <OtherSection />
            <ResultSection v-model="values" />
            <div class="tw-mb-4">
              <h2
                class="tw-text-lg tw-font-medium tw-leading-7 tw-text-gray-900"
              >
                Remboursement
              </h2>
              <p class="tw-text-xs tw-leading-6 tw-text-gray-600">
                Si vous le souhaitez, vous pouvez faire don de cette note de
                frais au club. <br />
                Vous recevrez en fin d'année un reçu fiscal en attestant.
              </p>
              <div class="tw-mt-2 tw-space-y-2">
                <div class="tw-flex tw-items-center tw-gap-x-3">
                  <input
                    type="radio"
                    id="refund_required_no"
                    name="refundRequired"
                    :value="false"
                    v-model="refundRequired"
                    class="tw-h-4 tw-w-4 tw-border-gray-300 tw-text-indigo-600 focus:tw-ring-indigo-600"
                  />
                  <label
                    for="refund_required_no"
                    class="tw-block tw-text-sm tw-font-medium tw-leading-6 tw-text-gray-900"
                    >Je fais don de cette note de frais au club
                  </label>
                </div>
                <div class="tw-flex tw-items-center tw-gap-x-3">
                  <input
                    type="radio"
                    id="refund_required_yes"
                    name="refundRequired"
                    :value="true"
                    v-model="refundRequired"
                    class="tw-h-4 tw-w-4 tw-border-gray-300 tw-text-indigo-600 focus:tw-ring-indigo-600"
                  />
                  <label
                    for="refund_required_no"
                    class="tw-block tw-text-sm tw-font-medium tw-leading-6 tw-text-gray-900"
                    >Je demande le remboursement de cette note de frais</label
                  >
                </div>
              </div>
            </div>
          </form>
          <div class="tw-mt-10 tw-flex tw-justify-end tw-gap-5">
            <button
              v-if="expenseReport.status === ExpenseStatus.DRAFT"
              type="button"
              :disabled="isSaveButtonLoading"
              :class="{
                'tw-cursor-not-allowed tw-opacity-50': isSaveButtonLoading,
              }"
              @click="onSaveAsDraft"
              class="tw-cursor-pointer tw-rounded-md tw-bg-slate-400 tw-px-2 tw-py-2 tw-font-semibold tw-text-white"
            >
              Enregistrer en brouillon
            </button>
            <button
              type="button"
              :disabled="isSubmitButtonLoading"
              :class="{
                'tw-cursor-not-allowed tw-opacity-50': isSubmitButtonLoading,
              }"
              @click="onSubmit"
              class="tw-cursor-pointer tw-rounded-md tw-bg-sky-500 tw-px-2 tw-py-2 tw-font-semibold tw-text-white"
            >
              {{ submitLabel }}
            </button>
          </div>
        </div>
      </div>
      <div v-else>Impossible de charger la note de frais</div>
    </div>
    <div class="tw-mt-4 tw-text-center">
      <a
        href="https://forms.gle/pjoKg3myPKhPmhdC6"
        target="_blank"
        rel="noopener noreferrer"
        class="tw-text-blue-600 hover:tw-underline"
        tabindex="0"
        aria-label="Formulaire de retour d'avis"
      >
        💬 Donnez-nous votre avis
      </a>
    </div>
    <div class="tw-h-4"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { ExpenseDetails, ExpenseStatus } from "./types/api";
import AccommodationSection from "./components/AccommodationSection.vue";
import TransportSection from "./components/TransportSection.vue";
import ExpenseReportSummary from "./components/ExpenseReportSummary.vue";
import ResultSection from "./components/ResultSection.vue";
import OtherSection from "./components/OtherSection.vue";
import { useExpenseReport } from "./composables/useExpenseReport";
import { schema } from "./schemas/expenses";
import { useField, useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/zod";
import { useAttachments } from "./composables/useAttachment";
import { useRequiredAttachmentFields } from "./composables/useRequiredAttachmentFields";

const currentEventId = (window as any).globals.currentEventId;

const isSubmitButtonLoading = ref(false);
const isSaveButtonLoading = ref(false);

const { expenseReport, isLoading, saveAsDraft, submit } =
  useExpenseReport(currentEventId);
const { getAttachmentByExpenseId } = useAttachments();
const { requiredFields } = useRequiredAttachmentFields();

const { handleSubmit, resetForm, values } = useForm<
  ExpenseDetails & { refundRequired: boolean }
>({
  validationSchema: toTypedSchema(schema),
  keepValuesOnUnmount: true,
});

const { value: refundRequired } = useField("refundRequired", undefined, {
  initialValue: false,
});

const badge = computed(
  (): { label: string; bgColor: string; fontColor: string } => {
    switch (expenseReport.value?.status) {
      case ExpenseStatus.DRAFT:
        return {
          label: "Brouillon",
          bgColor: "tw-bg-gray-100",
          fontColor: "tw-text-gray-700",
        };
      case ExpenseStatus.SUBMITTED:
        return {
          label: "En vérification",
          bgColor: "tw-bg-blue-50",
          fontColor: "tw-text-blue-700",
        };
      case ExpenseStatus.APPROVED:
        return {
          label: "Acceptée",
          bgColor: "tw-bg-green-50",
          fontColor: "tw-text-green-700",
        };
      case ExpenseStatus.REJECTED:
        return {
          label: "Refusée",
          bgColor: "tw-bg-red-50",
          fontColor: "tw-text-red-700",
        };
      case ExpenseStatus.ACCOUNTED:
        return {
          label: "Comptabilisée",
          bgColor: "tw-bg-purple-50",
          fontColor: "tw-text-purple-700",
        };
      default:
        return { label: "", bgColor: "", fontColor: "" };
    }
  },
);

const submitLabel = computed(() => {
  const isRejected = expenseReport.value?.status === ExpenseStatus.REJECTED;
  return `Soumettre la note${!isRejected ? "" : " corrigée"}`;
});

const checkAttachments = () => {
  for (const field of requiredFields.value) {
    const attachmentExists = getAttachmentByExpenseId(field.expenseId);
    if (!attachmentExists) {
      return { isValid: false, errorField: field.name, label: field.label };
    }
  }
  return { isValid: true };
};

const onInvalidSubmit = ({ errors }: { errors: Record<string, any> }) => {
  const firstError = Object.keys(errors)[0];
  const el = document.querySelector<HTMLElement>(`[name="${firstError}"]`);
  el?.scrollIntoView({
    behavior: "smooth",
  });
  el?.focus();
};

const onValidSubmit = async (values: Record<string, any>) => {
  const attachmentCheck = checkAttachments();
  if (!attachmentCheck.isValid) {
    alert(
      `Veuillez joindre un justificatif pour le champ : ${attachmentCheck.label}`,
    );
    const el = document.querySelector<HTMLElement>(
      `[name="${attachmentCheck.errorField}"]`,
    );
    el?.scrollIntoView({
      behavior: "smooth",
    });
    el?.focus();
    return;
  }

  isSubmitButtonLoading.value = true;
  const { refundRequired, transport, accommodations, others, ...rest } = values;
  const details = {
    transport,
    accommodations, 
    others,
    ...rest
  };
  await submit({ details, refundRequired });

  isSubmitButtonLoading.value = false;
};

const onSubmit = handleSubmit(onValidSubmit, onInvalidSubmit);

const onSaveAsDraft = async () => {
  isSaveButtonLoading.value = true;

  const { refundRequired, ...details } = values;
  await saveAsDraft({ details, refundRequired });

  isSaveButtonLoading.value = false;
};

// Watcher to set form values using fetchedReport
watch(
  expenseReport,
  (newValue) => {
    resetForm({
      values: {
        ...newValue?.details,
        refundRequired: newValue?.refundRequired,
      },
    });
  },
  { once: true },
);
</script>
