# Environnements

## Hébergement

Nous disposons de deux environnements hébergés sur [Clever Cloud](https://www.clever-cloud.com/) :

### Environnements disponibles

1. **Staging** ([www.clubalpinlyon.top](https://www.clubalpinlyon.top))
   - Environnement de test
   - Déploiement continu via Github Actions
   - Chaque Pull Request mergée devient rapidement disponible à tester

2. **Production** ([www.clubalpinlyon.fr](https://www.clubalpinlyon.fr))
   - Environnement final des utilisateurs
   - Déclenchement manuel et déploiement automatisé via Github Actions

## Infrastructure technique

- Serveur web
- Base de données MySQL 8.0 (hébergée et managée par Clever Cloud)
- Variables d'environnement gérées dans la console de Clever Cloud
- filesystem fourni par Clever Cloud: 1 pour le dossier `ftp` et un pour l'upload du fichier de la FFCAM.