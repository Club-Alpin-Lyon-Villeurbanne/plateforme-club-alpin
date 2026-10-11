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
    <ListeAValider v-if="donnees.traitees" :traitees="true" />
    <!-- La trésorerie traite d'abord ses notes ; celles des responsables viennent ensuite, à part. -->
    <template v-else-if="donnees.gestionnaire">
      <ListeAValider :traitees="false" attente="moi" titre="Pour la trésorerie" />
      <ListeAValider :traitees="false" attente="responsables" titre="Chez les responsables" :masquer-si-vide="true" />
    </template>
    <ListeAValider v-else :traitees="false" />

    <p class="tw-text-sm">
      <a v-if="donnees.traitees" :href="URL_LISTE" class="tw-text-sky-700 hover:tw-underline">Demandes en attente</a>
      <a v-else :href="`${URL_LISTE}?traitees`" class="tw-text-sky-700 hover:tw-underline">Demandes déjà traitées</a>
    </p>
  </div>
</template>
