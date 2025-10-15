# Schéma Base de Données - Formations et Niveaux de Pratique FFCAM

## Vue d'ensemble
Ce document décrit le schéma de base de données pour la gestion des formations et niveaux de pratique des adhérents du Club Alpin Français (CAF).

## Tables de référentiel (données maîtres FFCAM)

### 1. `formation_referentiel`
Catalogue des formations FFCAM.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| code_formation | VARCHAR(50) | PK | Code unique de la formation |
| intitule | VARCHAR(255) | NOT NULL | Intitulé de la formation |

### 2. `formation_niveau_referentiel`
Référentiel des niveaux de pratique par activité.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| id | INT | PK, AUTO_INCREMENT | Identifiant unique |
| cursus_niveau_id | INT | NOT NULL, UNIQUE | ID du niveau dans le cursus |
| code_activite | VARCHAR(10) | NOT NULL | Code de l'activité (ESC, ALP, SKI) |
| activite | VARCHAR(100) | NOT NULL | Nom de l'activité |
| niveau | VARCHAR(255) | NOT NULL | Description du niveau |
| libelle | VARCHAR(255) | NOT NULL | Libellé court |
| niveau_court | VARCHAR(50) | NULL | Abréviation du niveau |
| discipline | VARCHAR(100) | NULL | Discipline spécifique |

**Index:**
- UNIQUE: cursus_niveau_id
- INDEX: cursus_niveau_id, code_activite

## Tables de validation (données utilisateurs)

### 3. `formation_validation`
Formations suivies et validées par les adhérents.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| id | INT | PK, AUTO_INCREMENT | Identifiant unique |
| user_id | BIGINT | NOT NULL, FK → caf_user(id_user) | ID de l'utilisateur |
| code_formation | VARCHAR(50) | FK → formation_referentiel | Code de la formation |
| valide | TINYINT(1) | NOT NULL | Formation validée (0/1) |
| date_validation | DATE | NULL | Date de validation |
| numero_formation | VARCHAR(50) | NULL | Numéro FFCAM de la session |
| formateur | VARCHAR(255) | NULL | Nom du formateur |
| id_interne | VARCHAR(20) | NULL | ID interne FFCAM |
| intitule_formation | VARCHAR(255) | NULL | Intitulé tel qu'affiché sur l'extranet |
| created_at | DATETIME | NOT NULL | Date de création |
| updated_at | DATETIME | NOT NULL | Date de mise à jour |

**Index:**
- INDEX: user_id, code_formation, date_validation

### 4. `formation_niveau_validation`
Niveaux de pratique validés par les adhérents.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| id | INT | PK, AUTO_INCREMENT | Identifiant unique |
| user_id | BIGINT | NOT NULL, FK → caf_user(id_user) | ID de l'utilisateur |
| cursus_niveau_id | INT | NOT NULL, FK → formation_niveau_referentiel(id) | ID du niveau |
| date_validation | DATETIME | NULL | Date de validation |
| created_at | DATETIME | NOT NULL | Date de création |
| updated_at | DATETIME | NOT NULL | Date de mise à jour |

**Index:**
- UNIQUE: (user_id, cursus_niveau_id)
- INDEX: user_id, cursus_niveau_id, date_validation

## Table de synchronisation

### 5. `formation_last_sync`
Suivi des dernières synchronisations avec l'extranet FFCAM.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| type | VARCHAR(50) | PK | Type de sync ('formations', 'niveaux_pratique', 'competences') |
| last_sync | DATETIME | NULL | Date/heure de dernière sync |
| records_count | INT | DEFAULT 0 | Nombre d'enregistrements synchronisés |

## Relations et contraintes

### Clés étrangères et comportements

| Table | Colonne | Référence | ON DELETE |
|-------|---------|-----------|-----------|
| formation_validation | user_id | caf_user(id_user) | CASCADE |
| formation_validation | code_formation | formation_referentiel(code_formation) | SET NULL |
| formation_niveau_validation | user_id | caf_user(id_user) | CASCADE |
| formation_niveau_validation | cursus_niveau_id | formation_niveau_referentiel(id) | RESTRICT |

## Notes pour la synchronisation

### Flux de données recommandé

1. **Synchronisation des référentiels** (dans l'ordre)
   - `formation_referentiel`
   - `formation_niveau_referentiel`

2. **Synchronisation des données utilisateurs**
   - `formation_validation` (formations des adhérents)
   - `formation_niveau_validation` (niveaux atteints)

3. **Mise à jour de `formation_last_sync`** après chaque synchronisation réussie

### Points d'attention

- **Identifiant utilisateur** : Utiliser `user_id` (clé étrangère vers `caf_user`). Le CAFNUM peut être récupéré via jointure si nécessaire.
- **Codes uniques** : Les codes FFCAM (`code_formation`) sont les clés de synchronisation principales.
- **Contraintes UNIQUE** : Attention à la contrainte `(user_id, cursus_niveau_id)` pour éviter les doublons.
- **Timestamps** : Les colonnes `created_at` et `updated_at` doivent être gérées côté application (Symfony/Doctrine).

### Exemple de requêtes utiles

```sql
-- Récupérer les formations validées d'un utilisateur avec son CAFNUM
SELECT 
    u.cafnum_user,
    fv.*,
    fr.intitule as intitule_referentiel
FROM formation_validation fv
JOIN caf_user u ON fv.user_id = u.id_user
LEFT JOIN formation_referentiel fr ON fv.code_formation = fr.code_formation
WHERE u.cafnum_user = '0690123456' AND fv.valide = 1;

-- Récupérer les niveaux de pratique d'un utilisateur
SELECT 
    fnv.*,
    fnr.activite,
    fnr.niveau,
    fnr.libelle
FROM formation_niveau_validation fnv
JOIN formation_niveau_referentiel fnr ON fnv.cursus_niveau_id = fnr.id
WHERE fnv.user_id = 12345;
```

## Migration Doctrine

Les tables sont créées via la migration Doctrine `Version20250801180738.php`.

Pour exécuter la migration :
```bash
php bin/console doctrine:migrations:migrate
```