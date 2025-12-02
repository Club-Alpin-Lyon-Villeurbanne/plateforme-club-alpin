<?php

namespace App\Tests\Service;

use App\Entity\Commission;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Entity\Usertype;
use App\Mailer\Mailer;
use App\Repository\UserAttrRepository;
use App\Service\UserRightService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;

class UserRightServiceTest extends TestCase
{
    private function createGenerator(array $items): \Generator
    {
        foreach ($items as $item) {
            yield $item;
        }
    }

    private function buildUser(int $id, string $email, string $firstname, string $lastname, ?string $nickname = null): User
    {
        $user = new User($id);
        $user->setEmail($email);
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setNickname($nickname ?? ($firstname . ' ' . $lastname));

        return $user;
    }

    private function buildUsertype(string $code, string $title, int $hierarchie = 10, bool $limitedToComm = false): Usertype
    {
        $type = new Usertype();
        $type->setCode($code);
        $type->setTitle($title);
        $type->setHierarchie($hierarchie);
        $type->setLimitedToComm($limitedToComm);

        return $type;
    }

    private function buildCommission(string $code, string $title = 'Alpinisme'): Commission
    {
        return new Commission($title, $code, 1);
    }

    public function testNotifyForEncadrantSendsToUserResponsablesAndPresidents(): void
    {
        $actor = $this->buildUser(1, 'actor@example.org', 'Alice', 'Actor', 'Alice');
        $target = $this->buildUser(2, 'target@example.org', 'Bob', 'Target', 'Bob');

        $encadrantType = $this->buildUsertype(UserAttr::ENCADRANT, 'Encadrant', 5, true);
        $userRight = new UserAttr($target, $encadrantType, 'commission:ALPI');

        $commission = $this->buildCommission('ALPI', 'Alpinisme');

        $commissionRepo = $this->createMock(ObjectRepository::class);
        $commissionRepo->method('findOneBy')->with(['code' => 'ALPI'])->willReturn($commission);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(function (string $class) use ($commissionRepo) {
            if (Commission::class === $class) {
                return $commissionRepo;
            }

            return $this->createMock(ObjectRepository::class);
        });

        $respType = $this->buildUsertype(UserAttr::RESPONSABLE_COMMISSION, 'Responsable', 8, true);
        $presType = $this->buildUsertype(UserAttr::PRESIDENT, 'Président', 9, false);

        $respUser = $this->buildUser(3, 'resp@example.org', 'Rick', 'Resp', 'Rick');
        $presUser = $this->buildUser(4, 'pres@example.org', 'Pam', 'Prez', 'Pam');

        $respAttr = new UserAttr($respUser, $respType, 'commission:ALPI');
        $presAttr = new UserAttr($presUser, $presType, null);

        $attrRepo = $this->createMock(UserAttrRepository::class);
        $attrRepo->method('listAllEncadrants')->willReturn($this->createGenerator([$respAttr]));
        $attrRepo->method('listAllManagement')->willReturn($this->createGenerator([$presAttr]));

        $sendCalls = [];
        $mailer = $this->createMock(Mailer::class);
        $mailer->method('send')->willReturnCallback(function ($to, string $template, array $context) use (&$sendCalls) {
            $sendCalls[] = [$to, $template, $context];
        });

        $service = new UserRightService($mailer, $em, $attrRepo);
        $service->notify($userRight, 'ajout', $actor);

        $this->assertCount(3, $sendCalls, 'Expected three emails to be sent');

        [$to1, $tpl1, $ctx1] = $sendCalls[0];
        $this->assertSame($target, $to1);
        $this->assertSame('transactional/droits/ajout-utilisateur', $tpl1);
        $this->assertSame('Encadrant', $ctx1['right_name']);
        $this->assertSame('Alpinisme', $ctx1['commission']);
        $this->assertSame('Alice ACTOR', $ctx1['by_who']);

        $management = \array_slice($sendCalls, 1);
        $templates = array_map(fn ($c) => $c[1], $management);
        $this->assertContains('transactional/droits/ajout-responsables', $templates);
        foreach ($management as [$to, $tpl, $ctx]) {
            $this->assertSame('transactional/droits/ajout-responsables', $tpl);
            $this->assertSame('Encadrant', $ctx['right_name']);
            $this->assertSame('Bob TARGET', $ctx['user_name']);
            $this->assertSame('Alpinisme', $ctx['commission']);
            $this->assertSame('Alice ACTOR', $ctx['by_who']);
        }
    }

    public function testNotifyForAdminSendsOnlyToPresidents(): void
    {
        $actor = $this->buildUser(10, 'actor@example.org', 'Ana', 'Admin', 'Ana');
        $target = $this->buildUser(11, 'target@example.org', 'Tim', 'Target', 'Tim');

        $adminType = $this->buildUsertype(UserAttr::ADMINISTRATEUR, 'Administrateur', 100, false);
        $userRight = new UserAttr($target, $adminType, '');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($this->createMock(ObjectRepository::class));

        $presType = $this->buildUsertype(UserAttr::PRESIDENT, 'Président', 9, false);
        $presUser = $this->buildUser(12, 'pres@example.org', 'Paul', 'Prez', 'Paul');
        $presAttr = new UserAttr($presUser, $presType, '');

        $attrRepo = $this->createMock(UserAttrRepository::class);
        $attrRepo->method('listAllManagement')->willReturn($this->createGenerator([$presAttr]));

        $sendCalls = [];
        $mailer = $this->createMock(Mailer::class);
        $mailer->method('send')->willReturnCallback(function ($to, string $template, array $context) use (&$sendCalls) {
            $sendCalls[] = [$to, $template, $context];
        });

        $service = new UserRightService($mailer, $em, $attrRepo);
        $service->notify($userRight, 'suppression', $actor);

        $this->assertCount(2, $sendCalls);
        [$to1, $tpl1, $ctx1] = $sendCalls[0];
        $this->assertSame($target, $to1);
        $this->assertSame('transactional/droits/suppression-utilisateur', $tpl1);
        $this->assertSame('Administrateur', $ctx1['right_name']);
        $this->assertSame('', $ctx1['commission']);
        $this->assertSame('Ana ADMIN', $ctx1['by_who']);

        [$to2, $tpl2, $ctx2] = $sendCalls[1];
        $this->assertSame('transactional/droits/suppression-responsables', $tpl2);
        $this->assertSame('Administrateur', $ctx2['right_name']);
        $this->assertSame('Tim TARGET', $ctx2['user_name']);
        $this->assertSame('', $ctx2['commission']);
        $this->assertSame('Ana ADMIN', $ctx2['by_who']);
    }
}
