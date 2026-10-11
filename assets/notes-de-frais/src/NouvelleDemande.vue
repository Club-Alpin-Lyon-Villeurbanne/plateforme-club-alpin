<template>
  <div class="main-type">
    <h1>Demande de remboursement</h1>
    <p>Pour une formation ou un achat fait pour le club.</p>
  </div>

  <form
    class="expense-report-form tw-mx-[30px] tw-mb-8 tw-bg-white tw-font-sans tw-shadow-sm tw-ring-1 tw-ring-gray-200"
    @submit.prevent
  >
    <p v-if="chargement" class="tw-p-8 tw-text-sm tw-text-gray-600">Chargement de la demande…</p>
    <p v-else-if="erreurDeChargement" class="tw-p-8 tw-text-sm tw-text-red-600">{{ erreurDeChargement }}</p>

    <PremiereEtape
      v-else-if="etape === 1"
      v-model="demande"
      :commissions="donnees.commissions"
      :delai-envoi-jours="delaiEnvoiJours"
      :erreurs="erreursEtape1"
      :en-cours="enCours"
      @continuer="continuer"
    />

    <SecondeEtape
      v-else
      v-model:lignes="lignes"
      v-model:mot="motPourResponsable"
      :demande="demande"
      :commission-titre="commissionTitre"
      :justificatifs="justificatifs"
      :envois-en-cours="envoisEnCours"
      :erreurs="erreursEtape2"
      :en-cours="enCours"
      @modifier="etape = 1"
      @enregistrer="enregistrer"
      @justificatif="joindre"
    />
  </form>
</template>

<script setup lang="ts">
import { computed, inject, onMounted, ref } from "vue";
import PremiereEtape from "./components/PremiereEtape.vue";
import SecondeEtape from "./components/SecondeEtape.vue";
import { creerNote, enregistrerNote, envoyerJustificatif, lireNote, messagesDErreur } from "./api";
import { ecrireLignes, lireLignes, nouvelleLigne } from "./lignes";
import type { Demande, DonneesInitiales, Justificatif, Ligne, NoteDeFrais, Toastr } from "./types";

// Seul l'achat est proposé pour l'instant.
const TYPE = "autre";

const props = defineProps<{ donnees: DonneesInitiales }>();
const toastr = inject<Toastr>("toastr");

const note = ref<NoteDeFrais | null>(null);
const etape = ref<1 | 2>(1);
const demande = ref<Demande>({ commissionId: null, objet: "", dateDepense: "" });
const lignes = ref<Ligne[]>([]);
const motPourResponsable = ref("");
const justificatifs = ref<Record<string, Justificatif>>({});
const envoisEnCours = ref<string[]>([]);
const erreursEtape1 = ref<string[]>([]);
const erreursEtape2 = ref<string[]>([]);
const enCours = ref(false);
const chargement = ref(props.donnees.noteId !== null);
const erreurDeChargement = ref("");

// Une note renvoyée à corriger peut être renvoyée après le délai.
const delaiEnvoiJours = computed(() =>
  note.value === null || note.value.status === "draft" ? (props.donnees.delaisEnvoiJours[TYPE] ?? null) : null,
);

const commissionTitre = computed(
  () =>
    props.donnees.commissions.find((commission) => commission.id === demande.value.commissionId)?.titre ??
    note.value?.commission?.title ??
    "",
);

onMounted(async () => {
  if (props.donnees.noteId === null) {
    return;
  }

  try {
    reprendre(await lireNote(props.donnees.noteId));
  } catch {
    erreurDeChargement.value = "Impossible de charger cette demande.";
  } finally {
    chargement.value = false;
  }
});

function reprendre(noteLue: NoteDeFrais) {
  if (noteLue.type === "sortie") {
    erreurDeChargement.value = "Cette note de frais concerne une sortie : elle se remplit sur la page de la sortie.";
    return;
  }
  if (noteLue.status !== "draft" && noteLue.status !== "rejected") {
    erreurDeChargement.value = "Cette demande a été envoyée : elle ne peut plus être modifiée.";
    return;
  }

  note.value = noteLue;
  demande.value = {
    commissionId: noteLue.commission?.id ?? null,
    objet: noteLue.objet ?? "",
    dateDepense: noteLue.dateDepense ?? "",
  };
  motPourResponsable.value = noteLue.motPourResponsable ?? "";
  lignes.value = lireLignes(noteLue.details);
  if (!lignes.value.length) {
    lignes.value.push(nouvelleLigne());
  }
  for (const pieceJointe of noteLue.piecesJointes) {
    justificatifs.value[pieceJointe.expenseId] = { fileUrl: pieceJointe.fileUrl, nom: null };
  }
  etape.value = 2;
}

async function continuer() {
  // Une fois le brouillon créé, la première étape est enregistrée avec le reste.
  if (note.value !== null) {
    etape.value = 2;
    return;
  }

  enCours.value = true;
  erreursEtape1.value = [];
  try {
    note.value = await creerNote({
      type: TYPE,
      commissionId: demande.value.commissionId,
      objet: demande.value.objet.trim(),
      dateDepense: demande.value.dateDepense,
    });
    // Le brouillon se reprend par l'URL ; chemin absolu à cause du <base href> du site.
    history.replaceState(null, "", `${window.location.pathname}?id=${note.value.id}`);
    lignes.value = [nouvelleLigne()];
    etape.value = 2;
  } catch (erreur) {
    erreursEtape1.value = messagesDErreur(erreur);
  } finally {
    enCours.value = false;
  }
}

async function enregistrer() {
  if (note.value === null) {
    return;
  }

  enCours.value = true;
  erreursEtape2.value = [];
  try {
    note.value = await enregistrerNote(note.value.id, {
      commission: `/api/commissions/${demande.value.commissionId}`,
      objet: demande.value.objet.trim(),
      dateDepense: demande.value.dateDepense,
      motPourResponsable: motPourResponsable.value.trim() || null,
      details: ecrireLignes(lignes.value),
    });
    toastr?.info("Enregistrée. Vous la retrouverez dans « mes notes de frais ».");
  } catch (erreur) {
    erreursEtape2.value = messagesDErreur(erreur);
  } finally {
    enCours.value = false;
  }
}

async function joindre(ligneId: string, fichier: File) {
  if (note.value === null) {
    return;
  }

  envoisEnCours.value.push(ligneId);
  try {
    const pieceJointe = await envoyerJustificatif(note.value.id, ligneId, fichier);
    justificatifs.value[ligneId] = { fileUrl: pieceJointe.fileUrl, nom: fichier.name };
  } catch (erreur) {
    messagesDErreur(erreur).forEach((message) => toastr?.error(message));
  } finally {
    envoisEnCours.value = envoisEnCours.value.filter((id) => id !== ligneId);
  }
}
</script>
