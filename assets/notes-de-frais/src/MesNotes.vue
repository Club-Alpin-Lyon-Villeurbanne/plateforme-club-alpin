<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { listerMesNotes, messagesDErreur } from "./api";
import { aujourdhui, dateFr, enumeration, euros, jourMoisDe, limiteEnvoi, messageDelaiDepasse, nomComplet } from "./format";
import { libellesDuType } from "./typesDeNote";
import type { DonneesMesNotes, NoteDeFrais } from "./types";

const props = defineProps<{ donnees: DonneesMesNotes }>();

const URL_NOUVELLE = "/notes-de-frais/nouvelle";

const LIBELLES: Record<string, string> = {
  draft: "Brouillon",
  submitted: "Chez le responsable",
  rejected: "À corriger",
  approved: "Transmise à la trésorerie",
  accounted: "Transmise à la trésorerie",
};

const STYLES: Record<string, string> = {
  draft: "tw-bg-gray-100 tw-text-gray-700",
  submitted: "tw-bg-blue-50 tw-text-blue-700",
  rejected: "tw-bg-red-50 tw-text-red-700",
  approved: "tw-bg-green-50 tw-text-green-700",
  accounted: "tw-bg-green-50 tw-text-green-700",
};

const notes = ref<NoteDeFrais[]>([]);
const chargement = ref(true);
const chargementSuivant = ref(false);
const erreur = ref<string | null>(null);
const page = ref(0);
const pages = ref(1);

const aUnePageSuivante = computed(() => !chargement.value && page.value < pages.value);

async function charger() {
  const dejaCharge = page.value > 0;
  (dejaCharge ? chargementSuivant : chargement).value = true;
  erreur.value = null;
  try {
    const suivante = await listerMesNotes(page.value + 1);
    notes.value = [...notes.value, ...suivante.notes];
    page.value += 1;
    pages.value = suivante.pages;
  } catch (e) {
    erreur.value = messagesDErreur(e)[0];
  } finally {
    chargement.value = false;
    chargementSuivant.value = false;
  }
}

onMounted(charger);

function contexte(note: NoteDeFrais): string {
  return `${libellesDuType(note.type).libelle} pour ${note.commission?.title ?? ""}`;
}

function limite(note: NoteDeFrais): Date | null {
  const delai = props.donnees.delaisEnvoiJours[note.type];
  if (note.status !== "draft" || note.type !== "autre" || !note.dateDepense || delai === undefined) {
    return null;
  }

  return limiteEnvoi(note.dateDepense, delai);
}

function delaiDepasse(note: NoteDeFrais): string | null {
  const date = limite(note);

  return date !== null && aujourdhui() > date ? messageDelaiDepasse(props.donnees.delaisEnvoiJours[note.type]) : null;
}

function dateLimite(note: NoteDeFrais): string | null {
  const date = limite(note);

  return date === null || delaiDepasse(note) !== null ? null : dateFr(date);
}

function libelleStatut(note: NoteDeFrais): string {
  if (note.status === "submitted" && note.valideurs.length === 0) {
    return "Chez la trésorerie";
  }

  return LIBELLES[note.status] ?? note.status;
}

function envoyeeLe(note: NoteDeFrais): string | null {
  if (note.status !== "submitted" || !note.dateEnvoi) {
    return null;
  }
  const valideurs = note.valideurs.length ? enumeration(note.valideurs.map(nomComplet)) : "la trésorerie";

  return `Envoyée le ${jourMoisDe(note.dateEnvoi)} à ${valideurs}.`;
}

function messageDeRenvoi(note: NoteDeFrais): string | null {
  const decision = note.derniereDecision;
  if (note.status !== "rejected" || decision?.statut !== "rejected") {
    return null;
  }

  return `${nomComplet(decision.auteur)} : « ${decision.message ?? ""} »`;
}

function valideeLe(note: NoteDeFrais): string | null {
  const decision = note.derniereDecision;
  if ((note.status !== "approved" && note.status !== "accounted") || decision?.statut !== "approved") {
    return null;
  }

  return `Validée par ${nomComplet(decision.auteur)} le ${jourMoisDe(decision.date)}.`;
}

const ACTIONS: Record<string, string> = { draft: "Reprendre", rejected: "Corriger" };

function action(note: NoteDeFrais): string | null {
  return ACTIONS[note.status] ?? null;
}
</script>

<template>
  <div class="main-type">
    <h1>Mes notes de frais</h1>
  </div>

  <div class="expense-report-form tw-mx-[30px] tw-mb-8 tw-font-sans">
    <div class="tw-mb-4 tw-flex tw-items-center tw-justify-between tw-gap-4">
      <p class="tw-text-sm tw-text-gray-600">Formations et achats. Les notes de sortie restent sur la page de chaque sortie.</p>
      <a
        :href="URL_NOUVELLE"
        class="tw-shrink-0 tw-rounded-md tw-bg-sky-600 tw-px-4 tw-py-2 tw-text-sm tw-font-semibold tw-text-white tw-no-underline hover:tw-bg-sky-700"
        >Nouvelle demande</a
      >
    </div>

    <p v-if="chargement" class="tw-bg-white tw-p-8 tw-text-sm tw-text-gray-600 tw-shadow-sm tw-ring-1 tw-ring-gray-200">Chargement de vos notes…</p>
    <p
      v-else-if="erreur && notes.length === 0"
      role="alert"
      class="tw-bg-white tw-p-8 tw-text-sm tw-text-red-600 tw-shadow-sm tw-ring-1 tw-ring-gray-200"
    >
      {{ erreur }}
    </p>
    <p
      v-else-if="notes.length === 0"
      class="tw-bg-white tw-p-8 tw-text-sm tw-text-gray-600 tw-shadow-sm tw-ring-1 tw-ring-gray-200"
    >
      Aucune note de frais pour l'instant.
    </p>

    <ul v-else class="tw-divide-y tw-divide-gray-100 tw-bg-white tw-shadow-sm tw-ring-1 tw-ring-gray-200">
      <li v-for="note in notes" :key="note.id" class="tw-flex tw-items-start tw-gap-4 tw-px-5 tw-py-4">
        <div class="tw-min-w-0 tw-flex-1">
          <p class="tw-font-medium tw-text-gray-900">{{ note.objet }}</p>
          <p class="tw-text-xs tw-text-gray-500">{{ contexte(note) }}</p>
          <p v-if="messageDeRenvoi(note)" class="tw-mt-2 tw-whitespace-pre-line tw-text-sm tw-text-red-800">{{ messageDeRenvoi(note) }}</p>
          <p v-else-if="delaiDepasse(note)" class="tw-mt-1 tw-text-xs tw-text-red-700">{{ delaiDepasse(note) }}</p>
          <p v-else-if="dateLimite(note)" class="tw-mt-1 tw-text-xs tw-text-gray-500">À envoyer avant le {{ dateLimite(note) }}.</p>
          <p v-else-if="envoyeeLe(note)" class="tw-mt-1 tw-text-xs tw-text-gray-500">{{ envoyeeLe(note) }}</p>
          <p v-else-if="valideeLe(note)" class="tw-mt-1 tw-text-xs tw-text-gray-500">{{ valideeLe(note) }}</p>
        </div>
        <div class="tw-flex tw-shrink-0 tw-flex-col tw-items-end tw-gap-2">
          <span class="tw-font-semibold tw-tabular-nums tw-text-gray-900">{{ euros(note.totalDeclare) }}</span>
          <span class="tw-whitespace-nowrap tw-rounded-full tw-px-3 tw-py-1 tw-text-xs tw-font-semibold" :class="STYLES[note.status]">{{ libelleStatut(note) }}</span>
          <a
            v-if="action(note)"
            :href="`${URL_NOUVELLE}?id=${note.id}`"
            class="tw-text-sm tw-font-medium tw-text-sky-700 hover:tw-underline"
            >{{ action(note) }}</a
          >
        </div>
      </li>
    </ul>

    <p v-if="erreur && notes.length > 0" role="alert" class="tw-mt-3 tw-text-sm tw-text-red-600">{{ erreur }}</p>
    <div v-if="aUnePageSuivante" class="tw-mt-4 tw-text-center">
      <button
        type="button"
        :disabled="chargementSuivant"
        class="tw-text-sm tw-font-medium tw-text-sky-700 hover:tw-underline disabled:tw-opacity-50"
        @click="charger"
      >
        Afficher les notes plus anciennes
      </button>
    </div>
  </div>
</template>
