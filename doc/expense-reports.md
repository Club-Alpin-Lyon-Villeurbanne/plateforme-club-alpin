# Expense Reports (notes de frais)

## Routes

- Lister les notes de frais : `GET` `/expense-report`
- Récupérer une note de frais : `GET` `/api/expense-report/{id}`


## Structure des données

### Structure d'une notre de frais enregistrée

- Note de frais (ExpenseReport)
    - Groupe de dépenses (ExpenseGroup)
        - Dépenses (Expense)
            - Champs (ExpenseField)

### Structure du formulaire type

- Groupe de dépenses (ExpenseGroup)
    - Type de dépense (ExpenseType)
        - Types de champs (ExpenseFieldType)

## Génération du formulaire

## Serialization