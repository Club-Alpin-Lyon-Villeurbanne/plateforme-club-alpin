# Code source pour le site du Club Alpin Francais de Lyon-Villeurbanne

[![CircleCI](https://circleci.com/gh/Club-Alpin-Lyon-Villeurbanne/caflyon/tree/main.svg?style=shield&circle-token=843b806ceb348fde38d421c902bcfb734ed58668)](https://circleci.com/gh/Club-Alpin-Lyon-Villeurbanne/caflyon/tree/main)

Ce repo contient le code source du site https://www.clubalpinlyon.fr/.
Le site a été développé en php par l'agence HereWeCom il y a quelques années (environ 2010) et ils nous ont ensuite donné le code.
C'est du php sans framework particulier, avec une structure relativement complexe.
Il utilise une base mariadb.

# Deployement

Le deployement se fait automatiquement par [circleci](https://circleci.com/gh/Club-Alpin-Lyon-Villeurbanne/caflyon/tree/main).  
Pusher un commit (ou mergé une PR) sur `main` lancer le deployment [sur l'env de dev](https://test.clubalpinlyon.fr).  
Pusher les changements sur la branche `production` fera la meme chose [sur le site final](https://www.clubalpinlyon.fr).  
CircleCI va remplacer les credentials pour la DB par les vrais puis enverra en FTP les fichiers sur le server.
Les secrets (mot de passe de db, mot de passe ftp, etc...) sont stockés en tant [que variable d'environment dans circleci](https://app.circleci.com/settings/project/github/Club-Alpin-Lyon-Villeurbanne/caflyon/environment-variables).  

## Environments

Un environment de dev est accessible sur https://test.clubalpinlyon.fr.  
Le code de cet environment se trouve dans `/home/kahe0589/test.clubalpinlyon.fr` et la db utilisée est `kahe0589_dev`.

## local setup

 - cp legacy/config/config.php.tpl legacy/config/config.php
 - cp legacy/config/params.php.tpl legacy/config/params.php
 - installer Docker
 - executer Docker host manager (https://github.com/iamluc/docker-hostmanager)
 - executer `make build up setup-db migrate`
 - vous avez désormais accès au site sur `http://cafsite.caf/`, PHPMyAdmin sur `http://phpmyadmin.caf/`, les accès à PHPMyAdmin sont `root` - `test`
 - L'accès à l'UI se fait avec "contact@herewecom.com" et mot de passe "test"
 - Les tests se lancent apres avoir setup l'env de test `make setup-db migrate env=test` puis `make phpunit`
 
Todo
 - corriger les erreurs 404: https://error404.atomseo.com/SeoCheck/Report/www.clubalpinlyon.fr/2021-09-10/free?from=
