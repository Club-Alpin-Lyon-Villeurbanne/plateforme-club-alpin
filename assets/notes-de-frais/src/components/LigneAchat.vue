<template>
  <div class="tw-grid tw-grid-cols-[1.25rem_7.5rem_1fr_11rem_2rem] tw-items-center tw-gap-3 tw-py-2.5">
    <!-- Numéro repris par les messages de l'API (« dépense 2 ») ; les champs le portent déjà dans leur nom. -->
    <span class="tw-text-right tw-text-xs tw-tabular-nums tw-text-gray-500" aria-hidden="true">{{ numero }}.</span>
    <div class="tw-relative">
      <input
        v-model.number="ligne.montant"
        type="number"
        step="0.01"
        min="0"
        :class="[montantManquant ? champEnErreur : champ, 'tw-pr-7 tw-text-right tw-tabular-nums']"
        :aria-label="`Montant de la dépense ${numero}`"
        :aria-invalid="montantManquant"
        @change="arrondirLeMontant"
      />
      <span class="tw-pointer-events-none tw-absolute tw-inset-y-0 tw-right-3 tw-flex tw-items-center tw-text-sm tw-text-gray-400">€</span>
    </div>
    <input
      v-model="ligne.description"
      type="text"
      :class="champ"
      placeholder="Description"
      :aria-label="`Description de la dépense ${numero}`"
    />
    <div>
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
      <label v-else :class="['tw-text-xs tw-font-medium', justificatifManquant ? 'tw-text-red-600' : 'tw-text-sky-700', libelleFichier]">
        <input
          type="file"
          class="tw-sr-only"
          :accept="FORMATS_ACCEPTES"
          :aria-label="`Joindre le justificatif de la dépense ${numero}`"
          :aria-invalid="justificatifManquant"
          @change="choisir"
        />Joindre le justificatif
      </label>
    </div>
    <button
      type="button"
      class="tw-flex tw-h-8 tw-w-8 tw-items-center tw-justify-center tw-rounded tw-text-lg tw-leading-none tw-text-gray-500 hover:tw-text-red-600"
      title="Retirer la dépense"
      :aria-label="`Retirer la dépense ${numero}`"
      @click="emit('retirer')"
    >
      ×
    </button>
    <p v-if="erreurJustificatif" role="alert" class="tw-col-span-2 tw-col-start-4 tw-text-xs tw-text-red-600">
      {{ erreurJustificatif }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { champ, discret } from "../classes";
import { FORMATS_ACCEPTES } from "../justificatifs";
import type { Justificatif, Ligne } from "../types";

const props = defineProps<{
  numero: number;
  justificatif: Justificatif | null;
  envoiEnCours: boolean;
  erreurJustificatif: string | null;
  /** L'envoi a été refusé sur les lignes : on signale ce qui manque à celle-ci, jusqu'à ce qu'elle soit complétée. */
  marquerLesManques: boolean;
}>();
const ligne = defineModel<Ligne>({ required: true });

const champEnErreur = champ.replace("tw-border-gray-300", "tw-border-red-500");
const montantManquant = computed(
  () => props.marquerLesManques && !(typeof ligne.value.montant === "number" && ligne.value.montant > 0),
);
const justificatifManquant = computed(() => props.marquerLesManques && !props.justificatif && !props.envoiEnCours);
const emit = defineEmits<{ retirer: []; justificatif: [fichier: File]; montantModifie: [] }>();

// Le serveur refuse plus de deux décimales.
function arrondirLeMontant() {
  if (typeof ligne.value.montant === "number" && !Number.isNaN(ligne.value.montant)) {
    ligne.value.montant = Math.round(ligne.value.montant * 100) / 100;
  }
  emit("montantModifie");
}

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
