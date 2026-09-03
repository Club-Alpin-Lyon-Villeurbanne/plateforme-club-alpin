# Intégration email marketing — MailerLite

## Vue d'ensemble

Les adhérents lyonnais sont inscrits automatiquement, une fois par jour, dans l'un de deux
circuits MailerLite déclenchés par groupe :

- **`accueil-nouveau`** (anciennement `adherent`, identifiant `159667990712813289`) : circuit de
  bienvenue, pour les adhérents dont la licence de la saison est une première prise.
- **`accueil-renouvellement`** : circuit de renouvellement, pour les adhérents qui étaient déjà
  licenciés une saison précédente.

Le renommage `adherent` → `accueil-nouveau` côté MailerLite est sans effet technique : le code et
les automations se lient sur l'identifiant de groupe, jamais sur son nom.

Un troisième groupe, **`import`**, existe dans le même compte MailerLite : c'est la liste de
diffusion de la newsletter du club, maintenue à la main. Ce dispositif ne l'alimente jamais et ne
doit jamais y toucher.

Le circuit de bienvenue historique (synchronisation à la création/confirmation de compte) a été
supprimé en décembre 2025 ; il est resté en panne, sans que personne ne s'en aperçoive, jusqu'à ce
remplacement.

MailerLite n'est déployé qu'à Lyon. Chambéry et Clermont partagent ce dépôt et son
`clevercloud/cron.json`, mais n'ont pas de compte MailerLite : la commande le détecte (pas de
`MAILERLITE_API_KEY`) et ne fait rien, sans faire échouer le cron.

## La commande `mailerlite-accueil-sync`

```bash
bin/console mailerlite-accueil-sync              # dry-run : affiche la répartition, n'envoie rien
bin/console mailerlite-accueil-sync --execute     # envoie réellement
bin/console mailerlite-accueil-sync --season 2026 # force la saison traitée (par défaut : saison en cours)
bin/console mailerlite-accueil-sync --force       # passe outre le plafond de volume
```

Options :

| Option      | Effet |
|-------------|-------|
| `--execute` | Effectue réellement les envois (sinon dry-run). |
| `--force`   | Passe outre le plafond de volume (voir ci-dessous). |
| `--season`  | Force la saison traitée (année de septembre), utile pour rejouer une saison ou pour les tests. |
| `--now`     | Force la date de référence (tests uniquement). |

### Calcul de la saison

La saison sportive bascule le 1er septembre : une exécution le 31 août 2026 traite la saison 2025,
une exécution le 1er septembre 2026 traite la saison 2026. Ce calcul est dérivé de la date
courante, rien n'est à reconfigurer d'une année sur l'autre.

### Sélection et répartition

Sont éligibles les licenciés annuels non supprimés, dont la licence a été prise pour la saison
traitée, non radiés, avec un email valide, pas encore traités pour cette saison
(`UserRepository::findForAccueilCircuit()`).

Parmi les candidats, une fiche créée pendant la saison (à partir du 1er septembre) va vers
`accueil-nouveau` ; une fiche antérieure va vers `accueil-renouvellement`. Avant chaque ajout à un
groupe, la commande retire l'adhérent du groupe visé : les automations MailerLite se déclenchent
sur « rejoint le groupe », un ajout sans retrait préalable serait sans effet pour un abonné déjà
présent. Ces retraits sont espacés d'1 seconde : l'API MailerLite plafonne autour de 120 requêtes
par minute, et un jour de pointe (177 licences sur la journée la plus chargée de 2025) saturerait
sinon le quota. Un retrait en échec exclut l'adhérent du marquage : il sera repris à la prochaine
exécution, quitte à recevoir le circuit deux fois — un doublon est préférable à un oubli définitif.

### Plafond de volume

Au-delà de 800 candidats sur une seule exécution, la commande refuse d'envoyer (code de sortie 1)
et demande `--force`, en envoyant aussi un message Sentry (le cron ne passe jamais `--force` : sans
ce signal, la commande échouerait tous les matins sans que personne ne le voie). Ce volume ferait
suspecter une erreur de sélection plutôt qu'un pic normal d'inscriptions. Sur la saison 2025, le
cumul de licences prises atteignait déjà 828 au 10 septembre — un déploiement tardif peut donc
légitimement dépasser ce plafond dès la première exécution ; la marche à suivre est alors de
vérifier le dry-run puis de relancer une fois avec `--execute --force`.

### Colonne `accueil_season`

La table `caf_user` porte une colonne `accueil_season` (0 par défaut) qui retient la dernière
saison pour laquelle un adhérent a été traité. Le marquage se fait en SQL natif
(`UserRepository::markAccueilSeason()`) pour ne pas déclencher le trait `Timestampable` de l'ORM,
qui modifierait `updated_at` pour des milliers de fiches. Il n'a lieu qu'après confirmation par
l'API MailerLite ; en cas d'échec d'import, l'adhérent reste éligible et sera repris à la
prochaine exécution.

## Alerte de silence

Septembre et octobre concentrent l'essentiel des prises de licence (1 501 rien qu'en septembre sur
la saison 2025). Passé le 15 septembre et jusqu'au 31 octobre, si aucun adhérent n'a été traité
pour la saison en cours (`UserRepository::countAccueilForSeason()` renvoie 0), la commande écrit
une alerte sur la sortie, la journalise en erreur et envoie un message Sentry. Avant le 15
septembre, un compteur à zéro est normal (peu de renouvellements) et ne déclenche rien.

C'est la réponse directe à la panne du circuit de bienvenue du 2 décembre 2025, restée invisible
pendant neuf mois faute d'un signal de ce type.

## Alerte de configuration

Indépendamment de toute fenêtre de dates, si `MAILERLITE_API_KEY` est renseignée (donc si le club
utilise réellement MailerLite) mais qu'un identifiant de groupe manque, ou que les deux
identifiants sont identiques, la commande écrit une erreur, la journalise et envoie un message
Sentry — tout en renvoyant `SUCCESS`, pour ne pas rendre rouge en permanence un cron partagé avec
Chambéry et Clermont.

La même alerte se déclenche aussi quand `MAILERLITE_API_KEY` est absente mais que
`MAILERLITE_RENEWAL_GROUP_ID` est renseigné : ce dernier n'est posé qu'à la main sur la prod
lyonnaise, jamais à Chambéry ni Clermont, donc sa présence sans clé ne peut désigner que Lyon
amputée de sa clé API — pas un club sans MailerLite.

`MAILERLITE_WELCOME_GROUP_ID` est committé dans `.env` et donc renseigné partout, tandis que
`MAILERLITE_RENEWAL_GROUP_ID` n'est posé qu'à la main sur la prod lyonnaise — c'est ce qui en fait
le seul discriminant fiable entre « ce club n'utilise pas MailerLite » et « Lyon a perdu sa
configuration ». Ce contrôle joue toute l'année, là où l'alerte de silence est bornée au 15
septembre - 31 octobre : une variable perdue en novembre resterait sinon invisible dix mois.

## Ce que le dispositif ne détecte pas

**La case `repeatable` des automations MailerLite.** C'est le point unique de défaillance du
dispositif, et aucun code ne le surveille. Si elle n'est pas cochée, ou si elle saute lors d'une
refonte des automations, les retraits et les ajouts réussiront, les adhérents seront marqués comme
traités, et aucun mail ne partira — sans que rien ne l'indique. Une vérification manuelle des
envois est donc à faire à la mi-septembre 2027 : le mécanisme retrait/ajout n'aura jamais tourné
pour de vrai avant cette date, puisqu'en 2026 personne n'est encore dans les groupes cibles et que
tous les retraits sont des no-op.

**L'alerte de silence se désarme dès le premier adhérent traité de la saison.** Elle répond à
« rien n'est jamais parti cette saison », pas à « ça s'est arrêté en cours de route ». Une panne
survenant après les premiers envois de septembre n'est pas couverte par elle ; seule l'alerte de
configuration ci-dessus le serait, et uniquement si la cause est une variable d'environnement
perdue.

## Planification

**Le cron n'est pas planifié pour l'instant** : la ligne correspondante a été retirée de
`clevercloud/cron.json` tant que les circuits ne sont pas validés. Aucun envoi n'a donc lieu, même
en production. Pour activer la fonctionnalité, il suffit de remettre cette ligne :

```json
"45 7 * * * $ROOT/clevercloud/crons/mailerlite-accueil-sync.sh"
```

Une fois planifié, le cron `clevercloud/crons/mailerlite-accueil-sync.sh` appelle
`bin/console mailerlite-accueil-sync --execute` tous les jours à `45 7` (heure UTC de `clevercloud/cron.json`), soit 9 h 45 à Paris en heure d'été et 8 h 45 en heure d'hiver. Ce
créneau laisse passer la synchronisation FFCAM (`3 7`, 9h03 à Paris) puis l'anonymisation des
comptes (`28 7`, 9h28 à Paris), pour ne traiter que des fiches à jour.

Comme les autres crons du dépôt, le script vérifie `DEPLOY_ENV` : seul `web-prod` (où
`DEPLOY_ENV=production`) exécute réellement la synchro. `web-staging` (`DEPLOY_ENV=staging`) ne
fait rien.

## Configuration

Dans `.env.local` (ou variables d'environnement Clever Cloud) :

```bash
MAILERLITE_API_KEY=your_api_key_here
MAILERLITE_WELCOME_GROUP_ID=159667990712813289   # groupe accueil-nouveau
MAILERLITE_RENEWAL_GROUP_ID=your_group_id_here    # groupe accueil-renouvellement
```

Sans `MAILERLITE_API_KEY` ni `MAILERLITE_RENEWAL_GROUP_ID`, la commande se désactive
silencieusement (message « MailerLite non configure sur cette instance ») : c'est le cas normal
pour Chambéry et Clermont, qui n'ont rien à configurer. Avec une clé mais un identifiant de groupe
manquant ou dupliqué, ou sans clé mais avec `MAILERLITE_RENEWAL_GROUP_ID` renseigné, elle alerte
(voir « Alerte de configuration »).

## Mise en production

L'ordre compte : les groupes doivent exister côté MailerLite avant que la commande ne tourne pour
de vrai.

1. **Côté MailerLite** :
   - renommer le groupe `adherent` en `accueil-nouveau` (sans effet technique, voir plus haut) ;
   - créer le groupe `accueil-renouvellement` et son automation « Renouvellement de licence »,
     déclencheur `subscriber_joins_group` ;
   - cocher `repeatable` sur les deux automations — sans ce réglage, le circuit ne rejouera pas en
     2027.
2. **Variables d'environnement** : `clever env set MAILERLITE_RENEWAL_GROUP_ID <id> --alias
   web-prod`. Rien à faire pour Chambéry ni Clermont.
3. **Déployer** et appliquer la migration (colonne `accueil_season`).
4. **Premier passage à blanc en production** : `bin/console mailerlite-accueil-sync` (sans
   `--execute`), vérifier que la répartition nouveaux/renouvellements est plausible.
5. **Premier envoi réel** : laisser le cron tourner un jour, puis vérifier dans MailerLite que les
   abonnés sont arrivés dans le bon groupe et que l'automation s'est déclenchée.
6. **Contrôle à J+7** : comparer le nombre d'entrées dans les deux groupes au nombre de licences
   prises sur la période.

Une mise en production différée ne fait perdre personne : la commande est pilotée par la date de
prise de licence (`join_date`) et idempotente, elle rattrapera tous les adhérents qui auront
renouvelé entre-temps.
