<template>
    <div class="tw-mb-6">
      <h3 class="tw-text-base tw-font-semibold tw-mb-3">{{ title }}</h3>
      <div class="tw-bg-gray-50 tw-rounded-lg tw-p-4">
        <dl class="tw-grid tw-gap-y-2">
          <div v-for="(item, idx) in items" 
               :key="idx"
               class="tw-grid tw-grid-cols-2">
            <dt class="tw-text-sm tw-text-gray-600">{{ item.label }}</dt>
            <dd class="tw-text-sm">{{ item.value }}</dd>
          </div>
        </dl>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  interface SectionItem {
    label: string;
    value: string | number;
  }
  
  defineProps<{
    title: string;
    items: SectionItem[];
  }>();
  </script>