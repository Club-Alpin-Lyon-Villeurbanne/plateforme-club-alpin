<template>
  <input :value="texte" type="text" inputmode="decimal" autocomplete="off" @input="saisir" @change="arrondir" />
</template>

<script setup lang="ts">
// Un montant ou une distance, en texte : un champ nombre lit « 12,50 » comme 1250 dans un
// navigateur en anglais.
import { ref, watch } from "vue";
import { lireNombre } from "../lignes";

const valeur = defineModel<number | null>({ required: true });
const emit = defineEmits<{ change: [] }>();

function enTexte(nombre: number | null): string {
  return nombre === null ? "" : String(nombre).replace(".", ",");
}

const texte = ref(enTexte(valeur.value));
// Arrondie, ou changée ailleurs (note rechargée) : la saisie en cours, elle, n'est pas réécrite.
watch(valeur, (nouvelle) => {
  if (lireNombre(texte.value) !== nouvelle) {
    texte.value = enTexte(nouvelle);
  }
});

function saisir(evenement: Event) {
  texte.value = (evenement.target as HTMLInputElement).value;
  valeur.value = lireNombre(texte.value);
}

// Le serveur refuse plus de deux décimales.
function arrondir() {
  if (valeur.value !== null) {
    valeur.value = Math.round(valeur.value * 100) / 100;
  }
  emit("change");
}
</script>
