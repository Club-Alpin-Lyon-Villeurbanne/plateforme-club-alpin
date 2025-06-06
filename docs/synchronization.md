# Synchronisation des Adhérents

## Processus de Synchronisation

Un Cronjob est configuré pour synchroniser les nouveaux adhérents avec le système FFCAM :

1. La FFCAM upload un fichier CSV chaque nuit
2. Notre application parse ce fichier
3. Pour chaque adhérent :
   - Si l'adhérent existe (même nom, prénom, date de naissance) : mise à jour
   - Si l'adhérent n'existe pas : création du compte

## Accès

Les nouveaux adhérents peuvent accéder au site une fois leur compte créé. 