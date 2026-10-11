import { createApp } from "vue";
import NouvelleDemande from "./src/NouvelleDemande.vue";
import type { DonneesInitiales } from "./src/types";

const racine = document.getElementById("notes-de-frais");

if (racine) {
  const donnees: DonneesInitiales = JSON.parse(racine.dataset.donnees ?? "{}");

  createApp(NouvelleDemande, { donnees })
    .provide("toastr", (window as any).toastr)
    .mount(racine);
}
