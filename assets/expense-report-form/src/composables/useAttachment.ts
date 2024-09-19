import { ref, readonly } from "vue";
import { Attachment } from "../types/api";

const attachments = ref<Attachment[]>([]);

export function useAttachments() {
  const setAttachments = (newAttachments: Attachment[]) => {
    attachments.value = newAttachments;
  };

  const getAttachmentByExpenseId = (expenseId: string): Attachment | null => {
    return attachments.value.find((att) => att.expenseId === expenseId) || null;
  };

  const updateAttachment = (
    expenseId: string,
    newAttachment: Attachment | null,
  ) => {
    const index = attachments.value.findIndex(
      (att) => att.expenseId === expenseId,
    );
    if (index !== -1) {
      if (newAttachment) {
        attachments.value[index] = newAttachment;
      } else {
        attachments.value.splice(index, 1);
      }
    } else if (newAttachment) {
      attachments.value.push(newAttachment);
    }
  };

  const removeAttachmentByExpenseId = (expenseId: string) => {
    attachments.value = attachments.value.filter(
      (att) => att.expenseId !== expenseId,
    );
  };

  return {
    attachments: readonly(attachments),
    setAttachments,
    getAttachmentByExpenseId,
    updateAttachment,
    removeAttachmentByExpenseId,
  };
}
