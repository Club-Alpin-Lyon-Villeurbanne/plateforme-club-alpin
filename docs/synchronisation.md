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

### Description fichier FFCAM
voici la description du fichier fourni par la FFCAM:
```
Séparateur ;
Fin de ligne 

Fichier XXXX.txt ( XXXX : N° du club )
Champs : 
0 N° d'adhérent complet (12 caractères) Unique
1 N° de club (4 caractères)
2 N° d'adhérent (8 caractères)
3 Clé publique (2 caractères)
4 Catégorie (2 caractères)	
5 N° du référent familial eventuel (8 caractères)
6 Date de naissance (YYYY-MM-DD)
7 Date d'inscription (YYYY-MM-DD), renseignée si inscrit cette année
8 Qualité (4 caractères)
9 Nom (24 caractères)
10 Prénom (14 caractères)
11 Adresse 1, complément de nom (38 caractères)
12 Adresse 2 , Batiment	(38 caractères)
13 Adresse 3 , N° de rue (38 caractères)
14 Adresse 4 , Localité (38 caractères)
15 Code postal (5 caractères)
16 Ville (33 caractères)
17 Inscrit par Internet (1/0)
18 Option Assurance de personne (1/0)
19 Date Option assurance de personne (YYYY-MM-DD)
20 Extension d'assurance Extension Monde (1/0)
21 Date Extension d'assurance Extension Monde (YYYY-MM-DD ou vide)
22 Extension d'assurance Assurance corporelle renforcée (1/0)
23 Date Extension d'assurance Assurance corporelle renforcée (YYYY-MM-DD ou vide)
24 Extension paralpinisme (1/0)
25 Date extension paralpinisme (YYYY-MM-DD ou vide)
26 Telcom En cas d'accident (100 caractères)
27 Telcom Portable (100 caractères)
28 Telcom Email (100 caractères)
29 Telcom Téléphone domicile (100 caractères)
30 Date de radiation (YYYY-MM-DD ou vide)
31 Motif de radiation (DM/DC/MU ou vide)
32 Personne à prevenir en cas d'accident (100 caractères)
33 Activités pratiquées par l'adhérent séparées par ","
34 Millésime CACI
35 QS SPORT

Fichier activite_XXXX.txt ( XXXX : N° du club )
Champs : 
0 N° d'adhérent complet (12 caractères)
1 Code de l'activité propre au club
2 Libellé de l'activité propre au club
3 Date d'inscription à l'activité (YYYY-MM-DD)
4 Montant payé


Fichier decouverte_XXXX.txt (XXXX : N° du club)
Champs : 
0 N° de carte découverte (13 caractères)
1 Durée de validité
2 Date de début de validité
3 Heure de début de validité
4 Date de naissance
5 Qualité (4 caractères)
6 Nom (24 caractères)
7 Prénom (14 caractères)
8 Adresse 1, complément de nom (38 caractères)
9 Adresse 2 , Batiment	(38 caractères)
10 Adresse 3 , N° de rue (38 caractères)
11 Adresse 4 , Localité (38 caractères)
12 Code postal (5 caractères)
13 Ville (33 caractères)
14 Tél. domicile
15 Bureau
16 Email
17 Portable
18 En cas d'accident
19 Personne à prévenir en cas d'accident
20 Montant
21 Type de réglement
22 Date de règlement
23 Activités
```