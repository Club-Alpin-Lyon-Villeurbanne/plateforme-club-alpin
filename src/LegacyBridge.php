<?php

namespace App;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LegacyBridge
{
    public static function prepareLegacyScript(Request $request, Response $response, string $publicDirectory): ?string
    {
        // If Symfony successfully handled the route, you do not have to do anything.
        if (false === $response->isNotFound()) {
            return null;
        }

        $path = trim($request->getPathInfo(), '/');

        if ('' === $path) {
            $path = 'index.php';
        } elseif (preg_match('/^([a-zA-Z0-9-]+)\.html$/', $path, $matches)) {
            $path = 'index.php';
            $_GET['p1'] = $matches[1];
        } elseif (preg_match('/^([a-zA-Z0-9-]+)\/([a-zA-Z0-9-]+)\.html$/', $path, $matches)) {
            $path = 'index.php';
            $_GET['p1'] = $matches[1];
            $_GET['p2'] = $matches[2];
        } elseif (preg_match('/^([a-zA-Z0-9-]+)\/([a-zA-Z0-9-]+)\/([a-zA-Z0-9-]+)\.html$/', $path, $matches)) {
            $path = 'index.php';
            $_GET['p1'] = $matches[1];
            $_GET['p2'] = $matches[2];
            $_GET['p3'] = $matches[3];
        } elseif (preg_match('/^([a-zA-Z0-9-]+)\/([a-zA-Z0-9-]+)\/([a-zA-Z0-9-]+)\/([a-zA-Z0-9-]+)\.html$/', $path, $matches)) {
            $path = 'index.php';
            $_GET['p1'] = $matches[1];
            $_GET['p2'] = $matches[2];
            $_GET['p3'] = $matches[3];
            $_GET['p4'] = $matches[4];
        } elseif (preg_match('/^img\/(adresse-website\.png|logo\.png)$/', $path, $matches)) {
            $path = 'index.php';
            $_GET['cstImg'] = $matches[1];
        } elseif (preg_match('/^rss\.xml$/', $path, $matches)) {
            $path = 'rss.php';
        }

        if (
            0 === strpos('app/cron', $path) ||
            0 === strpos('app/templates', $path) ||
            0 === strpos('app/versions', $path) ||
            0 === strpos('config/', $path) ||
            0 === strpos('doc/', $path) ||
            0 === strpos('htmLawed/', $path) ||
            0 === strpos('scripts/', $path) ||
            preg_match('/app\/[a-z]+\.php]/', $path) > 0
        ) {
            throw new NotFoundHttpException();
        }

        $legacyDir = __DIR__.'/../legacy/';

        if (is_dir($legacyDir.$path) && is_file($legacyDir.$path.'/index.php')) {
            $path .= '/index.php';
        }

        $legacyScriptFilename = $legacyDir.$path;

        if (!file_exists($legacyScriptFilename)) {
            throw new NotFoundHttpException(sprintf('Path "%s" (%s) not found.', $path, $request->getPathInfo()));
        }

        return $legacyScriptFilename;
    }
}
