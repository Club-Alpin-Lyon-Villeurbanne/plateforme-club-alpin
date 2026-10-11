import { createApp } from "vue";
import MesNotes from "./src/MesNotes.vue";
import NouvelleDemande from "./src/NouvelleDemande.vue";

const racine = document.getElementById("notes-de-frais");

if (racine) {
  const donnees = JSON.parse(racine.dataset.donnees ?? "{}");
  const page = racine.dataset.page === "mes-notes" ? MesNotes : NouvelleDemande;

  createApp(page, { donnees })
    .provide("toastr", (window as any).toastr)
    .mount(racine);
}
