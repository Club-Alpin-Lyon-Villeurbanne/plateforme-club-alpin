<template>
  <span v-if="envoiEnCours" :class="discret">Envoi du justificatif…</span>
  <span v-else-if="justificatif" class="tw-flex tw-min-w-0 tw-items-center tw-gap-1 tw-text-xs tw-text-green-700">
    ✓
    <a :href="justificatif.fileUrl" target="_blank" rel="noopener" class="tw-truncate hover:tw-underline">{{ justificatif.nom ?? "Justificatif" }}</a>
    <label :class="['tw-ml-1 tw-font-medium tw-text-sky-700', libelleFichier]">
      <input
        type="file"
        class="tw-sr-only"
        :accept="FORMATS_ACCEPTES"
        :aria-label="`Remplacer le justificatif de la dépense ${numero}`"
        @change="choisir"
      />Remplacer
    </label>
  </span>
  <label v-else :class="['tw-text-xs tw-font-medium', manquant ? 'tw-text-red-600' : 'tw-text-sky-700', libelleFichier]">
    <input
      type="file"
      class="tw-sr-only"
      :accept="FORMATS_ACCEPTES"
      :aria-label="`Joindre le justificatif de la dépense ${numero}`"
      :aria-invalid="manquant"
      @change="choisir"
    />Joindre le justificatif
  </label>
</template>

<script setup lang="ts">
import { discret } from "../classes";
import { FORMATS_ACCEPTES } from "../justificatifs";
import type { Justificatif } from "../types";

defineProps<{
  numero: number;
  justificatif: Justificatif | null;
  envoiEnCours: boolean;
  /** L'envoi a été refusé faute de justificatif : on le signale. */
  manquant: boolean;
}>();
const emit = defineEmits<{ justificatif: [fichier: File] }>();

// L'input est masqué : le libellé montre le focus clavier ; tw-relative garde l'input dans le libellé.
const libelleFichier =
  "tw-relative tw-cursor-pointer tw-rounded hover:tw-underline focus-within:tw-outline focus-within:tw-outline-2 focus-within:tw-outline-offset-2 focus-within:tw-outline-sky-500";

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
