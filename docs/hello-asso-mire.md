# Hello Asso

## Configuration technique
### Configuration "partenaire"
Le groupement de club dispose d'un compte de type "partenaire" auprès d'Hello Asso, ce qui permet de gérer plusieurs associations (donc plusieurs clubs) de manière indépendante les uns des autres avec un seul "méta-accès". \
Les identifiants de ce compte sont en variables d'environnement (`HELLO_ASSO_CLIENT_ID` et `HELLO_ASSO_CLIENT_SECRET`).

L'URL de base de l'API est également à renseigner en variable d'environnement (`HELLO_ASSO_API_BASE_URL`). \
- En staging, mettre la sandbox HelloAsso : https://api.helloasso-sandbox.com
- En prod, l'URL est https://api.helloasso.com

### Mire d'autorisation : configuration par club
Le compte partenaire permettant de gérer plusieurs associations / clubs, il faut configurer l'accès pour chaque club. Cette manipulation ne devrait être à faire qu'une seule fois, cf "rotation du token".

https://dev.helloasso.com/docs/mire-authorisation

@todo, trouver une bonne manière de le faire, sachant que le "domaine" au niveau du compte partenaire ne doit pas changer (et ne devrait pas être celui du club de Lyon).
1. Se connecter avec un compte ayant accès à la mire (cf matrice des droits, rubrique "Hello Asso", droit "Mire d'autorisation HelloAsso")
2. Accéder à la page /ha-mire
3. Cliquer sur le bouton pour accéder à la page de connexion côté Hello Asso, où il faut sélectionner (ou créer s'il n'existe pas) le club que l'on souhaite "lier" (jargon Hello Asso) après s'être connecté avec le compte administrateur Hello Asso du club concerné.
4. On est ensuite redirigé vers le site du club et des informations sont sauvegardées en base de données dans la table `config` : le slug du club (= organisation dans le jargon Hello Asso), le refresh token et sa date d'obtention.

Pour cette partie, il existe également une variable d'environnement à renseigner : `HELLO_ASSO_AUTHORIZE_BASE_URL` 
- sand box (à utiliser en staging) : https://auth.helloasso-sandbox.com
- prod : https://auth.helloasso.com

### Rotation du token
Pour éviter de devoir refaire les manipulations de "Mire d'autorisation", on conserve le token fourni par Hello Asso et on le renouvelle automatiquement via un cron job (`ha-refresh-token`), à faire tourner chaque jour. \
Le refresh token est valable 30 j et il sera renouvelé par le cron, au plus tard, 28 j après sa date d'obtention. \
Chaque action nécessitant un token le renouvelle également. (@todo, voir si ce n'est pas un peu too much pour Hello Asso) \
Il y a donc peu de risque de "casser" la chaîne d'autorisation. Si ça se produit, il faudra refaire l'étape "Mire d'autorisation".

## Utilisation au quotidien
### Fonctionnelle
L'API est utilisée pour :
1. Créer une campagne associée à une sortie qui est configurée pour. La campagne se créée lors de l'approbation et publication de la sortie.
2. Lister les gens qui ont payé à l'affichage de la sortie.

### Technique
Définir la variable d'environnement `HELLO_ASSO_ACTIVITY_TYPE_ID` pour définir l'id du type "Sortie" (2907 en sand box).

Lors de la création de la campagne (= form dans le jargon Hello Asso), on stocke, au retour de l'appel API, l'URL de la billetterie et le slug. Ces informations sont stockées dans 2 nouveaux champs de la table `caf_evt` (`hello_asso_form_slug` et `hello_asso_form_url`).

### Limitations connues
1. Il ne semble pas possible de supprimer une campagne ou de la modifier via API, que faire en cas de modification de sortie si on souhaite enlever la billetterie ou modifier le montant ?
2. Le nombre de payeurs récupérés est limité à 100 / page => pour les très grosses sorties (plus de 100 participants), il faudra faire plusieurs appels API pour utiliser leur système de pagination

## Ressources utiles

- Doc Hello Asso : https://dev.helloasso.com/docs/introduction-%C3%A0-lapi-de-helloasso
- Doc API de Hello Asso : https://dev.helloasso.com/reference
- Créer une campagne : https://dev.helloasso.com/reference/post_organizations-organizationslug-forms-formtype-action-quick-create
- Lister les paiements : https://dev.helloasso.com/reference/get_organizations-organizationslug-forms-formtype-formslug-payments
