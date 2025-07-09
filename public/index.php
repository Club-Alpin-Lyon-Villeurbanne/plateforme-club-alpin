<?php

use App\Kernel;

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    global $kernel;

    // PATCH TEMPORAIRE - Debug Clever Cloud bug 2025-07-09
    
    // Essayer de récupérer APP_ENV de toutes les sources possibles
    $appEnv = $context['APP_ENV'] 
        ?? getenv('APP_ENV') 
        ?? $_ENV['APP_ENV'] 
        ?? $_SERVER['APP_ENV'] 
        ?? null;
    
    // Si toujours vide, forcer prod
    if (empty($appEnv)) {
        error_log('WARNING: APP_ENV is empty or not found, forcing to prod');
        $appEnv = 'prod';
    }
    
    // Même chose pour APP_DEBUG
    $appDebug = $context['APP_DEBUG'] 
        ?? getenv('APP_DEBUG') 
        ?? $_ENV['APP_DEBUG'] 
        ?? $_SERVER['APP_DEBUG'] 
        ?? '0';
    
    $kernel = new Kernel($appEnv, (bool) $appDebug);

    return $kernel;
};
