<template>
  <div class="tw-flex tw-flex-col">
    <Input :name="name" :label="label" type="number" />
    
    <AttachmentField
      v-if="requiresAttachment"
      :expense-id="expenseId"
      :is-required="isValueFilled"
      :attachment="attachment"
      @fileChanged="handleFileChanged"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, inject, watch, onUnmounted, onMounted } from "vue";
import axios from "axios";
import Input from "./Input.vue";
import AttachmentField from "./AttachmentField.vue";
import { useAttachments } from "../composables/useAttachment";
import { ExpenseReportKey } from "../composables/useExpenseReport";
import { useRequiredAttachmentFields } from "../composables/useRequiredAttachmentFields";
import { useField } from "vee-validate";
import config from "../config/expense-reports.json";

interface Props {
  name: string;
  label: string;
  expenseId: string;
  requiresAttachment?: boolean;
}

const props = withDefaults(
  defineProps<Props>(),
  {
    requiresAttachment: false,
  },
);

const { getAttachmentByExpenseId, updateAttachment } = useAttachments();
const { expenseReport } = inject(ExpenseReportKey)!;
const { registerField, unregisterField } = useRequiredAttachmentFields();
const { value } = useField(() => props.name);

const attachment = computed(() => getAttachmentByExpenseId(props.expenseId));
const isValueFilled = computed(() => Number(value.value) > 0);

const handleFileChanged = async (file: File) => {
  const formData = new FormData();
  formData.append("file", file);
  formData.append("expenseId", props.expenseId);

  try {
    if (!expenseReport.value?.id) {
      console.error("No expense report ID available");
      return;
    }

    const response = await axios.post(
      `${config.endpoints.notesDeFrais}/${expenseReport.value.id}/pieces-jointes`,
      formData,
      {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      },
    );
    updateAttachment(props.expenseId, { ...response.data });
  } catch (error) {
    console.error("Erreur lors de l'upload du fichier:", error);
  }
};

// On surveille les changements de valeur
watch(value, (newValue) => {
  if (!props.requiresAttachment) return;

  const numericValue = Number(newValue);
  if (numericValue > 0) {
    registerField({
      name: props.name,
      expenseId: props.expenseId,
      label: props.label,
    });
  } else {
    unregisterField(props.expenseId);
    if (attachment.value) {
      updateAttachment(props.expenseId, null);
    }
  }
});

// Initialisation
onMounted(() => {
  if (props.requiresAttachment && isValueFilled.value) {
    registerField({
      name: props.name,
      expenseId: props.expenseId,
      label: props.label,
    });
  }
});

// Nettoyage
onUnmounted(() => {
  if (props.requiresAttachment) {
    unregisterField(props.expenseId);
  }
});
</script>