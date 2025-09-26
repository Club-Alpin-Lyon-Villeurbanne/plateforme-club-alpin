# Documentation API REST - Plateforme Club Alpin Lyon

## Vue d'ensemble

L'API REST de la plateforme Club Alpin Lyon permet d'accéder aux données des sorties, utilisateurs, notes de frais et participations. L'API suit les standards REST et retourne des données au format JSON avec des métadonnées de pagination.

## URL de base

```
https://[votre-domaine]/api
```

## Authentification

L'API utilise l'authentification JWT (JSON Web Tokens). Vous devez être authentifié pour accéder aux endpoints.

```bash
# Obtenir un token JWT (à implémenter selon votre système d'authentification)
POST /api/auth/login
Content-Type: application/json

{
  "email": "utilisateur@example.com",
  "password": "motdepasse"
}
```

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
- `dateDebut[gte]` : Sorties après cette date (timestamp)
- `dateDebut[lte]` : Sorties avant cette date (timestamp)
- `order[dateDebut]` : Tri par date (`asc` ou `desc`)

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

#### Liste des notes de frais
```http
GET /api/notes-de-frais
```

**Paramètres de requête :**
- `event` : Filtrer par ID de sortie
- `inclure_brouillons` : Inclure les brouillons (true/false)

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
  "details": "{...}"
}
```

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
GET /api/sorties?commission=1&dateDebut[gte]=1735689600
```

## Tri

Le tri est disponible sur certains champs :

```http
GET /api/sorties?order[dateDebut]=desc
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