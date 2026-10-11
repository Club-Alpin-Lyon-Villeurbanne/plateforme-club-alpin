<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReportStatusHistory;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Plusieurs personnes peuvent décider d'une même note hors sortie : celle qui décide après une
 * autre apprend que la demande a déjà été traitée, et rien n'est enregistré.
 */
class NoteHorsSortieDejaTraiteeTest extends ExpenseReportApiTestCase
{
    private const DEJA_TRAITEE = 'Cette demande a déjà été traitée.';

    /**
     * @dataProvider decisionsTardivesProvider
     */
    public function testDeciderApresUnAutreEstRefuse(string $premiere, string $tardive, string $acteur): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $this->assertResponseStatus(200, $this->patch($this->responsable, $note, ['status' => $premiere, 'commentaireStatut' => 'Montant à revoir.']));
        $historique = $this->historique($note->getId());

        // Comme la page : « Valider » n'envoie que le statut, « Renvoyer » y joint le message.
        $corps = 'approved' === $tardive ? ['status' => 'approved'] : ['status' => 'rejected', 'commentaireStatut' => 'Il manque un justificatif.'];
        $response = $this->patch($this->{$acteur}, $note, $corps);

        $this->assertResponseStatus(422, $response);
        $this->assertContains(['status', self::DEJA_TRAITEE], array_map(static fn (array $violation) => [$violation['propertyPath'], $violation['message']], $response['body']['violations']));
        $traitee = $this->reload($note);
        $this->assertSame($premiere, $traitee->getStatus()->value);
        $this->assertSame('Montant à revoir.', $traitee->getStatusComment());
        $this->assertSame($historique, $this->historique($note->getId()));
        $this->assertCount(0, $this->getMailerMessages());
    }

    public static function decisionsTardivesProvider(): iterable
    {
        foreach (['co-responsable' => 'coResponsable', 'gestionnaire' => 'manager'] as $nom => $acteur) {
            yield 'validation après une validation, ' . $nom => ['approved', 'approved', $acteur];
            yield 'renvoi après un renvoi, ' . $nom => ['rejected', 'rejected', $acteur];
            yield 'validation après un renvoi, ' . $nom => ['rejected', 'approved', $acteur];
            yield 'renvoi après une validation, ' . $nom => ['approved', 'rejected', $acteur];
        }
    }

    public function testLaNoteRedevenueEnvoyeeSeDecideANouveau(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $this->assertResponseStatus(200, $this->renvoyer($this->responsable, $note));
        $this->assertResponseStatus(200, $this->envoyer($this->adherent, $note));

        $this->assertResponseStatus(200, $this->patch($this->coResponsable, $note, ['status' => 'approved']));
    }

    public function testUneNoteDeSortieGardeSonComportement(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::APPROVED);

        $this->assertResponseStatus(200, $this->patch($this->manager, $report, ['status' => 'approved']));
    }

    /**
     * @return list<array{?string, string, ?int}>
     */
    private function historique(int $noteId): array
    {
        $this->em()->clear();
        $lignes = $this->em()->getRepository(ExpenseReportStatusHistory::class)->findBy(['expenseReport' => $noteId], ['id' => 'ASC']);

        return array_map(static fn (ExpenseReportStatusHistory $ligne) => [$ligne->getOldStatus()?->value, $ligne->getNewStatus()->value, $ligne->getChangedBy()?->getId()], $lignes);
    }
}
