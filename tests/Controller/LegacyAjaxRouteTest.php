<?php

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class LegacyAjaxRouteTest extends KernelTestCase
{
    public static function fichiersAutorises(): array
    {
        return [
            ['content_html_updatevis'],
            ['contenus_save'],
            ['formater'],
            ['get_content_html'],
            ['operations'],
            ['pages_reorder'],
        ];
    }

    #[DataProvider('fichiersAutorises')]
    public function testLaRouteAccepteLesFichiersDeLaListeAutorisee(string $fichier): void
    {
        $route = self::getContainer()->get('router')->match('/ajax/' . $fichier);

        $this->assertSame('legacy_ajax', $route['_route']);
        $this->assertSame($fichier, $route['file']);
    }

    public function testLaRouteRefuseUnChemin(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        self::getContainer()->get('router')->match('/ajax/app/fonctions');
    }
}
