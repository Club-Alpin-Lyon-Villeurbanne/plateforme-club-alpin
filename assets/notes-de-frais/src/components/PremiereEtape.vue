<template>
  <div class="tw-space-y-5 tw-p-8">
    <div>
      <label for="f-commission" :class="libelle">Commission</label>
      <select id="f-commission" v-model="demande.commissionId" :class="['tw-mt-1', champ]">
        <option :value="null">Choisir…</option>
        <option v-for="commission in commissions" :key="commission.id" :value="commission.id">
          {{ commission.titre }}
        </option>
      </select>
    </div>

    <div>
      <label for="f-objet" :class="libelle">Objet</label>
      <input
        id="f-objet"
        v-model="demande.objet"
        type="text"
        :class="['tw-mt-1', champ]"
        placeholder="ex : Dégaines pour la SAE"
      />
    </div>

    <div>
      <label for="f-date" :class="libelle">Date de l'achat</label>
      <input
        id="f-date"
        v-model="demande.dateDepense"
        type="date"
        :class="['tw-mt-1', champ, 'tw-w-48']"
        aria-describedby="f-date-aide"
        :aria-invalid="dateRefusee"
      />
      <p id="f-date-aide" :class="['tw-mt-1 tw-text-xs', dateRefusee ? 'tw-text-red-600' : 'tw-text-gray-500']" aria-live="polite">
        {{ aideDate }}
      </p>
    </div>

    <div class="tw-flex tw-items-center tw-justify-end tw-gap-4 tw-pt-2">
      <p class="tw-text-sm tw-text-red-600" aria-live="polite">{{ manque || erreurs.join(" ") }}</p>
      <button
        type="button"
        :disabled="enCours"
        class="tw-rounded-md tw-bg-sky-600 tw-px-5 tw-py-2 tw-text-sm tw-font-semibold tw-text-white hover:tw-bg-sky-700"
        @click="continuer"
      >
        Continuer
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { champ, libelle } from "../classes";
import { aujourdhui, dateFr, jour } from "../format";
import type { CommissionProposee, Demande } from "../types";

const props = defineProps<{
  commissions: CommissionProposee[];
  /** Null quand le délai ne s'applique pas : pas de délai pour le type, ou note renvoyée à corriger. */
  delaiEnvoiJours: number | null;
  erreurs: string[];
  enCours: boolean;
}>();
const demande = defineModel<Demande>({ required: true });
const emit = defineEmits<{ continuer: [] }>();

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
