# Code source pour le site du Club Alpin Francais de Lyon-Villeurbanne

[![CircleCI](https://circleci.com/gh/Club-Alpin-Lyon-Villeurbanne/caflyon/tree/main.svg?style=shield&circle-token=843b806ceb348fde38d421c902bcfb734ed58668)](https://circleci.com/gh/Club-Alpin-Lyon-Villeurbanne/caflyon/tree/main)

Ce repo contient le code source du site https://www.clubalpinlyon.fr/.
Le site a été développé en php par l'agence HereWeCom il y a quelques années (environ 2010) et ils nous ont ensuite donné le code.
Un refactoring important a été effectué pour y intégrer le framework symfony.
Il utilise une base mariadb.

# organisation du projet

Nous utilisons [clickup](https://app.clickup.com/42653954/v/l/18np82-82) pour gérer les taches à développer. Pour y avoir accès, envoyer une demande au [groupe informatique](mailto:numerique@clubalpinlyon.fr).

# Infrastructure

Le site est hébergé chez [AWS](https://aws.amazon.com/fr/). L'infrastructure consiste en un serveur web classique (instance ec2), avec Apache, php et letsencrypt.
La DB est managée par [RDS](https://aws.amazon.com/fr/rds/). Il s'agit d'une DB Aurora avec mariadb comme moteur.
L'infrastructure de ce site est gérée par Terraform dans le repo [`infrastructure-website`](https://github.com/Club-Alpin-Lyon-Villeurbanne/infrastructure-website).

# Deployement


Le deployement se fait automatiquement par [circleci](https://circleci.com/gh/Club-Alpin-Lyon-Villeurbanne/caflyon/tree/main).  
Pusher un commit (ou mergé une PR) sur la branche `main` lancer le deployment [sur l'env de dev](https://www.clubalpinlyon.top).  
Pusher un commit (ou mergé une PR) sur la branche `production` lancer le deployment [sur l'env de production](https://www.clubalpinlyon.fr).  
CircleCI va remplacer les credentials pour la DB par les vrais puis enverra en FTP les fichiers sur le server.  
Les secrets (mot de passe de db, mot de passe ftp, etc...) sont stockés en tant [que variable d'environment dans circleci](https://app.circleci.com/settings/project/github/Club-Alpin-Lyon-Villeurbanne/caflyon/environment-variables).  

# Cronjobs
Quelques cronjobs sont programmés pour effectuer certaines taches:
- envoi de mail
- fichier adherent
- sauvegarde des images
- rappels
- renouvellement du certificat SSL

# Base de données

La base de données est hébergée et gérée par AWS RDS. Elle se trouve dans un VPC privée, ce qui nécessite d'utiliser un tunnel SSH pour y accéder.
Le hostname est: `caflv-production-aurora-mysql.cluster-cw75ek4t1pty.eu-west-3.rds.amazonaws.com`
Le nom de la base de prod est `caflvproduction`
Le nom d'utilisateur est `demander à Nicolas`
Idem pour le mot de passe :)

# local setup

⚠️ Le local setup semble ne pas fonctionner.

 - cp legacy/config/config.php.tpl legacy/config/config.php
 - cp legacy/config/params.php.tpl legacy/config/params.php
 - installer Docker
 - executer Docker host manager (https://github.com/iamluc/docker-hostmanager)
 - executer `make build up setup-db migrate`
 - vous avez désormais accès au site sur `http://cafsite.caf/`, PHPMyAdmin sur `http://phpmyadmin.caf/`, les accès à PHPMyAdmin sont `root` - `test`
 - L'accès à l'UI se fait avec "contact@herewecom.com" et mot de passe "test"
 - Les tests se lancent apres avoir setup l'env de test `make setup-db migrate env=test` puis `make phpunit`