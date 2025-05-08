# Notes de frais

L'application permet de gérer les notes de frais des sorties. Le système est divisé en deux parties principales :

## 1. Soumission des notes de frais (Partie encadrants)

### Interface VueJs
- Disponible dans la page de chaque sortie
- Template twig pour envoyer un récapitulatif de la demande de note de frais à l'encadrant
- API pour récupérer les infos de la note de frais pour l'utiliser dans la partie admin

### Configuration des taux
Les taux d'indemnités kilométriques sont configurés à deux endroits :
1. `assets/expense-report-form/config/expense-report.json` (côté client)
2. `config/services.yaml` (côté serveur)

⚠️ En cas de modification des taux, il faut bien penser à mettre à jour les deux endroits.

## 2. Vérification et validation (Partie comptabilité)

La vérification des notes de frais est gérée par une [interface distincte développée en NextJS](https://github.com/Club-Alpin-Lyon-Villeurbanne/compta-club).

Les taux d'indemnités kilométriques sont également configurés dans le fichier [config.ts](https://github.com/Club-Alpin-Lyon-Villeurbanne/compta-club/blob/main/app/config.ts) de ce projet. 