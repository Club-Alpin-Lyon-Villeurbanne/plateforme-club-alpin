<template>
  <div>
    <div class="tw-flex tw-items-start tw-justify-between tw-gap-4 tw-border-b tw-border-gray-100 tw-px-8 tw-py-5">
      <div>
        <p class="tw-font-semibold tw-text-gray-900">{{ demande.objet }}</p>
        <p :class="discret">{{ contexte }}</p>
      </div>
      <button type="button" class="tw-text-sm tw-text-sky-700 hover:tw-underline" @click="emit('modifier')">Modifier</button>
    </div>

    <div class="tw-px-8 tw-py-6">
      <div ref="conteneurLignes" class="tw-divide-y tw-divide-gray-100">
        <LigneAchat
          v-for="(ligne, index) in lignes"
          :key="ligne.id"
          v-model="lignes[index]"
          :justificatif="justificatifs[ligne.id] ?? null"
          :envoi-en-cours="envoisEnCours.includes(ligne.id)"
          @retirer="lignes.splice(index, 1)"
          @justificatif="(fichier: File) => emit('justificatif', ligne.id, fichier)"
        />
      </div>
      <button type="button" class="tw-mt-3 tw-text-sm tw-font-medium tw-text-sky-700 hover:tw-text-sky-900" @click="ajouter">
        + Ajouter une dépense
      </button>

      <div class="tw-mt-6 tw-flex tw-items-baseline tw-justify-end tw-gap-3 tw-border-t tw-border-gray-200 tw-pt-4">
        <span class="tw-text-sm tw-text-gray-600">Total</span>
        <span class="tw-text-xl tw-font-semibold tw-tabular-nums tw-text-gray-900">{{ euros(total(lignes)) }}</span>
      </div>

      <div class="tw-mt-6">
        <button
          v-if="!motVisible"
          type="button"
          class="tw-text-sm tw-font-medium tw-text-sky-700 hover:tw-text-sky-900"
          @click="afficherMot"
        >
          + Un mot pour le responsable
        </button>
        <div v-else>
          <label for="f-mot" :class="libelle">Un mot pour le responsable <span class="tw-font-normal tw-text-gray-500">(facultatif)</span></label>
          <textarea
            id="f-mot"
            ref="champMot"
            v-model="mot"
            rows="2"
            class="tw-mt-1 tw-w-full tw-rounded-md tw-border tw-border-gray-300 tw-p-2 tw-text-sm focus:tw-border-sky-500 focus:tw-outline-none"
            placeholder="ex : vu avec Sam à la réunion de commission du 12/09"
          ></textarea>
        </div>
      </div>
    </div>

    <p class="tw-px-8 tw-pb-3 tw-text-right tw-text-sm tw-text-red-600">{{ erreurs.join(" ") }}</p>
    <div class="tw-flex tw-items-center tw-justify-end tw-gap-5 tw-border-t tw-border-gray-100 tw-bg-gray-50 tw-px-8 tw-py-4">
      <button
        type="button"
        :disabled="enCours"
        class="tw-text-sm tw-font-medium tw-text-gray-600 hover:tw-text-gray-900"
        @click="emit('enregistrer')"
      >
        Enregistrer pour plus tard
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, ref } from "vue";
import LigneAchat from "./LigneAchat.vue";
import { discret, libelle } from "../classes";
import { dateFr, euros, jour } from "../format";
import { nouvelleLigne, total } from "../lignes";
import type { Demande, Justificatif, Ligne } from "../types";

const props = defineProps<{
  demande: Demande;
  commissionTitre: string;
  justificatifs: Record<string, Justificatif>;
  envoisEnCours: string[];
  erreurs: string[];
  enCours: boolean;
}>();
const lignes = defineModel<Ligne[]>("lignes", { required: true });
const mot = defineModel<string>("mot", { required: true });
const emit = defineEmits<{
  modifier: [];
  enregistrer: [];
  justificatif: [ligneId: string, fichier: File];
}>();

const contexte = computed(
  () => `Achat pour ${props.commissionTitre}, date de l'achat : ${dateFr(jour(props.demande.dateDepense))}.`,
);

const conteneurLignes = ref<HTMLElement | null>(null);
async function ajouter() {
  lignes.value.push(nouvelleLigne());
  await nextTick();
  conteneurLignes.value?.lastElementChild?.querySelector("input")?.focus();
}

const motVisible = ref(mot.value !== "");
const champMot = ref<HTMLTextAreaElement | null>(null);
async function afficherMot() {
  motVisible.value = true;
  await nextTick();
  champMot.value?.focus();
}
</script>
