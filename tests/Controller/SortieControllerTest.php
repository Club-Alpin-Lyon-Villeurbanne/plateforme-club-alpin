<?php

namespace App\Tests\Controller;

use App\Entity\Evt;
use App\Entity\EventParticipation;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Tests\WebTestCase;

class SortieControllerTest extends WebTestCase
{
    public function testDisplaySortieToOwner()
    {
        $user = $this->signup();
        $this->signin($user);

        $event = $this->createEvent($user);
        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $this->getContainer()->get('doctrine')->getManager()->flush();
        static::$client->request('GET', sprintf('/sortie/%s-%s.html', $event->getCode(), $event->getId()));
        $this->assertResponseStatusCodeSame(200);
    }

    public function testDisplaySortieWithFiliationsAndEmpietementsToOwner()
    {
        $user = $this->signup();
        $user->setCafnum(123456789);
        $this->signin($user);

        $filiated1 = $this->signup()->setCafnumParent($user->getCafnum());
        $filiated2 = $this->signup()->setCafnumParent($user->getCafnum());

        $event = $this->createEvent($user);
        $event2 = $this->createEvent($user);

        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $event2->setStatus(Evt::STATUS_PUBLISHED_VALIDE);

        $this->getContainer()->get('doctrine')->getManager()->flush();
        static::$client->request('GET', sprintf('/sortie/%s-%s.html', $event->getCode(), $event->getId()));
        $this->assertResponseStatusCodeSame(200);
    }

    public function testDisplaySortiPublishedToOwner()
    {
        $user = $this->signup();
        $this->signin($user);

        $event = $this->createEvent($user);
        static::$client->request('GET', sprintf('/sortie/%s-%s.html', $event->getCode(), $event->getId()));
        $this->assertResponseStatusCodeSame(200);
    }

    public function testDisplayUnpublishedSortieOtherUser()
    {
        $user = $this->signup();
        $userOwner = $this->signup();
        $this->signin($user);

        $event = $this->createEvent($userOwner);
        static::$client->request('GET', sprintf('/sortie/%s-%s.html', $event->getCode(), $event->getId()));
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDisplayUnpublishedSortieAdminUser()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $commissionAdmin = $this->signup();
        $this->addAttribute($commissionAdmin, UserAttr::RESPONSABLE_COMMISSION, 'commission:'.$event->getCommission()->getCode());

        $this->signin($commissionAdmin);

        static::$client->request('GET', sprintf('/sortie/%s-%s.html', $event->getCode(), $event->getId()));
        $this->assertResponseStatusCodeSame(200);
    }

    public function testDisplayPublishedSortieOtherUser()
    {
        $user = $this->signup();
        $userOwner = $this->signup();
        $this->signin($user);

        $event = $this->createEvent($userOwner);
        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $this->getContainer()->get('doctrine')->getManager()->flush();
        static::$client->request('GET', sprintf('/sortie/%s-%s.html', $event->getCode(), $event->getId()));
        $this->assertResponseStatusCodeSame(200);
    }

    public function testSortieValidate()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $commissionAdmin = $this->signup();
        $this->addAttribute($commissionAdmin, UserAttr::RESPONSABLE_COMMISSION, 'commission:'.$event->getCommission()->getCode());

        $this->signin($commissionAdmin);

        static::$client->request('POST', sprintf('/sortie/%d/validate', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken(static::$client, 'sortie_validate'),
        ]);
        $this->assertResponseStatusCodeSame(302);

        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $userOwner->getNickname(), $userOwner->getEmail()));
        $this->assertEmailTextBodyContains($emails[0], 'Félicitations, votre sortie');
        $this->assertEmailTextBodyContains($emails[0], 'a été publiée par les responsables.');
        $this->assertEmailHtmlBodyContains($emails[0], 'Félicitations, votre sortie');
        $this->assertEmailHtmlBodyContains($emails[0], 'a été publiée par les responsables.');
    }

    public function testSortieValidateInvalidCsrf()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $commissionAdmin = $this->signup();
        $this->addAttribute($commissionAdmin, UserAttr::RESPONSABLE_COMMISSION, 'commission:'.$event->getCommission()->getCode());

        $this->signin($commissionAdmin);

        static::$client->request('POST', sprintf('/sortie/%d/validate', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken(static::$client, 'invalid_csrf'),
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testSortieValidateNoRight()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $notCommissionAdmin = $this->signup();

        $this->signin($notCommissionAdmin);

        static::$client->request('POST', sprintf('/sortie/%d/validate', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken(static::$client, 'sortie_validate'),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testSortieRefus()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $commissionAdmin = $this->signup();
        $this->addAttribute($commissionAdmin, UserAttr::RESPONSABLE_COMMISSION, 'commission:'.$event->getCommission()->getCode());

        $this->signin($commissionAdmin);

        static::$client->request('POST', sprintf('/sortie/%d/refus', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken(static::$client, 'sortie_refus'),
            'msg' => 'rien ne va plus',
        ]);
        $this->assertResponseStatusCodeSame(302);

        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $userOwner->getNickname(), $userOwner->getEmail()));
        $this->assertEmailTextBodyContains($emails[0], 'Désolé, il semble que votre sortie');
        $this->assertEmailTextBodyContains($emails[0], 'rien ne va plus');
        $this->assertEmailHtmlBodyContains($emails[0], 'Désolé, il semble que votre sortie');
        $this->assertEmailHtmlBodyContains($emails[0], 'rien ne va plus');
    }

    public function testSortieRefusInvalidCsrf()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $commissionAdmin = $this->signup();
        $this->addAttribute($commissionAdmin, UserAttr::RESPONSABLE_COMMISSION, 'commission:'.$event->getCommission()->getCode());

        $this->signin($commissionAdmin);

        static::$client->request('POST', sprintf('/sortie/%d/refus', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken(static::$client, 'invalid_csrf'),
            'msg' => 'rien ne va plus',
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testSortieRefusNoRight()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $commissionAdmin = $this->signup();

        $this->signin($commissionAdmin);

        static::$client->request('POST', sprintf('/sortie/%d/refus', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken(static::$client, 'sortie_refus'),
            'msg' => 'rien ne va plus',
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testSortieLegalValidate()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $president = $this->signup();
        $this->addAttribute($president, UserAttr::PRESIDENT);

        $this->signin($president);

        static::$client->request('POST', sprintf('/sortie/%d/legal-validate', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken(static::$client, 'sortie_legal_validate'),
        ]);
        $this->assertResponseStatusCodeSame(302);

        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $userOwner->getNickname(), $userOwner->getEmail()));
        $this->assertEmailHeaderSame($emails[0], 'Subject', 'Votre sortie a été validée par le président');
        $this->assertEmailTextBodyContains($emails[0], 'Félicitations, votre sortie');
        $this->assertEmailHtmlBodyContains($emails[0], 'Félicitations, votre sortie');
    }

    public function testSortieLegalValidateInvalidCsrf()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $president = $this->signup();
        $this->addAttribute($president, UserAttr::PRESIDENT);

        $this->signin($president);

        static::$client->request('POST', sprintf('/sortie/%d/legal-validate', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken(static::$client, 'invalid_csrf'),
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testSortieLegalValidateNoRights()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $notPresident = $this->signup();

        $this->signin($notPresident);

        static::$client->request('POST', sprintf('/sortie/%d/legal-validate', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken(static::$client, 'sortie_legal_validate'),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testSortieLegalRefus()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $president = $this->signup();
        $this->addAttribute($president, UserAttr::PRESIDENT);

        $this->signin($president);

        static::$client->request('POST', sprintf('/sortie/%d/legal-refus', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken(static::$client, 'sortie_legal_refus'),
        ]);
        $this->assertResponseStatusCodeSame(302);

        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $userOwner->getNickname(), $userOwner->getEmail()));
        $this->assertEmailHeaderSame($emails[0], 'Subject', 'Votre sortie a été refusée par le président');
        $this->assertEmailTextBodyContains($emails[0], 'Désolé, votre sortie');
        $this->assertEmailHtmlBodyContains($emails[0], 'Désolé, votre sortie');
    }

    public function testSortieLegalRefusInvalidCsrf()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $president = $this->signup();
        $this->addAttribute($president, UserAttr::PRESIDENT);

        $this->signin($president);

        static::$client->request('POST', sprintf('/sortie/%d/legal-refus', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken(static::$client, 'invalid_csrf'),
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testSortieLegalRefusNoRight()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $notPresident = $this->signup();

        $this->signin($notPresident);

        static::$client->request('POST', sprintf('/sortie/%d/legal-refus', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken(static::$client, 'sortie_legal_refus'),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testSortieUncancel()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $event->setCancelled(true)
            ->setCancelledWhen(time())
            ->setCancelledWho($userOwner);

        $this->signin($userOwner);
        $this->addAttribute($userOwner, UserAttr::ENCADRANT, 'commission:'.$event->getCommission()->getCode());

        static::$client->request('POST', sprintf('/sortie/%d/uncancel', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken(static::$client, 'sortie_uncancel'),
        ]);
        $this->assertResponseStatusCodeSame(302);

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->refresh($event);

        $this->assertFalse($event->getCancelled());
        $this->assertNull($event->getCancelledWhen());
        $this->assertNull($event->getCancelledWho());
    }

    public function testSortieUncancelInvalidCsrf()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $event->setCancelled(true)
            ->setCancelledWhen(time())
            ->setCancelledWho($userOwner);

        $this->signin($userOwner);
        $this->addAttribute($userOwner, UserAttr::ENCADRANT, 'commission:'.$event->getCommission()->getCode());

        static::$client->request('POST', sprintf('/sortie/%d/uncancel', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken(static::$client, 'invalid_csrf'),
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testSortieUncancelNoRights()
    {
        $userOwner = $this->signup();
        $anotherUser = $this->signup();
        $event = $this->createEvent($userOwner);
        $event->setCancelled(true)
            ->setCancelledWhen(time())
            ->setCancelledWho($userOwner);
        $this->addAttribute($userOwner, UserAttr::ENCADRANT, 'commission:'.$event->getCommission()->getCode());

        $this->signin($anotherUser);

        static::$client->request('POST', sprintf('/sortie/%d/uncancel', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken(static::$client, 'sortie_uncancel'),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testContactParticipants()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $this->addAttribute($userOwner, UserAttr::ENCADRANT, 'commission:'.$event->getCommission()->getCode());

        $this->signin($userOwner);

        static::$client->request('POST', sprintf('/sortie/%d/contact-participants', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken(static::$client, 'contact_participants'),
            'status_sendmail' => '*',
            'objet' => 'un objet de culte',
            'message' => 'tirelipimpon',
        ]);
        $this->assertResponseStatusCodeSame(302);

        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $userOwner->getNickname(), $userOwner->getEmail()));
        $this->assertEmailHeaderSame($emails[0], 'Subject', 'un objet de culte');
        $this->assertEmailTextBodyContains($emails[0], 'Vous avez reçu un message de');
        $this->assertEmailTextBodyContains($emails[0], 'tirelipimpon');
        $this->assertEmailHtmlBodyContains($emails[0], 'Vous avez reçu un message de');
        $this->assertEmailHtmlBodyContains($emails[0], 'tirelipimpon');
    }

    public function testContactParticipantsNoTarget()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $this->addAttribute($userOwner, UserAttr::ENCADRANT, 'commission:'.$event->getCommission()->getCode());

        $this->signin($userOwner);

        static::$client->request('POST', sprintf('/sortie/%d/contact-participants', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken(static::$client, 'contact_participants'),
            'status_sendmail' => EventParticipation::STATUS_REFUSE,
            'objet' => 'un objet de culte',
            'message' => 'tirelipimpon',
        ]);
        $this->assertResponseStatusCodeSame(302);

        $emails = $this->getMailerMessages();
        $this->assertCount(0, $emails);
    }

    public function testContactParticipantsOneTarget()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $this->addAttribute($userOwner, UserAttr::ENCADRANT, 'commission:'.$event->getCommission()->getCode());

        $this->signin($userOwner);

        static::$client->request('POST', sprintf('/sortie/%d/contact-participants', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken(static::$client, 'contact_participants'),
            'status_sendmail' => EventParticipation::STATUS_VALIDE,
            'objet' => 'un objet de culte',
            'message' => 'Prout PROUT',
        ]);
        $this->assertResponseStatusCodeSame(302);

        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $userOwner->getNickname(), $userOwner->getEmail()));
        $this->assertEmailHeaderSame($emails[0], 'Subject', 'un objet de culte');
        $this->assertEmailTextBodyContains($emails[0], 'Vous avez reçu un message de');
        $this->assertEmailTextBodyContains($emails[0], 'Prout PROUT');
        $this->assertEmailHtmlBodyContains($emails[0], 'Vous avez reçu un message de');
        $this->assertEmailHtmlBodyContains($emails[0], 'Prout PROUT');
    }

    private function createEvent(User $user): Evt
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $commission = $this->createCommission();

        $event = new Evt($user, $commission, 'Titre !', 'code', new \DateTime('+7 days'), new \DateTime('+8 days'), 'Hotel de ville', 12, 2, 'Une chtite sortie', time(), 12, 12);
        $em->persist($event);
        $em->flush();

        return $event;
    }
}
