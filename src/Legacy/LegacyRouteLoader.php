<?php

namespace App\Legacy;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class LegacyRouteLoader extends Loader
{
    public function supports($resource, string $type = null): bool
    {
        return 'legacy' === $type;
    }

    public function load($resource, $type = null): mixed
    {
        $collection = new RouteCollection();
        $finder = new Finder();
        $finder
            ->files()
            ->name('*.php')
            ->notPath('app/cron')
            ->notPath('app/mailer')
            ->notPath('app/templates')
            ->notPath('app/versions')
            ->notPath('config')
            ->notPath('doc')
            ->notPath('htmLawed')
            ->notPath('scripts')
            ->notPath('app/redims')
            ->notPath('app/sessions')
            ->notPath('app/includes')
            ->notPath('app/fonctions')
            ->notPath('app/params')
            ->notPath('app/usercookies')
            ->notPath('app/langs')
            ->notPath('app/pages')
            ->notPath('dev.php')
        ;

        /** @var SplFileInfo $legacyScriptFile */
        foreach ($finder->in(__DIR__.'/../../legacy') as $legacyScriptFile) {
            if ('index.php' === $legacyScriptFile->getRelativePathname()) {
                continue;
            }

            $filename = $legacyScriptFile->getRelativePathname();
            $routeName = sprintf('legacy_%s', str_replace('/', '__', $filename));

            $collection->add($routeName, new Route($legacyScriptFile->getRelativePathname(), [
                '_controller' => 'App\Controller\LegacyController::loadLegacyScript',
                'requestPath' => '/'.$legacyScriptFile->getRelativePathname(),
                'legacyScript' => '/legacy/'.$legacyScriptFile->getRelativePathname(),
            ]), -10);
        }

        return $collection;
    }
}
