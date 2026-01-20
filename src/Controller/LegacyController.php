<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

class LegacyController extends AbstractController
{
    public function __construct(protected ManagerRegistry $registry)
    {
    }

    #[Route(path: '/', name: 'legacy_root', methods: ['GET', 'POST'])]
    public function rootAction(): StreamedResponse
    {
        return new StreamedResponse(function () {
            $legacyDir = __DIR__ . '/../../legacy/';
            $path = 'index.php';
            ob_start();
            require $legacyDir . $path;
            ob_end_flush();
        });
    }

    #[Route(path: '/ajax/{file}', name: 'legacy_ajax', requirements: ['p1' => '[a-zA-Z0-9-]+'], methods: ['GET', 'POST'])]
    public function ajaxAction($file): StreamedResponse
    {
        return new StreamedResponse(function () use ($file) {
            $legacyDir = __DIR__ . '/../../legacy/';
            $path = 'index.php';
            $_GET['ajx'] = $file;

            ob_start();
            require $legacyDir . $path;
            ob_end_flush();
        });
    }

    #[Route(path: '/{p1}.html', name: 'legacy_p1', requirements: ['p1' => '[a-zA-Z0-9-]+'], methods: ['GET', 'POST'])]
    public function p1Action($p1): StreamedResponse
    {
        return new StreamedResponse(function () use ($p1) {
            $legacyDir = __DIR__ . '/../../legacy/';
            $path = 'index.php';
            $_GET['p1'] = $p1;

            ob_start();
            require $legacyDir . $path;
            ob_end_flush();
        });
    }

    #[Route(path: '/{p1}/{p2}.html', name: 'legacy_p2', requirements: ['p1' => '[a-zA-Z0-9-]+', 'p2' => '[a-zA-Z0-9-]+'], methods: ['GET', 'POST'])]
    public function p2Action($p1, $p2, ArticleRepository $articleRepository): StreamedResponse
    {
        return new StreamedResponse(function () use ($p1, $p2, $articleRepository) {
            $legacyDir = __DIR__ . '/../../legacy/';
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
            require $legacyDir . $path;
            ob_end_flush();
        });
    }

    #[Route(path: '/{p1}/{p2}/{p3}.html', name: 'legacy_p3', requirements: ['p1' => '[a-zA-Z0-9-]+', 'p2' => '[a-zA-Z0-9-]+', 'p3' => '[a-zA-Z0-9-]+'], methods: ['GET', 'POST'])]
    public function p3Action($p1, $p2, $p3): StreamedResponse
    {
        return new StreamedResponse(function () use ($p1, $p2, $p3) {
            $legacyDir = __DIR__ . '/../../legacy/';
            $path = 'index.php';
            $_GET['p1'] = $p1;
            $_GET['p2'] = $current_commission = $p2;
            $_GET['p3'] = $p3;

            ob_start();
            require $legacyDir . $path;
            ob_end_flush();
        });
    }

    #[Route(path: '/{p1}/{p2}/{p3}/{p4}.html', name: 'legacy_p4', requirements: ['p1' => '[a-zA-Z0-9-]+', 'p2' => '[a-zA-Z0-9-]+', 'p3' => '[a-zA-Z0-9-]+', 'p4' => '[a-zA-Z0-9-]+'], methods: ['GET', 'POST'])]
    public function p4Action($p1, $p2, $p3, $p4): StreamedResponse
    {
        return new StreamedResponse(function () use ($p1, $p2, $p3, $p4) {
            $legacyDir = __DIR__ . '/../../legacy/';
            $path = 'index.php';
            $_GET['p1'] = $p1;
            $_GET['p2'] = $current_commission = $p2;
            $_GET['p3'] = $p3;
            $_GET['p4'] = $p4;

            ob_start();
            require $legacyDir . $path;
            ob_end_flush();
        });
    }

    public function loadLegacyScript(string $requestPath, string $legacyScript): StreamedResponse
    {
        return new StreamedResponse(
            function () use ($requestPath, $legacyScript) {
                $legacyScript = __DIR__ . '/../..' . $legacyScript;
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

    public function getRegistry(): ManagerRegistry
    {
        return $this->registry;
    }
}
