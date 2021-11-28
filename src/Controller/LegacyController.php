<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class LegacyController
{
    /**
     * @Route(
     *     name="legacy_root",
     *     path="/",
     *     methods={"GET", "POST"}
     * )
     */
    public function rootAction()
    {
        return new StreamedResponse(function () {
            $legacyDir = __DIR__.'/../../legacy/';
            $path = 'index.php';

            require $legacyDir.$path;
        });
    }

    /**
     * @Route(
     *     name="legacy_admin_root",
     *     path="/admin/",
     *     methods={"GET"}
     * )
     */
    public function legacyAdminAction()
    {
        return new RedirectResponse('/admin/index.php');
    }

    /**
     * @Route(
     *     name="legacy_rss",
     *     path="/rss.xml",
     *     methods={"GET"}
     * )
     */
    public function rssAction()
    {
        return new StreamedResponse(function () {
            $legacyDir = __DIR__.'/../../legacy/';
            $path = 'rss.php';

            require $legacyDir.$path;
        });
    }

    /**
     * @Route(
     *     name="legacy_img_adresse",
     *     path="/img/adresse-website.png",
     *     methods={"GET"}
     * )
     */
    public function adresseWebsiteAction()
    {
        return new StreamedResponse(function () {
            $legacyDir = __DIR__.'/../../legacy/';
            $path = 'index.php';
            $_GET['cstImg'] = 'adresse-website.png';

            require $legacyDir.$path;
        });
    }

    /**
     * @Route(
     *     name="legacy_img_logo",
     *     path="/img/logo.png",
     *     methods={"GET"}
     * )
     */
    public function logoAction()
    {
        return new StreamedResponse(function () {
            $legacyDir = __DIR__.'/../../legacy/';
            $path = 'index.php';
            $_GET['cstImg'] = 'logo.png';

            require $legacyDir.$path;
        });
    }

    /**
     * @Route(
     *     name="legacy_p1",
     *     path="/{p1}.html",
     *     requirements={
     *         "p1": "[a-zA-Z0-9-]+"
     *     },
     *     methods={"GET", "POST"}
     * )
     */
    public function p1Action($p1)
    {
        return new StreamedResponse(function () use ($p1) {
            $legacyDir = __DIR__.'/../../legacy/';
            $path = 'index.php';
            $_GET['p1'] = $p1;

            require $legacyDir.$path;
        });
    }

    /**
     * @Route(
     *     name="legacy_p2",
     *     path="/{p1}/{p2}.html",
     *     requirements={
     *         "p1": "[a-zA-Z0-9-]+",
     *         "p2": "[a-zA-Z0-9-]+"
     *     },
     *     methods={"GET", "POST"}
     * )
     */
    public function p2Action($p1, $p2)
    {
        return new StreamedResponse(function () use ($p1, $p2) {
            $legacyDir = __DIR__.'/../../legacy/';
            $path = 'index.php';
            $_GET['p1'] = $p1;
            $_GET['p2'] = $p2;

            require $legacyDir.$path;
        });
    }

    /**
     * @Route(
     *     name="legacy_p3",
     *     path="/{p1}/{p2}/{p3}.html",
     *     requirements={
     *         "p1": "[a-zA-Z0-9-]+",
     *         "p2": "[a-zA-Z0-9-]+",
     *         "p3": "[a-zA-Z0-9-]+"
     *     },
     *     methods={"GET", "POST"}
     * )
     */
    public function p3Action($p1, $p2, $p3)
    {
        return new StreamedResponse(function () use ($p1, $p2, $p3) {
            $legacyDir = __DIR__.'/../../legacy/';
            $path = 'index.php';
            $_GET['p1'] = $p1;
            $_GET['p2'] = $p2;
            $_GET['p3'] = $p3;

            require $legacyDir.$path;
        });
    }

    /**
     * @Route(
     *     name="legacy_p4",
     *     path="/{p1}/{p2}/{p3}/{p4}.html",
     *     requirements={
     *         "p1": "[a-zA-Z0-9-]+",
     *         "p2": "[a-zA-Z0-9-]+",
     *         "p3": "[a-zA-Z0-9-]+",
     *         "p4": "[a-zA-Z0-9-]+"
     *     },
     *     methods={"GET", "POST"}
     * )
     */
    public function p4Action($p1, $p2, $p3, $p4)
    {
        return new StreamedResponse(function () use ($p1, $p2, $p3, $p4) {
            $legacyDir = __DIR__.'/../../legacy/';
            $path = 'index.php';
            $_GET['p1'] = $p1;
            $_GET['p2'] = $p2;
            $_GET['p3'] = $p3;
            $_GET['p4'] = $p4;

            require $legacyDir.$path;
        });
    }

    public function loadLegacyScript(string $requestPath, string $legacyScript)
    {
        return new StreamedResponse(
            function () use ($requestPath, $legacyScript) {
                $legacyScript = __DIR__.'/../..'.$legacyScript;
                $_SERVER['PHP_SELF'] = $requestPath;
                $_SERVER['SCRIPT_NAME'] = $requestPath;
                $_SERVER['SCRIPT_FILENAME'] = $legacyScript;

                chdir(\dirname($legacyScript));

                require $legacyScript;
            }
        );
    }
}
