<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class RobotsController
{
    public function __construct(private readonly string $kernelEnvironment = 'dev')
    {
    }

    #[Route(path: '/robots.txt', name: 'robots_txt', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        // In non-prod environments, disallow everything
        if ('prod' !== $this->kernelEnvironment) {
            $content = "User-agent: *\nDisallow: /\n";
            $response = new Response($content);
            $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
            $response->setPublic();
            $response->setMaxAge(300);

            return $response;
        }

        $baseUrl = rtrim($request->getSchemeAndHttpHost(), '/');

        $lines = [
            'User-agent: *',
            '',
            'Disallow: /admin/',
            'Sitemap: ' . $baseUrl . '/sitemap.xml',
        ];

        $content = implode("\n", $lines) . "\n";

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }
}
