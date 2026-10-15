<template>
  <div
    v-if="envoyee"
    class="expense-report-form tw-mx-[30px] tw-mb-8 tw-bg-white tw-p-10 tw-text-center tw-font-sans tw-shadow-sm tw-ring-1 tw-ring-gray-200"
  >
    <div class="tw-mx-auto tw-flex tw-h-12 tw-w-12 tw-items-center tw-justify-center tw-rounded-full tw-bg-green-100 tw-text-xl tw-text-green-700" aria-hidden="true">✓</div>
    <h2 ref="titreEnvoyee" tabindex="-1" class="tw-mt-4 tw-text-xl tw-font-semibold tw-text-gray-900 focus:tw-outline-none">Note envoyée</h2>
    <p class="tw-mt-2 tw-text-sm tw-text-gray-600">{{ phrasePrevenus(note?.valideurs ?? []) }} Vous recevrez un mail dès qu'il y a une réponse.</p>
    <a :href="URL_MES_NOTES" class="tw-mt-6 tw-inline-block tw-text-sm tw-font-medium tw-text-sky-700 hover:tw-underline">Voir mes notes de frais</a>
  </div>

  <div v-if="!envoyee" class="main-type">
    <h1>{{ titre }}</h1>
    <p v-if="!erreurDeChargement">Pour une formation ou un achat fait pour le club.</p>
  </div>

  <form
    v-if="!envoyee"
    ref="formulaire"
    class="expense-report-form tw-mx-[30px] tw-mb-8 tw-bg-white tw-font-sans tw-shadow-sm tw-ring-1 tw-ring-gray-200"
    novalidate
    @submit.prevent
  >
    <p v-if="messageDeRefus" class="tw-whitespace-pre-line tw-border-b tw-border-red-100 tw-bg-red-50 tw-px-8 tw-py-3 tw-text-sm tw-text-red-800">{{ messageDeRefus }}</p>
    <p v-if="chargement" class="tw-p-8 tw-text-sm tw-text-gray-600">Chargement de la note…</p>
    <div v-else-if="erreurDeChargement" class="tw-p-8 tw-text-sm">
      <p ref="messageDeChargement" role="alert" tabindex="-1" class="tw-text-red-600 focus:tw-outline-none">{{ erreurDeChargement }}</p>
      <a :href="URL_MES_NOTES" class="tw-mt-4 tw-inline-block tw-font-medium tw-text-sky-700 hover:tw-underline">Voir mes notes de frais</a>
    </div>

    <PremiereEtape
      v-else-if="etape === 1"
      v-model="demande"
      :type-modifiable="note === null"
      :commissions="donnees.commissions"
      :sortie="donnees.sortie"
      :delai-envoi-jours="delaiEnvoiJours"
      :erreurs="erreursEtape1"
      :en-cours="enCours"
      @continuer="continuer"
    />

    <SecondeEtape
      v-else
      ref="secondeEtape"
      :key="rechargements"
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
      :part-remboursable="note ? partRemboursable(note, choix === false) : null"
      :avertissements="note?.avertissements ?? []"
      :delai-depasse="delaiDepasse"
      :erreurs="erreursEtape2"
      :refus-sur-les-lignes="refusSurLesLignes"
      :refus-sur-le-choix="refusSurLeChoix"
      :en-cours="enCours"
      :envoi-en-cours="envoiEnCours"
      :conflit="conflitDeVersion"
      :confirmation="confirmation"
      :brouillon="note?.status === 'draft'"
      :destinataire-du-mot="versLaTresorerie ? 'la trésorerie' : 'les responsables de la commission'"
      :libelle-envoi="versLaTresorerie ? 'Envoyer à la trésorerie' : 'Envoyer à la commission'"
      @modifier="afficherEtape(1)"
      @enregistrer="enregistrer"
      @envoyer="envoyer"
      @justificatif="joindre"
      @montants-modifies="enregistrerLignes"
      @recharger="recharger"
    />
  </form>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import PremiereEtape from "./components/PremiereEtape.vue";
import SecondeEtape from "./components/SecondeEtape.vue";
import { conflit, creerNote, enregistrerNote, envoyerJustificatif, erreurDeLecture, lireNote, messagesDErreur, NOTE_DEJA_ENVOYEE, refusDeLApi, sessionExpiree, statutDuRefus } from "./api";
import { auteurDeLaDecision, aujourdhui, limiteEnvoi, messageDelaiDepasse, partRemboursable, phrasePrevenus, phraseValideurs } from "./format";
import { refusDuFichier } from "./justificatifs";
import { ecrireLignes, lireLignes, nouvelleLigne, total } from "./lignes";
import type { TypeHorsSortie } from "./typesDeNote";
import type { Demande, DonneesInitiales, Justificatif, Ligne, NoteDeFrais, Refus } from "./types";

const URL_MES_NOTES = "/notes-de-frais";
// Texte de l'API (ControlesNoteHorsSortie) : il ne vaut plus dès qu'une dépense est saisie.
const SANS_DEPENSE = "Il manque au moins une dépense.";
const TITRE_DE_L_ONGLET = document.title;

const props = defineProps<{ donnees: DonneesInitiales }>();

const formulaire = ref<HTMLFormElement | null>(null);
const secondeEtape = ref<InstanceType<typeof SecondeEtape> | null>(null);
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
const messageDeChargement = ref<HTMLElement | null>(null);
const envoyee = ref(false);
const titreEnvoyee = ref<HTMLElement | null>(null);
// La note a changé ailleurs depuis sa lecture : plus rien ne s'enregistre avant « Recharger ».
const conflitDeVersion = ref<string | null>(null);
// Remonte la seconde étape sur la version rechargée.
const rechargements = ref(0);
// « Enregistrer pour plus tard » a réussi : la confirmation reste jusqu'à la prochaine modification.
const confirmation = ref<string | null>(null);

// Le choix, une fois fait, rend son refus caduc, comme une dépense saisie le refus d'une note sans
// dépense ; les autres refus attendent le prochain envoi.
const uneDepenseSaisie = computed(() => total(lignes.value, props.donnees.formation.tauxKilometrique) > 0);
const erreursEtape2 = computed(() =>
  Array.from(
    new Set([
      ...refusEnvoi.value
        .filter((un) => un.champ !== "refundRequired" || choix.value === null)
        .filter((un) => un.message !== SANS_DEPENSE || !uneDepenseSaisie.value)
        .map((un) => un.message),
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

// Une note refusée rappelle ce qu'il faut corriger, sur les deux étapes.
const messageDeRefus = computed(() => {
  const decision = note.value?.derniereDecision;
  if (note.value?.status !== "rejected" || decision?.statut !== "rejected") {
    return null;
  }

  return `Refusée par ${auteurDeLaDecision(decision)} : «\u00a0${decision.message ?? ""}\u00a0»`;
});

const titre = computed(() => (note.value?.status === "rejected" ? "Corriger la note" : "Nouvelle note de frais"));
watch(titre, (nouveau) => (document.title = TITRE_DE_L_ONGLET.replace("Nouvelle note de frais", nouveau)));

const commissionChoisie = computed(() => props.donnees.commissions.find((commission) => commission.id === demande.value.commissionId));

const commissionTitre = computed(() => commissionChoisie.value?.titre ?? note.value?.commission?.title ?? "");

const phraseDesValideurs = computed(() => (commissionChoisie.value ? phraseValideurs(commissionChoisie.value) : ""));

const versLaTresorerie = computed(() => (commissionChoisie.value?.valideurs ?? note.value?.valideurs ?? []).length === 0);

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
watch(() => JSON.stringify(empreintes()), () => (confirmation.value = null));

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
    erreurDeChargement.value = erreurDeLecture(erreur);
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
    void noteDejaEnvoyee();
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
  justificatifs.value = {};
  for (const pieceJointe of noteLue.piecesJointes) {
    justificatifs.value[pieceJointe.expenseId] = { fileUrl: pieceJointe.fileUrl, nom: null };
  }
  enregistre.value = empreintes();
  etape.value = 2;
}

// Le composant de l'étape quittée emporte le focus : on le pose sur le premier champ utile, celui
// de la première ligne vide (la nature d'une formation, le montant d'un achat).
async function afficherEtape(nouvelleEtape: 1 | 2) {
  etape.value = nouvelleEtape;
  await nextTick();

  if (nouvelleEtape === 1) {
    formulaire.value?.querySelector<HTMLElement>("#f-commission")?.focus();
    return;
  }
  secondeEtape.value?.focaliserLaLigne(Math.max(0, lignes.value.findIndex((ligne) => !renseignee(ligne))));
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
  if (conflitDeVersion.value) {
    await montrerLesRefus();
    return;
  }

  enCours.value = true;
  refusEnregistrement.value = [];
  if (await attendreLesJustificatifs()) {
    // Le résultat de l'enregistrement s'affiche en pied de page, pas en double sous les lignes.
    erreursJustificatifs.value = {};
  }
  try {
    const ecrite = await dansLOrdre(async () => {
      const empreinte = empreintes();
      if (!(await ecrire(corpsDeLaNote()))) {
        return false;
      }
      enregistre.value = empreinte;
      return true;
    });
    if (!ecrite) {
      await montrerLesRefus();
      return;
    }
    const maintenant = new Date();
    const heure = `${maintenant.getHours()}\u00a0h\u00a0${String(maintenant.getMinutes()).padStart(2, "0")}`;
    // Une saisie faite pendant l'enregistrement n'est pas enregistrée : pas de confirmation.
    if (!nonEnregistre.value) {
      confirmation.value = `Enregistrée à ${heure}. Vous la retrouverez dans «\u00a0Mes notes de frais\u00a0».`;
    }
  } catch (erreur) {
    if (!ongletPerime(erreur)) {
      refusEnregistrement.value = messagesDErreur(erreur);
    }
    await montrerLesRefus();
  } finally {
    enCours.value = false;
  }
}

async function envoyer() {
  if (note.value === null || enCours.value) {
    return;
  }
  if (conflitDeVersion.value) {
    await montrerLesRefus();
    return;
  }

  enCours.value = true;
  envoiEnCours.value = true;
  refusEnvoi.value = [];
  refusEnregistrement.value = [];
  try {
    // Sinon la note partirait avec l'ancien justificatif, et le nouveau serait refusé sur une note envoyée.
    if (!(await attendreLesJustificatifs())) {
      refusEnvoi.value = [{ champ: null, message: "Un justificatif n'a pas pu être envoyé : la note n'est pas partie." }];
      await montrerLesRefus();
      return;
    }
    erreursJustificatifs.value = {};
    // Une ligne restée vide n'est pas une dépense ; la retirer garde la numérotation des messages de l'API.
    lignes.value = lignes.value.filter(renseignee);
    // Une écriture passée avant dans la file a pu trouver la note déjà envoyée ou modifiée ailleurs.
    if (!(await dansLOrdre(() => ecrire({ ...corpsDeLaNote(), status: "submitted" })))) {
      await montrerLesRefus();
      return;
    }
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
    if (!ongletPerime(erreur)) {
      refusEnvoi.value = refusDeLApi(erreur);
    }
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

// —— Écritures, une à la fois ——

// Chaque écriture renvoie la version de la note reçue avec la précédente : deux écritures parties
// ensemble de ce formulaire se contrediraient, la seconde serait refusée comme venant d'un onglet périmé.
let fileDesEcritures: Promise<unknown> = Promise.resolve();

function dansLOrdre<T>(ecriture: () => Promise<T>): Promise<T> {
  const resultat = fileDesEcritures.then(ecriture);
  fileDesEcritures = resultat.catch(() => undefined);

  return resultat;
}

/** Faux, sans rien envoyer, quand la note est déjà envoyée ou modifiée ailleurs. */
async function ecrire(corps: Record<string, unknown>): Promise<boolean> {
  if (note.value === null || conflitDeVersion.value) {
    return false;
  }
  note.value = await enregistrerNote(note.value.id, { ...corps, version: note.value.version });

  return true;
}

/** Un autre onglet ou un autre appareil est passé avant : vrai si l'erreur le dit, et la page le montre. */
function ongletPerime(erreur: unknown): boolean {
  if (conflit(erreur)) {
    conflitDeVersion.value = messagesDErreur(erreur)[0];
    return true;
  }
  if (refusDeLApi(erreur).some((refus) => refus.message === NOTE_DEJA_ENVOYEE)) {
    void noteDejaEnvoyee();
    return true;
  }

  return false;
}

// Plus rien ne peut s'enregistrer : la page le dit, sans retenir le départ. Le formulaire se réduit
// à ce message : comme après un envoi, la page remonte pour qu'il ne reste pas au-dessus de l'écran.
async function noteDejaEnvoyee() {
  note.value = null;
  erreurDeChargement.value = "Cette note a été envoyée : elle ne peut plus être modifiée.";
  await nextTick();
  window.scrollTo({ top: 0 });
  messageDeChargement.value?.focus({ preventScroll: true });
}

// La saisie non enregistrée est remplacée par la note telle qu'elle est enregistrée.
async function recharger() {
  if (note.value === null) {
    return;
  }

  try {
    const lue = await lireNote(note.value.id);
    conflitDeVersion.value = null;
    refusEnvoi.value = [];
    refusEnregistrement.value = [];
    erreursEnregistrementAuto.value = [];
    erreursJustificatifs.value = {};
    rechargements.value += 1;
    reprendre(lue);
    await afficherEtape(2);
  } catch (erreur) {
    conflitDeVersion.value = messagesDErreur(erreur)[0];
  }
}

// —— Justificatifs et enregistrement automatique des lignes ——

// Envois de justificatifs en cours, chacun résolu à vrai s'il a réussi.
const envoisDeJustificatifs = new Set<Promise<boolean>>();

/** Attend les justificatifs en cours ; faux si l'un a échoué. Leurs lignes s'enregistrent avant ce qui suit. */
async function attendreLesJustificatifs(): Promise<boolean> {
  let reussis = true;
  // Un justificatif choisi pendant l'attente est attendu lui aussi.
  while (envoisDeJustificatifs.size) {
    const resultats = await Promise.all(Array.from(envoisDeJustificatifs));
    reussis = reussis && resultats.every(Boolean);
  }

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
    // Le serveur ne refuse un justificatif valide que sur une note déjà envoyée.
    if (statutDuRefus(erreur) === 422) {
      void noteDejaEnvoyee();
      return false;
    }
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

/**
 * Un justificatif est rattaché à sa ligne : la ligne doit exister en base dès qu'il est envoyé.
 * Un montant modifié est enregistré aussitôt, pour afficher les avertissements du serveur.
 */
function enregistrerLignes(): Promise<void> {
  return dansLOrdre(async () => {
    if (note.value === null || envoyee.value || conflitDeVersion.value) {
      return;
    }

    const empreinte = empreintes().lignes;
    try {
      await ecrire({ details: ecrireLignes(lignes.value) });
      enregistre.value = { ...enregistre.value, lignes: empreinte };
      erreursEnregistrementAuto.value = [];
      erreursJustificatifs.value = {};
    } catch (erreur) {
      erreursEnregistrementAuto.value = ongletPerime(erreur) ? [] : messagesDErreur(erreur);
    }
  });
}
</script>
