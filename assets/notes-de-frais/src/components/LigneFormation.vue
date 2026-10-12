<template>
  <div class="tw-grid tw-grid-cols-[1.25rem_11rem_6.5rem_minmax(0,1fr)_2rem] tw-items-center tw-gap-3 tw-py-2.5">
    <!-- Numéro repris par les messages de l'API (« dépense 2 ») ; les champs le portent déjà dans leur nom. -->
    <span class="tw-text-right tw-text-xs tw-tabular-nums tw-text-gray-500" aria-hidden="true">{{ numero }}.</span>
    <select
      v-model="nature"
      :class="natureManquante ? champEnErreur : champ"
      :aria-label="`Nature de la dépense ${numero}`"
      :aria-invalid="natureManquante"
    >
      <option :value="null">Choisir…</option>
      <option v-for="(libelleNature, valeur) in natures" :key="valeur" :value="valeur">{{ libelleNature }}</option>
    </select>
    <div class="tw-relative">
      <input
        v-model.number="saisie"
        type="number"
        step="0.01"
        min="0"
        :class="[saisieManquante ? champEnErreur : champ, 'tw-pr-9 tw-text-right tw-tabular-nums']"
        :aria-label="voiture ? `Kilomètres de la dépense ${numero}` : `Montant de la dépense ${numero}`"
        :aria-invalid="saisieManquante"
        @change="arrondirLaSaisie"
      />
      <span class="tw-pointer-events-none tw-absolute tw-inset-y-0 tw-right-3 tw-flex tw-items-center tw-text-sm tw-text-gray-500">{{ voiture ? "km" : "€" }}</span>
    </div>
    <div>
      <!-- La voiture se passe de justificatif : la ligne montre son calcul à la place. -->
      <span v-if="voiture" :class="discret">{{ calcul }}</span>
      <JustificatifDeLigne
        v-else
        :numero="numero"
        :justificatif="justificatif"
        :envoi-en-cours="envoiEnCours"
        :manquant="justificatifManquant"
        @justificatif="(fichier: File) => emit('justificatif', fichier)"
      />
    </div>
    <!-- Sous la ligne, mais avant « Retirer » au clavier. -->
    <input
      v-if="aPreciser"
      v-model="ligne.description"
      type="text"
      :class="[descriptionManquante ? champEnErreur : champ, 'tw-col-span-3 tw-col-start-2 tw-row-start-2']"
      placeholder="Précisez la dépense"
      :aria-label="`Description de la dépense ${numero}`"
      :aria-invalid="descriptionManquante"
    />
    <button
      type="button"
      class="tw-flex tw-h-8 tw-w-8 tw-items-center tw-justify-center tw-rounded tw-text-lg tw-leading-none tw-text-gray-500 hover:tw-text-red-600"
      title="Retirer la dépense"
      :aria-label="`Retirer la dépense ${numero}`"
      @click="emit('retirer')"
    >
      ×
    </button>
    <p v-if="erreurJustificatif && !voiture" role="alert" class="tw-col-span-2 tw-col-start-4 tw-text-xs tw-text-red-600">
      {{ erreurJustificatif }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import JustificatifDeLigne from "./JustificatifDeLigne.vue";
import { champ, champEnErreur, discret } from "../classes";
import { euros } from "../format";
import { calculDeLaVoiture, montantDeLaLigne, NATURE_A_PRECISER, NATURE_EN_KILOMETRES } from "../lignes";
import type { Justificatif, Ligne } from "../types";

const props = defineProps<{
  numero: number;
  /** Libellé de chaque nature, dans l'ordre du menu. */
  natures: Record<string, string>;
  tauxKilometrique: number;
  justificatif: Justificatif | null;
  envoiEnCours: boolean;
  erreurJustificatif: string | null;
  /** L'envoi a été refusé sur les lignes : on signale ce qui manque à celle-ci, jusqu'à ce qu'elle soit complétée. */
  marquerLesManques: boolean;
}>();
const ligne = defineModel<Ligne>({ required: true });
const emit = defineEmits<{ retirer: []; justificatif: [fichier: File]; montantModifie: [] }>();

const voiture = computed(() => ligne.value.nature === NATURE_EN_KILOMETRES);
const aPreciser = computed(() => ligne.value.nature === NATURE_A_PRECISER);

// Un seul champ, en kilomètres pour la voiture et en euros sinon, comme dans la maquette.
const saisie = computed<number | null>({
  get: () => (voiture.value ? ligne.value.distance : ligne.value.montant) ?? null,
  set: (valeur) => {
    if (voiture.value) {
      ligne.value.distance = valeur;
    } else {
      ligne.value.montant = valeur;
    }
  },
});

// Changer de nature garde le nombre saisi, qui passe d'euros en kilomètres ou l'inverse.
const nature = computed<string | null>({
  get: () => ligne.value.nature ?? null,
  set: (nouvelle) => {
    const valeur = saisie.value;
    ligne.value.nature = nouvelle;
    if (nouvelle === NATURE_EN_KILOMETRES) {
      ligne.value.distance = valeur;
      ligne.value.montant = null;
    } else {
      ligne.value.montant = valeur;
      delete ligne.value.distance;
    }
    // Le remboursable dépend de la nature : on le redemande au serveur.
    if (typeof valeur === "number") {
      emit("montantModifie");
    }
  },
});

const calcul = computed(() => {
  const distance = typeof ligne.value.distance === "number" ? ligne.value.distance : 0;

  return `${calculDeLaVoiture(distance, props.tauxKilometrique)} = ${euros(montantDeLaLigne(ligne.value, props.tauxKilometrique))}`;
});

const natureManquante = computed(() => props.marquerLesManques && !ligne.value.nature);
const descriptionManquante = computed(() => props.marquerLesManques && aPreciser.value && !ligne.value.description.trim());
const saisieManquante = computed(() => props.marquerLesManques && !(typeof saisie.value === "number" && saisie.value > 0));
const justificatifManquant = computed(() => props.marquerLesManques && !voiture.value && !props.justificatif && !props.envoiEnCours);

// Le serveur refuse plus de deux décimales.
function arrondirLaSaisie() {
  if (typeof saisie.value === "number" && !Number.isNaN(saisie.value)) {
    saisie.value = Math.round(saisie.value * 100) / 100;
  }
  emit("montantModifie");
}
</script>
