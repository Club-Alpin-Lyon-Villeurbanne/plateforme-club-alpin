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
make database-init
```

## Accès

- Site web : `http://127.0.0.1:8000/`
- Compte admin : `test@clubalpinlyon.fr` / `test`
- PHPMyAdmin : `http://127.0.0.1:8080/` (accès : `root` / `test`)
- Mailcatcher : `http://127.0.0.1:8025/`

Pour consommer les mails : `make consume-mails`

⚠️ L'upload d'images ne fonctionne pas dans un environnement dockerisé. 🚧

## Dépannage

### Problèmes courants

Après une migration vers un nouveau setup, exécutez :
```bash
docker stop www_pca && docker rm www_pca
```

### Windows

1. Installer [WSL 2](https://learn.microsoft.com/en-us/windows/wsl/install)
2. Installer [Docker Desktop](https://docs.docker.com/desktop/install/windows-install)
3. Activer le backend Docker WSL2 : https://docs.docker.com/desktop/wsl/

Vérification de l'installation :
```powershell
wsl --list --verbose
wsl
docker --version
```

Problèmes courants :
- Permission denied : `sudo usermod -a -G docker $USER`
- Erreur de démarrage du conteneur db : vérifier les logs avec `docker compose logs cafdb`
- Erreur d'initialisation : supprimer le contenu du dossier `./db` 