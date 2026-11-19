<?php

namespace App\Tests\Legacy;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

/**
 * Le legacy redéclare ses fonctions à chaque requête et ne peut donc pas être appelé plusieurs
 * fois dans un même processus de test : ces pages sont vérifiées sur leur code source.
 */
class PagesAdminGardeTest extends TestCase
{
    private const string LEGACY = __DIR__ . '/../../legacy/';

    public static function pagesAdmin(): array
    {
        return [
            'journal en fenêtre' => ['includes/admin-log.php', 'ROLE_ADMIN'],
            'journal en page' => ['pages/admin-log.php', 'ROLE_ADMIN'],
            'arborescence des pages' => ['pages/admin-pages.php', 'ROLE_CONTENT_MANAGER'],
            'ajout de page' => ['pages/admin-pages-add.php', 'ROLE_CONTENT_MANAGER'],
        ];
    }

    #[DataProvider('pagesAdmin')]
    public function testLaPageExigeLeRoleAttendu(string $page, string $role): void
    {
        $source = file_get_contents(self::LEGACY . $page);

        $this->assertStringContainsString('if (!isGranted(SecurityConstants::' . $role . ')) {', $source);
    }

    public function testAucunePageNeDependDeCurrentPage(): void
    {
        $fichiers = (new Finder())->files()->name('*.php')->in(self::LEGACY)->contains('/\$currentPage\[/');

        $this->assertSame([], array_keys(iterator_to_array($fichiers)));
    }
}
