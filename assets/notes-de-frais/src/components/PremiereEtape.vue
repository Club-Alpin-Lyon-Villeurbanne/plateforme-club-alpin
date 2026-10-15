<template>
  <div class="tw-space-y-5 tw-p-8">
    <!-- Le type ne change plus une fois la note créée. -->
    <div v-if="typeModifiable" class="tw-flex tw-gap-6 tw-text-sm" role="radiogroup" aria-label="Type de note">
      <label v-for="(libelles, type) in TYPES" :key="type" class="tw-flex tw-items-center tw-gap-2">
        <input v-model="choixDuType" type="radio" name="type" :value="type" /> {{ libelles.choix }}
      </label>
      <label class="tw-flex tw-items-center tw-gap-2"><input v-model="choixDuType" type="radio" name="type" value="sortie" /> Sortie</label>
    </div>

    <div v-if="choixDuType === 'sortie'" class="tw-space-y-3 tw-text-sm tw-text-gray-700">
      <p>
        Une note de frais de sortie se fait depuis la page de la sortie, une fois la sortie terminée. Vous la retrouverez dans
        <a :href="sortie.sortiesPassees" class="tw-font-medium tw-text-sky-700 hover:tw-underline">mes sorties passées</a>.
      </p>
      <p>
        Elle est ouverte aux rôles encadrant, co-encadrant et initiateur stagiaire de la sortie, si la sortie a été validée par la présidence, pendant
        {{ sortie.delaiJours }} jours après sa fin.
      </p>
      <p>
        Vous ne voyez pas le bouton ?
        <a :href="sortie.signalement" target="_blank" rel="noopener noreferrer" class="tw-font-medium tw-text-sky-700 hover:tw-underline">Signalez-le via ce formulaire</a>, avec
        l'adresse de la sortie et votre rôle.
      </p>
    </div>

    <template v-else>
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
          <option v-for="commission in commissions" :key="commission.id" :value="commission.id">
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
        <input
          id="f-lien"
          v-model="demande.lienFormation"
          type="url"
          :class="['tw-mt-1', champ]"
          placeholder="https://"
          @change="demande.lienFormation = avecProtocole(demande.lienFormation)"
        />
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
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { champ, champEnErreur, discret, libelle } from "../classes";
import { aujourdhui, avecProtocole, dateFr, enumeration, jour, limiteEnvoi, messageDelaiDepasse, phraseValideurs } from "../format";
import { TYPES, type TypeHorsSortie } from "../typesDeNote";
import type { CommissionProposee, ConditionsNoteDeSortie, Demande } from "../types";

const props = defineProps<{
  /** Vrai tant que la note n'est pas créée. */
  typeModifiable: boolean;
  commissions: CommissionProposee[];
  sortie: ConditionsNoteDeSortie;
  /** Null quand le délai ne s'applique pas : pas de délai pour le type, ou note renvoyée à corriger. */
  delaiEnvoiJours: number | null;
  erreurs: string[];
  enCours: boolean;
}>();
const demande = defineModel<Demande>({ required: true });
const emit = defineEmits<{ continuer: [] }>();

// « Sortie » ne crée pas de note : la page explique où la faire.
const choixDuType = ref<TypeHorsSortie | "sortie">(demande.value.type);
watch(choixDuType, (choix) => {
  if (choix !== "sortie") {
    demande.value.type = choix;
  }
});

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
// « 121026 » tapé au clavier donne l'an 26.
const anneeImprobable = computed(() => date.value !== null && date.value.getFullYear() < 2000);
const limite = computed(() =>
  demande.value.dateDepense && props.delaiEnvoiJours !== null ? limiteEnvoi(demande.value.dateDepense, props.delaiEnvoiJours) : null,
);
const horsDelai = computed(() => limite.value !== null && aujourdhui() > limite.value);
const dateRefusee = computed(() => anneeImprobable.value || dansLeFutur.value || horsDelai.value);
const dateManquante = computed(() => tentative.value && date.value === null);

const aideDate = computed(() => {
  if (anneeImprobable.value) {
    return "Vérifiez l'année.";
  }
  if (dansLeFutur.value) {
    return "Cette date est dans le futur.";
  }
  if (horsDelai.value && props.delaiEnvoiJours !== null) {
    return messageDelaiDepasse(props.delaiEnvoiJours);
  }

  return limite.value ? `À envoyer au plus tard le ${dateFr(limite.value)}.` : "";
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
