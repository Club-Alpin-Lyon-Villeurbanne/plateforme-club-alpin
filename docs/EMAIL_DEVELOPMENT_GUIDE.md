# Guide de Développement des Emails

## URLs dans les Templates d'Emails

### ❌ MAUVAISE PRATIQUE

Ne **JAMAIS** utiliser la fonction Twig `url()` directement dans les templates d'emails :

```twig
{# ❌ NE PAS FAIRE ❌ #}
<a href="{{ url('sortie', {code: event.code, id: event.id}) }}">Voir la sortie</a>
```

**Pourquoi ?** La fonction `url()` génère des URLs **relatives** (`/sortie/nom-123.html`) qui ne fonctionnent pas dans les emails envoyés via Brevo ou d'autres services d'emailing.

### ✅ BONNE PRATIQUE

Toujours générer les URLs **absolues** depuis le contrôleur/service et les passer au template :

**Dans le contrôleur/service :**
```php
$mailer->send($user, 'transactional/sortie-publiee', [
    'event_name' => $event->getTitre(),
    'event_url' => $this->generateUrl('sortie',
        ['code' => $event->getCode(), 'id' => $event->getId()],
        UrlGeneratorInterface::ABSOLUTE_URL  // ← IMPORTANT
    ),
]);
```

**Dans le template Twig :**
```twig
{# ✅ CORRECT ✅ #}
<a href="{{ event_url }}">Voir la sortie</a>
```

### Alternative : Fonction Twig `absolute_url()`

Si vous devez absolument générer l'URL dans le template (non recommandé), utilisez :

```twig
{# ⚠️ Acceptable mais non recommandé ⚠️ #}
<a href="{{ absolute_url(path('sortie', {code: event.code, id: event.id})) }}">Voir la sortie</a>
```

## Templates à Corriger

Les templates suivants utilisent actuellement `url()` et doivent être corrigés :

- [ ] `templates/email/transactional/rappel-sortie-a-valider-resp-commission.html.twig`
- [ ] `templates/email/transactional/expense-report-status-email.html.twig`
- [ ] `templates/email/transactional/notification-new-sortie.html.twig`
- [ ] `templates/email/transactional/notification-new-article.html.twig`

## Tests

Avant de merger un nouveau template d'email ou une modification :

1. **Test visuel** : Vérifier que les liens s'affichent avec le domaine complet
2. **Test fonctionnel** : Cliquer sur les liens dans l'email reçu (pas dans la preview)
3. **Grep check** : Vérifier qu'aucun `{{ url(` n'est présent dans le template

```bash
grep -r "{{ url(" templates/email/transactional/
```

## Checklist de Review de PR

Lors de la review d'une PR qui touche aux emails :

- [ ] Les URLs sont générées avec `UrlGeneratorInterface::ABSOLUTE_URL` dans le contrôleur
- [ ] Aucun `{{ url(` n'est utilisé dans les templates email
- [ ] Les URLs commencent par `https://` dans l'email de test
- [ ] Les liens fonctionnent quand on clique dessus depuis l'email reçu

## Ressources

- [Symfony UrlGenerator](https://symfony.com/doc/current/routing.html#generating-urls)
- [Twig absolute_url()](https://symfony.com/doc/current/reference/twig_reference.html#absolute-url)
- Documentation Brevo : Les emails nécessitent des URLs absolues
