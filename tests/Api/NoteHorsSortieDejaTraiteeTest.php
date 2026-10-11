<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReportStatusHistory;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Plusieurs personnes peuvent décider d'une même note hors sortie : celle qui décide quand la note
 * a quitté son étape apprend que la demande a déjà été traitée, et rien n'est enregistré.
 */
class NoteHorsSortieDejaTraiteeTest extends ExpenseReportApiTestCase
{
    private const DEJA_TRAITEE = 'Cette demande a déjà été traitée.';

    /**
     * @dataProvider decisionsTardivesProvider
     */
    public function testDeciderApresUnAutreEstRefuse(string $premierActeur, string $premiere, string $acteur, string $tardive): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        if ('responsable' !== $premierActeur) {
            $this->assertResponseStatus(200, $this->patch($this->responsable, $note, ['status' => 'preapproved']));
        }
        $this->assertResponseStatus(200, $this->patch($this->{$premierActeur}, $note, ['status' => $premiere, 'commentaireStatut' => 'Montant à revoir.']));
        $historique = $this->historique($note->getId());

        // Comme la page et compta-club : une validation n'envoie que le statut, un refus y joint le message.
        $corps = 'rejected' === $tardive ? ['status' => 'rejected', 'commentaireStatut' => 'Il manque un justificatif.'] : ['status' => $tardive];
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
        // Deux responsables reçoivent la note.
        yield 'validation après une validation, co-responsable' => ['responsable', 'preapproved', 'coResponsable', 'preapproved'];
        yield 'renvoi après un renvoi, co-responsable' => ['responsable', 'rejected', 'coResponsable', 'rejected'];
        yield 'validation après un renvoi, co-responsable' => ['responsable', 'rejected', 'coResponsable', 'preapproved'];
        yield 'renvoi après une validation, co-responsable' => ['responsable', 'preapproved', 'coResponsable', 'rejected'];
        // La trésorerie arrive après le refus d'un responsable.
        yield 'validation après un renvoi du responsable, gestionnaire' => ['responsable', 'rejected', 'manager', 'approved'];
        yield 'renvoi après un renvoi du responsable, gestionnaire' => ['responsable', 'rejected', 'manager', 'rejected'];
        // Deux personnes de la trésorerie.
        yield 'validation après une validation, autre gestionnaire' => ['manager', 'approved', 'otherManager', 'approved'];
        yield 'renvoi après un renvoi, autre gestionnaire' => ['manager', 'rejected', 'otherManager', 'rejected'];
        yield 'validation après un renvoi, autre gestionnaire' => ['manager', 'rejected', 'otherManager', 'approved'];
        yield 'renvoi après une validation, autre gestionnaire' => ['manager', 'approved', 'otherManager', 'rejected'];
        // Un responsable qui arrive après la trésorerie.
        yield 'validation après la validation de la trésorerie, co-responsable' => ['manager', 'approved', 'coResponsable', 'preapproved'];
        yield 'renvoi après le renvoi de la trésorerie, co-responsable' => ['manager', 'rejected', 'coResponsable', 'rejected'];
    }

    public function testLaNoteRedevenueEnvoyeeSeDecideANouveau(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $this->assertResponseStatus(200, $this->renvoyer($this->responsable, $note));
        $this->assertResponseStatus(200, $this->envoyer($this->adherent, $note));

        $this->assertResponseStatus(200, $this->patch($this->coResponsable, $note, ['status' => 'preapproved']));
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
