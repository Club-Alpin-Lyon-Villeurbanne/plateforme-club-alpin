# Workflows GitHub Actions - Documentation Déploiement

## ⚠️ IMPORTANT - Token Clever Cloud

### Caractéristiques du token
- **Type**: Token **NOMINATIF** lié à un compte personnel (pas de compte service disponible)
- **Portée**: Token **UNIQUE et PARTAGÉ** pour :
  - ✅ TOUS les clubs (Lyon, Chambéry, etc.)
  - ✅ TOUS les environnements (dev, staging, production)
- **Validité**: **1 AN maximum**
- **Criticité**: **ÉLEVÉE** - Un seul token pour toute l'infrastructure

### 🔴 Points d'attention critiques
1. **Expiration annuelle obligatoire** : Le token expire automatiquement après 1 an
2. **Départ d'un collaborateur** : Renouveler IMMÉDIATEMENT si le détenteur quitte le projet
3. **Impact global** : Une expiration ou révocation impacte TOUS les déploiements de TOUS les clubs

### 📋 Processus de renouvellement

#### Étapes à suivre
1. **Se connecter** sur [Clever Cloud Console](https://console.clever-cloud.com)
2. **Naviguer** vers `Profile > Tokens`
3. **Créer** un nouveau token et **NOTER LA DATE D'EXPIRATION**
4. **Mettre à jour** les secrets GitHub :
   - `CLEVER_TOKEN`
   - `CLEVER_SECRET`
5. **Documenter** le changement :
   - Mettre à jour ce README avec la nouvelle date
   - Notifier l'équipe via Slack
   - Mettre à jour le registre des accès si existant

#### Secrets à mettre à jour dans GitHub
- Aller dans `Settings > Secrets and variables > Actions`
- Mettre à jour :
  - `CLEVER_TOKEN` : Le token fourni par Clever Cloud
  - `CLEVER_SECRET` : Le secret associé au token

### 📅 Historique des tokens

| Date de création | Date d'expiration | Responsable | Notes |
|-----------------|-------------------|-------------|-------|
| À DOCUMENTER | À DOCUMENTER | À DOCUMENTER | Token actuel |

### 🚨 Alertes et rappels

#### Calendrier recommandé
- **J-30** : Première alerte de renouvellement
- **J-15** : Rappel urgent
- **J-7** : Action critique requise
- **J-1** : Urgence absolue

#### Checklist avant renouvellement
- [ ] Vérifier que vous avez accès à l'organisation Clever Cloud
- [ ] Prévenir l'équipe du renouvellement prévu
- [ ] Planifier une fenêtre de maintenance si nécessaire
- [ ] Préparer un plan de rollback

### 📞 Contacts et support

- **Documentation Clever Cloud** : [Tokens API](https://www.clever-cloud.com/doc/account/tokens-api/)
- **Support Clever Cloud** : support@clever-cloud.com
- **Équipe DevOps CAF** : À DOCUMENTER

---

## Workflows disponibles

### 1. production-deploy.yml
- **Objectif** : Déploiement en production pour Lyon-Villeurbanne
- **Déclenchement** : Manuel (workflow_dispatch)
- **Branches autorisées** : `main` et `hotfix-prod-*`
- **Notifications** : Slack sur le canal #deployments

### 2. playwright.yml
- **Objectif** : Tests end-to-end avec Playwright
- **Déclenchement** : Sur push et pull requests
- **Technologies** : Node.js, Playwright

## Variables d'environnement GitHub

### Variables de repository
- `LAST_DEPLOYED_SHA` : Hash du dernier commit déployé en production

### Secrets requis
- `CLEVER_TOKEN` : Token d'authentification Clever Cloud (voir section ci-dessus)
- `CLEVER_SECRET` : Secret associé au token Clever Cloud
- `SLACK_WEBHOOK_URL` : URL du webhook Slack pour les notifications
- `GITHUB_TOKEN` : Token GitHub (automatiquement fourni)

## Maintenance et monitoring

### Logs Clever Cloud
- [Console Clever Cloud](https://console.clever-cloud.com)
- Les logs sont accessibles dans la section Applications > Logs

### Journal des changements
- [Google Docs - Journal des changements](https://docs.google.com/document/d/1CzbCZnuNkAFWPn365V2vgJNvAouHgLD52IZ6fLw0du0/edit)
- À mettre à jour après chaque déploiement en production

## En cas de problème

1. **Token expiré** : Suivre immédiatement le processus de renouvellement ci-dessus
2. **Échec de déploiement** : Vérifier les logs dans la console Clever Cloud
3. **Problème de permissions** : Vérifier que le token a les bonnes permissions dans l'organisation

---

*Dernière mise à jour : À DOCUMENTER*