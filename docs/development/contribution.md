# Guide de Contribution

## Processus de Contribution

1. **Cloner le répertoire** : Clonez le répertoire sur votre machine locale.
2. **Création d'une branche** : Créez une nouvelle branche pour votre fonctionnalité ou correction.
3. **Modifications** : 
   - Passez le ticket en "EN COURS"
   - Effectuez les modifications en respectant les conventions de codage
   - ⚠️ Vérifiez que le changement est dans le backlog "PRET POUR DEV 🏁" ou validé par l'équipe
4. **Commit** : Faites un commit avec une description claire des modifications.
5. **Push** : Poussez votre branche sur GitHub.
6. **Pull Request** : 
   - Créez une PR en français
   - Incluez des captures d'écran pour les modifications visuelles
   - Passez le ticket en "EN REVIEW PAR DEV"
   - Ajoutez le nom de la PR en commentaire

Seule l'équipe informatique peut merger une PR.

## Rôles

Le site comporte deux rôles principaux :

1. **Admin** : Tous les droits, y compris la gestion des permissions importantes
2. **Gestionnaire de contenu** : Modification des pages et blocs de contenu

Accès : https://www.clubalpinlyon.fr/admin/
Identifiants locaux : 
- Admin : `admin` / `admin`
- Gestionnaire de contenu : `admin_contenu` / `contenu`

## Procédure de hotfix en production

1. **Identifier le dernier commit déployé en production**  
   - Rendez-vous sur le site en production ([www.clubalpinlyon.fr](https://www.clubalpinlyon.fr)).
   - Le hash du dernier commit déployé est affiché en bas à droite de la page.

2. **Créer une branche de hotfix depuis ce commit**
   - Dans votre terminal, exécutez :
     ```sh
     git checkout <hash_commit_prod> -b hotfix-prod-<description>
     ```
   - Remplacez `<hash_commit_prod>` par le hash relevé à l'étape 1, et `<description>` par un nom explicite.

3. **Cherry-pick les commits nécessaires**
   - Pour chaque correctif à appliquer, exécutez :
     ```sh
     git cherry-pick <hash_commit>
     ```
   - Remplacez `<hash_commit>` par le hash du commit à intégrer.

4. **Obtenir une review (optionnel)**
   - Pour faire relire les changements sans créer de confusion :
     - Créez une Pull Request en mode "Draft" avec le préfixe "[REVIEW ONLY - DO NOT MERGE]"
     - Ou partagez directement le lien des commits sur Slack
     - La review doit se concentrer sur la pertinence des commits cherry-pickés et leur ordre
   - ⚠️ Ces PR de review ne doivent JAMAIS être mergées car les changements sont déjà dans main

5. **Pousser la branche sur le remote**
   - Exécutez :
     ```sh
     git push origin hotfix-prod-<description>
     ```

6. **Déclencher le déploiement**
   - Le déploiement en production est autorisé pour les branches `main` et celles commençant par `hotfix-prod-`.
   - La règle de déploiement est :
     ```yaml
     if: github.ref == 'refs/heads/main' || startsWith(github.ref, 'refs/heads/hotfix-prod-')
     ```

7. **Après validation**
   - Une fois le correctif validé et déployé, supprimez la branche hotfix si elle n'est plus utile.
   - **Ne pas ouvrir de Pull Request vers main** : les correctifs sont déjà présents ou seront intégrés via le flux normal.
   - Si une PR de review a été créée, la fermer sans merger avec un commentaire explicatif

**Résumé**
- Toujours partir du commit de prod.
- Toujours nommer la branche `hotfix-prod-...`.
- Seules les branches `main` et `hotfix-prod-*` déclenchent un déploiement en production. 