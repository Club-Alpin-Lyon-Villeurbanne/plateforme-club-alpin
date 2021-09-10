# Code source pour le site du Club Alpin Francais de Lyon-Villeurbanne

Ce repo contient le code source du site https://www.clubalpinlyon.fr/.
Le site a été développé en php par l'agence HereWeCom il y a quelques années (environ 2010) et ils nous ont ensuite donné le code.
C'est du php sans framework particulier, avec une structure relativement complexe.
Il utilise une base mariadb.
Le deployement se fait manuellement sur [o2switch](https://missouri.o2switch.net:2083). Un processus de deploiement continu est en train d'etre mis en place.

Un environment de dev est accessible sur https://test.clubalpinlyon.fr. Le code de cet environment se trouve dans `/home/kahe0589/test` et la db utilisée est `kahe0589_dev`.

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
- 