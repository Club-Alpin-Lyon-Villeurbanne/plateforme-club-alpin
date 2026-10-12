# Documentation API REST - Plateforme Club Alpin Lyon

## Vue d'ensemble

L'API REST de la plateforme Club Alpin Lyon permet d'accéder aux données des sorties, utilisateurs, notes de frais et participations. L'API suit les standards REST et retourne des données au format JSON avec des métadonnées de pagination.

## URL de base

```
https://[votre-domaine]/api
```

## Authentification

L'API utilise l'authentification JWT (JSON Web Tokens). Vous devez être authentifié pour accéder aux endpoints.

### Obtenir un token JWT
```http
POST /api/auth
Content-Type: application/json

{
  "email": "utilisateur@example.com",
  "password": "motdepasse"
}
```

**Réponse (200) :**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "refresh_token": "abc123def456..."
}
```

### Rafraîchir un token
```http
POST /api/token/refresh
Content-Type: application/json

{
  "refresh_token": "abc123def456..."
}
```

**Réponse (200) :**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "refresh_token": "xyz789..."
}
```

### Durée de validité
- **Token JWT** : Configuré via `JWT_TOKEN_TTL` (variable d'environnement)
- **Refresh token** : 90 jours

### Utilisation du token

Incluez le token dans l'en-tête Authorization pour les requêtes suivantes :

```bash
Authorization: Bearer <votre-token-jwt>
```

## Format des réponses

Toutes les réponses de collections sont encapsulées avec des métadonnées de pagination :

```json
{
  "data": [
    // Tableau des résultats
  ],
  "meta": {
    "page": 1,
    "perPage": 30,
    "total": 150,
    "pages": 5
  }
}
```

## Endpoints disponibles

### 📍 Sorties (Événements)

#### Liste des sorties
```http
GET /api/sorties
```

**Paramètres de requête :**
- `page` : Numéro de page (défaut: 1)
- `itemsPerPage` : Nombre d'éléments par page (défaut: 30, max: 30)
- `commission` : Filtrer par ID de commission
- `participations.user.id` : Filtrer par ID d'utilisateur participant
- `startDate[gte]` : Sorties après cette date (format ISO 8601, ex: `2025-11-14T00:00:00`)
- `startDate[lte]` : Sorties avant cette date (format ISO 8601, ex: `2025-11-17T23:59:59`)
- `order[startDate]` : Tri par date (`asc` ou `desc`)

**Réponse exemple :**
```json
{
  "data": [
    {
      "id": 1,
      "titre": "Randonnée au Mont Blanc",
      "code": "randonnee-mont-blanc",
      "dateDebut": "2025-09-15 08:00:00",
      "dateFin": "2025-09-15 18:00:00",
      "lieuRendezVous": "Parking de Chamonix",
      "inscriptionsMax": 15,
      "participantsMax": 20,
      "difficulte": "Difficile",
      "description": "...",
      "commission": {
        "id": 1,
        "code": "alpinisme",
        "title": "Alpinisme"
      },
      "user": {
        "id": 42,
        "email": "guide@clubalpin.fr",
        "prenom": "Jean",
        "nom": "DUPONT",
        "surnom": "JD"
      }
    }
  ],
  "meta": {
    "page": 1,
    "perPage": 30,
    "total": 45,
    "pages": 2
  }
}
```

#### Détails d'une sortie
```http
GET /api/sorties/{id}
```

Retourne les détails complets d'une sortie incluant les informations supplémentaires (latitude, longitude, matériel, itinéraire, etc.)

### 👥 Utilisateurs

#### Détails de l'utilisateur connecté
```http
GET /api/utilisateurs/{id}
```

**Note :** Vous ne pouvez accéder qu'à vos propres informations ou celles d'un autre utilisateur si vous avez les droits admin.

**Réponse exemple :**
```json
{
  "id": 42,
  "email": "utilisateur@clubalpin.fr",
  "prenom": "Marie",
  "nom": "MARTIN",
  "surnom": "MM",
  "numeroLicence": "0123456789",
  "dateCreation": "2023-01-15 10:30:00",
  "dateNaissance": "1985-06-20",
  "telephone": "06 12 34 56 78",
  "adresse": "123 rue de la Montagne",
  "codePostal": "69000",
  "ville": "Lyon",
  "pays": "France"
}
```

### 💰 Notes de frais

#### Liste des notes de frais (utilisateur connecté)
```http
GET /api/notes-de-frais
```

Retourne uniquement les notes de frais de l'utilisateur connecté.

**Paramètres de requête :**
- `event` : Filtrer par ID de sortie
- `pagination` : `false` pour désactiver la pagination

#### Liste des notes de frais (admin/comptabilité)
```http
GET /api/admin/notes-de-frais
```

La liste de compta-club. Retourne les notes de frais de tous les utilisateurs : toutes les notes de sortie, et les notes hors sortie qui attendent la trésorerie ou qu'elle a traitées, et celles qu'un responsable a refusées. Une note hors sortie n'y figure ni en brouillon, ni `submitted` tant qu'elle a des valideurs (elle attend alors un responsable). Réservé aux administrateurs et aux utilisateurs ayant le droit `manage_expense_reports`.

**Sécurité :** `ROLE_ADMIN` ou droit `manage_expense_reports`

**Paramètres de requête :**
- `status` : une valeur, ou plusieurs (`status[]=submitted&status[]=preapproved`)
- `type` : `sortie`, `formation` ou `autre` (achat)
- `refundRequired` : `true` (remboursement) ou `false` (don)
- `user.lastname` : nom du demandeur, recherche partielle
- `event.titre` (recherche partielle) et `event.startDate[after]`, `event.startDate[before]`, `event.startDate[strictly_after]`, `event.startDate[strictly_before]` : ne portent que sur les notes de sortie ; une note hors sortie reste dans la liste quand ils sont actifs (compta-club, lui, ne demande alors que des sorties : `type=sortie`)
- `event` : Filtrer par ID de sortie
- `inclure_brouillons` : `true` pour inclure les brouillons des notes de sortie
- `pagination` : `false` pour désactiver la pagination

Une valeur inconnue de `status` ou de `type` donne une liste vide.

Une note hors sortie y porte les [champs d'une note hors sortie](#champs-dune-note-hors-sortie), comme à sa lecture par `GET /api/notes-de-frais/{id}`, que compta-club utilise pour la page d'une note.

**Réponse exemple :**
```json
{
  "data": [
    {
      "id": 1,
      "status": "submitted",
      "refundRequired": true,
      "utilisateur": {
        "id": 42,
        "prenom": "Marie",
        "nom": "MARTIN"
      },
      "sortie": {
        "id": 11,
        "titre": "Randonnée au Mont Blanc",
        "dateDebut": "2025-09-15 08:00:00"
      },
      "dateCreation": "2025-09-20",
      "commentaireStatut": null,
      "details": "{...}",
      "piecesJointes": [
        {
          "id": 1,
          "expenseId": "transport",
          "fileUrl": "http://..."
        }
      ]
    }
  ],
  "meta": {
    "page": 1,
    "perPage": 30,
    "total": 12,
    "pages": 1
  }
}
```

#### Créer une note de frais
```http
POST /api/notes-de-frais
Content-Type: application/json

{
  "event": 11,
  "details": "{...}",
  "refundRequired": true
}
```

#### Cloner une note de frais
```http
POST /api/notes-de-frais/{id}/clone
```

#### Modifier une note de frais
```http
PATCH /api/notes-de-frais/{id}
Content-Type: application/merge-patch+json

{
  "status": "submitted",
  "details": "{...}",
  "version": 3
}
```

`version`, facultatif : la version de la note lue par le client (le demandeur la lit avec sa note hors sortie). Si la note a changé depuis, la modification est refusée (409). Sans `version`, la dernière écriture passe.

**Refus :**
- `422` sur `status`, « Cette note a déjà été traitée. » : une décision sur une note qui a quitté l'étape de celui qui décide (deux onglets, deux personnes en même temps). compta-club reconnaît ce texte exact (`app/lib/apiError.ts`) : le changer demande de changer compta-club.
- `422` sur `status`, les autres changements de statut refusés : « Cette note attend encore la décision du responsable de la commission. » (la trésorerie sur une note hors sortie qui attend un responsable), « Vous ne pouvez pas décider sur votre propre note de frais. », sinon « Ce changement de statut n'est pas permis. ».
- `422` sur `commentaireStatut` : un message au demandeur est obligatoire pour refuser une note hors sortie.
- `422` sur le champ modifié (`details`, `refundRequired`, `objet`…), « Cette note a déjà été envoyée : elle ne peut plus être modifiée. » : le demandeur modifie une note qui n'est plus en brouillon ni à corriger (le site reconnaît ce texte exact). Pour toute autre personne : « Seul le demandeur peut modifier cette note. ».
- `409`, « Cette note a été modifiée dans un autre onglet ou sur un autre appareil. » : la `version` envoyée n'est plus celle de la note. Rien n'est écrit : relire la note.
- `409`, « Cette note a été modifiée en même temps par une autre action. » : une autre requête a modifié la note entre la lecture et l'écriture de celle-ci (verrou optimiste). La note n'est pas modifiée, mais pour une note de sortie le mail du changement de statut est déjà en file et part quand même. Relire la note avant de réessayer.
- `400` : `version` n'est pas un nombre entier.

#### Type et montants de toute note
Toute note porte `type` (`sortie`, `formation` ou `autre`), `totalDeclare` et `totalRemboursable` : figés au dernier envoi pour une note hors sortie (en brouillon ou à corriger, ils suivent ses lignes et le barème en vigueur), calculés avec les taux en vigueur pour une note de sortie. Dans ce calcul, une valeur de `details` qui n'est pas un nombre fini compte pour 0.

#### Champs d'une note hors sortie
Une note hors sortie remplit `commission`, `objet`, `dateDepense` (date de l'achat ou de la formation), `lienFormation` et `motPourResponsable`, nuls sur une note de sortie. Sur une note hors sortie, `refundRequired` (don ou remboursement) n'est lu que par le demandeur et les gestionnaires : il est absent pour un responsable de commission.

Une note de type `formation` ou `autre` porte en plus, en lecture seule :
- `lignesAffichees` : ses dépenses dans l'ordre de la saisie, avec leur libellé, leur détail et leur montant.
- `avertissements` : les messages des contrôles.
- `dateEnvoi` : son dernier envoi, `null` pour un brouillon.
- `valideurs` : les responsables qui la valident avant la trésorerie ; vide, la trésorerie décide dès l'envoi.
- `derniereDecision` : `{statut, niveau, auteur: {prenom, nom}, date, message}`, la dernière validation ou le dernier refus, `null` s'il n'y en a pas.
  - `statut` : `preapproved` (validée par un responsable), `approved` ou `rejected`. La comptabilisation n'est pas une décision.
  - `niveau` : `responsable` ou `tresorerie`. Il est établi au moment de la décision : un refus depuis `submitted` vient de la trésorerie si la note n'avait alors aucun valideur. `null` pour une décision plus ancienne que ce champ.
- `validationResponsable` : `{auteur: {prenom, nom}, date}` de la validation par un responsable depuis le dernier envoi (passage en `preapproved`), conservée après la décision de la trésorerie ; `null` si aucun responsable ne l'a validée depuis. Une note renvoyée après un refus repart à `null` jusqu'à sa nouvelle validation : celle d'avant le renvoi ne compte plus.
- `libelleComptable` : « NOM Prénom NDF JJ/MM/AAAA objet », avec le nom du demandeur et la date de la dépense, ce que la trésorerie saisit en comptabilité ; `null` pour un brouillon.
- `peutDecider` : l'utilisateur connecté est valideur de la note et elle est `submitted`.
- `version` : pour le demandeur seulement, la version de la note, à renvoyer avec chaque modification (voir « Modifier une note de frais »).

### 📎 Pièces jointes (Notes de frais)

#### Ajouter une pièce jointe
```http
POST /api/notes-de-frais/{expenseReportId}/pieces-jointes
Content-Type: multipart/form-data

file: [fichier]
expenseId: "transport"
```

#### Liste des pièces jointes
```http
GET /api/notes-de-frais/{expenseReportId}/pieces-jointes
```

### 🎫 Participations aux sorties

#### Liste des participations
```http
GET /api/participation-sorties
```

**Réponse exemple :**
```json
{
  "data": [
    {
      "id": 123,
      "statut": 1,
      "role": "inscrit",
      "dateInscription": 1735847547,
      "utilisateur": {
        "id": 42,
        "prenom": "Marie",
        "nom": "MARTIN"
      },
      "proposeCovoiturage": true
    }
  ],
  "meta": {
    "page": 1,
    "perPage": 30,
    "total": 250,
    "pages": 9
  }
}
```

#### Détails d'une participation
```http
GET /api/participation-sorties/{id}
```

## Statuts et codes

### Statuts des notes de frais
- `draft` : Brouillon
- `submitted` : Soumise
- `preapproved` : Validée par le responsable (note hors sortie seulement : elle attend la trésorerie)
- `approved` : Approuvée
- `rejected` : Rejetée
- `accounted` : Comptabilisée

### Statuts des participations
- `0` : Non confirmé
- `1` : Validé
- `2` : Refusé
- `3` : Absent

### Rôles de participation
- `inscrit` : Participant
- `encadrant` : Encadrant
- `coencadrant` : Co-encadrant
- `stagiaire` : Stagiaire
- `benevole_encadrement` : Bénévole d'encadrement
- `benevole` : Participant bénévole
- `manuel` : Inscription manuelle

## Pagination

La pagination est disponible sur tous les endpoints de collection avec des options de contrôle côté client :

### Paramètres de pagination

```http
GET /api/sorties?page=2&itemsPerPage=10
```

**Paramètres disponibles :**
- `page` : Numéro de la page à récupérer (défaut: 1)
- `itemsPerPage` : Nombre d'éléments par page (défaut: 30, max: 30)
- `pagination` : Active/désactive la pagination (défaut: true)

### Désactiver la pagination

Pour récupérer tous les résultats sans pagination :

```http
GET /api/sorties?pagination=false
```

⚠️ **Attention** : Désactiver la pagination peut retourner un grand nombre de résultats et impacter les performances.

### Exemples d'utilisation

```bash
# 50 éléments par page (limité à 30 max)
GET /api/notes-de-frais?itemsPerPage=50

# Tous les résultats sans pagination
GET /api/notes-de-frais?pagination=false

# Page 3 avec 15 éléments par page
GET /api/participation-sorties?page=3&itemsPerPage=15

# Combiné avec des filtres
GET /api/notes-de-frais?itemsPerPage=20&inclure_brouillons=true&page=2
```

### Métadonnées de pagination

Les métadonnées de pagination sont toujours incluses dans la réponse (sauf si `pagination=false`) :
- `page` : Page actuelle
- `perPage` : Nombre d'éléments par page
- `total` : Nombre total d'éléments
- `pages` : Nombre total de pages

## Filtrage

Les filtres sont disponibles selon les endpoints. Format général :

```http
GET /api/sorties?commission=1&startDate[gte]=2025-01-01T00:00:00
```

## Tri

Le tri est disponible sur certains champs :

```http
GET /api/sorties?order[startDate]=desc
```

## Codes d'erreur

| Code | Description |
|------|-------------|
| 200 | Succès |
| 201 | Créé avec succès |
| 400 | Requête invalide |
| 401 | Non authentifié |
| 403 | Accès refusé |
| 404 | Ressource non trouvée |
| 422 | Données invalides |
| 500 | Erreur serveur |

## Exemples d'utilisation

### JavaScript (fetch)
```javascript
const token = 'votre-token-jwt';

// Récupérer les sorties
fetch('https://clubalpinlyon.fr/api/sorties', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
})
.then(response => response.json())
.then(data => {
  console.log(`${data.meta.total} sorties trouvées`);
  data.data.forEach(sortie => {
    console.log(sortie.titre);
  });
});
```

### cURL
```bash
# Récupérer les notes de frais
curl -H "Authorization: Bearer <token>" \
     -H "Accept: application/json" \
     https://clubalpinlyon.fr/api/notes-de-frais

# Créer une note de frais
curl -X POST \
     -H "Authorization: Bearer <token>" \
     -H "Content-Type: application/json" \
     -d '{"event": 11, "refundRequired": true}' \
     https://clubalpinlyon.fr/api/notes-de-frais
```


## Limites et quotas

- Pagination : Maximum 30 éléments par page
- Taux de requêtes : À définir selon votre infrastructure
- Taille des fichiers uploadés : Maximum 10 MB par fichier

## Support et contact

Pour toute question concernant l'API, contactez l'équipe technique du Club Alpin Lyon.