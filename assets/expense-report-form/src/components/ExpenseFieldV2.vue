<template>
  <div class="tw-flex tw-flex-col">
    <Input :name="name" :label="label" type="number" @changed="handleChange" />
    <div v-if="requiresAttachment" class="tw-mt-2">
      <div v-if="attachment">
        <a class="hover:tw-underline" :href="attachment.fileUrl" target="_blank"
          >Voir le justificatif</a
        >
      </div>

      <div>
        <input
          type="file"
          ref="fileInput"
          @change="handleFileUpload"
          :id="expenseId"
          class="tw-hidden"
        />
        <label
          class="tw-cursor-pointer hover:tw-underline"
          :class="{
            'tw-italic tw-text-red-500':
              !attachment && highlightAttachmentLabel,
          }"
          :for="expenseId"
          >{{ attachment ? "Remplacer" : "Joindre un justificatif" }}</label
        >
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref, computed, inject, onMounted, onUnmounted } from "vue";
import axios from "axios";
import Input from "./Input.vue";
import { useAttachments } from "../composables/useAttachment";
import { ExpenseReportKey } from "../composables/useExpenseReport";
import { useRequiredAttachmentFields } from "../composables/useRequiredAttachmentFields";

const props = withDefaults(
  defineProps<{
    name: string;
    label: string;
    expenseId: string;
    requiresAttachment?: boolean;
  }>(),
  {
    requiresAttachment: false,
  },
);

let highlightAttachmentLabel = ref(false);

const { getAttachmentByExpenseId, updateAttachment } = useAttachments();
const { expenseReport } = inject(ExpenseReportKey)!;

const attachment = computed(() => getAttachmentByExpenseId(props.expenseId));

const fileInput = ref<HTMLInputElement | null>(null);

const handleFileUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (!target.files?.length) return;

  const file = target.files[0];
  const formData = new FormData();
  formData.append("file", file);
  formData.append("expenseId", props.expenseId);

  try {
    const response = await axios.post(
      `/expense-reports/${expenseReport.value.id}/attachments`,
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

const handleChange = () => {
  highlightAttachmentLabel.value = true;
};

const { registerField, unregisterField } = useRequiredAttachmentFields();

onMounted(() => {
  if (props.requiresAttachment && registerField) {
    registerField({
      name: props.name,
      expenseId: props.expenseId,
      label: props.label,
    });
  }
});

onUnmounted(() => {
  if (props.requiresAttachment && unregisterField) {
    unregisterField(props.expenseId);
  }
});
</script>
