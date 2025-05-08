# Synchronisation des adhérents

## Processus de synchronisation

Un Cronjob est en place pour synchroniser les nouveaux adhérents avec le système de la FFCAM.

### Fonctionnement

1. La FFCAM upload un fichier CSV avec les nouveaux adhérents chaque nuit
2. Notre application parse ce fichier et crée les adhérents dans la base de données
3. Si l'adhérent existe déjà (même nom, même prénom, même date de naissance), son compte existant sera mis à jour avec les nouvelles informations
4. Si l'adhérent n'existe pas, il sera créé et il pourra accéder au site 