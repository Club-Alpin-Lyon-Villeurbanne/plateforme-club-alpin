<template>
  <div class="tw-mt-1">
    <!-- Si un fichier est déjà attaché -->
    <div v-if="attachment" class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
      <div class="tw-flex tw-items-center tw-gap-1 tw-text-green-600">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 6L9 17l-5-5"/>
        </svg>
        <span>Justificatif fourni</span>
      </div>
      <a 
        :href="attachment.fileUrl" 
        target="_blank"
        class="tw-text-blue-600 hover:tw-underline"
      >
        Voir
      </a>
      <button 
        @click="openFileInput"
        class="tw-text-gray-600 hover:tw-text-gray-800"
        type="button"
      >
        Remplacer
      </button>
    </div>

    <!-- Si aucun fichier n'est attaché -->
    <div 
      v-else 
      class="tw-inline-block tw-border tw-border-dashed tw-rounded tw-py-1 tw-px-2.5 tw-bg-gray-50 hover:tw-bg-gray-100 tw-cursor-pointer tw-transition-colors"
      :class="{ 'tw-border-red-300 tw-bg-red-50 hover:tw-bg-red-100': isRequired }"
      @click="openFileInput"
    >
      <div class="tw-flex tw-items-center tw-gap-1.5">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tw-text-gray-400">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
          <polyline points="17 8 12 3 7 8"/>
          <line x1="12" y1="3" x2="12" y2="15"/>
        </svg>
        <span 
          :class="{ 'tw-text-red-600': isRequired }"
          class="tw-text-sm"
        >
          Justificatif requis
        </span>
      </div>
    </div>

    <input
      type="file"
      ref="fileInput"
      @change="handleFileChange"
      :id="expenseId"
      class="tw-hidden"
      accept="image/*,.pdf"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";

interface Props {
  expenseId: string;
  isRequired: boolean;
  attachment: {
    fileUrl: string;
  } | null;
}

defineProps<Props>();

const emit = defineEmits<{
  (e: "upload", file: File): void;
}>();

const fileInput = ref<HTMLInputElement | null>(null);

const openFileInput = () => {
  fileInput.value?.click();
};

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (!target.files?.length) return;

  emit("upload", target.files[0]);
};
</script> 