<?php

namespace App\Controller;

use App\Entity\Evt;
use App\Entity\EvtJoin;
use App\Mailer\Mailer;
use App\Repository\EvtJoinRepository;
use App\Repository\EvtRepository;
use App\Repository\ExpenseGroupRepository;
use App\Repository\ExpenseReportRepository;
use App\Repository\ExpenseTypeExpenseFieldTypeRepository;
use App\Repository\UserRepository;
use App\Security\AdminDetector;
use App\Twig\JavascriptGlobalsExtension;
use App\Utils\Enums\ExpenseReportEnum;
use App\Utils\Serialize\ExpenseFieldTypeSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class SortieController extends AbstractController
{
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            EvtRepository::class,
        ]);
    }

    #[Route(name: 'sortie', path: '/sortie/{code}-{id}.html', requirements: ['id' => '\d+', 'code' => '[a-z0-9-]+'], methods: ['GET'], priority: '10')]
    #[Template]
    public function sortie(
        Evt $event, 
        UserRepository $repository,
        EvtJoinRepository $participantRepository,
        ExpenseGroupRepository $expenseGroupRepository,
        ExpenseTypeExpenseFieldTypeRepository $expenseTypeFieldTypeRepository,
        ExpenseReportRepository $expenseReportRepository,
        Environment $twig,
    ) {
        if (!$this->isGranted('SORTIE_VIEW', $event)) {
            throw new AccessDeniedHttpException('Not found');
        }

        $user = $this->getUser();

        $currentExpenseReport = $expenseReportRepository->getExpenseReportByEventAndUser($event->getId(), $user->getId());
        
        // TODO:
        // generate the form from the existing draft expense report if there is one
        if ($currentExpenseReport 
            && $currentExpenseReport->getStatus() === ExpenseReportEnum::STATUS_DRAFT
        ) {
        } else {
            // generate a new empty expense report form structure
        }

        $expenseReportFormGroups = [];
        $expenseGroups = $expenseGroupRepository->findAll();

        // each expense group has a list of expense types
        // each expense type has a list of fields
        foreach ($expenseGroups as $expenseGroup) {
            $expenseReportFormGroups[$expenseGroup->getSlug()] = [
                'name' => $expenseGroup->getName(),
                'slug' => $expenseGroup->getSlug(),
                'type' => $expenseGroup->getType(),
                'expenseTypes' => [],
                'selectedType' => 0,
            ];

            foreach ($expenseGroup->getExpenseTypes() as $expenseType) {
                $fields = $expenseType->getFieldTypes();

                // add the needsJustification property to the field itself
                // (needsJustification comes from the join table)
                foreach ($fields as $field) {
                    $relation = $expenseTypeFieldTypeRepository->findOneBy([
                        'expenseType' => $expenseType,
                        'expenseFieldType' => $field
                    ]);
                    $field->setFlags([
                        'needsJustification' => $relation->getNeedsJustification(),
                        'displayOrder' => $relation->getDisplayOrder(),
                        'isMandatory' => $relation->isMandatory(),
                        'isUsedForTotal' => $relation->isUsedForTotal(),
                    ]);
                }

                // add the type to the group
                $expenseReportFormGroups[$expenseGroup->getSlug()]['expenseTypes'][] = [
                    'expenseTypeId' => $expenseType->getId(),
                    'name' => $expenseType->getName(),
                    'slug' => $expenseType->getSlug(),
                    'fields' => $expenseType->getFieldTypes()->map(
                        function ($expenseFieldType) {
                            return ExpenseFieldTypeSerializer::serialize($expenseFieldType);
                        }
                    )->toArray()
                ];
            }
        }

        $twig->getExtension(JavascriptGlobalsExtension::class)->registerGlobal(
            'enums', ['expenseReportStatuses' => ExpenseReportEnum::getConstants()]
        );
        $twig->getExtension(JavascriptGlobalsExtension::class)->registerGlobal(
            'currentEventId', $event->getId()
        );
        $twig->getExtension(JavascriptGlobalsExtension::class)->registerGlobal(
            'apiBaseUrl', !empty($_ENV['DOMAIN']) ? 'https://'.$_ENV['DOMAIN'] : false
        );

        return [
            'event' => $event,
            'participants' => $participantRepository->getSortedParticipants($event, null, null),
            'filiations' => $user ? $repository->getFiliations($user) : null,
            'empietements' => $participantRepository->getEmpietements($event),
            'expenseReportFormStructure' => $expenseReportFormGroups,
            'currentExpenseReport' => $currentExpenseReport,
        ];
    }

    #[Route(name: 'sortie_validate', path: '/sortie/{id}/validate', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function sortieValidate(Request $request, Evt $event, EntityManagerInterface $em, Mailer $mailer)
    {
        if (!$this->isCsrfTokenValid('sortie_validate', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('SORTIE_VALIDATE', $event)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à celà.');
        }

        $event->setStatus(Evt::STATUS_LEGAL_VALIDE)->setStatusWho($this->getUser());
        $em->flush();

        $mailer->send($event->getUser(), 'transactional/sortie-publiee', [
            'event_name' => $event->getTitre(),
            'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'event_date' => date('d/m/Y', $event->getTsp()),
        ]);

        foreach ($event->getParticipants() as $participant) {
            if ($participant->getUser() === $event->getUser()) {
                // mail already sent
                continue;
            }

            $mailer->send($participant->getUser(), 'transactional/sortie-publiee-inscrit', [
                'author_url' => $this->generateUrl('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'voir-profil/'.$event->getUser()->getId().'.html',
                'author_nickname' => $event->getUser()->getNickname(),
                'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'event_name' => $event->getTitre(),
                'role' => $participant->getRole(),
            ], [], null, $event->getUser()->getEmail());
        }

        $this->addFlash('info', 'La sortie est validée');

        return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
    }

    #[Route(name: 'sortie_update_inscription', path: '/sortie/{id}/update-inscriptions', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function sortieUpdateInscriptions(Request $request, Evt $event, EntityManagerInterface $em, Mailer $mailer)
    {
        if (!$this->isCsrfTokenValid('sortie_update_inscriptions', $request->request->get('csrf_token'))) {
            $this->addFlash('error', 'Jeton de validation invalide.');

            return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
        }

        if (!$this->isGranted('SORTIE_INSCRIPTIONS_MODIFICATION', $event)) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à celà.');

            return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
        }

        $user = $this->getUser();

        foreach ($request->request->all('id_evt_join', []) as $participantId) {
            $status = $request->request->get('status_evt_join_'.$participantId);
            $role = $request->request->get('role_evt_join_'.$participantId);

            if (null === $status) {
                // FIX ME Log something
                continue;
            }

            $status = (int) $status;

            if (null === $participant = $event->getParticipantById($participantId)) {
                continue;
            }

            if ($status < 0) {
                $em->remove($participant);

                continue;
            }

            if ($status === $participant->getStatus() && (null === $role || $role === $participant->getRole())) {
                continue;
            }

            // there can be no role passed in the request
            if ($role) {
                $participant->setRole($role);
            }

            $participant
                ->setStatus($status)
                ->setLastchangeWhen(time())
                ->setLastchangeWho($user)
            ;

            if (!\in_array($status, [EvtJoin::STATUS_VALIDE, EvtJoin::STATUS_REFUSE], true)) {
                continue;
            }

            if ($event->isFinished() || 'on' === $request->request->get('disablemails')) {
                continue;
            }

            if ($participant->getUser()->getNomade()) {
                $statusName = '';
                if (EvtJoin::STATUS_NON_CONFIRME === $status) {
                    $statusName = 'En attente';
                }
                if (EvtJoin::STATUS_VALIDE === $status) {
                    $statusName = 'Inscrit';
                }
                if (EvtJoin::STATUS_REFUSE === $status) {
                    $statusName = 'Refusé';
                }

                $this->addFlash('warning', sprintf('%s %s est un adhérent nomade. Il n\'a pas d\'email et '.
                    'doit être prévenu par téléphone de son nouveau statut : %s. Son téléphone: %s', $participant->getUser()->getFirstname(), $participant->getUser()->getLastname(), $statusName, $participant->getUser()->getTel()));

                continue;
            }

            $toMail = null !== $participant->getAffiliantUserJoin() ? $participant->getAffiliantUserJoin() : $participant->getUser();

            if (!$toMail) {
                continue;
            }

            switch ($participant->getRole()) {
                case EvtJoin::ROLE_ENCADRANT:
                case EvtJoin::ROLE_COENCADRANT:
                    $roleName = $participant->getRole().'(e)';
                    break;
                case EvtJoin::ROLE_BENEVOLE:
                case EvtJoin::ROLE_STAGIAIRE:
                    $roleName = $participant->getRole();
                    break;
                default:
                    $roleName = 'participant(e)';
                    break;
            }

            $context = [
                'role' => $roleName,
                'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'event_name' => $event->getTitre(),
            ];

            if (EvtJoin::STATUS_VALIDE === $status) {
                $mailer->send($toMail, 'transactional/sortie-participation-confirmee', $context);
            }
            if (EvtJoin::STATUS_REFUSE === $status) {
                $mailer->send($toMail, 'transactional/sortie-participation-declinee', $context);
            }
        }

        $em->flush();

        return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
    }

    #[Route(name: 'sortie_refus', path: '/sortie/{id}/refus', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function sortieRefus(Request $request, Evt $event, EntityManagerInterface $em, Mailer $mailer)
    {
        if (!$this->isCsrfTokenValid('sortie_refus', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('SORTIE_VALIDATE', $event)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à celà.');
        }

        $event->setStatus(Evt::STATUS_LEGAL_REFUSE)->setStatusWho($this->getUser());
        $em->flush();

        $mailer->send($event->getUser(), 'transactional/sortie-refusee', [
            'message' => $request->request->get('msg', '...'),
            'event_name' => $event->getTitre(),
            'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'event_date' => date('d/m/Y', $event->getTsp()),
        ]);

        $this->addFlash('info', 'La sortie est refusée');

        return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
    }

    #[Route(name: 'sortie_legal_validate', path: '/sortie/{id}/legal-validate', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function sortieLegalValidate(Request $request, Evt $event, EntityManagerInterface $em, Mailer $mailer)
    {
        if (!$this->isCsrfTokenValid('sortie_legal_validate', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('SORTIE_LEGAL_VALIDATION', $event)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à celà.');
        }

        $event->setStatusLegal(Evt::STATUS_LEGAL_VALIDE)->setStatusLegalWho($this->getUser());
        $em->flush();

        $mailer->send($event->getUser(), 'transactional/sortie-president-validee', [
            'event_name' => $event->getTitre(),
            'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'event_date' => date('d/m/Y', $event->getTsp()),
        ]);

        $this->addFlash('info', 'La sortie est validée légalement');

        return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
    }

    #[Route(name: 'sortie_legal_refus', path: '/sortie/{id}/legal-refus', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function sortieLegalRefus(Request $request, Evt $event, EntityManagerInterface $em, Mailer $mailer)
    {
        if (!$this->isCsrfTokenValid('sortie_legal_refus', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('SORTIE_LEGAL_VALIDATION', $event)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à celà.');
        }

        $event->setStatusLegal(Evt::STATUS_LEGAL_REFUSE)->setStatusLegalWho($this->getUser());
        $em->flush();

        $this->addFlash('info', 'La sortie n\'est pas validée légalement');

        $mailer->send($event->getUser(), 'transactional/sortie-president-refusee', [
            'event_name' => $event->getTitre(),
            'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'event_date' => date('d/m/Y', $event->getTsp()),
        ]);

        return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
    }

    #[Route(name: 'sortie_uncancel', path: '/sortie/{id}/uncancel', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function sortieUncancel(Request $request, Evt $event, EntityManagerInterface $em)
    {
        if (!$this->isCsrfTokenValid('sortie_uncancel', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('SORTIE_UNCANCEL', $event)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à celà.');
        }

        $event
            ->setCancelled(false)
            ->setCancelledWhen(null)
            ->setCancelledWho(null);
        $em->flush();

        $this->addFlash('info', 'La sortie est re-activée');

        return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
    }

    #[Route(name: 'contact_participants', path: '/sortie/{id}/contact-participants', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function contactParticipants(Request $request, Evt $event, Mailer $mailer)
    {
        if (!$this->isCsrfTokenValid('contact_participants', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('SORTIE_CONTACT_PARTICIPANTS', $event)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à celà.');
        }

        $status = $request->request->get('status_sendmail');
        $status = ctype_digit($status) ? (int) $status : $status;

        if (!\in_array($status, ['*', EvtJoin::STATUS_VALIDE, EvtJoin::STATUS_ABSENT, EvtJoin::STATUS_NON_CONFIRME, EvtJoin::STATUS_REFUSE], true)) {
            throw new BadRequestException(sprintf('Invalid status "%s".', $status));
        }

        $participants = $event
            ->getParticipants(null, '*' === $status ? null : $status)
            ->map(fn (EvtJoin $participant) => $participant->getUser())
            ->toArray();

        $mailer->send($participants, 'transactional/message-sortie', [
            'objet' => $request->request->get('objet'),
            'message_author' => sprintf('%s %s', $event->getUser()->getFirstname(), $event->getUser()->getLastname()),
            'url_sortie' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'name_sortie' => $event->getTitre(),
            'message' => $request->request->get('message'),
        ], [], $event->getUser(), $event->getUser()->getEmail());

        $this->addFlash('info', 'Votre message a bien été envoyé.');

        return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
    }

    #[Route(name: 'sortie_remove_participant', path: '/sortie/remove-participant/{id}', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function removeParticipant(Request $request, EvtJoin $participant, EntityManagerInterface $em, Mailer $mailer)
    {
        $event = $participant->getEvt();

        if (!$this->isCsrfTokenValid('remove_participant', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('PARTICIPANT_ANNULATION', $participant)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à celà.');
        }

        $em->remove($participant);
        $em->flush();

        $user = $this->getUser();

        if ($participant->isStatusValide()) {
            $mailer->send($event->getUser(), 'transactional/sortie-desinscription', [
                'username' => $participant->getUser()->getFirstname().' '.$participant->getUser()->getLastname(),
                'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'event_name' => $event->getTitre(),
                'user' => $user,
            ], [], null, $user->getEmail());
        }

        $this->addFlash('info', 'La participation est annulée');

        return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
    }

    #[Route(name: 'sortie_duplicate', path: '/sortie/{id}/duplicate', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function sortieDuplicate(Request $request, Evt $event, EntityManagerInterface $em, Mailer $mailer)
    {
        if (!$this->isGranted('SORTIE_DUPLICATE', $event)) {
            throw new AccessDeniedHttpException('Not found');
        }

        if (!$this->isCsrfTokenValid('sortie_duplicate', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        $newEvent = new Evt(
            $this->getUser(),
            $event->getCommission(),
            '',
            '',
            null,
            null,
            $event->getRdv(),
            $event->getLat(),
            $event->getLong(),
            '',
            null,
            $event->getJoinMax(),
            $event->getNgensMax()
        );
        $em->persist($newEvent);

        foreach ($event->getParticipants() as $participant) {
            if ($participant->getUser() === $newEvent->getUser()) {
                continue;
            }

            $join = $newEvent->addParticipant($participant->getUser(), $participant->getRole(), $participant->getStatus());
            $em->persist($join);
        }

        $em->flush();

        return $this->redirect(sprintf('/creer-une-sortie/%s/update-%d.html', $newEvent->getCommission()->getCode(), $newEvent->getId()));
    }
}
