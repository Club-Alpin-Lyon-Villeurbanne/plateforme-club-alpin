<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReport;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Deux requêtes sur la même note : la seconde écriture ne doit pas passer sur ce que la première a
 * changé depuis sa lecture. Une autre requête est simulée par une seconde connexion à la base,
 * qui écrit entre la lecture et l'écriture de la requête testée.
 */
class NoteDeFraisEcrituresConcurrentesTest extends ExpenseReportApiTestCase
{
    private const CONFLIT = 'Cette note a été modifiée en même temps par une autre action. Rechargez la page.';

    private ?ExpenseReport $noteDeSortie = null;

    protected function tearDown(): void
    {
        if (null !== $this->noteDeSortie) {
            $base = $this->em()->getConnection();
            $base->executeStatement('DELETE FROM expense_report_status_history WHERE expense_report_id = ?', [$this->noteDeSortie->getId()]);
            $base->executeStatement('DELETE FROM expense_report WHERE id = ?', [$this->noteDeSortie->getId()]);
        }

        parent::tearDown();
    }

    public function testUnEnvoiQuiCroiseUneModificationDesLignesEstRefuse(): void
    {
        $note = $this->creerAchat($this->adherent, ExpenseReportStatusEnum::DRAFT, self::LIGNES_ACHAT, $this->commissionDeTest);
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-2');
        $lignesJamaisControlees = '{"lignes":[{"id":"ligne-1","montant":5,"description":"Dégaines"},{"id":"sans-justificatif","montant":0,"description":"Jamais contrôlée"}]}';
        $this->uneAutreRequeteEcritAvant(Events::preFlush, $note, static fn (ExpenseReport $lue) => $lue->setDetails($lignesJamaisControlees));

        $reponse = $this->envoyer($this->adherent, $note);

        $this->assertResponseStatus(409, $reponse);
        $this->assertSame(self::CONFLIT, $reponse['body']['detail']);
        $this->assertSame(['status' => 'draft', 'total_declare' => null, 'details' => $lignesJamaisControlees], $this->ligneEnBase($note));
        $this->assertCount(0, $this->getMailerMessages());
    }

    public function testDeuxDecisionsSimultaneesSurUneNoteDeSortieNeSEcrasentPas(): void
    {
        $this->noteDeSortie = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);
        $this->uneAutreRequeteEcritAvant(Events::preFlush, $this->noteDeSortie, static fn (ExpenseReport $lue) => $lue->setStatus(ExpenseReportStatusEnum::APPROVED));

        $reponse = $this->patch($this->manager, $this->noteDeSortie, ['status' => 'rejected', 'commentaireStatut' => 'Justificatif manquant']);

        $this->assertResponseStatus(409, $reponse);
        $this->assertSame(self::CONFLIT, $reponse['body']['detail']);
        $this->assertSame('approved', $this->ligneEnBase($this->noteDeSortie)['status']);
    }

    public function testUnJustificatifQuiCroiseLEnvoiEstRefuse(): void
    {
        $note = $this->creerAchat($this->adherent, ExpenseReportStatusEnum::DRAFT, self::LIGNES_ACHAT, $this->commissionDeTest);
        $this->uneAutreRequeteEcritAvant(Events::postLoad, $note, static fn (ExpenseReport $lue) => $lue->setStatus(ExpenseReportStatusEnum::SUBMITTED));

        $this->assertResponseStatus(422, $this->uploadAttachment($this->adherent, $note, 'ligne-1'));
        $this->assertSame(0, (int) $this->em()->getConnection()->fetchOne('SELECT COUNT(*) FROM expense_attachment WHERE expense_report_id = ?', [$note->getId()]));
    }

    /**
     * Pendant la prochaine requête, dès que la note est lue (postLoad) ou au moment de l'écrire
     * (preFlush), une autre requête la modifie et enregistre sa modification.
     *
     * @param \Closure(ExpenseReport): mixed $modification
     */
    private function uneAutreRequeteEcritAvant(string $moment, ExpenseReport $note, \Closure $modification): void
    {
        // Sans redémarrage, la prochaine requête garde ce kernel et donc cet écouteur. La note ne doit
        // être connue qu'une fois lue par cette requête : le pare-feu écrit avant (dernière connexion).
        $this->client->disableReboot();
        $em = $this->em();
        $em->clear();
        $em->getEventManager()->addEventListener($moment, new class($em, $note->getId(), $modification) {
            private bool $faite = false;

            public function __construct(private readonly EntityManagerInterface $em, private readonly int $id, private readonly \Closure $modification)
            {
            }

            public function postLoad(PostLoadEventArgs $args): void
            {
                $note = $args->getObject();
                if ($note instanceof ExpenseReport && $note->getId() === $this->id) {
                    $this->ecrire();
                }
            }

            public function preFlush(PreFlushEventArgs $args): void
            {
                // Seulement l'écriture de la note, après sa lecture par la requête.
                if ($args->getObjectManager()->getUnitOfWork()->tryGetById($this->id, ExpenseReport::class) instanceof ExpenseReport) {
                    $this->ecrire();
                }
            }

            private function ecrire(): void
            {
                if ($this->faite) {
                    return;
                }
                $this->faite = true;

                $connexion = DriverManager::getConnection($this->em->getConnection()->getParams(), $this->em->getConnection()->getConfiguration());
                $autre = new EntityManager($connexion, $this->em->getConfiguration(), new EventManager());
                ($this->modification)($autre->find(ExpenseReport::class, $this->id));
                $autre->flush();
                $connexion->close();
            }
        });
    }

    /**
     * Lue directement en base : l'EntityManager de la requête refusée est fermé.
     *
     * @return array{status: string, total_declare: ?string, details: ?string}
     */
    private function ligneEnBase(ExpenseReport $note): array
    {
        $ligne = $this->em()->getConnection()->fetchAssociative('SELECT status, total_declare, details FROM expense_report WHERE id = ?', [$note->getId()]);

        return ['status' => $ligne['status'], 'total_declare' => $ligne['total_declare'], 'details' => json_decode($ligne['details'])];
    }
}
