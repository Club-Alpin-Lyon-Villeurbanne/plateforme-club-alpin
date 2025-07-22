# Guide de dépannage

## Problèmes courants

### Conflits d'images Docker

Après une migration vers un nouveau setup, exécutez :
```bash
docker stop www_pca && docker rm www_pca
```
pour éviter les conflits d'images Docker.

### Utilisateurs Windows

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

#### Erreurs courantes sous Windows

1. `permission denied while trying to connect to the Docker daemon socket`
   - Solution : Ajoutez votre utilisateur dans le groupe `docker` : `$ sudo usermod -a -G docker $USER`
   - Puis relancez WSL ([voir SO](https://stackoverflow.com/a/48450294))

2. Le conteneur `db_pca` ne démarre pas
   - Vérifiez les logs avec `$ docker compose logs cafdb`
   - Si l'erreur `Could not set file permission for ca-key.pem` apparaît :
     1. Démarrez les conteneurs depuis Powershell (`> docker compose up`)
     2. Retournez dans WSL
     3. Arrêtez-les (`$ make docker-stop`)
     4. Relancez (`$ make init`)
     ([voir SO](https://stackoverflow.com/a/78768559))

3. Erreur `--initialize specified but the data directory has files in it`
   - Solution : Supprimez le contenu du dossier `./db` 