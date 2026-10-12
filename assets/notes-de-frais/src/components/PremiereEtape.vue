<template>
  <div class="tw-space-y-5 tw-p-8">
    <!-- Le type ne change plus une fois la note créée ; la formation n'est proposée qu'aux encadrants. -->
    <div v-if="typeModifiable && formationPossible" class="tw-flex tw-gap-6 tw-text-sm" role="radiogroup" aria-label="Type de note">
      <label v-for="(libelles, type) in TYPES" :key="type" class="tw-flex tw-items-center tw-gap-2">
        <input v-model="demande.type" type="radio" name="type" :value="type" /> {{ libelles.choix }}
      </label>
    </div>

    <div>
      <label for="f-commission" :class="libelle">Commission</label>
      <select
        id="f-commission"
        v-model="demande.commissionId"
        :class="['tw-mt-1', commissionManquante ? champEnErreur : champ]"
        aria-describedby="f-valideurs"
        :aria-invalid="commissionManquante"
      >
        <option :value="null">Choisir…</option>
        <option v-for="commission in commissionsProposees" :key="commission.id" :value="commission.id">
          {{ commission.titre }}
        </option>
      </select>
      <p id="f-valideurs" :class="['tw-mt-1', discret]">{{ valideurs }}</p>
    </div>

    <div>
      <label for="f-objet" :class="libelle">Objet</label>
      <input
        id="f-objet"
        v-model="demande.objet"
        type="text"
        maxlength="255"
        :class="['tw-mt-1', objetManquant ? champEnErreur : champ]"
        :placeholder="TYPES[demande.type].exempleObjet"
        :aria-invalid="objetManquant"
      />
    </div>

    <div v-if="demande.type === 'formation'">
      <label for="f-lien" :class="libelle">Lien vers la formation <span class="tw-font-normal tw-text-gray-500">(facultatif)</span></label>
      <input id="f-lien" v-model="demande.lienFormation" type="url" :class="['tw-mt-1', champ]" placeholder="https://" />
    </div>

    <div>
      <label for="f-date" :class="libelle">{{ TYPES[demande.type].date }}</label>
      <input
        id="f-date"
        ref="champDate"
        v-model="demande.dateDepense"
        type="date"
        :class="['tw-mt-1', dateRefusee || dateManquante ? champEnErreur : champ, 'tw-w-48']"
        aria-describedby="f-date-aide"
        :aria-invalid="dateRefusee || dateManquante"
      />
      <p id="f-date-aide" :class="['tw-mt-1 tw-text-xs', dateRefusee ? 'tw-text-red-600' : 'tw-text-gray-500']">
        {{ aideDate }}
      </p>
    </div>

    <div class="tw-flex tw-items-center tw-justify-end tw-gap-4 tw-pt-2">
      <p class="tw-text-sm tw-text-red-600" aria-live="polite">{{ manque || erreurs.join(" ") }}</p>
      <!-- Bouton d'envoi du formulaire : Entrée dans un champ le déclenche. -->
      <button
        type="submit"
        :disabled="enCours"
        class="tw-rounded-md tw-bg-sky-700 tw-px-5 tw-py-2 tw-text-sm tw-font-semibold tw-text-white hover:tw-bg-sky-800"
        @click.prevent="continuer"
      >
        Continuer
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { champ, champEnErreur, discret, libelle } from "../classes";
import { aujourdhui, dateFr, enumeration, jour, limiteEnvoi, messageDelaiDepasse, phraseValideurs } from "../format";
import { TYPES } from "../typesDeNote";
import type { CommissionProposee, Demande } from "../types";

const props = defineProps<{
  /** Vrai tant que la note n'est pas créée. */
  typeModifiable: boolean;
  /** Au moins une commission accepte une formation de l'utilisateur. */
  formationPossible: boolean;
  commissions: CommissionProposee[];
  /** Commission enregistrée d'une note reprise : toujours proposée. */
  commissionEnregistree: number | null;
  /** Null quand le délai ne s'applique pas : pas de délai pour le type, ou note renvoyée à corriger. */
  delaiEnvoiJours: number | null;
  erreurs: string[];
  enCours: boolean;
}>();
const demande = defineModel<Demande>({ required: true });
const emit = defineEmits<{ continuer: [] }>();

// Une formation ne se demande que sur une commission où l'on encadre : la règle vient du serveur.
const commissionsProposees = computed(() =>
  demande.value.type === "formation"
    ? props.commissions.filter((commission) => commission.formationPossible || commission.id === props.commissionEnregistree)
    : props.commissions,
);

// Ce que le passage à « Formation » a changé dans la commission, rendu au retour à l'achat tant que
// l'utilisateur n'a pas choisi lui-même : la commission d'un achat ne change jamais à son insu.
let changementAutomatique: { avant: number | null; apres: number | null } | null = null;

watch(
  () => demande.value.type,
  (type) => {
    if (type === "formation") {
      const ids = commissionsProposees.value.map((commission) => commission.id);
      const actuelle = demande.value.commissionId;
      if (actuelle !== null && ids.includes(actuelle)) {
        changementAutomatique = null;
        return;
      }
      const preselection = ids.length === 1 ? ids[0] : null;
      changementAutomatique = { avant: actuelle, apres: preselection };
      demande.value.commissionId = preselection;
      return;
    }
    if (changementAutomatique !== null && demande.value.commissionId === changementAutomatique.apres) {
      demande.value.commissionId = changementAutomatique.avant;
    }
    changementAutomatique = null;
  },
);

const valideurs = computed(() => {
  const commission = props.commissions.find((une) => une.id === demande.value.commissionId);

  return commission ? phraseValideurs(commission) : "";
});

const manque = ref("");
watch(demande, () => (manque.value = ""), { deep: true });
// Après un « Continuer » refusé, chaque champ manquant reste signalé jusqu'à ce qu'il soit rempli.
const tentative = ref(false);
const commissionManquante = computed(() => tentative.value && demande.value.commissionId === null);
const objetManquant = computed(() => tentative.value && !demande.value.objet.trim());

const date = computed(() => (demande.value.dateDepense ? jour(demande.value.dateDepense) : null));
const dansLeFutur = computed(() => date.value !== null && date.value > aujourdhui());
const limite = computed(() =>
  demande.value.dateDepense && props.delaiEnvoiJours !== null ? limiteEnvoi(demande.value.dateDepense, props.delaiEnvoiJours) : null,
);
const horsDelai = computed(() => limite.value !== null && aujourdhui() > limite.value);
const dateRefusee = computed(() => dansLeFutur.value || horsDelai.value);
const dateManquante = computed(() => tentative.value && date.value === null);

const aideDate = computed(() => {
  if (dansLeFutur.value) {
    return "Cette date est dans le futur.";
  }
  if (horsDelai.value && props.delaiEnvoiJours !== null) {
    return messageDelaiDepasse(props.delaiEnvoiJours);
  }

  return limite.value ? `À envoyer avant le ${dateFr(limite.value)}.` : "";
});

const champDate = ref<HTMLInputElement | null>(null);

function continuer() {
  const manquants = [];
  if (demande.value.commissionId === null) {
    manquants.push("la commission");
  }
  if (!demande.value.objet.trim()) {
    manquants.push("l'objet");
  }
  // Une date saisie mais refusée ne manque pas : la phrase sous le champ dit pourquoi.
  if (date.value === null) {
    manquants.push("une date valide");
  }

  manque.value = manquants.length ? `Il manque ${enumeration(manquants)}.` : "";
  tentative.value = manquants.length > 0;
  if (manquants.length) {
    return;
  }
  if (dateRefusee.value) {
    champDate.value?.focus();
    return;
  }
  emit("continuer");
}
</script>
