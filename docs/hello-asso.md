# Hello Asso

## Configuration technique
### Clés d'API
Il faut récupérer ou générer des identifiants API depuis son compte Hello Asso. L'identifiant doit disposer du droit `OrganizationAdmin` (demander à Hello Asso de l'attribuer, si nécessaire).

Pour générer ou récupérer les identifiants, se connecter sur Hello Asso.
1. En haut à droite, cliquer sur son nom, puis sur le club souhaité (si vous en avez plusieurs)
2. Dans le menu de gauche, cliquer sur "Mon compte", puis sur "Integrations et API"
3. Copier "Mon clientId" et "Mon clientSecret" ou cliquer sur "Générer ma clé API" pour en créer une.

Les identifiants ainsi récupérés sont à définir en variables d'environnement (`HELLO_ASSO_CLIENT_ID` et `HELLO_ASSO_CLIENT_SECRET`).

### Autres paramètres d'API 
L'URL de base de l'API est également à renseigner en variable d'environnement (`HELLO_ASSO_API_BASE_URL`). \
- En staging, mettre la sandbox HelloAsso : https://api.helloasso-sandbox.com
- En prod, l'URL est https://api.helloasso.com

Enfin, le slug de l'association est à récupérer dans Hello Asso (dans l'URL) et à mettre dans la variable d'environnement `HELLO_ASSO_ORGANIZATION_SLUG` (ex, pour Lyon : club-alpin-francais-de-lyon-villeurbanne).

## Utilisation au quotidien
### Fonctionnelle
L'API est utilisée pour :
1. Créer une campagne associée à une sortie qui est configurée pour. La campagne se créée lors de l'approbation et publication de la sortie.
2. Publier la campagne sur Hello Asso.

### Technique
Définir la variable d'environnement `HELLO_ASSO_ACTIVITY_TYPE_ID` pour définir l'id du type "Sortie" (ex : 2907 en sand box).

Lors de la création de la campagne (= form dans le jargon Hello Asso), on stocke, au retour de l'appel API, l'URL de la billetterie et le slug. Ces informations sont stockées dans 2 nouveaux champs de la table `caf_evt` (`hello_asso_form_slug` et `payment_url`).

Lors d'un paiement, Hello Asso appelle un webhook (à configurer dans Hello Asso) qui valide le paiement (si l'email correspond). Les paiements sont stockés dans la table `caf_evt_join` (`has_paid`). \
Pour cela, il faut définir la variable d'environnement `HELLO_ASSO_WEBHOOK_SIGNATURE_KEY` ainsi que l'IP d'origine de l'appel webhook (`HELLO_ASSO_SERVER_IP`).

### Limitations connues
1. Il ne semble pas possible de supprimer une campagne ou de la modifier via API, que faire en cas de modification de sortie si on souhaite enlever la billetterie ou modifier le montant des frais ?

## Ressources utiles

- Doc Hello Asso : https://dev.helloasso.com/docs/introduction-%C3%A0-lapi-de-helloasso
- Doc API de Hello Asso : https://dev.helloasso.com/reference
- Créer une campagne : https://dev.helloasso.com/reference/post_organizations-organizationslug-forms-formtype-action-quick-create
- Lister les paiements : https://dev.helloasso.com/reference/get_organizations-organizationslug-forms-formtype-formslug-payments
- Webhook : https://dev.helloasso.com/docs/secure-webhook
