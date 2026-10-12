<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from "vue";
import { conflit, enregistrerNote, erreurDeLecture, lireNote, messagesDErreur, refusDeLApi } from "./api";
import { choixDuDemandeur, dateFr, dateFrDe, decisionSurLaPage, enAttenteDe, euros, jour, lienWeb, nomComplet, partRemboursable } from "./format";
import { libellesDuType } from "./typesDeNote";
import type { DonneesDetail, NoteDeFrais } from "./types";

const props = defineProps<{ donnees: DonneesDetail }>();

const URL_LISTE = "/notes-de-frais/a-valider";
const URL_MES_NOTES = "/notes-de-frais";
const MOTIFS = ["Il manque un justificatif", "Mauvaise commission", "Montant à revoir", "Pas d'accord préalable"];
const TITRE_DE_L_ONGLET = document.title;

const note = ref<NoteDeFrais | null>(null);
const chargement = ref(true);
const erreurDeChargement = ref("");

// Zone de décision : les deux boutons, puis le refus avec son motif, puis le résultat.
const refusOuvert = ref(false);
const message = ref("");
const enCours = ref(false);
const refus = ref<string[]>([]);
const fait = ref<{ texte: string; classe: string } | null>(null);

const champMessage = ref<HTMLTextAreaElement | null>(null);
const boutonRefuser = ref<HTMLButtonElement | null>(null);
const resultat = ref<HTMLElement | null>(null);

const demandeur = computed(() => (note.value ? nomComplet(note.value.utilisateur) : ""));
const lienFormation = computed(() => lienWeb(note.value?.lienFormation ?? null));

const contexte = computed(() => {
  if (!note.value) {
    return "";
  }
  const type = libellesDuType(note.value.type).libelle;
  const date = note.value.dateDepense ? ` du ${dateFr(jour(note.value.dateDepense))}` : "";
  const envoi = note.value.dateEnvoi ? `, envoyée le ${dateFrDe(note.value.dateEnvoi)}` : "";

  return `${type}${date} pour ${note.value.commission?.title ?? ""}${envoi}`;
});

// Un justificatif dont la ligne a été retirée reste rattaché à la note : on ne le montre pas.
const lignes = computed(() => {
  if (!note.value) {
    return [];
  }
  const justificatifs = new Map(note.value.piecesJointes.map((pieceJointe) => [pieceJointe.expenseId, pieceJointe.fileUrl]));

  // Lignes calculées par le serveur. Une ligne de formation se présente par sa nature, une ligne
  // d'achat par son numéro, suivi de sa description.
  return note.value.lignesAffichees.map((ligne, index) => ({
    id: ligne.id ?? `ligne-${index}`,
    numero: index + 1,
    libelle: ligne.nature ? ligne.libelle : `Dépense ${index + 1}`,
    detail: ligne.nature ? ligne.detail : ligne.libelle,
    montant: ligne.montant,
    justificatif: (ligne.id && justificatifs.get(ligne.id)) || null,
  }));
});

const decisionPrise = computed(() => (note.value ? decisionSurLaPage(note.value) : null));

// Un gestionnaire lit aussi le choix dans l'API : la page ne le montre qu'au demandeur.
const choix = computed(() => (note.value && props.donnees.estDemandeur ? choixDuDemandeur(note.value) : null));
const plafonds = computed(() => (note.value ? partRemboursable(note.value, note.value.refundRequired === false) : null));

// Les plafonds s'appliquent sans être un défaut : la phrase dit ce que le club retient.
const verification = computed(() => {
  if (!note.value || note.value.totalRemboursable >= note.value.totalDeclare) {
    return "✓ Justificatifs joints.";
  }
  const retenu = euros(note.value.totalRemboursable);

  return `✓ Justificatifs joints. Plafonds du club appliqués : ${note.value.refundRequired === false ? `${retenu} dans les plafonds du club` : `${retenu} remboursables`}.`;
});

const messageRempli = computed(() => message.value.trim() !== "");

// Une note en attente sans les boutons : on dit pourquoi, et qui décidera.
const attente = computed(() => (!note.value || note.value.peutDecider ? null : enAttenteDe(note.value)));

onMounted(async () => {
  try {
    const lue = await lireNote(props.donnees.noteId);
    if (lue.type === "sortie") {
      erreurDeChargement.value = "Cette note de frais concerne une sortie : elle ne se traite pas sur cette page.";
    } else {
      note.value = lue;
      const objet = lue.objet ?? "";
      document.title = `${TITRE_DE_L_ONGLET} : ${objet.length > 60 ? `${objet.slice(0, 59).trimEnd()}…` : objet}`;
    }
  } catch (erreur) {
    erreurDeChargement.value = erreurDeLecture(erreur);
  } finally {
    chargement.value = false;
  }
});

function nomDuFichier(url: string): string {
  return decodeURIComponent(url.split("/").pop() ?? url);
}

// Le nom enregistré porte un suffixe aléatoire : on montre seulement le type du fichier.
function libelleDuJustificatif(url: string): string {
  const extension = nomDuFichier(url).split(".").pop()?.toLowerCase();
  if (extension === "pdf") {
    return "Justificatif (PDF)";
  }

  return extension === "jpg" || extension === "jpeg" || extension === "png" ? "Justificatif (image)" : "Justificatif";
}

async function ouvrirLeRefus() {
  refusOuvert.value = true;
  await nextTick();
  champMessage.value?.focus();
}

async function annulerLeRefus() {
  refusOuvert.value = false;
  refus.value = [];
  await nextTick();
  boutonRefuser.value?.focus();
}

function contientLeMotif(motif: string): boolean {
  return message.value.includes(`${motif}.`);
}

// Un second clic retire le motif.
function basculerLeMotif(motif: string) {
  if (contientLeMotif(motif)) {
    message.value = message.value.replace(`${motif}.`, "").replace(/ {2,}/g, " ").trim();
    return;
  }
  message.value = `${message.value.trim() ? `${message.value.trim()} ` : ""}${motif}.`;
}

async function decider(corps: Record<string, unknown>, texte: string, classe: string) {
  if (!note.value || enCours.value) {
    return;
  }

  enCours.value = true;
  refus.value = [];
  try {
    note.value = await enregistrerNote(note.value.id, corps);
    fait.value = { texte, classe };
    refusOuvert.value = false;
    await nextTick();
    resultat.value?.focus();
  } catch (erreur) {
    // Traitée entre-temps par quelqu'un d'autre : on montre sa décision.
    if (refusDeLApi(erreur).some((un) => un.champ === "status")) {
      note.value = await lireNote(note.value.id).catch(() => note.value);
      refusOuvert.value = false;
      refus.value = ["Cette note a déjà été traitée."];
    } else if (conflit(erreur)) {
      // Une autre décision au même moment : la page relit la note, le refus en cours reste ouvert s'il a encore lieu d'être.
      note.value = await lireNote(note.value.id).catch(() => note.value);
      refusOuvert.value = refusOuvert.value && Boolean(note.value?.peutDecider);
      refus.value = messagesDErreur(erreur);
    } else {
      refus.value = messagesDErreur(erreur);
    }
  } finally {
    enCours.value = false;
  }
}

function valider() {
  void decider({ status: "preapproved" }, `✓ Validée et transmise à la trésorerie. ${demandeur.value} a été prévenu·e.`, "tw-text-green-700");
}

function refuser() {
  if (!messageRempli.value) {
    return;
  }
  void decider(
    { status: "rejected", commentaireStatut: message.value.trim() },
    `Refusée. ${demandeur.value} a reçu votre message.`,
    "tw-text-gray-700",
  );
}
</script>

<template>
  <div class="main-type">
    <p class="mini">
      <a v-if="donnees.estDemandeur" :href="URL_MES_NOTES">Mes notes de frais</a>
      <a v-else :href="URL_LISTE">Notes de frais à valider</a>
    </p>
    <h1>{{ note?.objet ?? "Note de frais" }}</h1>
  </div>

  <p v-if="chargement" class="expense-report-form tw-mx-[30px] tw-mb-8 tw-bg-white tw-p-8 tw-font-sans tw-text-sm tw-text-gray-600 tw-shadow-sm tw-ring-1 tw-ring-gray-200">
    Chargement de la note…
  </p>
  <div
    v-else-if="erreurDeChargement || !note"
    class="expense-report-form tw-mx-[30px] tw-mb-8 tw-bg-white tw-p-8 tw-font-sans tw-text-sm tw-shadow-sm tw-ring-1 tw-ring-gray-200"
  >
    <p role="alert" class="tw-text-red-600">{{ erreurDeChargement }}</p>
    <a :href="URL_MES_NOTES" class="tw-mt-4 tw-inline-block tw-font-medium tw-text-sky-700 hover:tw-underline">Voir mes notes de frais</a>
  </div>

  <div v-else class="expense-report-form tw-mx-[30px] tw-mb-8 tw-bg-white tw-font-sans tw-shadow-sm tw-ring-1 tw-ring-gray-200">
    <div class="tw-flex tw-items-start tw-justify-between tw-gap-4 tw-px-8 tw-py-6">
      <div>
        <p class="tw-font-medium tw-text-gray-900">{{ demandeur }}</p>
        <p class="tw-text-xs tw-text-gray-500">{{ contexte }}</p>
        <a v-if="lienFormation" :href="lienFormation" target="_blank" rel="noopener" class="tw-text-xs tw-text-sky-700 hover:tw-underline">Voir la formation</a>
        <p v-else-if="note.lienFormation" class="tw-break-all tw-text-xs tw-text-gray-500">{{ note.lienFormation }}</p>
      </div>
      <div class="tw-text-right">
        <p class="tw-text-2xl tw-font-semibold tw-tabular-nums tw-text-gray-900">{{ euros(note.totalDeclare) }}</p>
        <!-- Le choix du demandeur redit déjà le montant retenu ; le total en bas de la table le garde. -->
        <p v-if="choix" class="tw-mt-1 tw-text-sm tw-text-gray-700">{{ choix }}</p>
        <p v-else-if="plafonds" class="tw-text-xs tw-text-gray-500">{{ plafonds }}</p>
      </div>
    </div>

    <div class="tw-px-8">
      <p v-if="note.motPourResponsable" class="tw-mb-4 tw-whitespace-pre-line tw-border-l-2 tw-border-gray-300 tw-pl-3 tw-text-sm tw-text-gray-700">«&nbsp;{{ note.motPourResponsable }}&nbsp;»</p>
      <p v-if="note.status === 'submitted'" class="tw-text-sm tw-text-green-700">{{ verification }}</p>
      <p v-for="avertissement in note.avertissements" :key="avertissement" class="tw-mt-1 tw-text-sm tw-text-amber-700">{{ avertissement }}</p>

      <table class="tw-mt-5 tw-w-full tw-text-sm">
        <caption class="tw-sr-only">Dépenses</caption>
        <tbody>
        <tr v-for="ligne in lignes" :key="ligne.id" class="tw-border-t tw-border-gray-100">
          <td class="tw-py-2.5 tw-text-gray-900">
            {{ ligne.libelle }}<template v-if="ligne.detail"> · <span class="tw-text-gray-500">{{ ligne.detail }}</span></template>
          </td>
          <td class="tw-py-2.5 tw-text-right">
            <a
              v-if="ligne.justificatif"
              :href="ligne.justificatif"
              target="_blank"
              rel="noopener"
              :aria-label="`Justificatif de la dépense ${ligne.numero}`"
              :title="nomDuFichier(ligne.justificatif)"
              class="tw-whitespace-nowrap tw-text-xs tw-text-sky-700 hover:tw-underline"
              >{{ libelleDuJustificatif(ligne.justificatif) }}</a
            >
          </td>
          <td class="tw-w-24 tw-py-2.5 tw-text-right tw-tabular-nums">{{ euros(ligne.montant) }}</td>
        </tr>
        </tbody>
        <!-- Avec beaucoup de lignes, le total en haut est loin des boutons. -->
        <tfoot>
          <tr class="tw-border-t tw-border-gray-200">
            <td colspan="2" class="tw-pt-3 tw-text-right tw-text-gray-600">Total</td>
            <td class="tw-pt-3 tw-text-right tw-font-semibold tw-tabular-nums tw-text-gray-900">{{ euros(note.totalDeclare) }}</td>
          </tr>
          <tr v-if="plafonds">
            <td colspan="3" class="tw-text-right tw-text-xs tw-text-gray-500">{{ plafonds }}</td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div class="tw-mt-6 tw-border-t tw-border-gray-100 tw-bg-gray-50 tw-px-8 tw-py-5">
      <template v-if="decisionPrise && !fait">
        <p class="tw-mb-3 tw-text-sm tw-text-gray-700">
          {{ decisionPrise.texte }}
          <span v-if="decisionPrise.message" class="tw-whitespace-pre-line">«&nbsp;{{ decisionPrise.message }}&nbsp;»</span>
        </p>
        <p v-if="decisionPrise.suite" class="tw-mb-3 tw-text-sm tw-text-gray-700">{{ decisionPrise.suite }}</p>
      </template>

      <template v-if="fait">
        <p ref="resultat" tabindex="-1" role="status" :class="['tw-text-right tw-text-sm tw-font-medium focus:tw-outline-none', fait.classe]">{{ fait.texte }}</p>
        <p class="tw-mt-2 tw-text-right tw-text-sm"><a :href="URL_LISTE" class="tw-font-medium tw-text-sky-700 hover:tw-underline">Retour aux notes à valider</a></p>
      </template>

      <template v-else-if="note.peutDecider">
        <div v-if="!refusOuvert" class="tw-flex tw-justify-end tw-gap-3">
          <button
            ref="boutonRefuser"
            type="button"
            class="tw-rounded-md tw-px-4 tw-py-2 tw-text-sm tw-font-semibold tw-text-gray-700 hover:tw-bg-gray-100"
            @click="ouvrirLeRefus"
          >
            Refuser
          </button>
          <button
            type="button"
            :aria-disabled="enCours"
            class="tw-rounded-md tw-bg-green-700 tw-px-5 tw-py-2 tw-text-sm tw-font-semibold tw-text-white hover:tw-bg-green-800 aria-disabled:tw-cursor-wait aria-disabled:tw-opacity-60"
            @click="valider"
          >
            Valider
          </button>
        </div>
        <div v-else>
          <label for="message-refus" class="tw-block tw-text-sm tw-font-medium tw-text-gray-900">Motif du refus : que doit corriger {{ note.utilisateur.prenom }} ?</label>
          <div class="tw-mt-2 tw-flex tw-flex-wrap tw-gap-2">
            <button
              v-for="motif in MOTIFS"
              :key="motif"
              type="button"
              :aria-pressed="contientLeMotif(motif)"
              :class="[
                'tw-rounded-full tw-border tw-px-3 tw-py-1 tw-text-xs',
                contientLeMotif(motif) ? 'tw-border-sky-600 tw-bg-sky-50 tw-font-semibold tw-text-sky-800' : 'tw-border-gray-300 tw-bg-white tw-text-gray-700 hover:tw-border-sky-400',
              ]"
              @click="basculerLeMotif(motif)"
            >
              {{ motif }}
            </button>
          </div>
          <textarea
            id="message-refus"
            ref="champMessage"
            v-model="message"
            rows="2"
            class="tw-mt-3 tw-w-full tw-rounded-md tw-border tw-border-gray-300 tw-bg-white tw-p-2 tw-text-sm focus:tw-border-sky-500 focus:tw-outline-none"
          ></textarea>
          <div class="tw-mt-3 tw-flex tw-justify-end tw-gap-3">
            <button type="button" class="tw-px-3 tw-py-2 tw-text-sm tw-text-gray-600 hover:tw-text-gray-900" @click="annulerLeRefus">Annuler</button>
            <button
              type="button"
              :disabled="!messageRempli"
              :aria-disabled="enCours"
              class="tw-rounded-md tw-bg-red-600 tw-px-5 tw-py-2 tw-text-sm tw-font-semibold tw-text-white disabled:tw-opacity-40 aria-disabled:tw-cursor-wait aria-disabled:tw-opacity-60"
              @click="enCours || refuser()"
            >
              Refuser la note
            </button>
          </div>
        </div>
      </template>

      <p v-else-if="attente" class="tw-text-sm tw-text-gray-600">{{ attente }}</p>

      <div aria-live="polite" class="tw-text-right tw-text-sm tw-text-red-600">
        <p v-for="un in refus" :key="un" class="tw-mt-2">{{ un }}</p>
      </div>
    </div>
  </div>
</template>
