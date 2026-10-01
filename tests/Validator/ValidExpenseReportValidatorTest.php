<?php

namespace App\Tests\Validator;

use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidExpenseReportValidatorTest extends KernelTestCase
{
    /**
     * Une note qui a un id mais dont Doctrine ne connaît pas l'état d'origine (entité non gérée)
     * ne doit pas être traitée comme un brouillon : on refuse plutôt que d'ouvrir tous les droits.
     */
    public function testExistingReportWithUnknownOriginalStateIsRefused(): void
    {
        self::bootKernel();
        $owner = new User();
        (new \ReflectionProperty(User::class, 'id'))->setValue($owner, 4242);
        static::getContainer()->get(TokenStorageInterface::class)->setToken(new UsernamePasswordToken($owner, 'api', ['ROLE_USER']));

        $report = new ExpenseReport();
        $report->setId(987654);
        $report->setUser($owner);
        $report->setStatus(ExpenseReportStatusEnum::SUBMITTED);
        $report->setRefundRequired(true);
        $report->setDetails('{"transport":{"type":"PUBLIC_TRANSPORT","ticketPrice":0},"accommodations":[],"others":[]}');

        $violations = static::getContainer()->get(ValidatorInterface::class)->validate($report);

        $paths = [];
        foreach ($violations as $violation) {
            $paths[] = $violation->getPropertyPath();
        }
        $this->assertSame(['status'], $paths);
    }
}
