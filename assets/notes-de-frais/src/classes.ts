// Classes de la maquette, communes aux champs du formulaire.
export const champ =
  "tw-h-9 tw-w-full tw-appearance-none tw-rounded-md tw-border tw-border-gray-300 tw-bg-white tw-px-3 tw-text-sm tw-text-gray-900 placeholder:tw-text-gray-400 focus:tw-border-sky-500 focus:tw-outline-none focus:tw-ring-1 focus:tw-ring-sky-500";
export const libelle = "tw-block tw-text-sm tw-font-medium tw-text-gray-900";
export const discret = "tw-text-xs tw-text-gray-500";
// Un champ que le dernier refus d'envoi signale.
export const champEnErreur = champ.replace("tw-border-gray-300", "tw-border-red-500");
