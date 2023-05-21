# Code source pour le site du Club Alpin Francais de Lyon-Villeurbanne

[![CircleCI](https://dl.circleci.com/status-badge/img/gh/Club-Alpin-Lyon-Villeurbanne/caflyon/tree/main.svg?style=shield&circle-token=a61cbc12b55c1591fd843db8ac6a3726204562a9)](https://dl.circleci.com/status-badge/redirect/gh/Club-Alpin-Lyon-Villeurbanne/caflyon/tree/main)

Bienvenue sur le dépôt du code source de la plateforme en ligne du Club Alpin Français de Lyon-Villeurbanne.
Ce site est un portail dédié à notre communauté, offrant une multitude de fonctionnalités, de l'organisation d'événements à la gestion des adhésions et bien plus encore.
Le site a été développé en php par l'agence HereWeCom il y a quelques années (environ 2010) et ils nous ont ensuite donné le code.
Un refactoring important a été effectué pour y intégrer le framework symfony.
Il utilise une base mariadb.

## organisation du projet

Nous utilisons [clickup](https://app.clickup.com/42653954/v/l/18np82-82) pour gérer les taches à développer. Pour y avoir accès, envoyer une demande au [groupe informatique](mailto:numerique@clubalpinlyon.fr).

## Infrastructure

Le site est hébergé chez [AWS](https://aws.amazon.com/fr/). L'infrastructure consiste en un serveur web classique (instance ec2), avec Apache, php et letsencrypt.
La DB est managée par [RDS](https://aws.amazon.com/fr/rds/). Il s'agit d'une DB Aurora avec mariadb comme moteur.
L'infrastructure de ce site est gérée par Terraform dans le repo [`infrastructure-website`](https://github.com/Club-Alpin-Lyon-Villeurbanne/infrastructure-website).

## Deployement

Le deployement se fait automatiquement par [circleci](https://circleci.com/gh/Club-Alpin-Lyon-Villeurbanne/caflyon/tree/main).  
Pusher un commit (ou mergé une PR) sur la branche `main` lancer le deployment [sur l'env de dev](https://www.clubalpinlyon.top).  
Pusher un commit (ou mergé une PR) sur la branche `production` lancer le deployment [sur l'env de production](https://www.clubalpinlyon.fr).  
CircleCI va remplacer les credentials pour la DB par les vrais puis enverra en FTP les fichiers sur le server.  
Les secrets (mot de passe de db, mot de passe ftp, etc...) sont stockés en tant [que variable d'environment dans circleci](https://app.circleci.com/settings/project/github/Club-Alpin-Lyon-Villeurbanne/caflyon/environment-variables).  

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
Le nom de la base de prod est `caflvproduction`
Le nom d'utilisateur est `demander à Nicolas`
Idem pour le mot de passe :)

## Recaptcha

Recaptcha est utilisé pour s'assurer que l'utilisateur est bien humain. Ce système est transparent pour l'utilisateur final.
La config de recaptch (nom de domaine) se fait sur le site de recaptcha en utilisant le compte `clubcaflv@gmail.com`.

## Matrice des droits des utilisateurs

Un espace admin permet d'administrer différentes aspects du site.
https://www.clubalpinlyon.fr/admin/
Les identifiants sont stockés sur notre compte bitwarden.

[Matrice des droits des utilisateurs](matrice-des-droits.png)

## local setup

⚠️ Le local setup semble ne pas fonctionner.

 - cp legacy/config/config.php.tpl legacy/config/config.php
 - cp legacy/config/params.php.tpl legacy/config/params.php
 - installer Docker
 - executer Docker host manager (https://github.com/iamluc/docker-hostmanager)
 - executer `make build up setup-db migrate`
 - vous avez désormais accès au site sur `http://cafsite.caf/`, PHPMyAdmin sur `http://phpmyadmin.caf/`, les accès à PHPMyAdmin sont `root` - `test`
 - L'accès à l'UI se fait avec "contact@herewecom.com" et mot de passe "test"
 - Les tests se lancent apres avoir setup l'env de test `make setup-db migrate env=test` puis `make phpunit`


## 👋 Contribution au projet

Nous encourageons vivement les contributions à notre projet. Que vous soyez un développeur expérimenté ou un débutant passionné, votre participation est précieuse pour nous. Voici quelques directives pour faciliter le processus de contribution.

### Processus de contribution

1. **Clone du répertoire** : Clonez le répertoire sur votre machine locale pour y apporter des modifications.

2. **Création d'une nouvelle branche** : Créez une nouvelle branche sur votre clone. Nommez-la de manière appropriée en fonction de la fonctionnalité ou de la correction de bug sur laquelle vous travaillez.  
ℹ️ Notez que notre branche `main` est la branche principale de développement, elle est protégée et ne doit pas être utilisée pour le développement direct. Tout push sur cette branche déclenchera un déploiement sur notre environnement de test [https://www.clubalpinlyon.top](https://www.clubalpinlyon.top).

3. **Apportez vos modifications** : Effectuez les modifications nécessaires sur cette branche. Assurez-vous de suivre les conventions de codage du projet.

4. **Commit de vos modifications** : Une fois que vous êtes satisfait de vos modifications, faites un commit en décrivant clairement les modifications que vous avez apportées.

5. **Push vers le répertoire** : Faites un push de votre branche vers le répertoire sur GitHub. Un processus de CI/CD sera enclenché une fois que la branche est pushée.

6. **Créez une Pull Request (PR)** : Rendez-vous sur la page du répertoire principal du projet sur GitHub. Vous devriez voir une notification vous invitant à créer une PR à partir de votre branche récemment mise à jour. Cliquez sur le bouton "Compare & pull request" et remplissez les détails nécessaires. Lorsqu'une PR est mergée, le processus de CI/CD s'enclenche également pour assurer la qualité du code et l'intégrité de l'application.

### Code Reviews et Pull Requests

Nous utilisons des Pull Requests (PR) pour effectuer des revues de code. Une fois votre PR créée, elle sera examinée par un ou plusieurs membres de l'équipe du projet. Ce processus permet d'assurer que le code contribué est de haute qualité et qu'il ne brise rien dans le code existant.

Veuillez noter que votre PR peut recevoir des commentaires demandant des modifications ou des améliorations. Ne vous inquiétez pas, c'est un aspect normal du processus de revue de code. Cela permet de garantir que le code final est robuste et efficace.

Nous sommes impatients de voir vos contributions et nous vous remercions pour votre temps et votre effort ! 🙏🏼
