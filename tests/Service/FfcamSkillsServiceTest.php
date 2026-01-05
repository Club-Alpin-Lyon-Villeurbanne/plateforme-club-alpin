<?php

namespace App\Tests\Service;

use App\Entity\Commission;
use App\Service\FfcamSkillsService;
use PHPUnit\Framework\TestCase;

class FfcamSkillsServiceTest extends TestCase
{
    private FfcamSkillsService $service;

    protected function setUp(): void
    {
        $this->service = new FfcamSkillsService();
    }

    // ============== GET SKILLED COMMISSIONS TESTS ==============

    public function testGetSkilledCommissions(): void
    {
        $commissions = $this->service->getSkilledCommissions();

        $this->assertIsArray($commissions);
        $this->assertCount(16, $commissions);
        $this->assertContains('randonnee', $commissions);
        $this->assertContains('alpinisme', $commissions);
        $this->assertContains('escalade', $commissions);
    }

    public function testGetSkilledCommissionsContainsExpectedCommissions(): void
    {
        $commissions = $this->service->getSkilledCommissions();

        $expected = [
            'randonnee',
            'alpinisme',
            'snowboard-alpin',
            'marche-nordique',
            'snowboard-rando',
            'canyon',
            'escalade',
            'raquette',
            'ski-de-randonnee',
            'ski-de-piste',
            'ski-de-fond',
            'trail',
            'via-ferrata',
            'vtt',
            'environnement',
            'ski-randonnee-nordique',
        ];

        $this->assertEquals($expected, $commissions);
    }

    // ============== GET BREVETS TESTS ==============

    public function testGetBrevetWithNullCommission(): void
    {
        $brevets = $this->service->getBrevets(null);
        $this->assertEmpty($brevets);
    }

    public function testGetBrevetWithRandonneeCommission(): void
    {
        $commission = new Commission('Randonnée', 'randonnee', 1);
        $brevets = $this->service->getBrevets($commission);

        $expected = ['BF3-FC-CO', 'BF3-RA-RM', 'BFM-RA-RM', 'BF2-RA-RAL', 'BF1-RA-RM'];
        $this->assertEquals($expected, $brevets);
    }

    public function testGetBrevetWithAlpinismeCommission(): void
    {
        $commission = new Commission('Alpinisme', 'alpinisme', 1);
        $brevets = $this->service->getBrevets($commission);

        $expected = ['BF3-AL-AL', 'BFM-AL-GV', 'BFM-AL-CG', 'BF2-AL-GVE', 'BF2-AL-GV', 'BF2-AL-CG', 'BF1-AL-AL'];
        $this->assertEquals($expected, $brevets);
    }

    public function testGetBrevetWithEscaladeCommission(): void
    {
        $commission = new Commission('Escalade', 'escalade', 1);
        $brevets = $this->service->getBrevets($commission);

        $this->assertIsArray($brevets);
        $this->assertCount(11, $brevets);
        $this->assertContains('BF3-ES-ES', $brevets);
        $this->assertContains('BF1-ES-SAE', $brevets);
    }

    public function testGetBrevetWithSnowboardAlpinCommission(): void
    {
        $commission = new Commission('Snowboard Alpin', 'snowboard-alpin', 1);
        $brevets = $this->service->getBrevets($commission);

        $expected = ['BF3-SN-NA', 'BRV-BFSU10', 'BRV-BFST10'];
        $this->assertEquals($expected, $brevets);
    }

    public function testGetBrevetWithCanyonCommission(): void
    {
        $commission = new Commission('Canyon', 'canyon', 1);
        $brevets = $this->service->getBrevets($commission);

        $expected = ['BF3-CA-CA', 'BFM-CA-CA', 'BF1-CA-CA'];
        $this->assertEquals($expected, $brevets);
    }

    public function testGetBrevetWithMarcheNordiqueCommission(): void
    {
        $commission = new Commission('Marche Nordique', 'marche-nordique', 1);
        $brevets = $this->service->getBrevets($commission);

        $expected = ['BRV-QFMN10'];
        $this->assertEquals($expected, $brevets);
    }

    public function testGetBrevetWithViaFerrata(): void
    {
        $commission = new Commission('Via Ferrata', 'via-ferrata', 1);
        $brevets = $this->service->getBrevets($commission);

        $expected = ['BF2-ES-VF'];
        $this->assertEquals($expected, $brevets);
    }

    public function testGetBrevetWithUnknownCommission(): void
    {
        $commission = new Commission('Unknown', 'unknown-code', 1);
        $brevets = $this->service->getBrevets($commission);

        $this->assertEmpty($brevets);
    }

    public function testGetBrevetWithInvalidType(): void
    {
        $this->expectException(\TypeError::class);
        $this->service->getBrevets('not-a-commission');
    }

    // ============== GET FORMATIONS TESTS ==============

    public function testGetFormationsWithNullCommission(): void
    {
        $formations = $this->service->getFormations(null);
        $this->assertEmpty($formations);
    }

    public function testGetFormationsWithRandonneeCommission(): void
    {
        $commission = new Commission('Randonnée', 'randonnee', 1);
        $formations = $this->service->getFormations($commission);

        $expected = ['STG-FRA10', 'STG-FRD10', 'FOR-CIRM10', 'FOR-CIRA50', 'STG-FRD20', 'STG-FRM10'];
        $this->assertEquals($expected, $formations);
    }

    public function testGetFormationsWithAlpinismeCommission(): void
    {
        $commission = new Commission('Alpinisme', 'alpinisme', 1);
        $formations = $this->service->getFormations($commission);

        $expected = ['STG-FAL10', 'STG-FAL20', 'STG-FAM10', 'STG-FAT10', 'STG-UFGV10', 'STG-FCG10'];
        $this->assertEquals($expected, $formations);
    }

    public function testGetFormationsWithEscaladeCommission(): void
    {
        $commission = new Commission('Escalade', 'escalade', 1);
        $formations = $this->service->getFormations($commission);

        $this->assertIsArray($formations);
        $this->assertCount(8, $formations);
        $this->assertContains('STG-FEA10', $formations);
        $this->assertContains('STG-FES50', $formations);
    }

    public function testGetFormationsWithCanyonCommission(): void
    {
        $commission = new Commission('Canyon', 'canyon', 1);
        $formations = $this->service->getFormations($commission);

        $expected = ['STG-FCA00', 'FOR-CICA10', 'FOR-CICA20', 'FOR-CICA30', 'STG-FCA30'];
        $this->assertEquals($expected, $formations);
    }

    public function testGetFormationsWithEnvironnementCommission(): void
    {
        $commission = new Commission('Environnement', 'environnement', 1);
        $formations = $this->service->getFormations($commission);

        $expected = ['FOR-CIFC10', 'FOR-CIFC20', 'FOR-CIFC30', 'FOR-CIFC40'];
        $this->assertEquals($expected, $formations);
    }

    public function testGetFormationsWithSkiDeRandonneeCommission(): void
    {
        $commission = new Commission('Ski de Randonnée', 'ski-de-randonnee', 1);
        $formations = $this->service->getFormations($commission);

        $expected = ['FOR-CISM40', 'STG-FSM10', 'STG-FSM20', 'STG-FSM40'];
        $this->assertEquals($expected, $formations);
    }

    public function testGetFormationsWithVTTCommission(): void
    {
        $commission = new Commission('VTT', 'vtt', 1);
        $formations = $this->service->getFormations($commission);

        $expected = ['STG-FVM10', 'STG-FVM20', 'FOR-CIVM10'];
        $this->assertEquals($expected, $formations);
    }

    public function testGetFormationsWithUnknownCommission(): void
    {
        $commission = new Commission('Unknown', 'unknown-code', 1);
        $formations = $this->service->getFormations($commission);

        $this->assertEmpty($formations);
    }

    public function testGetFormationsWithInvalidType(): void
    {
        $this->expectException(\TypeError::class);
        $this->service->getFormations('not-a-commission');
    }

    // ============== INTEGRATION TESTS ==============

    public function testBrevetAndFormationConsistency(): void
    {
        // Test that formations and brevets don't have overlaps for a specific commission
        $commission = new Commission('Escalade', 'escalade', 1);
        $brevets = $this->service->getBrevets($commission);
        $formations = $this->service->getFormations($commission);

        // Get the unique codes
        $allCodes = array_merge($brevets, $formations);
        $uniqueCodes = array_unique($allCodes);

        // They should be all unique (no duplicates between brevets and formations)
        $this->assertCount(count($allCodes), $uniqueCodes);
    }
}
