<?php

namespace App\Tests\Security\Voter;

use App\Entity\User;
use App\Security\Voter\ExpenseReportVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ExpenseReportVoterTest extends TestCase
{
    use VoterTestHelperTrait;

    public function testDeniesWhenAnonymous(): void
    {
        $voter = new ExpenseReportVoter('1,2,3');

        $res = $voter->vote($this->getToken(null), null, [ExpenseReportVoter::MANAGE_EXPENSE_REPORTS]);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenUserIdInAuthorizedList(): void
    {
        $voter = new ExpenseReportVoter('10,20,30');

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(20);

        $res = $voter->vote($this->getToken($user), null, [ExpenseReportVoter::MANAGE_EXPENSE_REPORTS]);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenUserIdNotInAuthorizedList(): void
    {
        $voter = new ExpenseReportVoter('10,20,30');

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(99);

        $res = $voter->vote($this->getToken($user), null, [ExpenseReportVoter::MANAGE_EXPENSE_REPORTS]);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWithSingleAuthorizedId(): void
    {
        $voter = new ExpenseReportVoter('42');

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(42);

        $res = $voter->vote($this->getToken($user), null, [ExpenseReportVoter::MANAGE_EXPENSE_REPORTS]);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWithEmptyAuthorizedList(): void
    {
        $voter = new ExpenseReportVoter('');

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $res = $voter->vote($this->getToken($user), null, [ExpenseReportVoter::MANAGE_EXPENSE_REPORTS]);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }
}
