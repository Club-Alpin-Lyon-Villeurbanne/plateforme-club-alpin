<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RobotsController
{
    #[Route(path: '/robots.txt', name: 'robots_txt', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $baseUrl = rtrim($request->getSchemeAndHttpHost(), '/');

        $lines = [
            'User-agent: *',
            '',
            'Disallow: /admin/',
            'Disallow: /app/',
            'Disallow: /config/',
            'Disallow: /css/',
            'Disallow: /fonts/',
            'Disallow: /ftp/',
            'Disallow: /img/',
            'Disallow: /IMG/',
            'Disallow: /js/',
            'Disallow: /scripts/',
            'Disallow: /templates/',
            'Disallow: /tools/',
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

