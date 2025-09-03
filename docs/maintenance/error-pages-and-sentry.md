Erreurs en production et Sentry
==============================

Objectifs
---------
- Afficher des pages d'erreur conviviales (sans stack trace) aux utilisateurs.
- Envoyer les erreurs pertinentes vers Sentry (sans bruit: 404/403 ignorées).

Templates d'erreur
------------------
Les pages sont personnalisées sous:
- `templates/bundles/TwigBundle/Exception/error.html.twig`
- `templates/bundles/TwigBundle/Exception/error500.html.twig`
- `templates/bundles/TwigBundle/Exception/error404.html.twig`
- `templates/bundles/TwigBundle/Exception/error403.html.twig`

Sentry (prod)
-------------
La config Sentry ignore par défaut les exceptions 404/403 (voir `config/packages/sentry.yaml`).
Les erreurs sont aussi envoyées via Monolog (`config/packages/prod/monolog.yaml`).

Bonnes pratiques
----------------
- Toujours déployer avec `APP_ENV=prod` et `APP_DEBUG=0`.
- Laisser les détails techniques aux logs/Sentry, pas dans l'UI.
- Ajouter des tags utiles (route, version) si nécessaire via Monolog processors/subscribers.

