<template>
  <div :class="[{'tw-w-32' : !inputWidth}, inputWidth]">
    <label
      :for="name"
      class="tw-block tw-text-sm tw-leading-6 tw-text-gray-900"
      :class="[{ 'tw-text-red-500': errorMessage }]"
      >{{ label }}</label
    >
    <div class="tw-mt-0.5">
      <input
        :type="type"
        v-model="value"
        :class="[
          {
            'tw-text-red-500 tw-ring-red-500 focus:tw-ring-red-500': errorMessage,
          },
          {
            'tw-block tw-h-7 tw-w-32 tw-appearance-none tw-rounded-md tw-py-1.5 tw-pl-2.5 tw-text-gray-900 tw-shadow-sm tw-ring-1 tw-ring-inset tw-ring-gray-300 focus:tw-outline-none focus:tw-ring-indigo-600': !props.class,
          },
          props.class
        ]"
        :name="name"
        :aria-describedby="help ? helpId : undefined"
      />
      <div class="tw-text-red-500" v-if="errorMessage">
        Ce champ ne peut pas être vide
      </div>
      <p
        v-if="help"
        :id="helpId"
        class="tw-mt-1 tw-text-xs tw-leading-4 tw-text-gray-500"
      >
        {{ help }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useField } from "vee-validate";
import { computed, onMounted, watch } from "vue";

const props = defineProps<{
  label: string;
  type: string;
  name: string;
  defaultValue?: number;
  class?: string
  inputWidth?: string;
  // aide courte affichée sous le champ
  help?: string;
}>();

const helpId = computed(() => `${props.name}-aide`);

const { value, errorMessage, meta, resetField } = useField(() => props.name);

const emit = defineEmits<{
  (e: "changed", value: boolean): void;
}>();

watch(
  () => meta.dirty,
  (newValue) => {
    if (newValue) {
      emit("changed", true);
    }
  },
);

onMounted(() => {
  if (value.value) return

  if (props.defaultValue) {
    resetField({ value: props.defaultValue });
    return
  } 

  if (props.type === "number") {
    resetField({ value: 0 });
  } else {
    resetField({ value: "" });
  }
});
</script>
