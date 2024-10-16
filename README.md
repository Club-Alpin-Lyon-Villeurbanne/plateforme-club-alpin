# Code source pour le site du Club Alpin Francais de Lyon-Villeurbanne

[![CircleCI](https://dl.circleci.com/status-badge/img/gh/Club-Alpin-Lyon-Villeurbanne/caflyon/tree/main.svg?style=shield&circle-token=a61cbc12b55c1591fd843db8ac6a3726204562a9)](https://dl.circleci.com/status-badge/redirect/gh/Club-Alpin-Lyon-Villeurbanne/caflyon/tree/main)
[![CI/CD staging](https://github.com/Club-Alpin-Lyon-Villeurbanne/caflyon/actions/workflows/staging-deploy.yml/badge.svg)](https://github.com/Club-Alpin-Lyon-Villeurbanne/caflyon/actions/workflows/staging-deploy.yml)

Bienvenue sur le dépôt du code source de la plateforme en ligne du Club Alpin Français de Lyon-Villeurbanne.
Ce site est un portail dédié à notre communauté, offrant une multitude de fonctionnalités, de l'organisation d'événements à la gestion des adhésions et bien plus encore.
Le site a été développé en php par l'agence HereWeCom il y a quelques années (environ 2010) et ils nous ont ensuite donné le code.
Un refactoring important a été effectué pour y intégrer le framework symfony.
Il utilise une base MySQL 5.7.
Le tout est hébergé chez AWS et déploié automatiquement par Github Actions.

## organisation du projet

Nous utilisons [clickup](https://app.clickup.com/42653954/v/l/18np82-82) pour gérer les taches à développer. Pour y avoir accès, envoyer une demande au [groupe informatique](mailto:numerique@clubalpinlyon.fr).

## Infrastructure

Le site est hébergé chez [AWS](https://aws.amazon.com/fr/). L'infrastructure consiste en un serveur web classique (instance ec2), avec Apache, php et letsencrypt.
La DB est managée par [RDS](https://aws.amazon.com/fr/rds/). Il s'agit d'une DB Aurora avec Mysql comme moteur.
L'infrastructure de ce site est gérée par Terraform dans le repo [`infrastructure-website`](https://github.com/Club-Alpin-Lyon-Villeurbanne/infrastructure-website).

## Deployement

Le deployement se fait automatiquement par [Github Actions](https://github.com/Club-Alpin-Lyon-Villeurbanne/caflyon/actions).  
Pusher un commit (ou mergé une PR) sur la branche `main` lance le deployment [sur l'env de dev](https://www.clubalpinlyon.top).  
Un bouton dans github actions permet de déclencher le deployment [sur l'env de production](https://www.clubalpinlyon.fr).  
Github Actions va remplacer les credentials pour la DB par les vrais puis enverra en rsync les fichiers sur le serveur.  
Les secrets (mot de passe de db, mot de passe ftp, etc...) sont stockés en tant [que variable d'environment dans github actions](https://github.com/Club-Alpin-Lyon-Villeurbanne/caflyon/settings/secrets/actions).

## Cronjobs

Quelques cronjobs sont programmés pour effectuer certaines taches:

- envoi de mail
- fichier adherent: vérification des fichiers adhérents provenant de la FFCAM (validité des adhésions)
- sauvegarde des images
- rappels (chaque nuit, envoi des mails de validation des sorties)
- renouvellement du certificat SSL

Les cronjobs sont accessibles sur le serveur en utilisant la commande `sudo crontab -e`

## Base de données

La base de données est hébergée et gérée par AWS RDS. Elle se trouve dans un VPC privée, ce qui nécessite d'utiliser un tunnel SSH pour y accéder.
Le hostname est: `caflv-production-aurora-mysql.cluster-cw75ek4t1pty.eu-west-3.rds.amazonaws.com`
Le nom de la base de prod est `caflvproduction` et celle de dev est `caflvdev`.
Les identifiants sont dans bitwarden. Demandez un accès à Nicolas ou Romain.

## Recaptcha

Recaptcha est utilisé pour s'assurer que l'utilisateur est bien humain. Ce système est transparent pour l'utilisateur final.
La config de recaptcha (nom de domaine) se fait sur la console de recaptcha en utilisant le compte `clubcaflv@gmail.com`.

## Matrice des droits des utilisateurs

Un espace admin permet d'administrer différentes aspects du site:

- [matrice des droits des utilisateurs](matrice-des-droits.png)
- assignation des droits "responsables de commission" et président
- modification des partenaires
- modification des meta données du site
  https://www.clubalpinlyon.fr/admin/
  Les identifiants sont stockés sur notre compte bitwarden.

## local setup

#### Requirements

- [Docker](https://docs.docker.com/engine/install/) & docker-compose
- Make (disponible par défaut sous mac et Linux. [Installable](https://community.chocolatey.org/packages/make) via chocolatey sous Windows)

#### Steps

- `git clone git@github.com:Club-Alpin-Lyon-Villeurbanne/caflyon.git`
- `cd caflyon`
- `make init`: ceci va lancer les containers (site web, database, phpmyadmin & mailcatcher)
- `make database-init`: ceci va initialiser et hydrater la base de données

#### Résultat

- vous avez désormais accès au site sur `http://127.0.0.1:8000/`
- PHPMyAdmin sur `http://127.0.0.1:8080/`, les accès à PHPMyAdmin sont `root` - `test`
- Mailcatcher sur `http://127.0.0.1:1080/` pour visualiser les mails envoyés
- Un compte admin a été créé automatiquement sur le site avec les identifiants suivants: "test@clubalpinlyon.fr" et mot de passe "test"
- Les logs de symfony sont dispo avec un `tail -f var/log/dev.log`

⚠️ le setup pour lancer les tests ne fonctionne pas encore, il est en cours de refacto 🚧
⚠️ l'upload des images avec ce setup ne fonctionne pas encore. Nous y travaillons. 🚧

#### Troubleshooting

Après la migration vers le nouveau setup, il est possible que le nouveau build ne supprime pas les anciennes images, il faut donc faire un:
`docker stop cafsite && docker rm cafsite`

##### Utilisateurs MacOs

Sur les ordinateurs avec une puce Apple Silicon, on rencontre l'erreur `no matching manifest for linux/arm64/v8 in the manifest list entries`

Pour la résoudre, il suffit de rajouter un fichier `docker-compose.override.yml` à la racine du projet avec le contenu suivant :

```yml
version: "3"
services:
  cafdb:
    platform: linux/amd64
```

##### Utilisateurs Windows

Après avoir installé [WSL 2](https://learn.microsoft.com/en-us/windows/wsl/install) et [Docker Desktop](https://docs.docker.com/desktop/install/windows-install/), suivre les instructions ici pour activer le backend Docker WSL2: https://docs.docker.com/desktop/wsl/.

Pour vérifier l'installation de Docker, lancer ces commandes depuis Powershell:
```
PS > wsl --list --verbose
  NAME              STATE           VERSION
* Ubuntu-X.X        Running         2
  docker-desktop    Running         2
PS > wsl
$ docker --version
Docker version X.X.X, build xxxxxxx
```

Des erreurs peuvent apparaitre au moment de lancer les containers Docker avec `make init`:
- `permission denied while trying to connect to the Docker daemon socket`: Il faut ajouter son utilisateur dans le groupe `docker`: `$ sudo usermod -a -G docker $USER`, puis quitter et revenir dans WSL ([voir SO](https://stackoverflow.com/a/48450294)).
- Le container `db_caflyon` (database) peut ne pas démarrer: vérifier qu'il tourne avec `$ docker compose ps` (son statut doit etre `Up X minutes`).
Si il ne tourne pas: vérifier les logs avec `$ docker compose logs cafdb`, vous devriez avoir l'erreur `Could not set file permission for ca-key.pem` au début du log. Dans ce cas, sortir de WSL et lancer les containers depuis Powershell (`> docker compose up`), puis retourner dans WSL, les arrêter (`$ make docker-stop`) et relancer l'initialisation (`$ make init`) ([voir SO](https://stackoverflow.com/a/78768559)).
Si non résolue, cette erreur pourra se manifester à l'étape `make database-init` avec l'erreur `getaddrinfo for db_caflyon failed: Name or service not known`.
- Pour corriger l'erreur `--initialize specified but the data directory has files in it` dans les logs du container `db_caflyon`: supprimer le contenu du dossier `./db`.

#### Fixtures

Actuellement, seul un utilisateur admin et quelques articles sont créés automatiquement. Afin de pouvoir rapidement tester plus de cas d'utilisation, notamment sur les sorties, ca serait intéressant de créer plusieurs utilisateurs (notamment encadrant) et plusieurs sorties (une nouvellement créée, une nouvellement validée et une nouvellement publieé).

## 👋 Contribution au projet

Nous encourageons vivement les contributions à notre projet. Que vous soyez un développeur expérimenté ou un débutant passionné, votre participation est précieuse pour nous. Voici quelques directives pour faciliter le processus de contribution.

### Processus de contribution

1. **Forker le répertoire** : Forker le répertoire sur votre compte github.
2. **Clone du répertoire** : Clonez le répertoire forké sur votre machine locale pour y apporter des modifications.
3. **Création d'une nouvelle branche** : Créez une nouvelle branche sur votre clone. Nommez-la de manière appropriée en fonction de la fonctionnalité ou de la correction de bug sur laquelle vous travaillez. ℹ️ Notez que notre branche `main` est la branche principale de développement, elle est protégée et ne doit pas être utilisée pour le développement direct. Tout push sur cette branche déclenchera un déploiement sur notre environnement de test [https://www.clubalpinlyon.top](https://www.clubalpinlyon.top).
4. **Apportez vos modifications** : Effectuez les modifications nécessaires sur cette branche. Assurez-vous de suivre les conventions de codage du projet.
5. **Commit de vos modifications** : Une fois que vous êtes satisfait de vos modifications, faites un commit en décrivant clairement les modifications que vous avez apportées.
6. **Push vers le répertoire** : Faites un push de votre branche vers le répertoire sur GitHub. Un processus de CI/CD sera enclenché une fois que la branche est pushée.
7. **Créez une Pull Request (PR)** : Rendez-vous sur la page du répertoire principal du projet sur GitHub. Vous devriez voir une notification vous invitant à créer une PR à partir de votre branche récemment mise à jour. Cliquez sur le bouton "Compare & pull request" et remplissez les détails nécessaires. Lorsqu'une PR est mergée, le processus de CI/CD s'enclenche également pour assurer la qualité du code et l'intégrité de l'application.

### Code Reviews et Pull Requests

Nous utilisons des Pull Requests (PR) pour effectuer des revues de code. Une fois votre PR créée, elle sera examinée par un ou plusieurs membres de l'équipe du projet. Ce processus permet d'assurer que le code contribué est de haute qualité et qu'il ne brise rien dans le code existant.

Veuillez noter que votre PR peut recevoir des commentaires demandant des modifications ou des améliorations. Ne vous inquiétez pas, c'est un aspect normal du processus de revue de code. Cela permet de garantir que le code final est robuste et efficace.

Nous sommes impatients de voir vos contributions et nous vous remercions pour votre temps et votre effort ! 🙏🏼
