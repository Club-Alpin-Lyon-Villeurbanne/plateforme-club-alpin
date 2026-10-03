# Notes de Frais

L'application permet de gérer les notes de frais des sorties en deux parties distinctes.

## Soumission des Notes de Frais

Interface VueJS disponible dans la page de chaque sortie :
- Template Twig pour l'envoi du récapitulatif
- API pour récupérer les informations

### Configuration des Taux

Les taux d'indemnités kilométriques sont configurés à deux endroits :
1. `assets/expense-report-form/config/expense-report.json` (client)
2. `config/services.yaml` (server)

⚠️ En cas de modification des taux, mettre à jour les deux fichiers.

## Validation des Notes de Frais

Interface distincte développée en NextJS : [compta-club](https://github.com/Club-Alpin-Lyon-Villeurbanne/compta-club)

Les taux d'indemnités kilométriques sont également configurés dans :
https://github.com/Club-Alpin-Lyon-Villeurbanne/compta-club/blob/main/app/config.ts 
## Règles de gestion

### Rattachement et délai

- Une note de frais est toujours **rattachée à une sortie** : il n'existe pas de note de frais « hors sortie ».
- Elle peut être saisie jusqu'à **120 jours après la fin de la sortie** (`Evt::EXPENSE_REPORT_DEADLINE_DAYS`).
- Une sortie qui a une note de frais ne peut plus être supprimée.

### Statuts

`draft` (brouillon) → `submitted` (soumise) → `approved` (approuvée) ou `rejected` (refusée) → `accounted` (comptabilisée). L'historique des changements de statut est conservé (entité `ExpenseReportStatusHistory`).

La gestion des notes de frais sur le site est réservée aux utilisateurs dont l'identifiant figure dans la variable d'environnement `AUTHORIZED_IDS_FOR_EXPENSE_MANAGEMENT` (`ExpenseReportVoter`) : ce n'est pas un droit de la matrice.

### Calcul

Calcul serveur : `src/Service/ExpenseReportCalculator.php`. Paramètres dans `config/services.yaml` (clé `expense_report`) :

| Paramètre | Rôle | Valeur à Lyon |
|---|---|---|
| `tauxKilometriqueVoiture` | €/km en voiture personnelle | 0,20 |
| `tauxKilometriqueMinibus` | €/km en minibus du club | 0,30 |
| `divisionPeage` | diviseur appliqué au péage en voiture personnelle | 3 |
| `nuiteeMaxRemboursable` | plafond remboursable par nuit d'hébergement | 60 € |

| Type de transport | Formule |
|---|---|
| Voiture personnelle | distance × taux voiture + péage ÷ `divisionPeage` |
| Minibus du club | (distance × taux minibus + carburant + péage) ÷ nombre de passagers |
| Minibus de location | (location + carburant + péage) ÷ nombre de passagers |
| Transports en commun | prix du billet |

- **Hébergement** : chaque nuit est remboursable dans la limite de `nuiteeMaxRemboursable`.
- **Autres dépenses** : entièrement remboursables.
- Le **total remboursable** peut donc être inférieur au total dépensé.

### Saisie (formulaire)

Validation côté client : `assets/expense-report-form/src/schemas/expenses.ts`.

- La partie **transport est obligatoire** : il faut choisir l'un des 4 types ci-dessus (pas d'option « sans transport »).
- Voiture et minibus du club : distance d'au moins 1 km. Minibus : au moins 1 passager.
- Chaque hébergement ou autre dépense doit valoir au moins 1 €.
