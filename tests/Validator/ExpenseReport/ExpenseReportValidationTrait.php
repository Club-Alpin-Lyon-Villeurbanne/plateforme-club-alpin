<?php

namespace App\Tests\Validator\ExpenseReport;

use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Security\ExpenseReportActor;
use App\Security\Voter\ExpenseReportVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Context\ExecutionContextFactory;
use Symfony\Component\Validator\Validation;

/**
 * Outils communs aux tests unitaires des validateurs de notes de frais.
 *
 * La note appartient toujours à l'utilisateur 1 ; l'utilisateur connecté est 1 (propriétaire)
 * ou 2 (autre personne) selon l'acteur.
 */
trait ExpenseReportValidationTrait
{
    private static string $owner = 'propriétaire';
    private static string $manager = 'gestionnaire';
    private static string $managerOwner = 'gestionnaire propriétaire';
    private static string $stranger = 'tiers';

    /**
     * @return list<string>
     */
    private static function actors(): array
    {
        return [self::$owner, self::$manager, self::$managerOwner, self::$stranger];
    }

    private function actor(string $actor): ExpenseReportActor
    {
        $isOwner = \in_array($actor, [self::$owner, self::$managerOwner], true);
        $isManager = \in_array($actor, [self::$manager, self::$managerOwner], true);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($this->user($isOwner ? 1 : 2));
        $security->method('isGranted')->willReturnCallback(
            static fn (string $attribute) => ExpenseReportVoter::MANAGE_EXPENSE_REPORTS === $attribute && $isManager
        );

        return new ExpenseReportActor($security);
    }

    private function user(int $id): User
    {
        $user = new User();
        (new \ReflectionProperty(User::class, 'id'))->setValue($user, $id);

        return $user;
    }

    private function existingReport(): ExpenseReport
    {
        $report = new ExpenseReport();
        $report->setId(42);
        $report->setUser($this->user(1));

        return $report;
    }

    private function context(ExpenseReport $report): ExecutionContext
    {
        return (new ExecutionContextFactory(new IdentityTranslator()))->createContext(Validation::createValidator(), $report);
    }

    /**
     * @return list<string>
     */
    private function violationPaths(ExecutionContext $context): array
    {
        $paths = [];
        foreach ($context->getViolations() as $violation) {
            $paths[] = $violation->getPropertyPath();
        }

        return $paths;
    }
}
