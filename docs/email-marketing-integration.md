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
`clevercloud/cron.json`, mais n'ont pas de compte MailerLite : la commande le détecte (aucun
identifiant de groupe configuré) et ne fait rien, sans faire échouer le cron.

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
| `--execute` | Effectue réellement les envois (sinon dry-run). Neutralisée hors production : `DEPLOY_ENV` doit valoir `production`, sinon la commande bascule en dry-run et l'indique. |
| `--force`   | Passe outre le plafond de volume (voir ci-dessous). |
| `--season`  | Force la saison traitée (année de septembre), utile pour rejouer une saison ou pour les tests. |
| `--now`     | Force la date de référence (tests uniquement). |

### Calcul de la saison

La saison sportive bascule le 1er septembre : une exécution le 31 août 2026 traite la saison 2025,
une exécution le 1er septembre 2026 traite la saison 2026. Ce calcul est dérivé de la date
courante, rien n'est à reconfigurer d'une année sur l'autre.

Le dispositif démarre à la saison 2026-2027 (`FIRST_SEASON = 2026`) : les adhérents que le circuit
a manqués depuis décembre 2025 ne sont pas rattrapés. Une exécution sur une saison antérieure à
2026 ne sélectionne personne, même avec `--force`.

### Sélection et répartition

Sont éligibles les licenciés annuels non supprimés, dont la licence a été prise pour la saison
traitée, non radiés, avec un email valide, pas encore traités pour cette saison
(`UserRepository::findForAccueilCircuit()`).

Parmi les candidats, une fiche créée pendant la saison (à partir du 1er septembre) va vers
`accueil-nouveau` ; une fiche antérieure va vers `accueil-renouvellement`. Avant chaque ajout à un
groupe, la commande retire l'adhérent du groupe visé : les automations MailerLite se déclenchent
sur « rejoint le groupe », un ajout sans retrait préalable serait sans effet pour un abonné déjà
présent.

### Plafond de volume

Au-delà de 800 candidats sur une seule exécution, la commande refuse d'envoyer (code de sortie 1)
et demande `--force` : ce volume ferait suspecter une erreur de sélection plutôt qu'un pic normal
d'inscriptions. Sur la saison 2025, le cumul de licences prises atteignait déjà 828 au 10
septembre — un déploiement tardif peut donc légitimement dépasser ce plafond dès la première
exécution ; la marche à suivre est alors de vérifier le dry-run puis de relancer une fois avec
`--execute --force`.

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

## Planification

Le cron `clevercloud/crons/mailerlite-accueil-sync.sh` appelle `bin/console mailerlite-accueil-sync
--execute` tous les jours à `45 7` (heure UTC de `clevercloud/cron.json`), soit 9h45 à Paris. Ce
créneau laisse passer la synchronisation FFCAM (`3 7`, 9h03 à Paris) puis l'anonymisation des
comptes (`28 7`, 9h28 à Paris), pour ne traiter que des fiches à jour.

Comme les autres crons du dépôt, le script vérifie `DEPLOY_ENV` : seul `web-prod` (où
`DEPLOY_ENV=production`) exécute réellement la synchro. `web-staging` (`DEPLOY_ENV=staging`) ne
fait rien, la commande y basculerait de toute façon en dry-run.

## Configuration

Dans `.env.local` (ou variables d'environnement Clever Cloud) :

```bash
MAILERLITE_API_KEY=your_api_key_here
MAILERLITE_WELCOME_GROUP_ID=159667990712813289   # groupe accueil-nouveau
MAILERLITE_RENEWAL_GROUP_ID=your_group_id_here    # groupe accueil-renouvellement
```

Si `MAILERLITE_WELCOME_GROUP_ID` ou `MAILERLITE_RENEWAL_GROUP_ID` n'est pas configuré, la commande
se désactive silencieusement (message « MailerLite non configure sur cette instance ») : c'est le
cas normal pour Chambéry et Clermont, qui n'ont rien à configurer.

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
