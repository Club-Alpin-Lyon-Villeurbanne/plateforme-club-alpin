<?php

namespace App\Tests\Validator\ExpenseReport;

use App\Service\NoteDeFrais\ValideursNoteHorsSortie;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Validator\ExpenseReport\ExpenseReportOriginalState;
use App\Validator\ExpenseReport\StatusTransitionValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class StatusTransitionValidatorTest extends TestCase
{
    use ExpenseReportValidationTrait;

    /**
     * Seules transitions autorisées, par acteur. Tout le reste est refusé.
     */
    private const ALLOWED = [
        'propriétaire' => ['draft>submitted', 'rejected>submitted'],
        'gestionnaire' => ['submitted>approved', 'submitted>rejected', 'approved>accounted'],
        'gestionnaire propriétaire' => ['draft>submitted', 'rejected>submitted'],
        'tiers' => [],
    ];

    /**
     * @dataProvider transitionProvider
     */
    public function testTransitionIsAllowedOnlyForTheRightActor(string $actor, ExpenseReportStatusEnum $old, ExpenseReportStatusEnum $new, bool $allowed): void
    {
        $report = $this->existingReport();
        $report->setStatus($new);
        $context = $this->context($report);

        $this->validator($actor)->validate($report, $old, $context);

        $this->assertSame($allowed ? [] : ['status'], $this->violationPaths($context));
    }

    public static function transitionProvider(): iterable
    {
        foreach (self::ALLOWED as $actor => $allowedTransitions) {
            foreach (ExpenseReportStatusEnum::cases() as $old) {
                foreach (ExpenseReportStatusEnum::cases() as $new) {
                    if ($old === $new) {
                        continue;
                    }
                    $transition = $old->value . '>' . $new->value;
                    $allowed = \in_array($transition, $allowedTransitions, true);
                    yield sprintf('%s : %s %s', $actor, $transition, $allowed ? 'autorisé' : 'refusé') => [$actor, $old, $new, $allowed];
                }
            }
        }
    }

    /**
     * @dataProvider unchangedStatusProvider
     */
    public function testUnchangedStatusIsNotATransition(string $actor, ExpenseReportStatusEnum $status): void
    {
        $report = $this->existingReport();
        $report->setStatus($status);
        $context = $this->context($report);

        $this->validator($actor)->validate($report, $status, $context);

        $this->assertSame([], $this->violationPaths($context));
    }

    public static function unchangedStatusProvider(): iterable
    {
        foreach (self::actors() as $actor) {
            foreach (ExpenseReportStatusEnum::cases() as $status) {
                yield sprintf('%s, %s inchangé', $actor, $status->value) => [$actor, $status];
            }
        }
    }

    /**
     * Une note de sortie n'a pas de valideurs ni de message obligatoire : la base n'est jamais lue.
     */
    private function validator(string $actor): StatusTransitionValidator
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        return new StatusTransitionValidator($this->actor($actor), new ValideursNoteHorsSortie($entityManager), new ExpenseReportOriginalState($entityManager));
    }
}
