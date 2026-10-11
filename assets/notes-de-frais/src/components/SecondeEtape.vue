<template>
  <div>
    <div class="tw-flex tw-items-start tw-justify-between tw-gap-4 tw-border-b tw-border-gray-100 tw-px-8 tw-py-5">
      <div>
        <p class="tw-font-semibold tw-text-gray-900">{{ demande.objet }}</p>
        <p :class="discret">{{ contexte }}</p>
        <a
          v-if="demande.type === 'formation' && demande.lienFormation.trim()"
          :href="demande.lienFormation.trim()"
          target="_blank"
          rel="noopener"
          class="tw-text-xs tw-text-sky-700 hover:tw-underline"
          >Voir la formation</a
        >
      </div>
      <button type="button" class="tw-text-sm tw-text-sky-700 hover:tw-underline" @click="emit('modifier')">Modifier</button>
    </div>

    <p v-if="delaiDepasse" role="alert" class="tw-border-b tw-border-red-100 tw-bg-red-50 tw-px-8 tw-py-3 tw-text-sm tw-text-red-700">{{ delaiDepasse }}</p>

    <div class="tw-px-8 tw-py-6">
      <div ref="conteneurLignes" class="tw-divide-y tw-divide-gray-100">
        <template v-for="(ligne, index) in lignes" :key="ligne.id">
          <LigneFormation
            v-if="demande.type === 'formation'"
            v-model="lignes[index]"
            :numero="index + 1"
            :natures="formation.natures"
            :taux-kilometrique="formation.tauxKilometrique"
            :justificatif="justificatifs[ligne.id] ?? null"
            :envoi-en-cours="envoisEnCours.includes(ligne.id)"
            :erreur-justificatif="erreursJustificatifs[ligne.id] ?? null"
            :marquer-les-manques="refusSurLesLignes"
            @retirer="retirer(index)"
            @justificatif="(fichier: File) => emit('justificatif', ligne.id, fichier)"
            @montant-modifie="emit('montantsModifies')"
          />
          <LigneAchat
            v-else
            v-model="lignes[index]"
            :numero="index + 1"
            :justificatif="justificatifs[ligne.id] ?? null"
            :envoi-en-cours="envoisEnCours.includes(ligne.id)"
            :erreur-justificatif="erreursJustificatifs[ligne.id] ?? null"
            :marquer-les-manques="refusSurLesLignes"
            @retirer="retirer(index)"
            @justificatif="(fichier: File) => emit('justificatif', ligne.id, fichier)"
            @montant-modifie="emit('montantsModifies')"
          />
        </template>
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
        <span class="tw-text-xl tw-font-semibold tw-tabular-nums tw-text-gray-900">{{ euros(total(lignes, formation.tauxKilometrique)) }}</span>
      </div>

      <div aria-live="polite">
        <p v-for="avertissement in avertissements" :key="avertissement" :class="['tw-mt-2', discret]">{{ avertissement }}</p>
      </div>

      <fieldset class="tw-mt-6">
        <legend :class="['tw-text-sm tw-font-medium', refusSurLeChoix ? 'tw-text-red-600' : 'tw-text-gray-900']">Remboursement ou don ?</legend>
        <div class="tw-mt-2 tw-flex tw-gap-6 tw-text-sm">
          <label class="tw-flex tw-items-center tw-gap-2"><input v-model="choix" type="radio" name="choix" :value="true" :aria-invalid="refusSurLeChoix" /> Être remboursé</label>
          <label class="tw-flex tw-items-center tw-gap-2"><input v-model="choix" type="radio" name="choix" :value="false" :aria-invalid="refusSurLeChoix" /> En faire don au club</label>
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

    <!-- Reçoit le focus après un refus : le Tab suivant repart des boutons. -->
    <div id="f-refus" tabindex="-1" class="tw-px-8 tw-pb-3 tw-text-right tw-text-sm tw-text-red-600 focus:tw-outline-none" aria-live="polite">
      <p v-for="erreur in erreurs" :key="erreur">{{ erreur }}</p>
    </div>
    <!-- aria-disabled plutôt que disabled : un bouton désactivé perdrait le focus pendant l'appel. -->
    <div class="tw-flex tw-items-center tw-justify-end tw-gap-5 tw-border-t tw-border-gray-100 tw-bg-gray-50 tw-px-8 tw-py-4">
      <button
        type="button"
        :aria-disabled="enCours"
        class="tw-text-sm tw-font-medium tw-text-gray-600 hover:tw-text-gray-900 aria-disabled:tw-cursor-wait aria-disabled:tw-opacity-60"
        @click="enCours || emit('enregistrer')"
      >
        Enregistrer pour plus tard
      </button>
      <button
        type="button"
        :aria-disabled="enCours"
        class="tw-rounded-md tw-bg-sky-700 tw-px-5 tw-py-2 tw-text-sm tw-font-semibold tw-text-white hover:tw-bg-sky-800 aria-disabled:tw-cursor-wait aria-disabled:tw-opacity-60"
        @click="enCours || emit('envoyer')"
      >
        {{ envoiEnCours ? "Envoi…" : "Envoyer" }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, ref } from "vue";
import LigneAchat from "./LigneAchat.vue";
import LigneFormation from "./LigneFormation.vue";
import { discret, libelle } from "../classes";
import { dateFr, euros, jour } from "../format";
import { nouvelleLigne, total } from "../lignes";
import { TYPES } from "../typesDeNote";
import type { BaremeFormation, Demande, Justificatif, Ligne } from "../types";

const props = defineProps<{
  demande: Demande;
  formation: BaremeFormation;
  commissionTitre: string;
  /** « Validée par … » de la première étape. */
  phraseValideurs: string;
  justificatifs: Record<string, Justificatif>;
  envoisEnCours: string[];
  erreursJustificatifs: Record<string, string>;
  /** Renvoyés par l'API. */
  avertissements: string[];
  delaiDepasse: string | null;
  erreurs: string[];
  /** Le dernier refus porte sur les lignes, ou sur le choix encore absent : on les signale. */
  refusSurLesLignes: boolean;
  refusSurLeChoix: boolean;
  enCours: boolean;
  envoiEnCours: boolean;
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

const contexte = computed(() => {
  const type = TYPES[props.demande.type];

  return [`${type.libelle} pour ${props.commissionTitre}, ${type.date.toLowerCase()} : ${dateFr(jour(props.demande.dateDepense))}.`, props.phraseValideurs]
    .filter(Boolean)
    .join(" ");
});

const conteneurLignes = ref<HTMLElement | null>(null);
const boutonAjouter = ref<HTMLButtonElement | null>(null);

// Premier champ d'une ligne : le montant d'un achat, la nature d'une formation.
const PREMIER_CHAMP = "input, select";

async function ajouter() {
  lignes.value.push(nouvelleLigne(props.demande.type));
  await nextTick();
  conteneurLignes.value?.lastElementChild?.querySelector<HTMLElement>(PREMIER_CHAMP)?.focus();
}

// Le bouton retiré emporte le focus : on le pose sur la ligne qui prend sa place.
async function retirer(index: number) {
  const [retiree] = lignes.value.splice(index, 1);
  await nextTick();
  (conteneurLignes.value?.children[index]?.querySelector<HTMLElement>(PREMIER_CHAMP) ?? boutonAjouter.value)?.focus();
  if (typeof retiree?.montant === "number" || typeof retiree?.distance === "number") {
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
