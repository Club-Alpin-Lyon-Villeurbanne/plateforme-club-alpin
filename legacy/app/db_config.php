<?php

use App\Legacy\LegacyContainer;

if (!LegacyContainer::getParameter('legacy_env_SENTRY_DSN')) { 
    throw new \RuntimeException('Missing DB conf.');
}

return LegacyContainer::getParameter('legacy_env_SENTRY_DSN');
