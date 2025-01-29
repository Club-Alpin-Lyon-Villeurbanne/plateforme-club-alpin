<template>
  <div class="tw-relative tw-inline-block">
    <button
      type="button"
      @click="handleToggleTooltip"
      @blur="handleBlur"
      class="tw-flex tw-items-center tw-justify-center tw-rounded-full tw-bg-gray-100 tw-p-1.5 tw-text-gray-600 hover:tw-bg-gray-200 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-indigo-600"
      :aria-label="ariaLabel"
      :aria-expanded="isVisible"
    >
      <span class="tw-text-lg">ℹ️</span>
    </button>
    <div
      v-if="isVisible"
      class="tw-absolute tw-left-full tw-top-0 tw-ml-2 tw-w-64 tw-rounded-md tw-bg-gray-900 tw-p-2 tw-text-sm tw-text-white tw-shadow-lg"
    >
      <div class="tw-flex tw-justify-between tw-items-start">
        <div>
          {{ text }}
          <br />
          <a 
            href="https://docs.google.com/document/d/1rvqnbTK0wZoWX8k-XHNeyC-bdY5uhV1S37jYK0mUtto/edit?usp=sharing" 
            target="_blank" 
            rel="noopener noreferrer"
            class="tw-text-blue-300 hover:tw-text-blue-200 tw-underline"
          >
            Consultez l'aide
          </a>
          pour plus d'infos.
        </div>
        <button
          @click="isVisible = false"
          class="tw-ml-2 tw-text-gray-400 hover:tw-text-white"
          aria-label="Fermer"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="tw-h-4 tw-w-4"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
            <path
              fill-rule="evenodd"
              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
              clip-rule="evenodd"
            />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

defineProps<{
  text: string;
  ariaLabel: string;
}>();

const isVisible = ref(false);

const handleToggleTooltip = () => {
  isVisible.value = !isVisible.value;
};

const handleBlur = () => {
  isVisible.value = false;
};
</script> 