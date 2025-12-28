# Guide de Contribution

## Workflow des branches

```
main                    ← Branche principale de développement
  │                       (déploie auto sur staging via Clever Cloud)
  ├── feature/*         ← Nouvelles fonctionnalités
  ├── fix/*             ← Corrections de bugs
  └── docs/*            ← Documentation
        │
        ▼
production              ← Branche de déploiement en prod
  │
  └── hotfix-prod-*     ← Hotfixes urgents (déployables directement)
```

### Branches principales

| Branche | Rôle | Déploiement |
|---------|------|-------------|
| `main` | Développement, PRs, reviews | Staging (auto via Clever Cloud) |
| `production` | Code en production | Prod Lyon (auto via GitHub Actions) |
| `hotfix-prod-*` | Corrections urgentes | Production (manuel via GitHub Actions) |

### Environnements

| Environnement | URL | Branche | Mécanisme |
|---------------|-----|---------|-----------|
| Staging | https://www.clubalpinlyon.top | `main` | Clever Cloud (auto) |
| Production Lyon | https://www.clubalpinlyon.fr | `production` | GitHub Actions (auto) |

### Workflow standard

1. Créer une branche depuis `main` : `git checkout -b feature/ma-fonctionnalite`
2. Développer et commiter
3. Ouvrir une PR vers `main`
4. Après merge dans `main` → déploiement automatique sur staging (via Clever Cloud)
5. Tester sur staging (clubalpinlyon.top)
6. Déployer en prod : `git checkout production && git merge --ff-only main && git push`

### Hotfixes urgents

Pour les corrections critiques en production :
1. Créer une branche `hotfix-prod-*` depuis `production`
2. Corriger et tester
3. Déployer directement depuis cette branche
4. Merger ensuite dans `main` pour synchroniser

## Avant de Commencer

Avant de commencer à travailler sur une contribution :

1. **Vérifiez le backlog** : Assurez-vous que votre idée est dans le backlog "PRET POUR DEV 🏁" sur ClickUp
2. **Contactez l'équipe** : Discutez de votre proposition avec l'équipe pour valider son alignement avec notre roadmap
3. **Évaluez la complexité** : Assurez-vous que vous avez les compétences nécessaires pour mener à bien la contribution

## Critères de Qualité

Nous maintenons des standards de qualité élevés pour garantir la pérennité du projet. Votre contribution doit :

1. **Processus de Contribution**
   - Toutes les contributions doivent suivre ce guide
   - Les PR doivent être en français en utilisant les termes du [glossaire](glossaire.md)
   - Les modifications visuelles doivent inclure des captures d'écran (avant / après)

2. **Standards Techniques**
   - Code propre et documenté
   - Tests unitaires pour les nouvelles fonctionnalités
   - Respect des conventions de codage existantes
   - Pas de duplication de code
   - Gestion appropriée des erreurs
   - Les tests unitaires doivent passer sur GitHub Actions
   - PHPStan doit passer sans erreurs
   - PHP-CS doit valider le style de code

3. **Sécurité**
   - Pas d'exposition de données sensibles
   - Validation des entrées utilisateur
   - Protection contre les injections
   - Respect des bonnes pratiques de sécurité
   - Validation automatique par GitGuardian sur chaque pull request

4. **Maintenabilité**
   - Documentation claire
   - Nommage explicite
   - Architecture cohérente
   - Pas de dette technique

## Processus de Contribution

1. **Fork du projet** : 
   - Allez sur [https://github.com/Club-Alpin-Lyon-Villeurbanne/plateforme-club-alpin](https://github.com/Club-Alpin-Lyon-Villeurbanne/plateforme-club-alpin)
   - Cliquez sur le bouton "Fork" en haut à droite
   - Clonez votre fork localement
   - Ajoutez le repo original comme upstream : `git remote add upstream git@github.com:Club-Alpin-Lyon-Villeurbanne/plateforme-club-alpin.git`

2. **Création d'une branche** : 
   - Assurez-vous que votre fork est à jour : `git fetch upstream && git checkout main && git merge upstream/main`
   - Créez une nouvelle branche pour votre fonctionnalité ou correction

3. **Modifications** : 
   - Passez le ticket en "EN COURS"
   - Effectuez les modifications en respectant les conventions de codage
   - ⚠️ Vérifiez que le changement est dans le backlog "PRET POUR DEV 🏁" ou validé par l'équipe
   - Exécutez les tests et les outils d'analyse localement avant de pousser

4. **Commit** : 
   - Faites un commit avec une description claire des modifications
   - Poussez sur votre fork : `git push origin votre-branche`

5. **Pull Request** : 
   - Créez une PR depuis votre fork vers le repo original
   - Décrivez vos modifications en français
   - Incluez des captures d'écran pour les modifications visuelles
   - Passez le ticket en "EN REVIEW PAR DEV"
   - Ajoutez le nom de la PR en commentaire
   - Vérifiez que les GitHub Actions passent (tests, PHPStan, PHP-CS)

## Revue et Validation

Toutes les contributions sont soumises à une revue approfondie. Nous pouvons :
- Demander des modifications
- Rejeter une contribution qui ne répond pas à nos critères
- Accepter la contribution si elle répond à tous nos critères

Seule l'équipe informatique peut merger une PR.

## Rôles

Le site comporte deux rôles principaux :

1. **Admin** : Tous les droits, y compris la gestion des permissions importantes
2. **Gestionnaire de contenu** : Modification des pages et blocs de contenu

Accès : https://www.clubalpinlyon.fr/admin/
Identifiants locaux : 
- Admin : `admin` / `admin`
- Gestionnaire de contenu : `admin_contenu` / `contenu` 