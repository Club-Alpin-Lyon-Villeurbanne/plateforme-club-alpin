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
   - Déploiements manuels via Github Actions

## Infrastructure technique

- Serveur web
- Base de données MySQL 8.0 (hébergée et managée par Clever Cloud)
- Variables d'environnement gérées dans la console de Clever Cloud

## Outils

- 📋 Tickets : [Clickup](https://app.clickup.com/)
- 🐛 Report de bugs : [sentry](https://club-alpin-lyon.sentry.io/issues/?project=6021900&statsPeriod=14d)
- ⚙️ Build : [Github Actions](https://github.com/Club-Alpin-Lyon-Villeurbanne/caflyon/actions) 