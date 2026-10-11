<template>
  <div class="main-type">
    <h1>Demande de remboursement</h1>
    <p>Pour une formation ou un achat fait pour le club.</p>
  </div>

  <form
    ref="formulaire"
    class="expense-report-form tw-mx-[30px] tw-mb-8 tw-bg-white tw-font-sans tw-shadow-sm tw-ring-1 tw-ring-gray-200"
    @submit.prevent
  >
    <p v-if="chargement" class="tw-p-8 tw-text-sm tw-text-gray-600">Chargement de la demande…</p>
    <p v-else-if="erreurDeChargement" role="alert" class="tw-p-8 tw-text-sm tw-text-red-600">{{ erreurDeChargement }}</p>

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
      :erreurs-justificatifs="erreursJustificatifs"
      :erreurs="erreursEtape2"
      :en-cours="enCours"
      @modifier="afficherEtape(1)"
      @enregistrer="enregistrer"
      @justificatif="joindre"
    />
  </form>
</template>

<script setup lang="ts">
import { computed, inject, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import PremiereEtape from "./components/PremiereEtape.vue";
import SecondeEtape from "./components/SecondeEtape.vue";
import { creerNote, enregistrerNote, envoyerJustificatif, lireNote, messagesDErreur, sessionExpiree } from "./api";
import { refusDuFichier } from "./justificatifs";
import { ecrireLignes, lireLignes, nouvelleLigne } from "./lignes";
import type { Demande, DonneesInitiales, Justificatif, Ligne, NoteDeFrais, Toastr } from "./types";

// Seul l'achat est proposé pour l'instant.
const TYPE = "autre";

const props = defineProps<{ donnees: DonneesInitiales }>();
const toastr = inject<Toastr>("toastr");

const formulaire = ref<HTMLFormElement | null>(null);
const note = ref<NoteDeFrais | null>(null);
const etape = ref<1 | 2>(1);
const demande = ref<Demande>({ commissionId: null, objet: "", dateDepense: "" });
const lignes = ref<Ligne[]>([]);
const motPourResponsable = ref("");
const justificatifs = ref<Record<string, Justificatif>>({});
const envoisEnCours = ref<string[]>([]);
const erreursJustificatifs = ref<Record<string, string>>({});
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

// —— Ce qui est enregistré, pour prévenir avant de quitter la page ——

function empreintes() {
  const lignesRenseignees = lignes.value.filter(
    (ligne) =>
      typeof ligne.montant === "number" || ligne.description.trim() !== "" || justificatifs.value[ligne.id] !== undefined,
  );

  return {
    demande: JSON.stringify({ ...demande.value, objet: demande.value.objet.trim() }),
    lignes: ecrireLignes(lignesRenseignees),
    mot: motPourResponsable.value.trim(),
  };
}

const enregistre = ref(empreintes());
const nonEnregistre = computed(
  () => note.value !== null && JSON.stringify(empreintes()) !== JSON.stringify(enregistre.value),
);

function avertirAvantDeQuitter(evenement: BeforeUnloadEvent) {
  if (nonEnregistre.value) {
    evenement.preventDefault();
    evenement.returnValue = "";
  }
}

onMounted(() => window.addEventListener("beforeunload", avertirAvantDeQuitter));
onBeforeUnmount(() => window.removeEventListener("beforeunload", avertirAvantDeQuitter));

// —— Chargement et étapes ——

onMounted(async () => {
  if (props.donnees.noteId === null) {
    return;
  }

  try {
    reprendre(await lireNote(props.donnees.noteId));
  } catch (erreur) {
    erreurDeChargement.value = sessionExpiree(erreur) ? messagesDErreur(erreur)[0] : "Impossible de charger cette demande.";
  } finally {
    chargement.value = false;
  }
});

function reprendre(noteLue: NoteDeFrais) {
  if (noteLue.type === "sortie") {
    erreurDeChargement.value = "Cette note de frais concerne une sortie : elle se remplit sur la page de la sortie.";
    return;
  }
  // Cette page ne connaît que les lignes d'un achat : elle effacerait les natures d'une formation.
  if (noteLue.type !== TYPE) {
    erreurDeChargement.value = "Cette demande n'est pas un achat : elle ne peut pas encore être modifiée sur cette page.";
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
  enregistre.value = empreintes();
  etape.value = 2;
}

// Le composant de l'étape quittée emporte le focus : on le pose sur le premier champ utile.
async function afficherEtape(nouvelleEtape: 1 | 2) {
  etape.value = nouvelleEtape;
  await nextTick();

  if (nouvelleEtape === 1) {
    formulaire.value?.querySelector<HTMLElement>("#f-commission")?.focus();
    return;
  }
  const montants = Array.from(formulaire.value?.querySelectorAll<HTMLInputElement>("input[type=number]") ?? []);
  (montants.find((montant) => montant.value === "") ?? montants[0])?.focus();
}

async function continuer() {
  // Une fois le brouillon créé, la première étape est enregistrée avec le reste.
  if (note.value !== null) {
    await afficherEtape(2);
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
    enregistre.value = empreintes();
    await afficherEtape(2);
  } catch (erreur) {
    erreursEtape1.value = messagesDErreur(erreur);
  } finally {
    enCours.value = false;
  }
}

// —— Enregistrement ——

async function enregistrer() {
  if (note.value === null) {
    return;
  }

  enCours.value = true;
  erreursEtape2.value = [];
  const empreinte = empreintes();
  try {
    note.value = await enregistrerNote(note.value.id, {
      commission: `/api/commissions/${demande.value.commissionId}`,
      objet: demande.value.objet.trim(),
      dateDepense: demande.value.dateDepense,
      motPourResponsable: motPourResponsable.value.trim() || null,
      details: ecrireLignes(lignes.value),
    });
    enregistre.value = empreinte;
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

  delete erreursJustificatifs.value[ligneId];
  const refus = refusDuFichier(fichier);
  if (refus !== null) {
    erreursJustificatifs.value[ligneId] = refus;
    return;
  }

  envoisEnCours.value.push(ligneId);
  try {
    const pieceJointe = await envoyerJustificatif(note.value.id, ligneId, fichier);
    justificatifs.value[ligneId] = { fileUrl: pieceJointe.fileUrl, nom: fichier.name };
  } catch (erreur) {
    erreursJustificatifs.value[ligneId] = sessionExpiree(erreur)
      ? messagesDErreur(erreur)[0]
      : "Le justificatif n'a pas pu être envoyé. Réessayez avec un fichier JPG, PNG ou PDF.";
    return;
  } finally {
    envoisEnCours.value = envoisEnCours.value.filter((id) => id !== ligneId);
  }

  await enregistrerLignes();
}

// Un justificatif est rattaché à sa ligne : la ligne doit exister en base dès qu'il est envoyé.
async function enregistrerLignes() {
  if (note.value === null) {
    return;
  }

  const empreinte = empreintes().lignes;
  try {
    note.value = await enregistrerNote(note.value.id, { details: ecrireLignes(lignes.value) });
    enregistre.value = { ...enregistre.value, lignes: empreinte };
    erreursEtape2.value = [];
  } catch (erreur) {
    erreursEtape2.value = messagesDErreur(erreur);
  }
}
</script>
