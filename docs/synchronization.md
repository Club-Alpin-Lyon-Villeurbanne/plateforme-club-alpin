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
SÈparateur ;
Fin de ligne 

Fichier XXXX.txt ( XXXX : N∞ du club )
Champs : 
0 N∞ d'adhÈrent complet (12 caractËres) Unique
1 N∞ de club (4 caractËres)
2 N∞ d'adhÈrent (8 caractËres)
3 ClÈ publique (2 caractËres)
4 CatÈgorie (2 caractËres)	
5 N∞ du rÈfÈrent familial eventuel (8 caractËres)
6 Date de naissance (YYYY-MM-DD)
7 Date d'inscription (YYYY-MM-DD), renseignÈe si inscrit cette annÈe
8 QualitÈ (4 caractËres)
9 Nom (24 caractËres)
10 PrÈnom (14 caractËres)
11 Adresse 1, complÈment de nom (38 caractËres)
12 Adresse 2 , Batiment	(38 caractËres)
13 Adresse 3 , N∞ de rue (38 caractËres)
14 Adresse 4 , LocalitÈ (38 caractËres)
15 Code postal (5 caractËres)
16 Ville (33 caractËres)
17 Inscrit par Internet (1/0)
18 Option Assurance de personne (1/0)
19 Date Option assurance de personne (YYYY-MM-DD)
20 Extension d'assurance Extension Monde (1/0)
21 Date Extension d'assurance Extension Monde (YYYY-MM-DD ou vide)
22 Extension d'assurance Assurance corporelle renforcÈe (1/0)
23 Date Extension d'assurance Assurance corporelle renforcÈe (YYYY-MM-DD ou vide)
24 Extension paralpinisme (1/0)
25 Date extension paralpinisme (YYYY-MM-DD ou vide)
26 Telcom En cas d'accident (100 caractËres)
27 Telcom Portable (100 caractËres)
28 Telcom Email (100 caractËres)
29 Telcom TÈlÈphone domicile (100 caractËres)
30 Date de radiation (YYYY-MM-DD ou vide)
31 Motif de radiation (DM/DC/MU ou vide)
32 Personne ‡ prevenir en cas d'accident (100 caractËres)
33 ActivitÈs pratiquÈes par l'adhÈrent sÈparÈes par ","
34 MillÈsime CACI
35 QS SPORT

Fichier activite_XXXX.txt ( XXXX : N∞ du club )
Champs : 
0 N∞ d'adhÈrent complet (12 caractËres)
1 Code de l'activitÈ propre au club
2 LibellÈ de l'activitÈ propre au club
3 Date d'inscription ‡ l'activitÈ (YYYY-MM-DD)
4 Montant payÈ


Fichier decouverte_XXXX.txt (XXXX : N∞ du club)
Champs : 
0 N∞ de carte dÈcouverte (13 caractËres)
1 DurÈe de validitÈ
2 Date de dÈbut de validitÈ
3 Heure de dÈbut de validitÈ
4 Date de naissance
5 QualitÈ (4 caractËres)
6 Nom (24 caractËres)
7 PrÈnom (14 caractËres)
8 Adresse 1, complÈment de nom (38 caractËres)
9 Adresse 2 , Batiment	(38 caractËres)
10 Adresse 3 , N∞ de rue (38 caractËres)
11 Adresse 4 , LocalitÈ (38 caractËres)
12 Code postal (5 caractËres)
13 Ville (33 caractËres)
14 TÈl. domicile
15 Bureau
16 Email
17 Portable
18 En cas d'accident
19 Personne ‡ prÈvenir en cas d'accident
20 Montant
21 Type de rÈglement
22 Date de rËglement
23 ActivitÈs
```