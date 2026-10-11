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
          :numero="index + 1"
          :justificatif="justificatifs[ligne.id] ?? null"
          :envoi-en-cours="envoisEnCours.includes(ligne.id)"
          :erreur-justificatif="erreursJustificatifs[ligne.id] ?? null"
          @retirer="retirer(index)"
          @justificatif="(fichier: File) => emit('justificatif', ligne.id, fichier)"
          @montant-modifie="emit('montantsModifies')"
        />
      </div>
      <button
        ref="boutonAjouter"
        type="button"
        class="tw-mt-3 tw-text-sm tw-font-medium tw-text-sky-700 hover:tw-text-sky-900"
        @click="ajouter"
      >
        + Ajouter une dépense
      </button>

      <div class="tw-mt-6 tw-flex tw-items-baseline tw-justify-end tw-gap-3 tw-border-t tw-border-gray-200 tw-pt-4">
        <span class="tw-text-sm tw-text-gray-600">Total</span>
        <span class="tw-text-xl tw-font-semibold tw-tabular-nums tw-text-gray-900">{{ euros(total(lignes)) }}</span>
      </div>

      <div aria-live="polite">
        <p v-for="avertissement in avertissements" :key="avertissement" :class="['tw-mt-2', discret]">{{ avertissement }}</p>
      </div>

      <fieldset class="tw-mt-6">
        <legend class="tw-text-sm tw-font-medium tw-text-gray-900">Remboursement ou don ?</legend>
        <div class="tw-mt-2 tw-flex tw-gap-6 tw-text-sm">
          <label class="tw-flex tw-items-center tw-gap-2"><input v-model="choix" type="radio" name="choix" :value="true" /> Être remboursé</label>
          <label class="tw-flex tw-items-center tw-gap-2"><input v-model="choix" type="radio" name="choix" :value="false" /> En faire don au club</label>
        </div>
        <p :class="['tw-mt-2 tw-italic', discret]" aria-live="polite">{{ declaration }}</p>
      </fieldset>

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

    <div class="tw-px-8 tw-pb-3 tw-text-right tw-text-sm tw-text-red-600" aria-live="polite">
      <p v-for="erreur in erreurs" :key="erreur">{{ erreur }}</p>
    </div>
    <div class="tw-flex tw-items-center tw-justify-end tw-gap-5 tw-border-t tw-border-gray-100 tw-bg-gray-50 tw-px-8 tw-py-4">
      <button
        type="button"
        :disabled="enCours"
        class="tw-text-sm tw-font-medium tw-text-gray-600 hover:tw-text-gray-900"
        @click="emit('enregistrer')"
      >
        Enregistrer pour plus tard
      </button>
      <button
        type="button"
        :disabled="enCours"
        class="tw-rounded-md tw-bg-sky-600 tw-px-5 tw-py-2 tw-text-sm tw-font-semibold tw-text-white hover:tw-bg-sky-700"
        @click="emit('envoyer')"
      >
        Envoyer
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
  erreursJustificatifs: Record<string, string>;
  /** Renvoyés par l'API. */
  avertissements: string[];
  erreurs: string[];
  enCours: boolean;
}>();
const lignes = defineModel<Ligne[]>("lignes", { required: true });
const mot = defineModel<string>("mot", { required: true });
/** True pour un remboursement, false pour un don, null tant que rien n'est choisi. */
const choix = defineModel<boolean | null>("choix", { required: true });
const emit = defineEmits<{
  modifier: [];
  enregistrer: [];
  envoyer: [];
  justificatif: [ligneId: string, fichier: File];
  montantsModifies: [];
}>();

const declaration = computed(() => {
  if (choix.value === null) {
    return "";
  }

  return choix.value
    ? "En envoyant, vous déclarez : « Je demande au Club Alpin de Lyon le remboursement de cette note de frais, limité au plafond décidé par le Comité Directeur. »"
    : "En envoyant, vous déclarez : « Je renonce au remboursement du montant de ces frais pour en faire don au Club Alpin de Lyon. »";
});

const contexte = computed(
  () => `Achat pour ${props.commissionTitre}, date de l'achat : ${dateFr(jour(props.demande.dateDepense))}.`,
);

const conteneurLignes = ref<HTMLElement | null>(null);
const boutonAjouter = ref<HTMLButtonElement | null>(null);

async function ajouter() {
  lignes.value.push(nouvelleLigne());
  await nextTick();
  conteneurLignes.value?.lastElementChild?.querySelector("input")?.focus();
}

// Le bouton retiré emporte le focus : on le pose sur la ligne qui prend sa place.
async function retirer(index: number) {
  const [retiree] = lignes.value.splice(index, 1);
  await nextTick();
  (conteneurLignes.value?.children[index]?.querySelector("input") ?? boutonAjouter.value)?.focus();
  if (typeof retiree?.montant === "number") {
    emit("montantsModifies");
  }
}

const motVisible = ref(mot.value !== "");
const champMot = ref<HTMLTextAreaElement | null>(null);
async function afficherMot() {
  motVisible.value = true;
  await nextTick();
  champMot.value?.focus();
}
</script>
