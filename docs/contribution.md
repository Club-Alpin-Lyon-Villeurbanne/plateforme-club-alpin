# Guide de Contribution

## Workflow des branches

```text
main                    ← Branche principale de développement
  │                       (déploie auto sur staging via Clever Cloud)
  ├── feat/*            ← Nouvelles fonctionnalités
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
| Staging | [clubalpinlyon.top](https://www.clubalpinlyon.top) | `main` | Clever Cloud (auto) |
| Production Lyon | [clubalpinlyon.fr](https://www.clubalpinlyon.fr) | `production` | GitHub Actions (auto) |

### Workflow standard

1. Créer une branche depuis `main` : `git checkout -b feat/ma-fonctionnalite`
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
   - PHPStan doit passer sans erreurs (`make phpstan`)
   - PHP-CS doit valider le style de code (`make php-cs`)
   - Rector ne doit rien avoir à corriger (`make rector`)

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

## Outillage qualité

Tous les outils sont des dépendances de dev, installées par `composer install` et
versionnées dans `composer.lock` : pas d'installation séparée à faire.

| Commande | Rôle |
|---|---|
| `make php-cs` / `make php-cs-fix` | Style de code (PHP-CS-Fixer, préréglage Symfony) |
| `make phpstan` | Analyse statique, **niveau 3** |
| `make phpstan-baseline` | Régénère la baseline après avoir corrigé un lot d'erreurs |
| `make rector` / `make rector-fix` | Modernisation automatique du code |
| `make tests` | Suite PHPUnit |

### La baseline PHPStan

`phpstan-baseline.neon` fige les erreurs qui existaient au moment où le niveau 3 a
été activé : elles n'échouent pas en CI, mais **toute nouvelle erreur échoue**.

La bonne façon de la faire baisser est d'en corriger un lot, puis de lancer
`make phpstan-baseline` et de committer la baseline réduite. Il ne faut jamais
régénérer la baseline pour y faire entrer une erreur qu'on vient d'introduire.

### PHP-CS-Fixer est épinglé

La contrainte est volontairement fixée à `3.87.*`, pas `^3.87`. À partir de 3.95, le
préréglage `@Symfony:risky` embarque `declare_strict_types` en stratégie `remove` :
il retire `declare(strict_types=1)` des fichiers qui en ont un, ce qui fait basculer
leur exécution en coercition de types. La règle est aussi neutralisée explicitement
dans `.php-cs-fixer.dist.php`.

Monter l'outil est possible, mais c'est une PR à part : il faut d'abord décider ce
qu'on veut pour `strict_types` dans le projet (16 fichiers l'ont aujourd'hui, 354 ne
l'ont pas).

### Monter les niveaux

PHPStan comme Rector sont volontairement réglés bas pour que la CI soit verte
dès maintenant. Les monter d'un cran (`level` dans `phpstan.dist.neon`,
`withDeadCodeLevel()` / `withCodeQualityLevel()` dans `rector.php`) se fait dans
une PR dédiée, pour que le diff reste relisible.

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

Identifiants locaux : voir [guide d'installation](installation.md#accès) 