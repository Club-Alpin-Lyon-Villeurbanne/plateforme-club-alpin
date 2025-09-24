<?php

namespace App\Tests\Service;

use App\Entity\Evt;
use App\Entity\User;
use App\Service\UserLicenseHelper;
use PHPUnit\Framework\TestCase;

class UserLicenseCheckerTest extends TestCase
{
    private UserLicenseHelper $userLicenseChecker;

    protected function setUp(): void
    {
        $this->userLicenseChecker = new UserLicenseHelper();
    }

    /**
     * @dataProvider validLicenseDataProvider
     */
    public function testIsLicenseValidForEventWithValidLicense(
        int $adhesionTimestamp,
        int $eventEndTimestamp,
        bool $expectedResult
    ): void {
        $user = $this->createMockUser($adhesionTimestamp);
        $event = $this->createMockEvent($eventEndTimestamp);

        $result = $this->userLicenseChecker->isLicenseValidForEvent($user, $event);

        $this->assertEquals($expectedResult, $result);
    }

    public function validLicenseDataProvider(): array
    {
        return [
            'Adhésion récente, événement dans la période' => [
                strtotime('2025-01-15'), // Adhésion en janvier 2025 => valide jusqu'au 30 septembre 2025
                strtotime('2025-06-15'), // Événement en juin 2025
                true
            ],
            'Adhésion en début d\'année, événement avant fin de période' => [
                strtotime('2025-01-01'), // Adhésion 1er janvier 2025 => valide jusqu'au 30 septembre 2025
                strtotime('2025-09-29'), // Événement juste avant le 30 septembre
                true
            ],
            'Adhésion ancienne, événement l\'année suivante dans la période' => [
                strtotime('2024-12-01'), // Adhésion en décembre 2024 => valide jusqu'au 30 septembre 2025
                strtotime('2025-08-15'), // Événement en août 2025
                true
            ],
            'Adhésion expirée, événement avant fin de période' => [
                strtotime('2024-01-15'), // Adhésion en janvier 2024 => valide jusqu'au 30 septembre 2024
                strtotime('2025-09-27'), // Événement avant le 30 septembre 2025
                false
            ],
            'Adhésion expirée, événement après la fin de période' => [
                strtotime('2024-01-15'), // Adhésion en janvier 2024 => valide jusqu'au 30 septembre 2024
                strtotime('2025-10-01'), // Événement après le 30 septembre 2025
                false
            ],
            'Adhésion récente, événement loin dans le futur' => [
                strtotime('2024-01-15'), // Adhésion en janvier 2024 => valide jusqu'au 30 septembre 2024
                strtotime('2026-01-01'), // Événement en 2026
                false
            ],
            'Cas limite adhésion expirée : événement exactement le 30 septembre' => [
                strtotime('2024-01-01'), // Adhésion 1er janvier 2024 => valide jusqu'au 30 septembre 2024
                strtotime('2025-09-30'), // Événement le 30 septembre 2025
                false
            ],
            'Cas limite adhésion en cours : événement exactement le 30 septembre' => [
                strtotime('2025-01-01'), // Adhésion 1er janvier 2025 => valide jusqu'au 30 septembre 2025
                strtotime('2025-09-30'), // Événement le 30 septembre 2025
                true
            ],
        ];
    }

    public function testIsLicenseValidForEventWithNullAdhesionDate(): void
    {
        $user = $this->createMockUser(null);
        $event = $this->createMockEvent(strtotime('2025-06-15'));

        $result = $this->userLicenseChecker->isLicenseValidForEvent($user, $event);

        $this->assertFalse($result, 'Un utilisateur sans date d\'adhésion ne devrait pas avoir une licence valide');
    }

    public function testIsLicenseValidForEventWithDifferentYears(): void
    {
        $user = $this->createMockUser(strtotime('2023-11-15'));
        $event = $this->createMockEvent(strtotime('2024-09-15'));

        $result = $this->userLicenseChecker->isLicenseValidForEvent($user, $event);

        $this->assertTrue($result, 'Une adhésion en novembre 2023 devrait être valide jusqu\'au 30 septembre 2024');
    }

    public function testIsLicenseValidForEventWithSameYear(): void
    {
        $user = $this->createMockUser(strtotime('2024-03-16'));
        $event = $this->createMockEvent(strtotime('2024-09-15'));

        $result = $this->userLicenseChecker->isLicenseValidForEvent($user, $event);

        $this->assertTrue($result, 'Une adhésion en mars 2024 devrait être valide jusqu\'au 30 septembre 2024');
    }

    public function testDateCalculationLogic(): void
    {
        $adhesionDate = strtotime('2025-02-15');
        $user = $this->createMockUser($adhesionDate);

        $eventJustBefore = $this->createMockEvent(strtotime('2025-09-29'));
        $eventJustAfter = $this->createMockEvent(strtotime('2025-10-02'));

        $this->assertTrue(
            $this->userLicenseChecker->isLicenseValidForEvent($user, $eventJustBefore),
            'La licence devrait être valide pour événement le 29 septembre 2025'
        );

        $this->assertFalse(
            $this->userLicenseChecker->isLicenseValidForEvent($user, $eventJustAfter),
            'La licence ne devrait pas être valide pour événement le 2 octobre 2025'
        );
    }

    private function createMockUser(?int $dateAdhesion): User
    {
        $user = $this->createMock(User::class);
        $user->method('getDateAdhesion')->willReturn($dateAdhesion);

        return $user;
    }

    private function createMockEvent(int $tspEnd): Evt
    {
        $event = $this->createMock(Evt::class);
        $event->method('getTspEnd')->willReturn($tspEnd);

        return $event;
    }
}
