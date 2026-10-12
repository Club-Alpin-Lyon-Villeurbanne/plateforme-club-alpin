<?php

namespace App\Tests\Api;

use App\Entity\User;

class NoteDeFraisPageDeLaNoteTest extends ExpenseReportApiTestCase
{
    public function testSeulLeDemandeurEstReconnuSurLaPageDeSaNote(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertTrue($this->donneesDeLaPage($this->adherent, $note->getId())['estDemandeur']);
        $this->assertFalse($this->donneesDeLaPage($this->responsable, $note->getId())['estDemandeur']);
        $this->assertFalse($this->donneesDeLaPage($this->manager, $note->getId())['estDemandeur']);
    }

    public function testUneNoteInconnueNAPasDeDemandeur(): void
    {
        $this->assertFalse($this->donneesDeLaPage($this->adherent, 999999999)['estDemandeur']);
    }

    private function donneesDeLaPage(User $utilisateur, int $id): array
    {
        $this->signin($this->em()->find(User::class, $utilisateur->getId()));
        $page = $this->client->request('GET', '/notes-de-frais/' . $id);
        $this->assertResponseIsSuccessful();

        return json_decode($page->filter('#notes-de-frais')->attr('data-donnees'), true);
    }
}
