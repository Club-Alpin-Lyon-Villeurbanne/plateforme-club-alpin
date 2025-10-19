# Fixes pour la Saturation des Connexions DB

## Problème Identifié

L'application saturait le pool de 15 connexions DB sur Clever Cloud, notamment à cause :
1. **Fuites de connexions MySQLi** - jamais fermées
2. **Event listeners déclenchés sur les 404** - ouvrent des connexions DB inutilement
3. **Spam de bots** - POST massifs sur URLs inexistantes

## Correctifs Appliqués

### 1. ✅ MysqliHandler - Fermeture Propre des Connexions

**Fichier**: `src/Utils/MysqliHandler.php`

**Problème**:
- Connexion MySQLi ouverte dans le constructeur
- Jamais fermée → fuite de connexion permanente
- Service singleton → 1 connexion par conteneur même sans requête DB

**Solution**:
```php
public function __destruct()
{
    if ($this->mysqli instanceof \mysqli) {
        $this->mysqli->close();
        $this->mysqli = null;
    }
}
```

### 2. ✅ MysqliHandler - Lazy Loading

**Problème**:
- Connexion ouverte dès l'instanciation du service
- Sur les 404, le container charge le service → connexion ouverte inutilement

**Solution**:
- Retrait de `initializeConnection()` du constructeur
- Connexion ouverte uniquement au premier `query()`, `prepare()`, etc.
- Guard dans `initializeConnection()` pour éviter les connexions multiples

```php
public function __construct(...)
{
    // ...
    // Lazy loading: connection will be initialized on first use
}

private function initializeConnection(): void
{
    if ($this->mysqli instanceof \mysqli) {
        return; // Already connected
    }
    // ... create connection
}

public function query(string $sql)
{
    $this->initializeConnection(); // ← Connexion à la demande
    $result = $this->mysqli->query($sql);
    // ...
}
```

### 3. ✅ AuthenticationListener - Skip DB sur 404

**Fichier**: `src/EventListener/AuthenticationListener.php`

**Problème**:
- Event listener `kernel.response` s'exécute sur TOUTES les réponses
- Fait un `flush()` Doctrine même sur les 404 → connexion DB inutile

**Solution**:
```php
public function onResponse(ResponseEvent $responseEvent)
{
    // Skip DB operations for non-successful responses (404, 500, etc.)
    if (!$responseEvent->getResponse()->isSuccessful()) {
        return;
    }

    // ... reste du code
}
```

## Recommandations Anti-Spam (À Implémenter)

### 🚨 URGENT : Bloquer le Spam AVANT PHP

**Problème**: Les bots spamment des URLs inexistantes → Symfony se charge → connexions DB ouvertes

**Solutions par ordre de priorité**:

#### 1. Rate Limiting au niveau Clever Cloud / Reverse Proxy

Ajouter dans la configuration Clever Cloud :
- Rate limiting par IP : max 100 requêtes/minute
- Bloquer les IPs après X 404 consécutifs
- Whitelist des IPs connues

#### 2. Règles dans `.htaccess` ou nginx

Si accès à la config serveur web, ajouter :

```nginx
# Nginx - Bloquer les requêtes suspectes AVANT PHP
location ~ \.(asp|aspx|jsp|cgi)$ {
    return 404;
}

# Limiter les connexions
limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
limit_req zone=api burst=20;
```

```apache
# Apache .htaccess
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Bloquer les extensions suspectes
    RewriteRule \.(asp|aspx|jsp|cgi)$ - [F,L]

    # Bloquer les bots connus
    RewriteCond %{HTTP_USER_AGENT} (bot|crawler|spider) [NC]
    RewriteCond %{REQUEST_METHOD} POST
    RewriteRule .* - [F,L]
</IfModule>
```

#### 3. Firewall Applicatif dans Symfony

Créer un Event Listener `kernel.request` avec priorité haute :

```php
// src/EventListener/AntiSpamListener.php
class AntiSpamListener implements EventSubscriberInterface
{
    private array $blockedPatterns = [
        '/\.asp$/',
        '/\.aspx$/',
        '/\.jsp$/',
        '/\.cgi$/',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1000], // Haute priorité
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Bloquer les patterns suspects
        foreach ($this->blockedPatterns as $pattern) {
            if (preg_match($pattern, $path)) {
                throw new NotFoundHttpException();
            }
        }

        // Rate limiting simple en cache
        // TODO: implémenter avec Redis/Memcached
    }
}
```

#### 4. Monitoring et Alertes

- Logger les IPs qui génèrent beaucoup de 404
- Alert Sentry si > 50 connexions DB simultanées
- Dashboard de monitoring des connexions DB actives

## Métriques de Succès

Avant les fixes :
- ❌ Saturation à 15/15 connexions
- ❌ MysqliHandler ouvre connexion dès l'instanciation
- ❌ AuthenticationListener flush DB sur tous les 404

Après les fixes :
- ✅ Connexions MySQLi fermées proprement
- ✅ Lazy loading → pas de connexion si pas de requête SQL
- ✅ AuthenticationListener skip DB sur 404
- 🎯 Objectif : < 10 connexions simultanées en moyenne

## Tests de Validation

1. **Test lazy loading**:
```bash
# Requête 404 - ne doit PAS ouvrir de connexion MySQLi
curl -X POST http://localhost/inexistant.asp
# Vérifier logs : aucune connexion MySQLi ouverte
```

2. **Test fermeture connexion**:
```php
// Test unitaire
$handler = new MysqliHandler(...);
$handler->query("SELECT 1"); // Ouvre connexion
unset($handler); // Doit fermer connexion
```

3. **Monitoring production**:
```sql
-- Sur la DB, vérifier connexions actives
SHOW PROCESSLIST;
SHOW STATUS LIKE 'Threads_connected';
```

## Configuration Doctrine (Optionnel)

Si problèmes persistent, ajuster dans `config/packages/doctrine.yaml` :

```yaml
doctrine:
    dbal:
        # Timeouts
        options:
            1002: 30 # PDO::ATTR_TIMEOUT

        # Pool de connexions
        connections:
            default:
                idle_timeout: 60
                max_lifetime: 3600
```

## Checklist de Déploiement

- [x] Fix MysqliHandler destructeur
- [x] Fix MysqliHandler lazy loading
- [x] Fix AuthenticationListener skip 404
- [ ] Activer rate limiting Clever Cloud
- [ ] Implémenter AntiSpamListener
- [ ] Monitoring connexions DB en prod
- [ ] Alertes Sentry si saturation

## Ressources

- [Doctrine Connection Pooling](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html)
- [Symfony Event Dispatcher](https://symfony.com/doc/current/components/event_dispatcher.html)
- [Clever Cloud Rate Limiting](https://www.clever-cloud.com/doc/)
