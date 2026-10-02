<?php

namespace App\Tests\Entity;

use App\Entity\Commune;
use PHPUnit\Framework\TestCase;

class CommuneLabelTest extends TestCase
{
    public function testBuildLabelWithoutLigne5(): void
    {
        $this->assertSame('69510 Messimy', Commune::buildLabel('69510', 'Messimy', null));
        $this->assertSame('69510 Messimy', Commune::buildLabel('69510', 'Messimy', ''));
    }

    public function testBuildLabelWithLigne5(): void
    {
        $this->assertSame(
            '74400 Chamonix (Chamonix-Mont-Blanc)',
            Commune::buildLabel('74400', 'Chamonix', 'Chamonix-Mont-Blanc')
        );
    }

    public function testGetLabelUsesSetters(): void
    {
        $commune = (new Commune())
            ->setCodePostal('69510')
            ->setNomCommune('Messimy')
            ->setLigne5('Hameau');

        $this->assertSame('69510 Messimy (Hameau)', $commune->getLabel());
    }

    public function testGetLabelWhenLigne5NeverSet(): void
    {
        // `ligne5` est typé non-nullable mais la colonne est nullable :
        // getLabel() ne doit pas lever de TypeError quand la propriété n'est pas initialisée.
        $commune = (new Commune())
            ->setCodePostal('38000')
            ->setNomCommune('Grenoble');

        $this->assertSame('38000 Grenoble', $commune->getLabel());
    }
}
