import { createApp, type Component } from "vue";
import AValider from "./src/AValider.vue";
import DetailNote from "./src/DetailNote.vue";
import MesNotes from "./src/MesNotes.vue";
import NouvelleDemande from "./src/NouvelleDemande.vue";

const PAGES: Record<string, Component> = {
  "mes-notes": MesNotes,
  "a-valider": AValider,
  detail: DetailNote,
};

const racine = document.getElementById("notes-de-frais");

if (racine) {
  const donnees = JSON.parse(racine.dataset.donnees ?? "{}");
  const page = PAGES[racine.dataset.page ?? ""] ?? NouvelleDemande;

  createApp(page, { donnees })
    .provide("toastr", (window as any).toastr)
    .mount(racine);
}
