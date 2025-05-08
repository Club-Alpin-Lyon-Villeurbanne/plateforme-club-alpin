# Installation de l'environnement local

## Prérequis

- [Docker](https://docs.docker.com/engine/install/) & docker-compose
- Make (installé par défaut sur Mac et Linux ; disponible via [Chocolatey](https://community.chocolatey.org/packages/make) pour Windows)
- Si vous avez d'autres projets utilisant les mêmes ports, pensez à les arrêter avant de lancer le projet CAF ;) (ou changez les ports)

## Étapes d'installation

1. Cloner le repository :
```bash
git clone git@github.com:Club-Alpin-Lyon-Villeurbanne/caflyon.git
cd caflyon
```

2. Initialiser l'environnement :
```bash
make init
```
Cette commande lance les conteneurs (site web, base de données, phpMyAdmin & mailcatcher)

3. Initialiser la base de données :
```bash
make database-init
```

## Accès aux services

- Site web : `http://127.0.0.1:8000/`
- Compte admin par défaut : `test@clubalpinlyon.fr` / `test`
- PHPMyAdmin : `http://127.0.0.1:8080/`, accès : `root` / `test`
- Mailcatcher : `http://127.0.0.1:8025/`

Pour "consommer" les mails, exécutez :
```bash
docker compose exec cafsite bin/console messenger:consume mails --limit=50 --quiet --no-interaction
```

⚠️ L'upload d'images ne fonctionne pas dans un environnement dockerisé. 🚧 