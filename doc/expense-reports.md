# Expense Reports (notes de frais)

## Routes

- Lister les notes de frais : `GET` `/expense-report`
- Récupérer une note de frais : `GET` `/api/expense-report/{id}`


## Structure des données

### Structure d'une notre de frais enregistrée

- Note de frais (`ExpenseReport`)
    - Groupe de dépenses (`ExpenseGroup`)
        - Dépenses (`Expense`)
            - Type de dépense (`ExpenseType`)
            - Champs (`ExpenseField`)

### Structure du formulaire type

- Groupe de dépenses (`ExpenseGroup`)
    - Type de dépense (`ExpenseType`)
        - Types de champs (`ExpenseFieldType`)

## Ressources REST

### Liste des notes de frais

`GET` `/api/expense-report`


<details>
<summary>Response payload</summary>
   
```json
{
	"success": true,
	"expenseReports": [
		{
			"id": 27,
			"owner": 4896,
			"event": 6087,
			"status": "draft",
			"refundRequired": false,
			"createdAt": "2024-02-27 05:37:50",
			"updatedAt": "2024-02-27 13:47:51"
		},
		{
			"id": 28,
			"owner": 4896,
			"event": 5943,
			"status": "draft",
			"refundRequired": false,
			"createdAt": "2024-03-11 17:22:18",
			"updatedAt": "2024-03-11 17:22:18"
		}
	]
}
```
</details>

### Récupérer une note de frais

`GET` `/api/expense-report/{id}`


<details>
<summary>Response payload</summary>
   
```json
{
	"success": true,
	"expenseReport": {
		"id": 27,
		"status": "draft",
		"refundRequired": false,
		"user": 4896,
		"event": 6087,
		"createdAt": "2024-02-27 05:37:50",
		"updatedAt": "2024-02-27 13:47:51",
		"expenseGroups": {
			"transport": {
				"0": {
					"id": 104,
					"expenseType": {
						"id": 2,
						"name": "Véhicule personnel",
						"slug": "vehicule_personnel",
						"fieldTypes": [
							{
								"id": 1,
								"name": "Distance (en kilomètres)",
								"slug": "distance",
								"inputType": "numeric",
								"fieldTypeId": 1,
								"flags": []
							},
							{
								"id": 4,
								"name": "Péage",
								"slug": "peage",
								"inputType": "numeric",
								"fieldTypeId": 4,
								"flags": []
							},
							{
								"id": 3,
								"name": "Description",
								"slug": "description",
								"inputType": "text",
								"fieldTypeId": 3,
								"flags": []
							},
							{
								"id": 5,
								"name": "Nombre de voyageurs",
								"slug": "nombre_voyageurs",
								"inputType": "numeric",
								"fieldTypeId": 5,
								"flags": []
							}
						]
					},
					"fields": [
						{
							"id": 231,
							"justificationDocument": null,
							"value": "15",
							"expense": 104,
							"fieldType": 1,
							"inputType": "numeric",
							"createdAt": "2024-02-28 10:26:17",
							"updatedAt": "2024-02-28 10:26:17"
						},
						{
							"id": 232,
							"justificationDocument": null,
							"value": null,
							"expense": 104,
							"fieldType": 4,
							"inputType": "numeric",
							"createdAt": "2024-02-28 10:26:17",
							"updatedAt": "2024-02-28 10:26:17"
						},
						{
							"id": 233,
							"justificationDocument": null,
							"value": null,
							"expense": 104,
							"fieldType": 3,
							"inputType": "text",
							"createdAt": "2024-02-28 10:26:17",
							"updatedAt": "2024-02-28 10:26:17"
						},
						{
							"id": 234,
							"justificationDocument": null,
							"value": "2",
							"expense": 104,
							"fieldType": 5,
							"inputType": "numeric",
							"createdAt": "2024-02-28 10:26:17",
							"updatedAt": "2024-02-28 10:26:17"
						}
					]
				},
				"selectedType": "vehicule_personnel"
			},
			"hebergement": [
				{
					"id": 105,
					"expenseType": {
						"id": 3,
						"name": "Nuitée (demi-pension)",
						"slug": "nuitee",
						"fieldTypes": [
							{
								"id": 2,
								"name": "Prix (en Euros)",
								"slug": "prix",
								"inputType": "numeric",
								"fieldTypeId": 2,
								"flags": []
							},
							{
								"id": 3,
								"name": "Description",
								"slug": "description",
								"inputType": "text",
								"fieldTypeId": 3,
								"flags": []
							}
						]
					},
					"fields": [
						{
							"id": 235,
							"justificationDocument": null,
							"value": "15",
							"expense": 105,
							"fieldType": 2,
							"inputType": "numeric",
							"createdAt": "2024-02-28 10:26:17",
							"updatedAt": "2024-02-28 10:26:17"
						},
						{
							"id": 236,
							"justificationDocument": null,
							"value": null,
							"expense": 105,
							"fieldType": 3,
							"inputType": "text",
							"createdAt": "2024-02-28 10:26:17",
							"updatedAt": "2024-02-28 10:26:17"
						}
					]
				}
			],
			"autres": [
				{
					"id": 106,
					"expenseType": {
						"id": 4,
						"name": "Autre",
						"slug": "autre-depense",
						"fieldTypes": [
							{
								"id": 2,
								"name": "Prix (en Euros)",
								"slug": "prix",
								"inputType": "numeric",
								"fieldTypeId": 2,
								"flags": []
							},
							{
								"id": 3,
								"name": "Description",
								"slug": "description",
								"inputType": "text",
								"fieldTypeId": 3,
								"flags": []
							}
						]
					},
					"fields": [
						{
							"id": 237,
							"justificationDocument": null,
							"value": null,
							"expense": 106,
							"fieldType": 2,
							"inputType": "numeric",
							"createdAt": "2024-02-28 10:26:17",
							"updatedAt": "2024-02-28 10:26:17"
						},
						{
							"id": 238,
							"justificationDocument": null,
							"value": null,
							"expense": 106,
							"fieldType": 3,
							"inputType": "text",
							"createdAt": "2024-02-28 10:26:17",
							"updatedAt": "2024-02-28 10:26:17"
						}
					]
				}
			]
		}
	}
}
```
</details>