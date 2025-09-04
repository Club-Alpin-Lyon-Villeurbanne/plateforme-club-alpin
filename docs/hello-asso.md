# Hello Asso

## Configuration technique
### Clés d'API
Il faut récupérer ou générer des identifiants API dans son compte Hello Asso. L'identifiant doit disposer du droit `OrganizationAdmin` (demander à Hello Asso de l'attribuer, si nécessaire).

Les identifiants ainsi récupérés sont à définir en variables d'environnement (`HELLO_ASSO_CLIENT_ID` et `HELLO_ASSO_CLIENT_SECRET`).

### Autres paramètres d'API 
L'URL de base de l'API est également à renseigner en variable d'environnement (`HELLO_ASSO_API_BASE_URL`). \
- En staging, mettre la sandbox HelloAsso : https://api.helloasso-sandbox.com
- En prod, l'URL est https://api.helloasso.com

Enfin, le slug de l'association est à récupérer dans Hello Asso (dans l'URL) et à mettre dans la variable d'environnement `HELLO_ASSO_ORGANIZATION_SLUG` (ex, pour Lyon : club-alpin-francais-de-lyon-villeurbanne).

### Rotation du token
Pour éviter de devoir se reconnecter à chaque utilisation, on conserve le token fourni par Hello Asso et on le renouvelle automatiquement via un cron job (`ha-refresh-token`), à faire tourner chaque jour. \
Le refresh token est valable 30 j et il sera renouvelé par le cron, au plus tard, 28 j après sa date d'obtention. \
Chaque action nécessitant un token le renouvelle également. (@todo, voir si ce n'est pas un peu too much pour Hello Asso)

## Utilisation au quotidien
### Fonctionnelle
L'API est utilisée pour :
1. Créer une campagne associée à une sortie qui est configurée pour. La campagne se créée lors de l'approbation et publication de la sortie.
2. Lister les gens qui ont payé à l'affichage de la sortie.

### Technique
Définir la variable d'environnement `HELLO_ASSO_ACTIVITY_TYPE_ID` pour définir l'id du type "Sortie" (ex : 2907 en sand box).

Lors de la création de la campagne (= form dans le jargon Hello Asso), on stocke, au retour de l'appel API, l'URL de la billetterie et le slug. Ces informations sont stockées dans 2 nouveaux champs de la table `caf_evt` (`hello_asso_form_slug` et `hello_asso_form_url`).

### Limitations connues
1. Il ne semble pas possible de supprimer une campagne ou de la modifier via API, que faire en cas de modification de sortie si on souhaite enlever la billetterie ou modifier le montant des frais ?
2. Le nombre de payeurs récupérés est limité à 100 / page => pour les très grosses sorties (plus de 100 participants), il faudra faire plusieurs appels API pour utiliser leur système de pagination

## Ressources utiles

- Doc Hello Asso : https://dev.helloasso.com/docs/introduction-%C3%A0-lapi-de-helloasso
- Doc API de Hello Asso : https://dev.helloasso.com/reference
- Créer une campagne : https://dev.helloasso.com/reference/post_organizations-organizationslug-forms-formtype-action-quick-create
- Lister les paiements : https://dev.helloasso.com/reference/get_organizations-organizationslug-forms-formtype-formslug-payments
