# Code source pour le site du Club Alpin Français de Lyon-Villeurbanne

[![Static Badge](https://img.shields.io/badge/Automatisation-github_actions-orange)](https://github.com/Club-Alpin-Lyon-Villeurbanne/caflyon/actions)
[![Clever Cloud](https://img.shields.io/badge/Hébergement-Clever_cloud-yellow)](https://console.clever-cloud.com/)
![PHP Version](https://img.shields.io/badge/PHP-8.2-blue?logo=php)
![Symfony](https://img.shields.io/badge/Symfony-6.4-6d6dff?logo=symfony)
![Database](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql)


Bienvenue sur le dépôt du code source de la plateforme en ligne du Club Alpin Français de Lyon-Villeurbanne.
Ce site est un portail dédié à notre communauté, offrant des fonctionnalités variées, de l'organisation d'événements à la gestion des adhésions et bien plus encore.
Le site a été développé en PHP par l'agence HereWeCom vers 2010, puis ils nous ont cédé le code. Un refactoring a ensuite intégré le framework Symfony.
Il utilise une base MySQL 5.7.
Le déploiement est hébergé sur Clever Cloud, avec un CI/CD via Github Actions.

## Organisation du projet

Nous utilisons [ClickUp](https://app.clickup.com/42653954/v/l/18np82-82) pour gérer les tâches de développement. Pour y accéder, envoyez une demande au [groupe informatique](mailto:numerique@clubalpinlyon.fr).

## Infrastructure

Le site est hébergé sur [Clever Cloud](https://www.clever-cloud.com/). L’infrastructure consiste en un serveur web et une base de données MySQL 5.7. Cette base est hébergée et managée par Clever Cloud, sans accès SSH. Les identifiants sont stockés directement dans l’interface de Clever Cloud ; pour y accéder, adressez-vous au groupe informatique.

## Cronjobs

Les cronjobs sont maintenant gérés via l'interface de Clever Cloud et non plus directement sur le serveur. Ils incluent des tâches comme :

- envoi de mails
- vérification de la validité des adhésions via des fichiers FFCAM
- sauvegarde des images
- rappels de validation des sorties (tâches nocturnes)
- renouvellement du certificat SSL

## Rôles

Le site comporte deux rôles principaux :

1. **Admin** : ce rôle dispose de tous les droits, y compris la possibilité de modifier les permissions importantes, comme les rôles de président ou de responsables de commission.
2. **Gestionnaire de contenu** : ce rôle permet de modifier les pages et les blocs de contenu du site sans disposer des droits d'administration complets.

## Local setup

#### Prérequis

- [Docker](https://docs.docker.com/engine/install/) & docker-compose
- Make (installé par défaut sur Mac et Linux ; disponible via [Chocolatey](https://community.chocolatey.org/packages/make) pour Windows)

#### Étapes

- `git clone git@github.com:Club-Alpin-Lyon-Villeurbanne/caflyon.git`
- `cd caflyon`
- `make init` : lance les conteneurs (site web, base de données, phpMyAdmin & mailcatcher)
- `make database-init` : initialise et hydrate la base de données

#### Résultat

- Accès au site : `http://127.0.0.1:8000/`
- PHPMyAdmin : `http://127.0.0.1:8080/`, accès : `root` / `test`
- Mailcatcher : `http://127.0.0.1:1080/`
- Compte admin par défaut : `test@clubalpinlyon.fr` / `test`

⚠️ Les tests et l'upload d'images sont encore en cours de configuration. 🚧

#### Troubleshooting

Après une migration vers un nouveau setup, exécutez `docker stop cafsite && docker rm cafsite` pour éviter les conflits d'images Docker.

##### Utilisateurs MacOS

Sur les ordinateurs avec une puce Apple Silicon, on rencontre l'erreur `no matching manifest for linux/arm64/v8 in the manifest list entries`. Pour la résoudre, ajoutez un fichier `docker-compose.override.yml` à la racine du projet avec le contenu suivant :

```yml
version: "3"
services:
  cafdb:
    platform: linux/amd64
```

##### Utilisateurs Windows

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

Des erreurs peuvent apparaître lors du lancement des conteneurs Docker avec `make init` :

- `permission denied while trying to connect to the Docker daemon socket` : ajoutez votre utilisateur dans le groupe `docker` : `$ sudo usermod -a -G docker $USER`, puis relancez WSL ([voir SO](https://stackoverflow.com/a/48450294)).
- Le conteneur `db_caflyon` peut ne pas démarrer. Dans ce cas, vérifiez les logs avec `$ docker compose logs cafdb`. Si l’erreur `Could not set file permission for ca-key.pem` apparaît, démarrez les conteneurs depuis Powershell (`> docker compose up`), retournez dans WSL, arrêtez-les (`$ make docker-stop`) puis relancez (`$ make init`) ([voir SO](https://stackoverflow.com/a/78768559)).
- Pour corriger l’erreur `--initialize specified but the data directory has files in it`, supprimez le contenu du dossier `./db`.

## Contribution au projet

Nous encourageons les contributions ! Que vous soyez un développeur expérimenté ou un débutant, votre participation est précieuse. Si vous êtes nouveau, consultez le backlog sur ClickUp pour trouver un ticket. Si vous souhaitez contribuer sur un sujet non présent dans ClickUp, contactez l’équipe informatique pour proposer votre idée, confirmer sa pertinence et éviter de travailler inutilement.

### Processus de contribution

1. **Forker le répertoire** : Forker le répertoire sur votre compte GitHub.
2. **Cloner le répertoire** : Clonez le répertoire forké sur votre machine locale pour y apporter des modifications.
3. **Création d'une nouvelle branche** : Créez une nouvelle branche sur votre clone, nommée en fonction de la fonctionnalité ou du bug sur lequel vous travaillez.
4. **Effectuez vos modifications** : Effectuez les modifications nécessaires sur cette branche en respectant les conventions de codage.
5. **Commit** : Une fois satisfait, faites un commit en décrivant clairement les modifications apportées.
6. **Push** : Faites un push de votre branche sur GitHub.
7. **Pull Request (PR)** : Créez une PR et décrivez-la en français. Pour toute modification visuelle, incluez une capture d’écran. Seule l'équipe informatique peut merger une PR.

Nous attendons avec impatience vos contributions et vous remercions pour votre temps et votre effort ! 🙏🏼

### FAQ

**Pourquoi le code n'est-il pas open source ?**  
Nous avons une réelle volonté d'ouvrir ce code, mais un audit SSI approfondi a révélé que le projet nécessite encore des corrections au niveau de la sécurité avant d'être partagé publiquement.
