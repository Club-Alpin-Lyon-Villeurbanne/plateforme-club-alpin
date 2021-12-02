<?php

$config = __DIR__.'/../config/config.php';

if (!file_exists($config)) {
    throw new \RuntimeException('Missing DB conf.');
}

return (require $config)['db'];
