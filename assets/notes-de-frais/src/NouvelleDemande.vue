<template>
  <div
    v-if="envoyee"
    class="expense-report-form tw-mx-[30px] tw-mb-8 tw-bg-white tw-p-10 tw-text-center tw-font-sans tw-shadow-sm tw-ring-1 tw-ring-gray-200"
  >
    <div class="tw-mx-auto tw-flex tw-h-12 tw-w-12 tw-items-center tw-justify-center tw-rounded-full tw-bg-green-100 tw-text-xl tw-text-green-700" aria-hidden="true">✓</div>
    <h2 ref="titreEnvoyee" tabindex="-1" class="tw-mt-4 tw-text-xl tw-font-semibold tw-text-gray-900 focus:tw-outline-none">Demande envoyée</h2>
    <p class="tw-mt-2 tw-text-sm tw-text-gray-600">{{ phrasePrevenus(note?.valideurs ?? []) }} Vous recevrez un mail dès qu'il y a une réponse.</p>
    <a :href="URL_MES_NOTES" class="tw-mt-6 tw-inline-block tw-text-sm tw-font-medium tw-text-sky-700 hover:tw-underline">Voir mes notes de frais</a>
  </div>

  <div v-if="!envoyee" class="main-type">
    <h1>Demande de remboursement</h1>
    <p>Pour une formation ou un achat fait pour le club.</p>
  </div>

  <form
    v-if="!envoyee"
    ref="formulaire"
    class="expense-report-form tw-mx-[30px] tw-mb-8 tw-bg-white tw-font-sans tw-shadow-sm tw-ring-1 tw-ring-gray-200"
    @submit.prevent
  >
    <p v-if="messageDeRenvoi" class="tw-whitespace-pre-line tw-border-b tw-border-red-100 tw-bg-red-50 tw-px-8 tw-py-3 tw-text-sm tw-text-red-800">{{ messageDeRenvoi }}</p>
    <p v-if="chargement" class="tw-p-8 tw-text-sm tw-text-gray-600">Chargement de la demande…</p>
    <p v-else-if="erreurDeChargement" role="alert" class="tw-p-8 tw-text-sm tw-text-red-600">{{ erreurDeChargement }}</p>

    <PremiereEtape
      v-else-if="etape === 1"
      v-model="demande"
      :type-modifiable="note === null"
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
      v-model:choix="choix"
      :demande="demande"
      :formation="donnees.formation"
      :commission-titre="commissionTitre"
      :phrase-valideurs="phraseDesValideurs"
      :justificatifs="justificatifs"
      :envois-en-cours="envoisEnCours"
      :erreurs-justificatifs="erreursJustificatifs"
      :avertissements="note?.avertissements ?? []"
      :delai-depasse="delaiDepasse"
      :erreurs="erreursEtape2"
      :refus-sur-les-lignes="refusSurLesLignes"
      :refus-sur-le-choix="refusSurLeChoix"
      :en-cours="enCours"
      :envoi-en-cours="envoiEnCours"
      @modifier="afficherEtape(1)"
      @enregistrer="enregistrer"
      @envoyer="envoyer"
      @justificatif="joindre"
      @montants-modifies="enregistrerLignes"
    />
  </form>
</template>

<script setup lang="ts">
import { computed, inject, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import PremiereEtape from "./components/PremiereEtape.vue";
import SecondeEtape from "./components/SecondeEtape.vue";
import { creerNote, enregistrerNote, envoyerJustificatif, lireNote, messagesDErreur, refusDeLApi, sessionExpiree } from "./api";
import { aujourdhui, limiteEnvoi, messageDelaiDepasse, nomComplet, phrasePrevenus, phraseValideurs } from "./format";
import { refusDuFichier } from "./justificatifs";
import { ecrireLignes, lireLignes, nouvelleLigne } from "./lignes";
import type { TypeHorsSortie } from "./typesDeNote";
import type { Demande, DonneesInitiales, Justificatif, Ligne, NoteDeFrais, Refus, Toastr } from "./types";

const URL_MES_NOTES = "/notes-de-frais";

const props = defineProps<{ donnees: DonneesInitiales }>();
const toastr = inject<Toastr>("toastr");

const formulaire = ref<HTMLFormElement | null>(null);
const note = ref<NoteDeFrais | null>(null);
const etape = ref<1 | 2>(1);
const demande = ref<Demande>({ type: "autre", commissionId: null, objet: "", dateDepense: "", lienFormation: "" });
const lignes = ref<Ligne[]>([]);
const motPourResponsable = ref("");
const choix = ref<boolean | null>(null);
const justificatifs = ref<Record<string, Justificatif>>({});
const envoisEnCours = ref<string[]>([]);
const erreursJustificatifs = ref<Record<string, string>>({});
const erreursEtape1 = ref<string[]>([]);
// Chaque source de refus garde les siens jusqu'à sa prochaine tentative : « Envoyer », « Enregistrer
// pour plus tard » et l'enregistrement automatique des lignes.
const refusEnvoi = ref<Refus[]>([]);
const refusEnregistrement = ref<string[]>([]);
const erreursEnregistrementAuto = ref<string[]>([]);
const enCours = ref(false);
const envoiEnCours = ref(false);
const chargement = ref(props.donnees.noteId !== null);
const erreurDeChargement = ref("");
const envoyee = ref(false);
const titreEnvoyee = ref<HTMLElement | null>(null);

// Le choix, une fois fait, rend son refus caduc ; les autres refus attendent le prochain envoi.
const erreursEtape2 = computed(() =>
  Array.from(
    new Set([
      ...refusEnvoi.value.filter((un) => un.champ !== "refundRequired" || choix.value === null).map((un) => un.message),
      ...refusEnregistrement.value,
      ...erreursEnregistrementAuto.value,
    ]),
  ),
);
const refusSurLesLignes = computed(() => refusEnvoi.value.some((un) => un.champ === "details"));
const refusSurLeChoix = computed(() => choix.value === null && refusEnvoi.value.some((un) => un.champ === "refundRequired"));

// Une note renvoyée à corriger peut être renvoyée après le délai ; une formation n'en a pas.
const delaiEnvoiJours = computed(() =>
  note.value === null || note.value.status === "draft" ? (props.donnees.delaisEnvoiJours[demande.value.type] ?? null) : null,
);

const delaiDepasse = computed(() => {
  if (note.value?.status !== "draft" || delaiEnvoiJours.value === null || !demande.value.dateDepense) {
    return null;
  }

  return aujourdhui() > limiteEnvoi(demande.value.dateDepense, delaiEnvoiJours.value) ? messageDelaiDepasse(delaiEnvoiJours.value) : null;
});

// Une note renvoyée rappelle ce qu'il faut corriger, sur les deux étapes.
const messageDeRenvoi = computed(() => {
  const decision = note.value?.derniereDecision;
  if (note.value?.status !== "rejected" || decision?.statut !== "rejected") {
    return null;
  }

  return `Renvoyée par ${nomComplet(decision.auteur)} : « ${decision.message ?? ""} »`;
});

const commissionChoisie = computed(() => props.donnees.commissions.find((commission) => commission.id === demande.value.commissionId));

const commissionTitre = computed(() => commissionChoisie.value?.titre ?? note.value?.commission?.title ?? "");

const phraseDesValideurs = computed(() => (commissionChoisie.value ? phraseValideurs(commissionChoisie.value.valideurs) : ""));

// —— Ce qui est enregistré, pour prévenir avant de quitter la page ——

function renseignee(ligne: Ligne): boolean {
  return (
    typeof ligne.montant === "number" ||
    typeof ligne.distance === "number" ||
    ligne.description.trim() !== "" ||
    justificatifs.value[ligne.id] !== undefined
  );
}

function empreintes() {
  return {
    demande: JSON.stringify({ ...demande.value, objet: demande.value.objet.trim(), lienFormation: demande.value.lienFormation.trim() }),
    lignes: ecrireLignes(lignes.value.filter(renseignee)),
    mot: motPourResponsable.value.trim(),
    choix: choix.value,
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
  if (noteLue.status !== "draft" && noteLue.status !== "rejected") {
    erreurDeChargement.value = "Cette demande a été envoyée : elle ne peut plus être modifiée.";
    return;
  }

  note.value = noteLue;
  demande.value = {
    type: noteLue.type as TypeHorsSortie,
    commissionId: noteLue.commission?.id ?? null,
    objet: noteLue.objet ?? "",
    dateDepense: noteLue.dateDepense ?? "",
    lienFormation: noteLue.lienFormation ?? "",
  };
  motPourResponsable.value = noteLue.motPourResponsable ?? "";
  choix.value = noteLue.refundRequired ?? null;
  lignes.value = lireLignes(noteLue.details);
  if (!lignes.value.length) {
    lignes.value.push(nouvelleLigne(demande.value.type));
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
      type: demande.value.type,
      commissionId: demande.value.commissionId,
      objet: demande.value.objet.trim(),
      dateDepense: demande.value.dateDepense,
      ...lienDeLaFormation(),
    });
    // Le brouillon se reprend par l'URL ; chemin absolu à cause du <base href> du site.
    history.replaceState(null, "", `${window.location.pathname}?id=${note.value.id}`);
    lignes.value = [nouvelleLigne(demande.value.type)];
    enregistre.value = empreintes();
    await afficherEtape(2);
  } catch (erreur) {
    erreursEtape1.value = messagesDErreur(erreur);
  } finally {
    enCours.value = false;
  }
}

// —— Enregistrement et envoi ——

// Un achat n'a pas de lien : l'API le refuserait.
function lienDeLaFormation(): { lienFormation?: string | null } {
  return demande.value.type === "formation" ? { lienFormation: demande.value.lienFormation.trim() || null } : {};
}

function corpsDeLaNote(): Record<string, unknown> {
  return {
    commission: `/api/commissions/${demande.value.commissionId}`,
    objet: demande.value.objet.trim(),
    dateDepense: demande.value.dateDepense,
    ...lienDeLaFormation(),
    motPourResponsable: motPourResponsable.value.trim() || null,
    details: ecrireLignes(lignes.value),
    // L'API refuse de remettre le choix à « pas encore choisi ».
    ...(choix.value === null ? {} : { refundRequired: choix.value }),
  };
}

async function enregistrer() {
  if (note.value === null || enCours.value) {
    return;
  }

  enCours.value = true;
  refusEnregistrement.value = [];
  if (await attendreLesEnregistrementsEnCours()) {
    // Le résultat de l'enregistrement s'affiche en pied de page, pas en double sous les lignes.
    erreursJustificatifs.value = {};
  }
  const empreinte = empreintes();
  try {
    note.value = await enregistrerNote(note.value.id, corpsDeLaNote());
    enregistre.value = empreinte;
    toastr?.info("Enregistrée. Vous la retrouverez dans « mes notes de frais ».");
  } catch (erreur) {
    refusEnregistrement.value = messagesDErreur(erreur);
    await montrerLesRefus();
  } finally {
    enCours.value = false;
  }
}

async function envoyer() {
  if (note.value === null || enCours.value) {
    return;
  }

  enCours.value = true;
  envoiEnCours.value = true;
  refusEnvoi.value = [];
  refusEnregistrement.value = [];
  try {
    // Sinon la note partirait avec l'ancien justificatif, et le nouveau serait refusé sur une note envoyée.
    if (!(await attendreLesEnregistrementsEnCours())) {
      refusEnvoi.value = [{ champ: null, message: "Un justificatif n'a pas pu être envoyé : la demande n'est pas partie." }];
      await montrerLesRefus();
      return;
    }
    erreursJustificatifs.value = {};
    // Une ligne restée vide n'est pas une dépense ; la retirer garde la numérotation des messages de l'API.
    lignes.value = lignes.value.filter(renseignee);
    note.value = await enregistrerNote(note.value.id, { ...corpsDeLaNote(), status: "submitted" });
    enregistre.value = empreintes();
    envoyee.value = true;
    await nextTick();
    // Le formulaire, plus long, laisse la page défilée : l'en-tête fixe du site masquerait le haut.
    window.scrollTo({ top: 0 });
    titreEnvoyee.value?.focus({ preventScroll: true });
  } catch (erreur) {
    if (!lignes.value.length) {
      lignes.value.push(nouvelleLigne(demande.value.type));
    }
    refusEnvoi.value = refusDeLApi(erreur);
    await montrerLesRefus();
  } finally {
    enCours.value = false;
    envoiEnCours.value = false;
  }
}

// Le focus resterait sur le bouton : on le pose sur les refus, pour qu'ils soient lus et parcourus.
async function montrerLesRefus() {
  await nextTick();
  formulaire.value?.querySelector<HTMLElement>("#f-refus")?.focus();
}

// —— Justificatifs et enregistrement automatique des lignes ——

// Envois de justificatifs en cours, chacun résolu à vrai s'il a réussi.
const envoisDeJustificatifs = new Set<Promise<boolean>>();

/** Attend les justificatifs et l'enregistrement des lignes en cours ; faux si un justificatif a échoué. */
async function attendreLesEnregistrementsEnCours(): Promise<boolean> {
  let reussis = true;
  // Un justificatif choisi pendant l'attente est attendu lui aussi.
  while (envoisDeJustificatifs.size) {
    const resultats = await Promise.all(Array.from(envoisDeJustificatifs));
    reussis = reussis && resultats.every(Boolean);
  }
  await enregistrementDesLignes;

  return reussis;
}

function joindre(ligneId: string, fichier: File): Promise<boolean> {
  const envoi = envoyerLeJustificatif(ligneId, fichier);
  envoisDeJustificatifs.add(envoi);
  void envoi.finally(() => envoisDeJustificatifs.delete(envoi));

  return envoi;
}

async function envoyerLeJustificatif(ligneId: string, fichier: File): Promise<boolean> {
  if (note.value === null) {
    return false;
  }

  delete erreursJustificatifs.value[ligneId];
  const refusDuJustificatif = refusDuFichier(fichier);
  if (refusDuJustificatif !== null) {
    erreursJustificatifs.value[ligneId] = refusDuJustificatif;
    return false;
  }

  envoisEnCours.value.push(ligneId);
  try {
    const pieceJointe = await envoyerJustificatif(note.value.id, ligneId, fichier);
    justificatifs.value[ligneId] = { fileUrl: pieceJointe.fileUrl, nom: fichier.name };
  } catch (erreur) {
    erreursJustificatifs.value[ligneId] = sessionExpiree(erreur)
      ? messagesDErreur(erreur)[0]
      : "Le justificatif n'a pas pu être envoyé. Réessayez avec un fichier JPG, PNG ou PDF.";
    return false;
  } finally {
    envoisEnCours.value = envoisEnCours.value.filter((id) => id !== ligneId);
  }

  await enregistrerLignes();

  return true;
}

// Enregistrement des seules lignes : en cours, « Enregistrer » et « Envoyer » l'attendent.
let enregistrementDesLignes: Promise<void> = Promise.resolve();

/**
 * Un justificatif est rattaché à sa ligne : la ligne doit exister en base dès qu'il est envoyé.
 * Un montant modifié est enregistré aussitôt, pour afficher les avertissements du serveur.
 */
function enregistrerLignes(): Promise<void> {
  enregistrementDesLignes = enregistrementDesLignes.then(async () => {
    if (note.value === null || envoyee.value) {
      return;
    }

    const empreinte = empreintes().lignes;
    try {
      note.value = await enregistrerNote(note.value.id, { details: ecrireLignes(lignes.value) });
      enregistre.value = { ...enregistre.value, lignes: empreinte };
      erreursEnregistrementAuto.value = [];
      erreursJustificatifs.value = {};
    } catch (erreur) {
      erreursEnregistrementAuto.value = messagesDErreur(erreur);
    }
  });

  return enregistrementDesLignes;
}
</script>
