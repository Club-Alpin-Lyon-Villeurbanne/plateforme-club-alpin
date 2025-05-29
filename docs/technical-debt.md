# Dette Technique

Ce document liste les principaux sujets de dette technique identifiés dans le projet. Ces points sont prioritaires pour maintenir la qualité et la maintenabilité du code.

## Migration vers Symfony

### État actuel
- Une partie du code est encore en PHP legacy (avant 2019)
- Certaines fonctionnalités n'ont pas encore été migrées vers Symfony

### Objectifs
- Migrer toutes les fonctionnalités vers Symfony 6.4
- Utiliser les composants Symfony modernes
- Implémenter les bonnes pratiques Symfony

### Impact
- Meilleure maintenabilité
- Code plus sécurisé
- Meilleure performance
- Facilité de recrutement de développeurs

## Nettoyage du JavaScript

### État actuel
- Mélange de jQuery et de JavaScript vanilla
- Pas de tests unitaires
- Utilisation de vieilles versions deprecated de librairies (fancybox, datatables, etc...)

### Objectifs
- Mettre à jour les librairies
- Utiliser le workflow de Vite pour la gestion des assets javascript
- Refactorer le code legacy en modules réutilisables
- Ajouter des tests unitaires
- Standardiser les conventions de codage

### Impact
- Code plus maintenable
- Meilleure performance
- Réduction des bugs
- Meilleure expérience développeur

## Nettoyage du CSS

### État actuel
- CSS non structuré
- Duplication de styles
- Mélange de classes utilitaires et de styles spécifiques
- Pas de système de design cohérent

### Objectifs
- Migrer vers TailwindCSS
- Créer un système de design cohérent
- Supprimer les styles dupliqués
- Documenter les composants réutilisables

### Impact
- Interface utilisateur plus cohérente
- Meilleure maintenabilité
- Réduction de la taille des assets
- Meilleure expérience utilisateur

## Priorisation

1. **Migration Symfony** : Priorité haute
   - Impact sur la sécurité et la maintenabilité
   - Base pour les autres améliorations

2. **Nettoyage JavaScript** : Priorité haute
   - Impact sur la performance et la qualité du code
   - Peut être fait progressivement

3. **Nettoyage CSS** : Priorité faible
   - Impact sur l'expérience utilisateur
   - Peut être fait en parallèle des autres tâches

## Contribution

Si vous souhaitez contribuer à la réduction de cette dette technique :

1. Consultez le [guide de contribution](contribution.md)
2. Contactez l'équipe pour discuter de votre proposition

## Suivi

- Revue trimestrielle de l'état de la dette technique
- Mise à jour de ce document
- Ajustement des priorités si nécessaire 