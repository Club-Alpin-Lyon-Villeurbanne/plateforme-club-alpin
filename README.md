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
Le code de cet environment se trouve dans `/home/kahe0589/test` et la db utilisée est `kahe0589_dev`.

## local setup

- ajouter une entrée dans votre fichier `/etc/hosts`. Par ex: dev.clubalpinlyon.fr
- créer un dossier `dev.clubalpinlyon.fr` dans le repertoire `config`.
- dans ce nouveau dossier, ajouter les fichiers `db_config.php` et `params.php`
- installer apache et php (`brew install httpd php`)
- installer un certificat SSL localement pour le domaine `dev.clubalpinlyon.fr` et localhost
- configurer un hote pour le domaine `dev.clubalpinlyon.fr` (incluant 443 et le ssl nouvellement créé)
- pointer ce hote vers le repertoire contenant ce repo


Todo
- ajouter une redirection pour https://clubalpinlyon.fr/
- corriger les erreurs 404: https://error404.atomseo.com/SeoCheck/Report/www.clubalpinlyon.fr/2021-09-10/free?from=
- ajouter disallow dans le robots.txt de test
- 