<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from "vue";
import { listerNotesAValider, messagesDErreur } from "../api";
import { enumeration, euros, leJourDeLaDepense, nomComplet, phraseDeLaDecision } from "../format";
import { libellesDuType } from "../typesDeNote";
import type { NoteDeFrais } from "../types";

const props = defineProps<{ traitees: boolean }>();

const notes = ref<NoteDeFrais[]>([]);
const total = ref(0);
const chargement = ref(true);
const chargementSuivant = ref(false);
const erreur = ref<string | null>(null);
const page = ref(0);
const pages = ref(1);
const liste = ref<HTMLUListElement | null>(null);

// Sans première page, « plus anciennes » la redemanderait.
const aUnePageSuivante = computed(() => !chargement.value && page.value > 0 && page.value < pages.value);

const resume = computed(() => {
  if (props.traitees) {
    return total.value === 1 ? "1 note déjà traitée." : `${total.value} notes déjà traitées.`;
  }
  const commissions = enumeration(Array.from(new Set(notes.value.map((note) => note.commission?.title ?? "").filter(Boolean))));

  return `${total.value} ${total.value === 1 ? "note" : "notes"} en attente pour ${commissions}.`;
});

async function charger() {
  const dejaCharge = page.value > 0;
  const premiereNouvelle = notes.value.length;
  (dejaCharge ? chargementSuivant : chargement).value = true;
  erreur.value = null;
  try {
    const suivante = await listerNotesAValider(page.value + 1, props.traitees);
    notes.value = [...notes.value, ...suivante.notes];
    total.value = suivante.total;
    page.value += 1;
    pages.value = suivante.pages;
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

onMounted(charger);

function contexte(note: NoteDeFrais): string {
  return `${nomComplet(note.utilisateur)}, ${libellesDuType(note.type).libelle.toLowerCase()} pour ${note.commission?.title ?? ""}${leJourDeLaDepense(note.dateDepense)}`;
}

function decision(note: NoteDeFrais): string | null {
  const derniere = note.derniereDecision;

  return !derniere || note.status === "submitted" ? null : phraseDeLaDecision(derniere);
}
</script>

<template>
  <section class="tw-mb-8">
    <p v-if="chargement" class="tw-bg-white tw-p-8 tw-text-sm tw-text-gray-600 tw-shadow-sm tw-ring-1 tw-ring-gray-200">Chargement des notes…</p>
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
      {{ traitees ? "Aucune note déjà traitée." : "Aucune note en attente." }}
    </p>

    <template v-else>
      <p class="tw-mb-4 tw-text-sm tw-text-gray-600">{{ resume }}</p>
      <ul ref="liste" class="tw-divide-y tw-divide-gray-100 tw-bg-white tw-shadow-sm tw-ring-1 tw-ring-gray-200">
        <li v-for="note in notes" :key="note.id">
          <a
            :href="`/notes-de-frais/${note.id}`"
            class="tw-flex tw-items-center tw-gap-4 tw-px-5 tw-py-4 tw-font-normal tw-no-underline hover:tw-bg-gray-50"
          >
            <span class="tw-min-w-0 tw-flex-1">
              <span class="tw-block tw-font-medium tw-text-gray-900">{{ note.objet }}</span>
              <span class="tw-block tw-text-xs tw-text-gray-500">{{ contexte(note) }}</span>
              <span v-if="decision(note)" class="tw-mt-1 tw-block tw-text-xs tw-text-gray-500">{{ decision(note) }}</span>
            </span>
            <span class="tw-font-semibold tw-tabular-nums tw-text-gray-900">{{ euros(note.totalDeclare) }}</span>
            <span class="tw-text-gray-300" aria-hidden="true">›</span>
          </a>
        </li>
      </ul>
    </template>

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
  </section>
</template>
