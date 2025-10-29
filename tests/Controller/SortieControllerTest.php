<?php

namespace App\Tests\Controller;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\UserAttr;
use App\Messenger\Message\SortiePubliee;
use App\Tests\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\MessageBusInterface;

class SortieControllerTest extends WebTestCase
{
    public function testDisplaySortieToOwner()
    {
        $user = $this->signup();
        $this->signin($user);

        $event = $this->createEvent($user);
        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $event->setStatusWho($user);
        $this->getContainer()->get('doctrine')->getManager()->flush();
        $this->client->request('GET', sprintf('/sortie/%s-%s.html', $event->getCode(), $event->getId()));
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
        $event->setStatusWho($user);
        $event2->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $event->setStatusWho($user);

        $this->getContainer()->get('doctrine')->getManager()->flush();
        $this->client->request('GET', sprintf('/sortie/%s-%s.html', $event->getCode(), $event->getId()));
        $this->assertResponseStatusCodeSame(200);
    }

    public function testDisplaySortiPublishedToOwner()
    {
        $user = $this->signup();
        $this->signin($user);

        $event = $this->createEvent($user);
        $this->client->request('GET', sprintf('/sortie/%s-%s.html', $event->getCode(), $event->getId()));
        $this->assertResponseStatusCodeSame(200);
    }

    public function testDisplayUnpublishedSortieOtherUser()
    {
        $user = $this->signup();
        $userOwner = $this->signup();
        $this->signin($user);

        $event = $this->createEvent($userOwner);
        $this->client->request('GET', sprintf('/sortie/%s-%s.html', $event->getCode(), $event->getId()));
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDisplayUnpublishedSortieAdminUser()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $commissionAdmin = $this->signup();
        $this->addAttribute($commissionAdmin, UserAttr::RESPONSABLE_COMMISSION, 'commission:' . $event->getCommission()->getCode());

        $this->signin($commissionAdmin);

        $this->client->request('GET', sprintf('/sortie/%s-%s.html', $event->getCode(), $event->getId()));
        $this->assertResponseStatusCodeSame(200);
    }

    public function testDisplayPublishedSortieOtherUser()
    {
        $user = $this->signup();
        $userOwner = $this->signup();
        $this->signin($user);

        $event = $this->createEvent($userOwner);
        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $event->setStatusWho($user);
        $this->getContainer()->get('doctrine')->getManager()->flush();
        $this->client->request('GET', sprintf('/sortie/%s-%s.html', $event->getCode(), $event->getId()));
        $this->assertResponseStatusCodeSame(200);
    }

    public function testSortieValidate()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $commissionAdmin = $this->signup();
        $this->addAttribute($commissionAdmin, UserAttr::RESPONSABLE_COMMISSION, 'commission:' . $event->getCommission()->getCode());

        $this->signin($commissionAdmin);

        $this->client->request('POST', sprintf('/sortie/%d/validate', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken($this->client, 'sortie_validate'),
        ]);
        $this->assertResponseStatusCodeSame(302);

        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $userOwner->getNickname(), $userOwner->getEmail()));
        $this->assertEmailTextBodyContains($emails[0], 'Félicitations, votre sortie');
        $this->assertEmailTextBodyContains($emails[0], 'a été approuvée par les responsables.');
        $this->assertEmailHtmlBodyContains($emails[0], 'Félicitations, votre sortie');
        $this->assertEmailHtmlBodyContains($emails[0], 'a été approuvée par les responsables.');

        $messages = self::getContainer()->get(MessageBusInterface::class)->getDispatchedMessages();
        $this->assertCount(2, $messages);
        $this->assertInstanceOf(SortiePubliee::class, $messages[0]['message']);
        $this->assertInstanceOf(SendEmailMessage::class, $messages[1]['message']);
    }

    public function testSortieValidateInvalidCsrf()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $commissionAdmin = $this->signup();
        $this->addAttribute($commissionAdmin, UserAttr::RESPONSABLE_COMMISSION, 'commission:' . $event->getCommission()->getCode());

        $this->signin($commissionAdmin);

        $this->client->request('POST', sprintf('/sortie/%d/validate', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken($this->client, 'invalid_csrf'),
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testSortieValidateNoRight()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $notCommissionAdmin = $this->signup();

        $this->signin($notCommissionAdmin);

        $this->client->request('POST', sprintf('/sortie/%d/validate', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken($this->client, 'sortie_validate'),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testSortieRefus()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $commissionAdmin = $this->signup();
        $this->addAttribute($commissionAdmin, UserAttr::RESPONSABLE_COMMISSION, 'commission:' . $event->getCommission()->getCode());

        $this->signin($commissionAdmin);

        $this->client->request('POST', sprintf('/sortie/%d/refus', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken($this->client, 'sortie_refus'),
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
        $this->addAttribute($commissionAdmin, UserAttr::RESPONSABLE_COMMISSION, 'commission:' . $event->getCommission()->getCode());

        $this->signin($commissionAdmin);

        $this->client->request('POST', sprintf('/sortie/%d/refus', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken($this->client, 'invalid_csrf'),
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

        $this->client->request('POST', sprintf('/sortie/%d/refus', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken($this->client, 'sortie_refus'),
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

        $this->client->request('POST', sprintf('/sortie/%d/legal-validate', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken($this->client, 'sortie_legal_validate'),
        ]);
        $this->assertResponseStatusCodeSame(302);

        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $userOwner->getNickname(), $userOwner->getEmail()));
        $this->assertEmailHeaderSame($emails[0], 'Subject', '[' . $event->getCommission()->getTitle() . '][Sortie validée] ' . $event->getTitre() . ' du ' . $event->getStartDate()->format('d/m/Y'));
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

        $this->client->request('POST', sprintf('/sortie/%d/legal-validate', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken($this->client, 'invalid_csrf'),
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

        $this->client->request('POST', sprintf('/sortie/%d/legal-validate', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken($this->client, 'sortie_legal_validate'),
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

        $this->client->request('POST', sprintf('/sortie/%d/legal-refus', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken($this->client, 'sortie_legal_refus'),
        ]);
        $this->assertResponseStatusCodeSame(302);

        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $userOwner->getNickname(), $userOwner->getEmail()));
        $this->assertEmailHeaderSame($emails[0], 'Subject', '[' . $event->getCommission()->getTitle() . '][Sortie refusée] ' . $event->getTitre() . ' du ' . $event->getStartDate()->format('d/m/Y'));
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

        $this->client->request('POST', sprintf('/sortie/%d/legal-refus', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken($this->client, 'invalid_csrf'),
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

        $this->client->request('POST', sprintf('/sortie/%d/legal-refus', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken($this->client, 'sortie_legal_refus'),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testSortieUncancel()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $event->setCancelled(true)
            ->setCancellationDate(new \DateTimeImmutable())
            ->setCancelledWho($userOwner);

        $this->signin($userOwner);
        $this->addAttribute($userOwner, UserAttr::ENCADRANT, 'commission:' . $event->getCommission()->getCode());

        $this->client->request('POST', sprintf('/sortie/%d/uncancel', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken($this->client, 'sortie_uncancel'),
        ]);
        $this->assertResponseStatusCodeSame(302);

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->refresh($event);

        $this->assertFalse($event->getCancelled());
        $this->assertNull($event->getCancellationDate());
        $this->assertNull($event->getCancelledWho());
    }

    public function testSortieUncancelInvalidCsrf()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $event->setCancelled(true)
            ->setCancellationDate(new \DateTimeImmutable())
            ->setCancelledWho($userOwner);

        $this->signin($userOwner);
        $this->addAttribute($userOwner, UserAttr::ENCADRANT, 'commission:' . $event->getCommission()->getCode());

        $this->client->request('POST', sprintf('/sortie/%d/uncancel', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken($this->client, 'invalid_csrf'),
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testSortieUncancelNoRights()
    {
        $userOwner = $this->signup();
        $anotherUser = $this->signup();
        $event = $this->createEvent($userOwner);
        $event->setCancelled(true)
            ->setCancellationDate(new \DateTimeImmutable())
            ->setCancelledWho($userOwner);
        $this->addAttribute($userOwner, UserAttr::ENCADRANT, 'commission:' . $event->getCommission()->getCode());

        $this->signin($anotherUser);

        $this->client->request('POST', sprintf('/sortie/%d/uncancel', $event->getId()), [
            'csrf_token' => $this->generateCsrfToken($this->client, 'sortie_uncancel'),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testContactParticipants()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $this->addAttribute($userOwner, UserAttr::ENCADRANT, 'commission:' . $event->getCommission()->getCode());

        $this->signin($userOwner);

        $participants = $event->getParticipations(null, null);
        $testParticipantId = $participants[0]->getId();
        $this->client->request('POST', sprintf('/sortie/%d/contact-participants', $event->getId()), [
            'csrf_token_contact' => $this->generateCsrfToken($this->client, 'contact_participants'),
            'contact_participant' => [$testParticipantId => $testParticipantId],
            'objet' => 'un objet de culte',
            'message' => 'tirelipimpon',
        ]);
        $this->assertResponseStatusCodeSame(302);

        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $userOwner->getNickname(), $userOwner->getEmail()));
        $this->assertEmailHeaderSame($emails[0], 'Subject', '[' . $event->getCommission()->getTitle() . '][Message] ' . $event->getTitre() . ' du ' . $event->getStartDate()->format('d/m/Y'));
        $this->assertEmailTextBodyContains($emails[0], 'Vous avez reçu un message de');
        $this->assertEmailTextBodyContains($emails[0], 'tirelipimpon');
        $this->assertEmailHtmlBodyContains($emails[0], 'Vous avez reçu un message de');
        $this->assertEmailHtmlBodyContains($emails[0], 'tirelipimpon');
    }

    public function testContactParticipantsNoTarget()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $this->addAttribute($userOwner, UserAttr::ENCADRANT, 'commission:' . $event->getCommission()->getCode());

        $this->signin($userOwner);

        $this->client->request('POST', sprintf('/sortie/%d/contact-participants', $event->getId()), [
            'csrf_token_contact' => $this->generateCsrfToken($this->client, 'contact_participants'),
            'contact_participant' => [17861268532135 => '17861268532135'],
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
        $this->addAttribute($userOwner, UserAttr::ENCADRANT, 'commission:' . $event->getCommission()->getCode());

        $this->signin($userOwner);

        $participants = $event->getParticipations(null, null);
        $testParticipantId = $participants[0]->getId();
        $this->client->request('POST', sprintf('/sortie/%d/contact-participants', $event->getId()), [
            'csrf_token_contact' => $this->generateCsrfToken($this->client, 'contact_participants'),
            'contact_participant' => [$testParticipantId => $testParticipantId],
            'objet' => 'un objet de culte',
            'message' => 'Prout PROUT',
        ]);
        $this->assertResponseStatusCodeSame(302);

        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $userOwner->getNickname(), $userOwner->getEmail()));
        $this->assertEmailHeaderSame($emails[0], 'Subject', '[' . $event->getCommission()->getTitle() . '][Message] ' . $event->getTitre() . ' du ' . $event->getStartDate()->format('d/m/Y'));
        $this->assertEmailTextBodyContains($emails[0], 'Vous avez reçu un message de');
        $this->assertEmailTextBodyContains($emails[0], 'Prout PROUT');
        $this->assertEmailHtmlBodyContains($emails[0], 'Vous avez reçu un message de');
        $this->assertEmailHtmlBodyContains($emails[0], 'Prout PROUT');
    }

    public function testSortieUpdateInscriptionsAccepte()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $participant = $this->signup();

        $participation = new EventParticipation($event, $participant, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_NON_CONFIRME);
        self::getContainer()->get(EntityManagerInterface::class)->persist($participation);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        self::getContainer()->get(EntityManagerInterface::class)->refresh($event);

        $this->signin($userOwner);
        $this->addAttribute($userOwner, UserAttr::ENCADRANT, 'commission:' . $event->getCommission()->getCode());

        // Update status to valide
        $this->client->request('POST', sprintf('/sortie/%d/update-inscriptions', $event->getId()), [
            'csrf_token_inscriptions' => $this->generateCsrfToken($this->client, 'sortie_update_inscriptions'),
            'id_evt_join' => [$participation->getId()],
            'status_evt_join_' . $participation->getId() => EventParticipation::STATUS_VALIDE,
            'role_evt_join_' . $participation->getId() => EventParticipation::ROLE_INSCRIT,
        ]);

        $this->assertResponseStatusCodeSame(302);

        // Check email
        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $participant->getNickname(), $participant->getEmail()));
        $this->assertEmailHeaderSame($emails[0], 'Subject', '[' . $event->getCommission()->getTitle() . '][Acceptée] Votre demande d\'inscription à ' . $event->getTitre() . ' du ' . $event->getStartDate()->format('d/m/Y'));
        $this->assertEmailTextBodyContains($emails[0], 'confirmé(e)');
        $this->assertEmailHtmlBodyContains($emails[0], 'confirmé(e)');
    }

    public function testSortieUpdateInscriptionsRefuse()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $participant = $this->signup();

        $participation = new EventParticipation($event, $participant, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_NON_CONFIRME);
        self::getContainer()->get(EntityManagerInterface::class)->persist($participation);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        self::getContainer()->get(EntityManagerInterface::class)->refresh($event);

        $this->signin($userOwner);
        $this->addAttribute($userOwner, UserAttr::ENCADRANT, 'commission:' . $event->getCommission()->getCode());

        // Update status to refuse
        $this->client->request('POST', sprintf('/sortie/%d/update-inscriptions', $event->getId()), [
            'csrf_token_inscriptions' => $this->generateCsrfToken($this->client, 'sortie_update_inscriptions'),
            'id_evt_join' => [$participation->getId()],
            'status_evt_join_' . $participation->getId() => EventParticipation::STATUS_REFUSE,
            'role_evt_join_' . $participation->getId() => EventParticipation::ROLE_INSCRIT,
        ]);

        $this->assertResponseStatusCodeSame(302);

        // Check email
        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $participant->getNickname(), $participant->getEmail()));
        $this->assertEmailHeaderSame($emails[0], 'Subject', '[' . $event->getCommission()->getTitle() . '][Refusée] Votre demande d\'inscription à ' . $event->getTitre() . ' du ' . $event->getStartDate()->format('d/m/Y'));
        $this->assertEmailTextBodyContains($emails[0], 'déclinée');
        $this->assertEmailHtmlBodyContains($emails[0], 'déclinée');
    }

    public function testSortieUpdateInscriptionsAbsent()
    {
        $userOwner = $this->signup();
        $event = $this->createEvent($userOwner);
        $participant = $this->signup();

        $participation = new EventParticipation($event, $participant, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE);
        self::getContainer()->get(EntityManagerInterface::class)->persist($participation);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        self::getContainer()->get(EntityManagerInterface::class)->refresh($event);

        $this->signin($userOwner);
        $this->addAttribute($userOwner, UserAttr::ENCADRANT, 'commission:' . $event->getCommission()->getCode());

        // Update status to absent
        $this->client->request('POST', sprintf('/sortie/%d/update-inscriptions', $event->getId()), [
            'csrf_token_inscriptions' => $this->generateCsrfToken($this->client, 'sortie_update_inscriptions'),
            'id_evt_join' => [$participation->getId()],
            'status_evt_join_' . $participation->getId() => EventParticipation::STATUS_ABSENT,
            'role_evt_join_' . $participation->getId() => EventParticipation::ROLE_INSCRIT,
        ]);

        $this->assertResponseStatusCodeSame(302);

        // Check email
        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $participant->getNickname(), $participant->getEmail()));
        $this->assertEmailHeaderSame($emails[0], 'Reply-To', sprintf('"%s" <%s>', $userOwner->getEmail(), $userOwner->getEmail()));
        $this->assertEmailHeaderSame($emails[0], 'Subject', '[' . $event->getCommission()->getTitle() . '][Absent] ' . $event->getTitre() . ' du ' . $event->getStartDate()->format('d/m/Y'));
        $this->assertEmailTextBodyContains($emails[0], 'absent à la sortie');
        $this->assertEmailHtmlBodyContains($emails[0], 'absent à la sortie');
    }
}
