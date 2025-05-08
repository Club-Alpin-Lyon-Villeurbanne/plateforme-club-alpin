# FAQ (Questions Fréquentes)

## Questions générales

### Pourquoi le code n'est-il pas open source ?
Nous avons une réelle volonté d'ouvrir ce code, mais un audit SSI approfondi a révélé que le projet nécessite encore des corrections au niveau de la sécurité avant d'être partagé publiquement.

### Pourquoi l'upload d'images ne fonctionne pas en local ?
L'upload d'images ne fonctionne pas dans un environnement dockerisé. C'est une limitation connue de l'environnement de développement local.

### Comment accéder à l'administration ?
L'interface d'administration est accessible via l'url : https://www.clubalpinlyon.fr/admin/

En environnement local, vous pouvez utiliser ces identifiants :
- Admin : `admin` / `admin`
- Gestionnaire de contenu : `admin_contenu` / `contenu`

## Questions techniques

### Comment gérer les conflits d'images Docker ?
Après une migration vers un nouveau setup, exécutez :
```bash
docker stop www_caflyon && docker rm www_caflyon
```

### Comment résoudre les problèmes de permissions Docker sous Windows ?
Si vous rencontrez l'erreur `permission denied while trying to connect to the Docker daemon socket` :
1. Ajoutez votre utilisateur dans le groupe `docker` : `$ sudo usermod -a -G docker $USER`
2. Relancez WSL

### Comment gérer les erreurs de base de données ?
Si vous rencontrez l'erreur `--initialize specified but the data directory has files in it`, supprimez le contenu du dossier `./db`. 