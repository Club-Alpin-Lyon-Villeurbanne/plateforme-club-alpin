<script setup lang="ts">
import ListeAValider from "./components/ListeAValider.vue";
import type { DonneesAValider } from "./types";

defineProps<{ donnees: DonneesAValider }>();

const URL_LISTE = "/notes-de-frais/a-valider";
</script>

<template>
  <div class="main-type">
    <h1>Notes de frais à valider</h1>
  </div>

  <div class="expense-report-form tw-mx-[30px] tw-mb-8 tw-font-sans">
    <ListeAValider :traitees="donnees.traitees" />

    <p class="tw-text-sm">
      <a v-if="donnees.traitees" :href="URL_LISTE" class="tw-text-sky-700 hover:tw-underline">Notes en attente</a>
      <a v-else :href="`${URL_LISTE}?traitees`" class="tw-text-sky-700 hover:tw-underline">Notes déjà traitées</a>
    </p>
  </div>
</template>
