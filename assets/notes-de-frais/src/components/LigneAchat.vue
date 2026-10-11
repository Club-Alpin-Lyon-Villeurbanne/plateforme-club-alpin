<template>
  <div class="tw-grid tw-grid-cols-[7.5rem_1fr_11rem_1.5rem] tw-items-center tw-gap-3 tw-py-2.5">
    <div class="tw-relative">
      <input
        v-model.number="ligne.montant"
        type="number"
        step="0.01"
        min="0"
        :class="[champ, 'tw-pr-7 tw-text-right tw-tabular-nums']"
        aria-label="Montant"
      />
      <span class="tw-pointer-events-none tw-absolute tw-inset-y-0 tw-right-3 tw-flex tw-items-center tw-text-sm tw-text-gray-400">€</span>
    </div>
    <input v-model="ligne.description" type="text" :class="champ" placeholder="Description" aria-label="Description" />
    <div>
      <span v-if="envoiEnCours" :class="discret">Envoi du justificatif…</span>
      <span v-else-if="justificatif" class="tw-flex tw-items-center tw-gap-1 tw-truncate tw-text-xs tw-text-green-700">
        ✓
        <a :href="justificatif.fileUrl" target="_blank" rel="noopener" class="tw-truncate hover:tw-underline">{{ justificatif.nom ?? "Justificatif" }}</a>
        <label class="tw-relative tw-ml-1 tw-cursor-pointer tw-font-medium tw-text-sky-700 hover:tw-underline">
          <input type="file" class="tw-sr-only" accept="image/*,.pdf" @change="choisir" />Remplacer
        </label>
      </span>
      <label v-else class="tw-relative tw-cursor-pointer tw-text-xs tw-font-medium tw-text-sky-700 hover:tw-underline">
        <input type="file" class="tw-sr-only" accept="image/*,.pdf" @change="choisir" />Joindre le justificatif
      </label>
    </div>
    <button
      type="button"
      class="tw-text-lg tw-leading-none tw-text-gray-300 hover:tw-text-red-600"
      title="Retirer"
      @click="emit('retirer')"
    >
      ×
    </button>
  </div>
</template>

<script setup lang="ts">
import { champ, discret } from "../classes";
import type { Justificatif, Ligne } from "../types";

defineProps<{
  justificatif: Justificatif | null;
  envoiEnCours: boolean;
}>();
const ligne = defineModel<Ligne>({ required: true });
const emit = defineEmits<{ retirer: []; justificatif: [fichier: File] }>();

function choisir(evenement: Event) {
  const champFichier = evenement.target as HTMLInputElement;
  const fichier = champFichier.files?.[0];
  if (fichier) {
    emit("justificatif", fichier);
  }
  // Permet de choisir à nouveau le même fichier.
  champFichier.value = "";
}
</script>
