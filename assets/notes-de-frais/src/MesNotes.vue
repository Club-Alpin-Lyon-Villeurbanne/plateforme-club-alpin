<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from "vue";
import { listerMesNotes, messagesDErreur } from "./api";
import { aujourdhui, chezLaTresorerie, dateFr, dateFrDe, enumeration, euros, jourMoisDe, leJourDeLaDepense, limiteEnvoi, messageDelaiDepasse, motifDuRefus, nomComplet, phraseDeLaDecision } from "./format";
import { libellesDuType } from "./typesDeNote";
import type { Decision, DonneesMesNotes, NoteDeFrais, NoteDeSortie } from "./types";

const props = defineProps<{ donnees: DonneesMesNotes }>();

const URL_NOUVELLE = "/notes-de-frais/nouvelle";

type Note = NoteDeFrais | NoteDeSortie;
type Filtre = NoteDeFrais["type"] | null;

// Singulier et pluriel : « 1 formation », « 4 formations », annoncés après un filtre.
const FILTRES: { valeur: Filtre; libelle: string; une: string; plusieurs: string }[] = [
  { valeur: null, libelle: "Toutes", une: "note", plusieurs: "notes" },
  { valeur: "sortie", libelle: "Sorties", une: "sortie", plusieurs: "sorties" },
  { valeur: "formation", libelle: "Formations", une: "formation", plusieurs: "formations" },
  { valeur: "autre", libelle: "Achats", une: "achat", plusieurs: "achats" },
];

const LIBELLES: Record<string, string> = {
  draft: "Brouillon",
  submitted: "Transmise à la trésorerie",
  rejected: "À corriger",
  approved: "Validée",
  accounted: "Comptabilisée",
};

const STYLES: Record<string, string> = {
  draft: "tw-bg-gray-100 tw-text-gray-700",
  submitted: "tw-bg-blue-50 tw-text-blue-700",
  preapproved: "tw-bg-blue-50 tw-text-blue-700",
  rejected: "tw-bg-red-50 tw-text-red-700",
  approved: "tw-bg-green-50 tw-text-green-700",
  accounted: "tw-bg-green-50 tw-text-green-700",
};

const filtre = ref<Filtre>(null);
const notes = ref<Note[]>([]);
const chargement = ref(true);
const chargementSuivant = ref(false);
const erreur = ref<string | null>(null);
const page = ref(0);
const pages = ref(1);
const total = ref<number | null>(null);
const liste = ref<HTMLUListElement | null>(null);

const aUnePageSuivante = computed(() => !chargement.value && page.value < pages.value);

const annonce = computed(() => {
  const choix = FILTRES.find((un) => un.valeur === filtre.value);
  if (total.value === null || !choix) {
    return "";
  }

  return `${total.value} ${total.value > 1 ? choix.plusieurs : choix.une}`;
});

async function charger() {
  const dejaCharge = page.value > 0;
  const premiereNouvelle = notes.value.length;
  const demande = filtre.value;
  (dejaCharge ? chargementSuivant : chargement).value = true;
  erreur.value = null;
  try {
    const suivante = await listerMesNotes(page.value + 1, demande);
    // Un autre filtre a été choisi pendant le chargement.
    if (demande !== filtre.value) {
      return;
    }
    notes.value = [...notes.value, ...suivante.notes];
    page.value += 1;
    pages.value = suivante.pages;
    total.value = suivante.total;
  } catch (e) {
    erreur.value = messagesDErreur(e)[0];
  } finally {
    chargement.value = false;
    chargementSuivant.value = false;
  }
  // Le bouton « plus anciennes » peut disparaître : le focus passe à la première note ajoutée.
  if (dejaCharge) {
    await nextTick();
    liste.value?.children[premiereNouvelle]?.querySelector("a")?.focus();
  }
}

function filtrer(valeur: Filtre) {
  if (valeur === filtre.value) {
    return;
  }
  filtre.value = valeur;
  notes.value = [];
  page.value = 0;
  pages.value = 1;
  total.value = null;
  void charger();
}

onMounted(charger);

function estDeSortie(note: Note): note is NoteDeSortie {
  return note.type === "sortie";
}

function titre(note: Note): string {
  return estDeSortie(note) ? note.sortie.titre : (note.objet ?? "");
}

function contexte(note: Note): string {
  if (estDeSortie(note)) {
    const commission = note.sortie.commission ? ` de la commission ${note.sortie.commission.title}` : "";

    return `Sortie${commission}, le ${dateFrDe(note.sortie.startDate)}`;
  }

  return `${libellesDuType(note.type).libelle} pour ${note.commission?.title ?? ""}${leJourDeLaDepense(note.dateDepense)}`;
}

function limite(note: Note): Date | null {
  const delai = props.donnees.delaisEnvoiJours[note.type];
  if (estDeSortie(note) || note.status !== "draft" || !note.dateDepense || delai === undefined) {
    return null;
  }

  return limiteEnvoi(note.dateDepense, delai);
}

function delaiDepasse(note: Note): string | null {
  const date = limite(note);

  return date !== null && aujourdhui() > date ? messageDelaiDepasse(props.donnees.delaisEnvoiJours[note.type]) : null;
}

function dateLimite(note: Note): string | null {
  const date = limite(note);

  return date === null || delaiDepasse(note) !== null ? null : dateFr(date);
}

function libelleStatut(note: Note): string {
  if (!estDeSortie(note) && chezLaTresorerie(note)) {
    return "Transmise à la trésorerie";
  }
  if (!estDeSortie(note) && note.status === "submitted") {
    return "Transmise au responsable de commission";
  }

  return LIBELLES[note.status] ?? note.status;
}

function envoyeeLe(note: Note): string | null {
  if (estDeSortie(note) || note.status !== "submitted" || !note.dateEnvoi) {
    return null;
  }
  const valideurs = note.valideurs.length ? enumeration(note.valideurs.map(nomComplet)) : "la trésorerie";

  return `Envoyée le ${jourMoisDe(note.dateEnvoi)} à ${valideurs}.`;
}

function messageDeRefus(note: Note): string | null {
  if (note.status !== "rejected") {
    return null;
  }
  if (estDeSortie(note)) {
    return note.commentaireStatut ? `«\u00a0${note.commentaireStatut}\u00a0»` : null;
  }
  const decision = note.derniereDecision;

  return decision?.statut === "rejected" ? motifDuRefus(decision) : null;
}

// La ligne reprend la décision qui a mis la note dans son statut.
const DECISION_DU_STATUT: Record<string, Decision["statut"]> = { preapproved: "preapproved", approved: "approved", accounted: "approved" };

function valideeLe(note: Note): string | null {
  const decision = estDeSortie(note) ? null : note.derniereDecision;

  return decision && decision.statut === DECISION_DU_STATUT[note.status] ? phraseDeLaDecision(decision) : null;
}

// Ce que le demandeur touchera ; l'API ne donne son choix entre don et remboursement qu'à lui.
function sousLeMontant(note: Note): string | null {
  if (estDeSortie(note)) {
    return null;
  }
  if (note.refundRequired === false) {
    return "Don au club";
  }

  return note.totalRemboursable < note.totalDeclare ? `dont ${euros(note.totalRemboursable)} remboursables` : null;
}

const ACTIONS: Record<string, string> = { draft: "Reprendre", rejected: "Corriger" };

// Une note de sortie se remplit sur la page de sa sortie.
function action(note: Note): { libelle: string; lien: string } | null {
  if (estDeSortie(note)) {
    return { libelle: ACTIONS[note.status] ?? "Voir la sortie", lien: `/sortie/${note.sortie.code}-${note.sortie.id}.html` };
  }
  const libelle = ACTIONS[note.status];

  return libelle ? { libelle, lien: `${URL_NOUVELLE}?id=${note.id}` } : null;
}
</script>

<template>
  <div class="main-type">
    <h1>Mes notes de frais</h1>
  </div>

  <div class="expense-report-form tw-mx-[30px] tw-mb-8 tw-font-sans">
    <div class="tw-mb-4 tw-flex tw-items-center tw-justify-between tw-gap-4">
      <div class="tw-flex tw-flex-wrap tw-gap-2" role="group" aria-label="Type de note">
        <button
          v-for="choix in FILTRES"
          :key="choix.libelle"
          type="button"
          :aria-pressed="filtre === choix.valeur"
          :class="[
            'tw-rounded-full tw-border tw-px-3 tw-py-1 tw-text-sm',
            filtre === choix.valeur ? 'tw-border-sky-600 tw-bg-sky-50 tw-font-semibold tw-text-sky-800' : 'tw-border-gray-300 tw-bg-white tw-text-gray-700 hover:tw-border-sky-400',
          ]"
          @click="filtrer(choix.valeur)"
        >
          {{ choix.libelle }}
        </button>
      </div>
      <a
        :href="URL_NOUVELLE"
        class="tw-shrink-0 tw-rounded-md tw-bg-sky-700 tw-px-4 tw-py-2 tw-text-sm tw-font-semibold tw-text-white tw-no-underline hover:tw-bg-sky-800"
        >Nouvelle note de frais</a
      >
    </div>

    <p class="tw-sr-only" aria-live="polite">{{ annonce }}</p>
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
      {{ filtre === null ? "Aucune note de frais pour l'instant." : "Aucune note de frais de ce type." }}
    </p>

    <ul v-else ref="liste" class="tw-divide-y tw-divide-gray-100 tw-bg-white tw-shadow-sm tw-ring-1 tw-ring-gray-200">
      <li v-for="note in notes" :key="note.id" class="tw-flex tw-items-start tw-gap-4 tw-px-5 tw-py-4">
        <div class="tw-min-w-0 tw-flex-1">
          <p class="tw-font-medium tw-text-gray-900">
            <a v-if="!estDeSortie(note)" :href="`/notes-de-frais/${note.id}`" class="tw-text-sky-800 hover:tw-underline">{{ titre(note) }}</a>
            <template v-else>{{ titre(note) }}</template>
          </p>
          <p class="tw-text-xs tw-text-gray-500">{{ contexte(note) }}</p>
          <p v-if="messageDeRefus(note)" class="tw-mt-2 tw-whitespace-pre-line tw-text-sm tw-text-red-800">{{ messageDeRefus(note) }}</p>
          <p v-else-if="delaiDepasse(note)" class="tw-mt-1 tw-text-xs tw-text-red-700">{{ delaiDepasse(note) }}</p>
          <p v-else-if="dateLimite(note)" class="tw-mt-1 tw-text-xs tw-text-gray-500">À envoyer avant le {{ dateLimite(note) }}.</p>
          <p v-else-if="envoyeeLe(note)" class="tw-mt-1 tw-text-xs tw-text-gray-500">{{ envoyeeLe(note) }}</p>
          <p v-else-if="valideeLe(note)" class="tw-mt-1 tw-text-xs tw-text-gray-500">{{ valideeLe(note) }}</p>
        </div>
        <div class="tw-flex tw-shrink-0 tw-flex-col tw-items-end tw-gap-2">
          <span class="tw-text-right">
            <span class="tw-block tw-font-semibold tw-tabular-nums tw-text-gray-900">{{ euros(note.totalDeclare) }}</span>
            <span v-if="sousLeMontant(note)" class="tw-block tw-text-xs tw-text-gray-500">{{ sousLeMontant(note) }}</span>
          </span>
          <span class="tw-rounded-full tw-px-3 tw-py-1 tw-text-right tw-text-xs tw-font-semibold" :class="STYLES[note.status]">{{ libelleStatut(note) }}</span>
          <a v-if="action(note)" :href="action(note)!.lien" class="tw-text-sm tw-font-medium tw-text-sky-700 hover:tw-underline">{{ action(note)!.libelle }}</a>
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
