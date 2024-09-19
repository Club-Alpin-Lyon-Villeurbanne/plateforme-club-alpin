import { readonly, ref } from "vue";

interface AttachmentField {
  name: string;
  expenseId: string;
  label: string;
}

const fields = ref<AttachmentField[]>([]);

export function useRequiredAttachmentFields() {
  const registerField = (field: AttachmentField) => {
    const existingIndex = fields.value.findIndex(
      (f) => f.expenseId === field.expenseId,
    );
    if (existingIndex === -1) {
      fields.value.push(field);
    } else {
      fields.value[existingIndex] = field;
    }
  };

  const unregisterField = (expenseId: string) => {
    fields.value = fields.value.filter((f) => f.expenseId !== expenseId);
  };

  return {
    registerField,
    unregisterField,
    requiredFields: readonly(fields),
  };
}
