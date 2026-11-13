# Guide d'Installation

## Prérequis

- [Docker](https://docs.docker.com/engine/install/) & docker-compose
- Make (installé par défaut sur Mac et Linux ; disponible via [Chocolatey](https://community.chocolatey.org/packages/make) pour Windows)
- Si vous avez d'autres projets utilisant les mêmes ports, pensez à les arrêter avant de lancer le projet CAF ;) (ou changez les ports)

## Installation

1. **Fork du projet** :
   - Allez sur [https://github.com/Club-Alpin-Lyon-Villeurbanne/plateforme-club-alpin](https://github.com/Club-Alpin-Lyon-Villeurbanne/plateforme-club-alpin)
   - Cliquez sur le bouton "Fork" en haut à droite
   - Clonez votre fork localement :
   ```bash
   git clone git@github.com:VOTRE-USERNAME/plateforme-club-alpin.git
   cd plateforme-club-alpin
   ```
   - Ajoutez le repo original comme upstream :
   ```bash
   git remote add upstream git@github.com:Club-Alpin-Lyon-Villeurbanne/plateforme-club-alpin.git
   ```

2. Initialiser l'environnement :
```bash
make init
# Puis choisir UNE des deux cibles ci-dessous selon le besoin :
make database-init        # seed minimal (12 communes), démarrage rapide
# ou
make database-bootstrap   # full import (~39 000 communes, ~2 min réseau)
```

### Initialisation de la base de données

Le projet propose deux cibles selon ton besoin :

| Cible | Communes en base | Quand l'utiliser |
|---|---|---|
| `make database-init` | Seed minimal (12 communes montagne) | Démarrage rapide, CI |
| `make database-bootstrap` | ~39 000 communes (API La Poste, ~2 min réseau) | Install initiale, autocomplete complet France |

Le seed minimal inclut Lyon (1er & 3e), le massif du Mont-Blanc (Chamonix, Saint-Gervais, Annecy), les Écrins (Saint-Christophe-en-Oisans avec La Bérarde, Bourg-d'Oisans, Pelvoux, Briançon), la Vanoise (Pralognan, Bonneval-sur-Arc) et le Vercors (Villard-de-Lans) — suffisant pour tester le calcul du bilan carbone des sorties.

Pour rafraîchir uniquement les communes sans toucher au reste : `make database-import-communes` (full) ou `make database-seed-communes` (minimal).

## Accès

- Site web : `http://127.0.0.1:8000/`
- Compte admin : `admin@test-clubalpinlyon.fr` / `test`
- PHPMyAdmin : `http://127.0.0.1:8080/` (accès : `root` / `test`)
- Mailcatcher : `http://127.0.0.1:8025/`

Pour consommer les mails : `make consume-mails`

⚠️ L'upload d'images ne fonctionne pas dans un environnement dockerisé. 🚧

## Tests

```bash
make tests clear=1                          # la première fois : crée la base de test (caf_test)
make tests                                  # toute la suite
make tests path=tests/Service/FooTest.php   # un seul fichier
```

Chaque test tourne dans une transaction annulée à la fin (`dama/doctrine-test-bundle`) : la base de test ne se remplit pas d'un run à l'autre. `clear=1` ne sert qu'à la recréer :

- la première fois ;
- après avoir récupéré une nouvelle migration ou une modification des fixtures ;
- si des tests lancés depuis une branche plus ancienne, sans cette transaction, y ont laissé des données.

La connexion mysqli du legacy (`MysqliHandler`) est une connexion à part, hors de cette transaction :

- une page legacy ne voit pas les données créées par Doctrine pendant le test, qui ne sont pas encore validées ;
- ce qu'elle écrit reste en base.

Pour un test qui en a besoin, l'attribut `#[SkipDatabaseRollback]` (`DAMA\DoctrineTestBundle\PHPUnit\SkipDatabaseRollback`), sur la méthode ou la classe, le fait tourner hors transaction. Ses données restent alors en base : à supprimer dans son `tearDown()`.

## Dépannage

### Conflits d'images Docker

Après une migration vers un nouveau setup, exécutez :
```bash
docker stop www_pca && docker rm www_pca
```
pour éviter les conflits d'images Docker.

### Utilisateurs Windows

Après avoir installé [WSL 2](https://learn.microsoft.com/en-us/windows/wsl/install) et [Docker Desktop](https://docs.docker.com/desktop/install/windows-install), suivez les instructions pour activer le backend Docker WSL2 : https://docs.docker.com/desktop/wsl/.

Pour vérifier l'installation de Docker, lancez ces commandes depuis Powershell :
```
PS > wsl --list --verbose
  NAME              STATE           VERSION
* Ubuntu-X.X        Running         2
  docker-desktop    Running         2
PS > wsl
$ docker --version
Docker version X.X.X, build xxxxxxx
```

#### Erreurs courantes sous Windows

1. `permission denied while trying to connect to the Docker daemon socket`
   - Solution : Ajoutez votre utilisateur dans le groupe `docker` : `$ sudo usermod -a -G docker $USER`
   - Puis relancez WSL ([voir SO](https://stackoverflow.com/a/48450294))

2. Le conteneur `db_pca` ne démarre pas
   - Vérifiez les logs avec `$ docker compose logs cafdb`
   - Si l'erreur `Could not set file permission for ca-key.pem` apparaît :
     1. Démarrez les conteneurs depuis Powershell (`> docker compose up`)
     2. Retournez dans WSL
     3. Arrêtez-les (`$ make docker-stop`)
     4. Relancez (`$ make init`)
     ([voir SO](https://stackoverflow.com/a/78768559))

3. Erreur `--initialize specified but the data directory has files in it`
   - Solution : Supprimez le contenu du dossier `./db` 