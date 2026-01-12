# Cas de Tests Fonctionnels - Automatisation Playwright

## 📋 Vue d'ensemble

Ce document propose des cas de tests fonctionnels complets pour automatiser les workflows critiques de la plateforme Club Alpin avec Playwright.

**Contexte** : Application de gestion pour clubs alpins avec plusieurs rôles (Admin, Encadrants, Gestionnaires de contenu, Utilisateurs)

---

## 🔐 1. AUTHENTIFICATION & GESTION DE COMPTE

### TC-AUTH-001 : Connexion valide
**Scénario** : Utilisateur se connecte avec des identifiants valides
- **Données** : Email valide, mot de passe correct
- **Étapes** :
  1. Accéder à `/login`
  2. Remplir email et mot de passe
  3. Cliquer sur "Connexion"
- **Résultat attendu** : Redirection vers la page d'accueil, session créée

### TC-AUTH-002 : Connexion avec identifiants invalides
**Scénario** : Échec d'authentification avec mauvais identifiants
- **Données** : Email valide, mot de passe incorrect
- **Étapes** :
  1. Soumettre le formulaire avec mot de passe incorrect
- **Résultat attendu** : Message d'erreur affiché, pas de redirection

### TC-AUTH-003 : Déconnexion
**Scénario** : Utilisateur se déconnecte
- **Étapes** :
  1. Cliquer sur profil en haut à droite
  2. Cliquer "Déconnexion"
- **Résultat attendu** : Redirection vers page publique, session supprimée

### TC-AUTH-004 : Accès à compte utilisateur
**Scénario** : Affichage des informations du profil
- **Étapes** :
  1. Se connecter avec un utilisateur
  2. Cliquer sur "Mon compte"
- **Résultat attendu** : Affichage du pseudonyme, email, rôles

### TC-AUTH-005 : Réinitialisation de mot de passe
**Scénario** : Utilisateur réinitialise son mot de passe oublié
- **Étapes** :
  1. Cliquer "Mot de passe oublié" sur la page login
  2. Entrer email valide
  3. Valider email de réinitialisation reçu
  4. Définir nouveau mot de passe
- **Résultat attendu** : Accès possible avec nouveau mot de passe

---

## 📰 2. GESTION DES ARTICLES

### TC-ARTICLE-001 : Créer un article
**Scénario** : Rédacteur crée un nouvel article *(test existant à améliorer)*
- **Rôle** : Rédacteur
- **Données** : 
  - Titre : "Test article {timestamp}"
  - Commission : Sélectionnée
  - Type : Article
  - Image de couverture : uploadée
  - Contenu : texte formaté
- **Étapes** :
  1. Menu → "Rédiger un article"
  2. Sélectionner commission
  3. Remplir formulaire
  4. Cocher "Accord ligne éditoriale" et "Autorisation images"
  5. Cliquer "ENREGISTRER ET DEMANDER LA PUBLICATION"
- **Résultat attendu** : 
  - Redirection vers `/profil/articles.html`
  - Message de succès
  - Article visible en statut "En attente de publication"

### TC-ARTICLE-002 : Créer un compte-rendu de sortie
**Scénario** : Encadrant crée un compte-rendu après une sortie
- **Rôle** : Encadrant
- **Données** :
  - Type : Compte-rendu (CR)
  - Sortie associée : Sélectionnée
  - Contenu détaillé
- **Étapes** :
  1. Menu → "Rédiger un article"
  2. Sélectionner type "CR"
  3. Associer une sortie
  4. Remplir contenu
  5. Soumettre
- **Résultat attendu** : CR créé, en attente de modération

### TC-ARTICLE-003 : Valider et publier un article
**Scénario** : Responsable de commission publie un article *(test existant à améliorer)*
- **Rôle** : Responsable de commission
- **Étapes** :
  1. Menu → "Gestion des articles"
  2. Localiser article en attente
  3. Cliquer "Autoriser & publier"
- **Résultat attendu** : 
  - Article passe au statut "Publié"
  - Message "Opération effectuée avec succès"
  - Article visible sur le site public

### TC-ARTICLE-004 : Refuser un article
**Scénario** : Modérateur refuse la publication d'un article
- **Rôle** : Responsable de commission
- **Données** : Raison du refus
- **Étapes** :
  1. Page gestion articles
  2. Cliquer "Refuser" sur un article
  3. Ajouter commentaire de refus
  4. Confirmer
- **Résultat attendu** : 
  - Article passe au statut "Refusé"
  - Auteur reçoit email de refus avec raison
  - Article modifiable/résoumettable

### TC-ARTICLE-005 : Modifier un article en brouillon
**Scénario** : Auteur modifie son brouillon
- **Rôle** : Rédacteur
- **Étapes** :
  1. Accéder à `/profil/articles.html`
  2. Cliquer "Modifier" sur un brouillon
  3. Changer contenu
  4. Sauvegarder
- **Résultat attendu** : Modifications enregistrées, article reste en brouillon

### TC-ARTICLE-006 : Supprimer un article en brouillon
**Scénario** : Rédacteur supprime un brouillon
- **Rôle** : Rédacteur
- **Étapes** :
  1. Accéder liste articles
  2. Cliquer "Supprimer" sur brouillon
  3. Confirmer suppression
- **Résultat attendu** : Article supprimé, n'apparaît plus dans la liste

### TC-ARTICLE-007 : Rechercher/filtrer articles
**Scénario** : Utilisateur recherche des articles par commission
- **Étapes** :
  1. Accéder page articles publics
  2. Filtrer par commission
  3. Trier par date
- **Résultat attendu** : Articles filtrés correctement

---

## 🏔️ 3. GESTION DES SORTIES (ÉVÉNEMENTS)

### TC-EVENT-001 : Créer une sortie
**Scénario** : Encadrant crée une nouvelle sortie *(test existant à améliorer)*
- **Rôle** : Encadrant
- **Données** :
  - Titre : "Test sortie {timestamp}"
  - Commission : Sélectionnée
  - Type : Sorties familles
  - Date/heure RDV : +7 jours, 08:00
  - Lieu RDV : "Bron"
  - Encadrant : Coché
  - Max participants : 9
  - Inscriptions démarrent : même date
  - Max inscriptions internet : 9
  - Description : contenu test
  - Images autorisées : coché
  - Accord édito : coché
- **Étapes** :
  1. Menu → "Proposer une sortie"
  2. Sélectionner type
  3. Remplir formulaire
  4. Placer repère sur carte
  5. Cliquer "ENREGISTRER ET DEMANDER LA PUBLICATION"
- **Résultat attendu** :
  - Redirection vers détail sortie
  - Statut "En attente de validation éditoriale"
  - Email de confirmation envoyé

### TC-EVENT-002 : Valider une sortie (éditorial)
**Scénario** : Responsable valide une sortie éditorialement
- **Rôle** : Responsable de commission
- **Étapes** :
  1. Menu → "Approbation des sorties"
  2. Localiser sortie en attente
  3. Cliquer "Valider"
- **Résultat attendu** :
  - Sortie passe au statut "Validée éditorialement"
  - Passe à la validation juridique
  - Email envoyé à responsable juridique

### TC-EVENT-003 : Valider une sortie (juridique)
**Scénario** : Responsable juridique valide une sortie
- **Rôle** : Responsable juridique
- **Étapes** :
  1. Menu → "Approbation juridique des sorties"
  2. Localiser sortie validée éditorialement
  3. Cliquer "Valider"
- **Résultat attendu** :
  - Sortie passe au statut "Publié et validé"
  - Accessible à la recherche/consultation
  - Inscriptions possibles

### TC-EVENT-004 : Refuser une sortie
**Scénario** : Modérateur refuse une sortie
- **Rôle** : Responsable commission / juridique
- **Données** : Raison du refus
- **Étapes** :
  1. Page approbation sorties
  2. Cliquer "Refuser"
  3. Ajouter motif
  4. Confirmer
- **Résultat attendu** :
  - Sortie passe au statut "Refusée"
  - Encadrant reçoit email avec motif
  - Sortie éditable et résoumettable

### TC-EVENT-005 : S'inscrire à une sortie (utilisateur)
**Scénario** : Utilisateur s'inscrit à une sortie publiée
- **Rôle** : Utilisateur connecté
- **Données** :
  - Sortie publiée avec places disponibles
  - Email validé
- **Étapes** :
  1. Accéder page détail sortie
  2. Cliquer "S'inscrire"
  3. Confirmer
- **Résultat attendu** :
  - Message "Inscription confirmée"
  - Utilisateur dans liste participants
  - Email de confirmation reçu

### TC-EVENT-006 : S'inscrire avec le maximal de participants atteint
**Scénario** : Tentative d'inscription avec liste pleine
- **Étapes** :
  1. Accéder sortie avec 0 place
  2. Tenter inscription
- **Résultat attendu** : Message "Places complètes", pas d'inscription

### TC-EVENT-007 : Ajouter manuellement un participant
**Scénario** : Encadrant ajoute un participant non-adhérent
- **Rôle** : Encadrant
- **Données** :
  - Nom, prénom, email (personne externe)
- **Étapes** :
  1. Menu → "Ajouter un participant" sur sortie
  2. Chercher/sélectionner user
  3. Valider ajout
- **Résultat attendu** :
  - Participant ajouté à la liste
  - Email invitation envoyé (si mode "nomad")

### TC-EVENT-008 : Supprimer un participant
**Scénario** : Encadrant retire un participant d'une sortie
- **Rôle** : Encadrant
- **Étapes** :
  1. Accéder liste participants de sortie
  2. Cliquer "Supprimer" sur participant
  3. Confirmer
- **Résultat attendu** :
  - Participant retiré de la liste
  - Email de notification envoyé
  - Place libérée

### TC-EVENT-009 : Désinscrire utilisateur d'une sortie
**Scénario** : Utilisateur annule son inscription
- **Rôle** : Utilisateur
- **Étapes** :
  1. Menu → "Mes sorties"
  2. Cliquer "Me désinscrire"
  3. Confirmer
- **Résultat attendu** :
  - Inscription supprimée
  - Email confirmation annulation
  - Place libérée

### TC-EVENT-010 : Visualiser la liste des participants
**Scénario** : Encadrant consulte les inscrits
- **Rôle** : Encadrant
- **Étapes** :
  1. Accéder détail sortie
  2. Aller à l'onglet/section "Participants"
- **Résultat attendu** :
  - Liste complète avec noms, emails, téléphones
  - Nombre total/max affiché
  - Possibilité d'exporter

### TC-EVENT-011 : Ajouter une image à une sortie
**Scénario** : Encadrant ajoute galerie de photos
- **Rôle** : Encadrant
- **Données** : Plusieurs fichiers images
- **Étapes** :
  1. Accéder page sortie
  2. Cliquer "Ajouter des images"
  3. Uploader images
  4. Valider
- **Résultat attendu** : Images visibles dans galerie

### TC-EVENT-012 : Modifier une sortie en brouillon
**Scénario** : Encadrant modifie sortie non encore publiée
- **Rôle** : Encadrant
- **Étapes** :
  1. Accéder sortie en statut brouillon/attente
  2. Cliquer "Modifier"
  3. Changer détails
  4. Sauvegarder
- **Résultat attendu** : Modifications enregistrées

### TC-EVENT-013 : Annuler une sortie publiée
**Scénario** : Encadrant annule une sortie publiée
- **Rôle** : Encadrant
- **Données** : Raison de l'annulation
- **Étapes** :
  1. Accéder sortie publiée
  2. Cliquer "Annuler cette sortie"
  3. Entrer raison
  4. Confirmer
- **Résultat attendu** :
  - Statut passe à "Annulée"
  - Tous les participants reçoivent email d'annulation
  - Plus visible en recherche

---

## 💰 4. NOTES DE FRAIS (EXPENSE REPORTS)

### TC-EXPENSE-001 : Créer une note de frais
**Scénario** : Encadrant crée une demande de remboursement après sortie
- **Rôle** : Encadrant
- **Données** :
  - Sortie associée
  - Type de dépenses : Carburant, péage, repas, etc.
  - Montants détaillés
  - Pièces justificatives (photos/PDF)
- **Étapes** :
  1. Accéder page sortie
  2. Cliquer "Ajouter note de frais"
  3. Sélectionner type de dépense
  4. Remplir montants
  5. Upload justificatifs
  6. Valider
- **Résultat attendu** :
  - Note créée en statut "Brouillon"
  - Affichage total estimé
  - Sauvegardable

### TC-EXPENSE-002 : Soumettre une note de frais
**Scénario** : Encadrant soumet sa note de frais pour approbation
- **Rôle** : Encadrant
- **Étapes** :
  1. Accéder note en brouillon
  2. Vérifier totaux
  3. Cliquer "Soumettre"
  4. Confirmer
- **Résultat attendu** :
  - Statut passe à "Soumise"
  - Email notification comptable
  - Archivée en brouillon impossible

### TC-EXPENSE-003 : Dupliquer une note de frais
**Scénario** : Encadrant clone une note existante comme base
- **Rôle** : Encadrant
- **Étapes** :
  1. Accéder note existante
  2. Cliquer "Dupliquer"
  3. Modifier détails (dates, montants)
  4. Soumettre nouvelle
- **Résultat attendu** : Nouvelle note créée avec données copiées

### TC-EXPENSE-004 : Ajouter une pièce jointe à une note
**Scénario** : Encadrant ajoute justificatif
- **Rôle** : Encadrant
- **Données** : Image/PDF de ticket/facture
- **Étapes** :
  1. Accéder note
  2. Cliquer "Ajouter pièce jointe"
  3. Sélectionner fichier
  4. Sauvegarder
- **Résultat attendu** : Fichier uploadé et visible

### TC-EXPENSE-005 : Valider une note de frais (comptable)
**Scénario** : Comptable approuve une note soumise
- **Rôle** : Comptable
- **Étapes** :
  1. Accéder interface compta-club (application NextJS)
  2. Localiser note à valider
  3. Vérifier montants et justificatifs
  4. Cliquer "Approuver"
- **Résultat attendu** :
  - Statut passe à "Approuvée"
  - Email de confirmation à encadrant
  - Prête pour comptabilisation

### TC-EXPENSE-006 : Rejeter une note de frais
**Scénario** : Comptable rejette une note avec motif
- **Rôle** : Comptable
- **Données** : Raison du rejet
- **Étapes** :
  1. Accéder note à valider
  2. Cliquer "Rejeter"
  3. Ajouter motif
  4. Confirmer
- **Résultat attendu** :
  - Statut passe à "Rejetée"
  - Email au créateur avec motif
  - Peut être modifiée/resoumise

### TC-EXPENSE-007 : Consulter l'historique d'une note
**Scénario** : Suivi des changements d'état
- **Rôle** : Tous
- **Étapes** :
  1. Accéder note de frais
  2. Cliquer "Historique" ou onglet "Modifications"
- **Résultat attendu** :
  - Liste des changements d'état avec dates/auteurs
  - Commentaires visibles

### TC-EXPENSE-008 : Calculer indemnités kilométriques
**Scénario** : Calcul automatique basé sur distance + taux
- **Données** :
  - Distance : 150 km
  - Taux : 0,45€/km
- **Étapes** :
  1. Entrer distance dans note
  2. Système calcule automatiquement
- **Résultat attendu** : Montant = 150 × 0,45 = 67,50€

---

## 👥 5. GESTION DES UTILISATEURS & RÔLES

### TC-USER-001 : Créer un utilisateur (Admin)
**Scénario** : Admin crée manuellement un nouvel utilisateur
- **Rôle** : Admin
- **Données** : Email, nom, prénom, rôle(s)
- **Étapes** :
  1. Menu Admin → "Gestion des utilisateurs"
  2. Cliquer "Ajouter utilisateur"
  3. Remplir formulaire
  4. Assigner rôle(s)
  5. Valider
- **Résultat attendu** :
  - Utilisateur créé
  - Email d'invitation envoyé
  - Accès immédiat

### TC-USER-002 : Modifier les rôles d'un utilisateur
**Scénario** : Admin change les permissions d'un utilisateur
- **Rôle** : Admin
- **Étapes** :
  1. Accéder page gestion utilisateurs
  2. Sélectionner utilisateur
  3. Cocher/décocher rôles (Encadrant, Responsable commission, etc.)
  4. Sauvegarder
- **Résultat attendu** : Rôles mis à jour, permissions appliquées

### TC-USER-003 : Désactiver un utilisateur
**Scénario** : Admin désactive accès d'un utilisateur
- **Rôle** : Admin
- **Étapes** :
  1. Accéder utilisateur
  2. Cliquer "Désactiver compte"
  3. Confirmer
- **Résultat attendu** :
  - Utilisateur ne peut plus se connecter
  - Status "Inactif" affiché

### TC-USER-004 : Réactiver un utilisateur
**Scénario** : Admin réactive un compte désactivé
- **Rôle** : Admin
- **Étapes** :
  1. Filtrer utilisateurs inactifs
  2. Cliquer "Réactiver"
- **Résultat attendu** : Utilisateur peut se reconnecter

### TC-USER-005 : Anonymiser un utilisateur (RGPD)
**Scénario** : Suppression de données personnelles
- **Rôle** : Admin
- **Étapes** :
  1. Accéder utilisateur
  2. Cliquer "Anonymiser"
  3. Confirmer irréversibilité
- **Résultat attendu** :
  - Données personnelles supprimées
  - Pseudonyme générique assigné
  - Données liaison conservées (pour statistiques)

### TC-USER-006 : Synchronisation FFCAM (Cronjob)
**Scénario** : Nouveaux adhérents FFCAM apparaissent sur la plateforme
- **Données** : Fichier FFCAM fourni chaque nuit
- **Étapes** :
  1. Cronjob déclenché automatiquement
  2. Parsing fichier CSV FFCAM
  3. Création/mise à jour comptes adhérents
- **Résultat attendu** :
  - Nouveaux adhérents créés
  - Adhérents existants mis à jour (si changements)
  - Accès immédiat pour nouveaux
  - Email de bienvenue envoyé

### TC-USER-007 : Consulter profil utilisateur
**Scénario** : Utilisateur voit ses données publiques
- **Rôle** : Tous
- **Étapes** :
  1. Cliquer sur utilisateur dans liste/article
- **Résultat attendu** :
  - Affichage nom, prénom, bio, commissions
  - Sorties encadrées
  - Articles rédigés
  - Pas de données sensibles exposées

### TC-USER-008 : Afficher les droits d'un utilisateur
**Scénario** : Admin visualise les permissions d'un user
- **Rôle** : Admin
- **Étapes** :
  1. Accéder page utilisateur
  2. Cliquer "Détails des droits"
- **Résultat attendu** :
  - Rôles listés
  - Commissions gérées
  - Permissions spéciales visibles

---

## 📊 6. GESTION DES CONTENUS & PAGES

### TC-CONTENT-001 : Modifier une page statique
**Scénario** : Gestionnaire de contenu met à jour une page
- **Rôle** : Gestionnaire de contenu / Admin
- **Données** : Page "À propos", contenu HTML/texte
- **Étapes** :
  1. Menu Admin → "Gestion des pages"
  2. Cliquer sur page
  3. Modifier contenu
  4. Sauvegarder et publier
- **Résultat attendu** :
  - Changements visibles immédiatement
  - Historique conservé
  - URL inchangée

### TC-CONTENT-002 : Créer un bloc de contenu
**Scénario** : Création d'un bloc réutilisable
- **Rôle** : Gestionnaire de contenu
- **Données** : Texte, images, liens
- **Étapes** :
  1. Menu → "Blocs de contenu"
  2. Cliquer "Nouveau bloc"
  3. Remplir contenu
  4. Assigner à page(s)
  5. Publier
- **Résultat attendu** : Bloc visible sur pages assignées

### TC-CONTENT-003 : Ajouter un partenaire
**Scénario** : Admin ajoute nouveau partenaire à la plateforme
- **Rôle** : Admin
- **Données** :
  - Nom, logo, URL, description
  - Catégorie (sponsor, média, etc.)
- **Étapes** :
  1. Menu Admin → "Partenaires"
  2. Cliquer "Ajouter partenaire"
  3. Remplir formulaire
  4. Upload logo
  5. Valider
- **Résultat attendu** :
  - Partenaire affiché en page d'accueil
  - Logo visible
  - Lien fonctionnel

### TC-CONTENT-004 : Supprimer un partenaire
**Scénario** : Admin retire un partenaire
- **Rôle** : Admin
- **Étapes** :
  1. Accéder liste partenaires
  2. Cliquer "Supprimer" sur partenaire
  3. Confirmer
- **Résultat attendu** :
  - Partenaire supprimé
  - Logo retiré du site
  - Aucune erreur d'affichage

---

## 🔍 7. RECHERCHE & CONSULTATION

### TC-SEARCH-001 : Rechercher une sortie
**Scénario** : Utilisateur cherche des sorties avec filtres
- **Données** :
  - Commission : Alpinisme
  - Date : Février 2025
  - Type : Ascension
- **Étapes** :
  1. Accéder page "Agenda"
  2. Utiliser filtres (commission, date, type)
  3. Valider recherche
- **Résultat attendu** :
  - Sorties filtrées affichées
  - Tri par date croissante
  - Total affiché

### TC-SEARCH-002 : Rechercher un article
**Scénario** : Recherche articles par mot-clé
- **Étapes** :
  1. Accéder "Articles"
  2. Entrer mot-clé dans barre recherche
  3. Valider
- **Résultat attendu** :
  - Articles contenant mot-clé
  - Tri par pertinence/date

### TC-SEARCH-003 : Agenda global
**Scénario** : Affichage calendrier de toutes les sorties
- **Étapes** :
  1. Accéder "Agenda"
- **Résultat attendu** :
  - Calendrier affiché
  - Sorties placées aux dates
  - Clic sur sortie = détail

### TC-SEARCH-004 : Flux RSS
**Scénario** : Lecture flux RSS articles/sorties
- **Étapes** :
  1. Accéder `/rss` ou flux configuré
  2. Lire flux dans lecteur RSS
- **Résultat attendu** :
  - Flux valide XML
  - Articles/sorties récentes listées
  - Liens fonctionnels

---

## 📧 8. NOTIFICATIONS & EMAILS

### TC-NOTIF-001 : Email de bienvenue (nouvel adhérent)
**Scénario** : Nouvel adhérent FFCAM reçoit email de bienvenue
- **Déclencheur** : Création compte via sync FFCAM
- **Résultat attendu** :
  - Email reçu avec bienvenue
  - Lien de connexion
  - Guide utilisateur

### TC-NOTIF-002 : Email de confirmation d'inscription sortie
**Scénario** : Utilisateur inscrit reçoit confirmation
- **Déclencheur** : Inscription validée
- **Résultat attendu** :
  - Email reçu
  - Détails sortie (date, lieu, encadrant)
  - Lien pour se désinscrire

### TC-NOTIF-003 : Email d'annulation de sortie
**Scénario** : Tous les inscrits notifiés d'annulation
- **Déclencheur** : Encadrant clique "Annuler"
- **Résultat attendu** :
  - Tous les inscrits reçoivent email
  - Raison de l'annulation mentionnée
  - Explications fournies

### TC-NOTIF-004 : Alerte nouvelle sortie
**Scénario** : Utilisateur reçoit alerte pour commission suivie
- **Données** : Utilisateur suit commission "Alpinisme"
- **Déclencheur** : Sortie Alpinisme publiée
- **Résultat attendu** :
  - Email d'alerte reçu
  - Détails et lien vers sortie

### TC-NOTIF-005 : Notification refus article/sortie
**Scénario** : Auteur notifié du refus de son contenu
- **Déclencheur** : Modérateur rejette article/sortie
- **Résultat attendu** :
  - Email reçu avec motif
  - Lien vers brouillon pour modification
  - Délai avant nouvelle soumission (si applicable)

---

## 🛡️ 9. SÉCURITÉ & PERMISSIONS

### TC-SEC-001 : Refuser accès sans authentification
**Scénario** : Accès à zone protégée sans login
- **Étapes** :
  1. Tenter accès à `/admin` sans login
- **Résultat attendu** : Redirection vers `/login`

### TC-SEC-002 : Refuser accès avec rôle insuffisant
**Scénario** : Utilisateur sans rôle Admin accède à `/admin`
- **Données** : User avec rôle "Rédacteur"
- **Étapes** :
  1. Se connecter avec "Rédacteur"
  2. Tenter accès `/admin`
- **Résultat attendu** : Erreur 403 (Accès interdit)

### TC-SEC-003 : Vérifier isolation des données utilisateur
**Scénario** : Utilisateur A ne voit pas données sensibles de B
- **Étapes** :
  1. Utilisateur A connecté
  2. Tenter accès `/api/users/{id_B}`
- **Résultat attendu** : Erreur 403 ou données anonymisées

### TC-SEC-004 : Validation CSRF
**Scénario** : Formulaire avec token CSRF valide
- **Étapes** :
  1. Charger formulaire
  2. Vérifier présence token `_token` hidden
  3. Soumettre formulaire
- **Résultat attendu** : Soumission acceptée

### TC-SEC-005 : Prévention injection SQL
**Scénario** : Requête malveillante échoue
- **Données** : Payload SQL injection en recherche
- **Résultat attendu** : Requête saine, pas d'exécution SQL

### TC-SEC-006 : Prévention XSS
**Scénario** : Script injections en contenu bloqué
- **Données** : `<script>alert('xss')</script>` en article
- **Résultat attendu** : Script échappé, affiché comme texte

---

## 🔧 10. FONCTIONNALITÉS AVANCÉES

### TC-ADV-001 : Gérer les commissions
**Scénario** : Admin gère les commissions
- **Rôle** : Admin
- **Étapes** :
  1. Menu Admin → "Commissions"
  2. Ajouter/modifier/supprimer commission
  3. Assigner responsable
  4. Valider
- **Résultat attendu** : Commission disponible pour créateurs

### TC-ADV-002 : Gérer les groupes utilisateurs
**Scénario** : Admin crée/modifie groupes
- **Rôle** : Admin
- **Données** :
  - Nom du groupe
  - Membres
  - Permissions
- **Étapes** :
  1. Menu Admin → "Groupes"
  2. Créer/modifier groupe
  3. Ajouter membres
  4. Sauvegarder
- **Résultat attendu** : Groupe crée, permissions appliquées

### TC-ADV-003 : Minibus - Réserver transport
**Scénario** : Encadrant réserve un transport minibu
- **Rôle** : Encadrant
- **Données** :
  - Date, durée
  - Nombre de places
  - Itinéraire
- **Étapes** :
  1. Accéder page Minibu
  2. Créer réservation
  3. Sélectionner dates/durée
  4. Valider
- **Résultat attendu** :
  - Réservation enregistrée
  - Calendrier mis à jour
  - Confirmation envoyée

### TC-ADV-004 : Matériel - Louer équipement
**Scénario** : Encadrant loue du matériel pour sortie
- **Rôle** : Encadrant
- **Données** :
  - Type matériel (tente, corde, etc.)
  - Quantité
  - Dates location
  - Sortie associée
- **Étapes** :
  1. Menu → "Matériel"
  2. Créer demande de prêt
  3. Sélectionner équipement
  4. Dates de prêt
  5. Valider
- **Résultat attendu** :
  - Demande créée
  - Responsable matériel notifié
  - Confirmée si disponible

### TC-ADV-005 : Formations - Consulter catalogue
**Scénario** : Utilisateur consulte formations FFCAM
- **Étapes** :
  1. Accéder "Formations"
  2. Voir catalogue
  3. Filtrer par niveau/type
- **Résultat attendu** :
  - Formations affichées
  - Filtres fonctionnels
  - Infos complètes (dates, lieu, tarif)

### TC-ADV-006 : Métabase - Consulter rapports
**Scénario** : Admin visualise rapports d'activité
- **Rôle** : Admin
- **Étapes** :
  1. Accéder interface Metabase
  2. Sélectionner rapport
  3. Filtrer par période
- **Résultat attendu** :
  - Graphiques chargés
  - Données correctes
  - Export possible (PNG/CSV)

### TC-ADV-007 : Radios - Consulter fréquences
**Scénario** : Utilisateur consulte fréquences radio d'urgence
- **Étapes** :
  1. Accéder page "Radios"
- **Résultat attendu** :
  - Fréquences affichées
  - Mise en page lisible
  - Infos à jour

---

## 📱 11. API & INTÉGRATIONS

### TC-API-001 : Récupérer liste sorties (API)
**Scénario** : Client API récupère sorties avec filtres
- **Endpoint** : `GET /api/sorties`
- **Filtres** : `?commission=16&startDate[after]=2025-01-01`
- **Résultat attendu** :
  - Code 200
  - JSON valide avec sorties
  - Pagination fonctionnelle

### TC-API-002 : Créer note de frais (API)
**Scénario** : Création programmatique via API
- **Endpoint** : `POST /api/notes-de-frais`
- **Données** : Event ID, montants, description
- **Résultat attendu** :
  - Code 201
  - Retour ID note créée
  - Consultable via GET

### TC-API-003 : Webhook HelloAsso
**Scénario** : Notification HelloAsso pour transaction
- **Événement** : Paiement adhésion reçu
- **Étapes** :
  1. HelloAsso envoie webhook
  2. Application traite notification
  3. Utilisateur créé/activé
- **Résultat attendu** :
  - Webhook accepté
  - User account updated
  - Email de confirmation

---

## 🚀 12. PERFORMANCE & CHARGE

### TC-PERF-001 : Recherche avec 1000 articles
**Scénario** : Recherche rapide sur base importante
- **Étapes** :
  1. Effectuer recherche
  2. Mesurer temps réponse
- **Résultat attendu** : < 500ms, pagination OK

### TC-PERF-002 : Afficher agenda avec 500 sorties
**Scénario** : Rendu calendrier avec nombreuses sorties
- **Résultat attendu** : 
  - Chargement < 2s
  - Pas de freeze UI
  - Navigation fluide

### TC-PERF-003 : Export rapport avec 10000 données
**Scénario** : Génération XLS/CSV volumineux
- **Résultat attendu** : 
  - Export < 10s
  - Fichier valide
  - Pas d'erreur mémoire

---

## ✅ 13. RÉGRESSION / SMOKE TESTS

### TC-SMOKE-001 : Page d'accueil charge correctement
**Scénario** : Vérification fonctionnelle de base
- **Étapes** :
  1. Accéder `/`
  2. Vérifier éléments clés chargés
- **Résultat attendu** :
  - Page charge
  - Navigation visible
  - Liens fonctionnels

### TC-SMOKE-002 : Menu principal accessible
**Scénario** : Navigation disponible
- **Étapes** :
  1. Cliquer menu hamburger
  2. Vérifier tous liens
- **Résultat attendu** : Tous les liens naviguent correctement

### TC-SMOKE-003 : Footer avec infos de contact
**Scénario** : Données de contact visibles
- **Résultat attendu** :
  - Email affiché
  - Téléphone présent
  - Liens sociaux fonctionnels

---

## 📝 NOTES IMPORTANTES POUR L'AUTOMATISATION

### Configuration Playwright
```typescript
// playwright.config.ts - Settings recommandés
{
  testDir: './e2e',
  timeout: 30000,
  retries: 1,
  use: {
    baseURL: process.env.BASE_URL || 'http://127.0.0.1:8000',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    trace: 'on-first-retry',
  },
  webServer: {
    command: 'npm run dev',
    port: 8000,
    reuseExistingServer: !process.env.CI,
  },
}
```

### Données de Test
- **Admin** : `admin@test-clubalpinlyon.fr` / `test`
- **Encadrant** : `encadrant@test-clubalpinlyon.fr` / `test`
- **Rédacteur** : `redacteur@test-clubalpinlyon.fr` / `test`
- **Resp. Commission** : `resp.comm@test-clubalpinlyon.fr` / `test`

### Bonnes Pratiques
1. ✅ Utiliser `page.getByRole()` pour accessibilité
2. ✅ Attendre éléments avec `toBeVisible()`
3. ✅ Capturer screenshots en cas d'erreur
4. ✅ Nettoyer données entre tests (fixtures)
5. ✅ Tests parallèles avec `test.parallel`
6. ✅ Assertions claires et explicites
7. ✅ Timeouts appropriés selon l'action
8. ✅ Nommer tests de manière descriptive

### Cronjobs à Tester
- Synchronisation FFCAM (chaque nuit)
- Rappels licence/renouvellement
- Nettoyage alertes anciennes
- Synchronisation compétences FFCAM

### Points de Risque (à prioriser)
1. 🔴 Flux de création/publication article (contenu éditorial)
2. 🔴 Flux complet sortie (validation 2 niveaux)
3. 🔴 Notes de frais (données financières)
4. 🔴 Synchronisation adhérents FFCAM (critique)
5. 🔴 Gestion permissions utilisateurs (sécurité)
6. 🔴 Notifications email (communication)

---

## 🎯 ROADMAP D'IMPLÉMENTATION

**Phase 1 (Semaine 1-2)** : Tests critiques
- TC-AUTH-001, 002, 003
- TC-EVENT-001, 002, 003
- TC-ARTICLE-001, 003, 005

**Phase 2 (Semaine 3)** : Complémentaires
- TC-EXPENSE-001 à 008
- TC-NOTIF-001 à 005
- TC-SEC-001 à 006

**Phase 3 (Semaine 4)** : Avancés + Performance
- TC-ADV-001 à 007
- TC-API-001 à 003
- TC-PERF-001 à 003

**Phase 4** : Maintenance continue
- Mise à jour mensuelle
- Ajout nouvelles fonctionnalités
- Regression tests

---

*Document actualisé le 12 janvier 2026*
