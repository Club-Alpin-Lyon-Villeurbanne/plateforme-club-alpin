# Instructions pour Claude

## Règles importantes

### Git et commits

- **NE JAMAIS COMMITER SANS REVIEW** : Toujours attendre la validation explicite de l'utilisateur avant de faire un commit
- Préparer les changements avec `git add`
- Montrer le diff ou status pour review
- Attendre l'accord explicite avant `git commit`

### Code

- Ne pas ajouter de commentaires dans le code sauf si explicitement demandé
- Supprimer le support pour IE/MSIE (plus nécessaire)
- Préférer les solutions simples et maintenables

### Tests

- Toujours tester les changements avant de les proposer
- Vérifier qu'il n'y a pas de régression
- Lancer les linters si disponibles

## Contexte du projet

- Application PHP/Symfony pour le Club Alpin Lyon
- Migration jQuery en cours (consolidation des versions)
- Legacy code à moderniser progressivement