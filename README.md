# Code source pour l'appli web du Club Alpin Français de Lyon-Villeurbanne

[![CI/CD](https://img.shields.io/badge/Automatisation-github_actions-orange)](https://github.com/Club-Alpin-Lyon-Villeurbanne/caflyon/actions)
[![Clever Cloud](https://img.shields.io/badge/Hébergement-Clever_cloud-yellow)](https://console.clever-cloud.com/)
![PHP Version](https://img.shields.io/badge/PHP-8.2-blue?logo=php)
![Symfony](https://img.shields.io/badge/Symfony-6.4-6d6dff?logo=symfony)
![Database](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql)

## Documentation

La documentation complète du projet est disponible dans le répertoire [docs/](docs/README.md).

### Liens rapides

- [Guide d'installation](docs/getting-started/installation.md)
- [Guide de contribution](docs/development/contribution.md)
- [Environnement de test](https://www.clubalpinlyon.top)
- [Production](https://www.clubalpinlyon.fr)

### Outils

- 📋 Tickets : [Clickup](https://app.clickup.com/)
- 🐛 Report de bugs : [sentry](https://club-alpin-lyon.sentry.io/issues/?project=6021900&statsPeriod=14d)
- ⚙️ Build : [Github Actions](https://github.com/Club-Alpin-Lyon-Villeurbanne/caflyon/actions)

Bienvenue sur le dépôt du code source de l'application web utilisée du Club Alpin Français de Lyon-Villeurbanne pour la gestion des adhérents, du contenu du site (pages & articles), de la gestion des sorties et des participants à ces sorties.
Le développement de ce site en php a commencé vers 2010. Vers 2019, un groupe de bénévole a entrepris de réduire la dette technique afin de faciliter la maintenance et l'évolution de cet outil vital pour le Club Alpin de Lyon.
Cette phase d'amélioration a permis de migrer vers Symfony, d'améliorer l'infrastructure, la sécurité et de rajouter des fonctionnalités.

## 🛠️ Outils & Environnements
### Outils
- 📋 Tickets : [Clickup](https://app.clickup.com/)
- 🐛 Report de bugs : [sentry](https://club-alpin-lyon.sentry.io/issues/?project=6021900&statsPeriod=14d)
- ⚙️ Build : [Github Actions](https://github.com/Club-Alpin-Lyon-Villeurbanne/caflyon/actions)

### Environnements
- 🧪 Test / staging : [www.clubalpinlyon.top](https://www.clubalpinlyon.top)
- 🚀 Production : [www.clubalpinlyon.fr](https://www.clubalpinlyon.fr)

## Infrastructure

Nous disposons de deux environnements hébergés sur [Clever Cloud](https://www.clever-cloud.com/) :

La [staging](https://www.clubalpinlyon.top) pour réaliser nos tests une fois les développements intégrés
La [production](https://www.clubalpinlyon.fr), l'environnement final de nos utilisateurs.
Un déploiement continu via une Github Action est en place pour la staging, ce qui signifie que chaque Pull Request mergée devient rapidement disponible à tester sur cet environnement.

Pour la production, les déploiements se font manuellement par une Github Action.

L'infrastructure consiste en un serveur web et une base de données MySQL 8.0. Cette base est hébergée et managée par Clever Cloud.

Les variables d'environnement sont gérées dans la console de Clever Cloud.

## Cronjobs

Les tâches récurrentes sont gérées directement depuis le code en s'appuyant sur le module de cronjobs fourni par Clever Cloud.
Elles sont stockées dans le répertoire `clevercloud/crons`. Il faut se référer à cette documentation si besoin : https://developers.clever-cloud.com/doc/administrate/cron/

- vérification de la validité des adhésions via des fichiers FFCAM
- sauvegarde des images
- rappels de validation des sorties

## Installation de l'environnement local

#### Prérequis

- [Docker](https://docs.docker.com/engine/install/) & docker-compose
- Make (installé par défaut sur Mac et Linux ; disponible via [Chocolatey](https://community.chocolatey.org/packages/make) pour Windows)
- Si vous avez d'autres projets utilisant les mêmes ports, pensez à les arrêter avant de lancer le projet CAF ;) (ou changez les ports)

#### Étapes

- `git clone git@github.com:Club-Alpin-Lyon-Villeurbanne/caflyon.git`
- `cd caflyon`
- `make init` : lance les conteneurs (site web, base de données, phpMyAdmin & mailcatcher)
- `make database-init` : initialise et hydrate la base de données

- Accès au site : `http://127.0.0.1:8000/`
- Compte admin par défaut : `test@clubalpinlyon.fr` / `test`
- PHPMyAdmin : `http://127.0.0.1:8080/`, accès : `root` / `test`
- Mailcatcher : `http://127.0.0.1:8025/` ; lancer cette commande pour "consommer" les mails : `make consume-mails` (ou `docker compose exec cafsite bin/console messenger:consume mails --limit=50 --quiet --no-interaction`)

⚠️ L'upload d'images ne fonctionne pas dans un environnement dockerisé. 🚧

#### Troubleshooting

Après une migration vers un nouveau setup, exécutez `docker stop www_caflyon && docker rm www_caflyon` pour éviter les conflits d'images Docker.

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
- Le conteneur `db_caflyon` peut ne pas démarrer. Dans ce cas, vérifiez les logs avec `$ docker compose logs cafdb`. Si l'erreur `Could not set file permission for ca-key.pem` apparaît, démarrez les conteneurs depuis Powershell (`> docker compose up`), retournez dans WSL, arrêtez-les (`$ make docker-stop`) puis relancez (`$ make init`) ([voir SO](https://stackoverflow.com/a/78768559)).
- Pour corriger l'erreur `--initialize specified but the data directory has files in it`, supprimez le contenu du dossier `./db`.

## Contribution au projet

Nous encourageons les contributions ! Que vous soyez un développeur expérimenté ou un débutant, votre participation est précieuse. Si vous êtes nouveau, consultez le backlog sur ClickUp pour trouver un ticket, en priorité dans "PRET POUR DEV 🏁". Si vous souhaitez contribuer sur un sujet non présent dans ClickUp, contactez l'équipe informatique pour proposer votre idée, confirmer sa pertinence et éviter de travailler inutilement.

### Processus de contribution

1. **Cloner le répertoire** : Clonez le répertoire sur votre machine locale pour y apporter des modifications.
2. **Création d'une nouvelle branche** : Créez une nouvelle branche, nommée en fonction de la fonctionnalité ou du bug sur lequel vous travaillez.
3. **Effectuez vos modifications** : Passez le ticket en "EN COURS". Effectuez les modifications nécessaires sur cette branche en respectant les conventions de codage. ⚠️ Avant de contribuer au code, soyez sûr que le changement que vous souhaitez apporter est dans notre backlog sur ClickUp ("PRET POUR DEV 🏁") ou que vous avez bien validé cette idée avec l'équipe informatique.
4. **Commit** : Une fois satisfait, faites un commit en décrivant clairement les modifications apportées.
5. **Push** : Faites un push de votre branche sur GitHub.
6. **Pull Request (PR)** : Créez une PR et décrivez-la en français. Pour toute modification visuelle, incluez une capture d’écran. Seule l'équipe informatique peut merger une PR. Passez le ticket en "EN REVIEW PAR DEV" et ajouter le nom de la PR en commentaire. 

Nous attendons avec impatience vos contributions et vous remercions pour votre temps et votre effort ! 🙏🏼

## Rôles

Le site comporte deux rôles annexes :

1. **Admin** : ce rôle dispose de tous les droits, y compris la possibilité de modifier les permissions importantes, comme les rôles de président ou de responsables de commission.
2. **Gestionnaire de contenu** : ce rôle permet de modifier les pages et les blocs de contenu du site sans disposer des droits d'administration complets.

On y accède via l'url https://www.clubalpinlyon.fr/admin/. Les identifiants en local sont : `admin` / `admin` et `admin_contenu` / `contenu`.

### FAQ

**Pourquoi le code n'est-il pas open source ?**  
Nous avons une réelle volonté d'ouvrir ce code, mais un audit SSI approfondi a révélé que le projet nécessite encore des corrections au niveau de la sécurité avant d'être partagé publiquement.


### Synchronisation des nouveaux adhérents

Un Cronjob est en place pour synchroniser les nouveaux adhérents avec le système de la FFCAM.
La FFCAM upload un fichier CSV avec les nouveaux adhérents chaque nuit.
Notre appli va parser ce fichier et créer les adhérents dans la base de données.
Si l'adhérent existe déjà (même nom, même prénom, même date de naissance), son compte existant sera mis à jour avec les nouvelles informations.
Si l'adhérent n'existe pas, il sera créé et il pourra accéder au site.


### Notes de frais
L'application permet de gérer les notes de frais des sorties.
Cela consiste en 2 parties : 
#### la soumission des notes de frais par les encadrants (partie soumission)
La première partie est une interface VueJs disponible dans la page de chaque sortie.
Un template twig pour envoyer un récap de la demande de note de frais à l'encadrant.
Une API pour récupérer les infos de la note de frais pour l'utiliser dans la partie admin.

La config des taux d'indemnités kilométriques est faite dans le fichier `assets/expense-report-form/config/expense-report.json` pour la partie `client` et également dans `config/services.yaml` pour l'injection dans le container coté `server`.
⚠️ en cas de modif des taux, il faut bien penser à mettre à jour les deux endroits.

#### la vérification et validation des notes de frais par la comptabilité (partie admin).

La deuxième partie, vérification des notes de frais, est une [interface distincte développée en nextjs](https://github.com/Club-Alpin-Lyon-Villeurbanne/compta-club).
Les taux d'indemnités kilométriques sont également configurés dans le fichier https://github.com/Club-Alpin-Lyon-Villeurbanne/compta-club/blob/main/app/config.ts.
