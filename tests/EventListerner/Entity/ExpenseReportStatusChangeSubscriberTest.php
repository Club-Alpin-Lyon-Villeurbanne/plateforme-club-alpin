<?php

namespace App\Tests\EventListener\Entity;

use App\Entity\ExpenseReport;
use App\Tests\WebTestCase;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Doctrine\ORM\EntityManagerInterface;

class ExpenseReportStatusChangeSubscriberTest extends WebTestCase
{
    public function testItSendMailOnStatusChangeSubmitted()
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = $this->signup();
        $event = $this->createEvent($user);

        $expenseReport = new ExpenseReport();
        $expenseReport->setUser($user);
        $expenseReport->setStatus(ExpenseReportStatusEnum::DRAFT);
        $expenseReport->setEvent($event);
        $em->persist($expenseReport);
        $em->flush();

        $emails = $this->getMailerMessages();
        $this->assertCount(0, $emails);

        $expenseReport->setStatus(ExpenseReportStatusEnum::SUBMITTED);
        $expenseReport->setDetails(json_encode([]));

        $em->flush();

        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $user->getNickname(), $user->getEmail()));
        $this->assertEmailTextBodyContains($emails[0], 'Si vous avez fait une erreur et souhaitez modifier votre note de frais,');
        $this->assertEmailHtmlBodyContains($emails[0], 'Si vous avez fait une erreur et souhaitez modifier votre note de frais,');
        $this->assertEmailTextBodyContains($emails[0], 'est envoyée à la comptabilité.');
        $this->assertEmailHtmlBodyContains($emails[0], 'a été traitée et est envoyée à la comptabilité.');
    }

    public function testItSendMailOnStatusChangeRejected()
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = $this->signup();
        $event = $this->createEvent($user);

        $expenseReport = new ExpenseReport();
        $expenseReport->setUser($user);
        $expenseReport->setStatus(ExpenseReportStatusEnum::SUBMITTED);
        $expenseReport->setDetails(json_encode([]));
        $expenseReport->setEvent($event);
        $em->persist($expenseReport);
        $em->flush();

        $emails = $this->getMailerMessages();
        $this->assertCount(0, $emails);

        $expenseReport->setStatus(ExpenseReportStatusEnum::REJECTED);
        $em->flush();

        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $user->getNickname(), $user->getEmail()));
        $this->assertEmailTextBodyContains($emails[0], 'a été refusée');
        $this->assertEmailHtmlBodyContains($emails[0], 'a été traitée et a été refusée');
    }

    public function testItSendMailOnStatusChangeApproved()
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = $this->signup();
        $event = $this->createEvent($user);

        $expenseReport = new ExpenseReport();
        $expenseReport->setUser($user);
        $expenseReport->setStatus(ExpenseReportStatusEnum::SUBMITTED);
        $expenseReport->setDetails(json_encode([]));
        $expenseReport->setEvent($event);
        $em->persist($expenseReport);
        $em->flush();

        $emails = $this->getMailerMessages();
        $this->assertCount(0, $emails);

        $expenseReport->setStatus(ExpenseReportStatusEnum::APPROVED);
        $em->flush();

        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $user->getNickname(), $user->getEmail()));
        $this->assertEmailTextBodyContains($emails[0], 'a été acceptée');
        $this->assertEmailHtmlBodyContains($emails[0], 'a été traitée et a été acceptée');
    }
}
