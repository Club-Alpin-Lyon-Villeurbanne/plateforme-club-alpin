<?php

namespace App\Tests\Api;

/**
 * Création d'une formation : un lien facultatif, sans délai d'envoi. Qui peut la demander :
 * NoteHorsSortieAccesTest.
 */
class NoteHorsSortieFormationCreationTest extends ExpenseReportApiTestCase
{
    public function testUnEncadrantDeLaCommissionDemandeUneFormation(): void
    {
        $response = $this->create($this->encadrant, $this->formation(['lienFormation' => 'https://example.org/formation-initiateur']));

        $this->assertResponseStatus(201, $response);
        $this->assertSame('formation', $response['body']['type']);
        $this->assertSame('draft', $response['body']['status']);
        $this->assertSame($this->commissionDeTest->getId(), $response['body']['commission']['id']);
        $this->assertSame('https://example.org/formation-initiateur', $response['body']['lienFormation']);
    }

    public function testUneFormationSansLienEstAcceptee(): void
    {
        $response = $this->create($this->encadrant, $this->formation());

        $this->assertResponseStatus(201, $response);
        $this->assertNull($response['body']['lienFormation']);
    }

    public function testUnLienQuiNEstPasUneAdresseWebEstRefuse(): void
    {
        $this->assertRefused($this->create($this->encadrant, $this->formation(['lienFormation' => 'javascript:alert(1)'])), 'lienFormation');
    }

    public function testUneFormationPlusAncienneQueLeDelaiDesAchatsEstAcceptee(): void
    {
        $date = (new \DateTimeImmutable('today'))->modify(sprintf('-%d days', $this->configHorsSortie()['delaiEnvoiJours']['autre'] + 60));

        $this->assertResponseStatus(201, $this->create($this->encadrant, $this->formation(['dateDepense' => $date->format('Y-m-d')])));
    }

    public function testUneFormationDateeDansLeFuturEstRefusee(): void
    {
        $response = $this->create($this->encadrant, $this->formation(['dateDepense' => (new \DateTimeImmutable('+1 day'))->format('Y-m-d')]));

        $this->assertRefusAvec($response, 'dateDepense', 'La date ne peut pas être dans le futur.');
    }

    // « 12/10/26 » tapé au clavier donne l'an 26 ; une formation n'a pas de délai pour l'arrêter.
    public function testUneFormationDateeAvantLAn2000EstRefuseeALaCreationEtALEnvoi(): void
    {
        $this->assertRefusAvec($this->create($this->encadrant, $this->formation(['dateDepense' => '0026-10-12'])), 'dateDepense', 'Vérifiez l\'année de la date.');
        $this->assertResponseStatus(201, $this->create($this->encadrant, $this->formation(['dateDepense' => '2000-01-01'])));

        $note = $this->creerFormation($this->encadrant, '{"lignes":[{"id":"ligne-1","nature":"voiture","distance":120}]}', new \DateTimeImmutable('0026-10-12'));
        $this->assertRefusAvec($this->envoyer($this->encadrant, $note), 'dateDepense', 'Vérifiez l\'année de la date.');
    }

    public function testMesNotesDeFraisListeLesFormations(): void
    {
        $formation = $this->creerFormation($this->encadrant);

        $response = $this->list($this->encadrant, '/api/notes-de-frais', ['type' => ['autre', 'formation'], 'inclure_brouillons' => 'true']);

        $this->assertResponseStatus(200, $response);
        $notes = array_column($response['body']['data'], null, 'id');
        $this->assertArrayHasKey($formation->getId(), $notes);
        $this->assertSame('formation', $notes[$formation->getId()]['type']);
    }
}
