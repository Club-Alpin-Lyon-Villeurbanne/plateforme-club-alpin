<?php

namespace App\Tests\Service;

use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Service\ExpenseReportCalculator;
use App\Utils\Enums\ExpenseReportTypeEnum;
use PHPUnit\Framework\TestCase;

/**
 * Plafond du total d'un achat : la configuration du club n'en a pas encore, il suffit d'en ajouter
 * la valeur. Testé ici parce qu'un test API ne peut pas changer la configuration.
 */
class ExpenseReportCalculatorPlafondsTest extends TestCase
{
    public function testUnPlafondDuTotalDUnAchatSeConfigureSansCode(): void
    {
        $calculator = new ExpenseReportCalculator(['horsSortie' => [
            'formation' => ['tauxKilometrique' => 0.4, 'plafonds' => ['parNature' => [], 'total' => null]],
            'autre' => ['plafonds' => ['parNature' => [], 'total' => 100]],
        ]]);
        $achat = ExpenseReport::nouvelleNoteHorsSortie(new User(), ExpenseReportTypeEnum::AUTRE);
        $achat->setDetails('{"lignes":[{"id":"ligne-1","montant":64.9,"description":"Dégaines"},{"id":"ligne-2","montant":80,"description":"Corde"}]}');

        $this->assertSame(['totalDeclare' => 144.9, 'totalRemboursable' => 100.0], $calculator->calculerNoteHorsSortie($achat));
    }
}
