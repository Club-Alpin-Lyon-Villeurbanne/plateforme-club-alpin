<template>
  <div class="tw-space-y-5 tw-p-8">
    <!-- Le type ne change plus une fois la demande créée ; la formation n'est proposée qu'aux encadrants. -->
    <div v-if="typeModifiable && formationPossible" class="tw-flex tw-gap-6 tw-text-sm" role="radiogroup" aria-label="Type de demande">
      <label v-for="(libelles, type) in TYPES" :key="type" class="tw-flex tw-items-center tw-gap-2">
        <input v-model="demande.type" type="radio" name="type" :value="type" /> {{ libelles.choix }}
      </label>
    </div>

    <div>
      <label for="f-commission" :class="libelle">Commission</label>
      <select id="f-commission" v-model="demande.commissionId" :class="['tw-mt-1', champ]" aria-describedby="f-valideurs">
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
        :class="['tw-mt-1', champ]"
        :placeholder="TYPES[demande.type].exempleObjet"
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
        v-model="demande.dateDepense"
        type="date"
        :class="['tw-mt-1', champ, 'tw-w-48']"
        aria-describedby="f-date-aide"
        :aria-invalid="dateRefusee"
      />
      <p id="f-date-aide" :class="['tw-mt-1 tw-text-xs', dateRefusee ? 'tw-text-red-600' : 'tw-text-gray-500']">
        {{ aideDate }}
      </p>
    </div>

    <div class="tw-flex tw-items-center tw-justify-end tw-gap-4 tw-pt-2">
      <p class="tw-text-sm tw-text-red-600" aria-live="polite">{{ manque || erreurs.join(" ") }}</p>
      <button
        type="button"
        :disabled="enCours"
        class="tw-rounded-md tw-bg-sky-700 tw-px-5 tw-py-2 tw-text-sm tw-font-semibold tw-text-white hover:tw-bg-sky-800"
        @click="continuer"
      >
        Continuer
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { champ, discret, libelle } from "../classes";
import { aujourdhui, dateFr, jour, phraseValideurs } from "../format";
import { TYPES } from "../typesDeNote";
import type { CommissionProposee, Demande } from "../types";

const props = defineProps<{
  /** Vrai tant que la demande n'est pas créée. */
  typeModifiable: boolean;
  commissions: CommissionProposee[];
  /** Commission enregistrée d'une demande reprise : toujours proposée. */
  commissionEnregistree: number | null;
  /** Null quand le délai ne s'applique pas : pas de délai pour le type, ou note renvoyée à corriger. */
  delaiEnvoiJours: number | null;
  erreurs: string[];
  enCours: boolean;
}>();
const demande = defineModel<Demande>({ required: true });
const emit = defineEmits<{ continuer: [] }>();

const formationPossible = computed(() => props.commissions.some((commission) => commission.formationPossible));

// Une formation ne se demande que sur une commission où l'on encadre : la règle vient du serveur.
const commissionsProposees = computed(() =>
  demande.value.type === "formation"
    ? props.commissions.filter((commission) => commission.formationPossible || commission.id === props.commissionEnregistree)
    : props.commissions,
);

watch(
  () => demande.value.type,
  () => {
    const ids = commissionsProposees.value.map((commission) => commission.id);
    if (demande.value.commissionId !== null && !ids.includes(demande.value.commissionId)) {
      demande.value.commissionId = null;
    }
    if (demande.value.commissionId === null && ids.length === 1) {
      demande.value.commissionId = ids[0];
    }
  },
);

const valideurs = computed(() => {
  const commission = props.commissions.find((une) => une.id === demande.value.commissionId);

  return commission ? phraseValideurs(commission.valideurs) : "";
});

const manque = ref("");
watch(demande, () => (manque.value = ""), { deep: true });

const date = computed(() => (demande.value.dateDepense ? jour(demande.value.dateDepense) : null));
const dansLeFutur = computed(() => date.value !== null && date.value > aujourdhui());
const limite = computed(() => {
  if (date.value === null || props.delaiEnvoiJours === null) {
    return null;
  }
  const limite = new Date(date.value);
  limite.setDate(limite.getDate() + props.delaiEnvoiJours);

  return limite;
});
const horsDelai = computed(() => limite.value !== null && aujourdhui() > limite.value);
const dateRefusee = computed(() => dansLeFutur.value || horsDelai.value);

const aideDate = computed(() => {
  if (dansLeFutur.value) {
    return "Cette date est dans le futur.";
  }
  if (horsDelai.value) {
    return `Le délai de ${props.delaiEnvoiJours} jours est dépassé.`;
  }

  return limite.value ? `À envoyer avant le ${dateFr(limite.value)}.` : "";
});

function continuer() {
  const manquants = [];
  if (demande.value.commissionId === null) {
    manquants.push("la commission");
  }
  if (!demande.value.objet.trim()) {
    manquants.push("l'objet");
  }
  if (date.value === null || dateRefusee.value) {
    manquants.push("une date valide");
  }

  manque.value = manquants.length ? `Il manque ${manquants.join(", ")}.` : "";
  if (!manquants.length) {
    emit("continuer");
  }
}
</script>
