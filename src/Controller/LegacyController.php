<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class LegacyController extends AbstractController
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

            ob_start();
            require $legacyDir.$path;
            ob_end_flush();
        });
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

            ob_start();
            require $legacyDir.$path;
            ob_end_flush();
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

            ob_start();
            require $legacyDir.$path;
            ob_end_flush();
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

            ob_start();
            require $legacyDir.$path;
            ob_end_flush();
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

            ob_start();
            require $legacyDir.$path;
            ob_end_flush();
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
    public function p2Action($p1, $p2, ArticleRepository $articleRepository)
    {
        return new StreamedResponse(function () use ($p1, $p2, $articleRepository) {
            $legacyDir = __DIR__.'/../../legacy/';
            $path = 'index.php';
            $_GET['p1'] = $p1;
            $_GET['p2'] = $current_commission = $p2;

            if ('article' === $p1) {
                $current_commission = null;
                $id_article = \array_slice(explode('-', $p2), -1)[0];
                $article = $articleRepository->find($id_article);
                if ($article && $article->getCommission()) {
                    $current_commission = $article->getCommission()->getCode();
                }
            }

            ob_start();
            require $legacyDir.$path;
            ob_end_flush();
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
            $_GET['p2'] = $current_commission = $p2;
            $_GET['p3'] = $p3;

            ob_start();
            require $legacyDir.$path;
            ob_end_flush();
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
            $_GET['p2'] = $current_commission = $p2;
            $_GET['p3'] = $p3;
            $_GET['p4'] = $p4;

            ob_start();
            require $legacyDir.$path;
            ob_end_flush();
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

                ob_start();
                require $legacyScript;
                ob_end_flush();
            }
        );
    }
}
