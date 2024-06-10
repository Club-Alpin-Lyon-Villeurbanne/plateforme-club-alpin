<?php

namespace App\Controller;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Mailer\Mailer;
use App\Repository\EventParticipationRepository;
use App\Repository\EvtRepository;
use App\Repository\ExpenseGroupRepository;
use App\Repository\ExpenseReportRepository;
use App\Repository\ExpenseTypeExpenseFieldTypeRepository;
use App\Repository\UserRepository;
use App\Twig\JavascriptGlobalsExtension;
use App\Utils\Enums\ExpenseReportEnum;
use App\Utils\Serialize\ExpenseFieldTypeSerializer;
use App\Utils\Serialize\ExpenseReportSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
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
    #[Template('sortie/sortie.html.twig')]
    public function sortie(
        Evt $event,
        UserRepository $repository,
        EventParticipationRepository $participationRepository,
        ExpenseGroupRepository $expenseGroupRepository,
        ExpenseTypeExpenseFieldTypeRepository $expenseTypeFieldTypeRepository,
        ExpenseReportRepository $expenseReportRepository,
        ExpenseReportSerializer $expenseReportSerializer,
        Environment $twig
    ) {
        if (!$this->isGranted('SORTIE_VIEW', $event)) {
            throw new AccessDeniedHttpException('Not found');
        }

        $user = $this->getUser();

        // generate a new empty expense report form structure
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
                $fields = array_map(function ($expenseFieldTypeRelation) {
                    return $expenseFieldTypeRelation->getExpenseFieldType();
                }, $expenseType->getExpenseFieldTypeRelations()->toArray());

                // add the needsJustification property to the field itself
                // (needsJustification comes from the join table)
                foreach ($fields as $field) {
                    $relation = $expenseTypeFieldTypeRepository->findOneBy([
                        'expenseType' => $expenseType,
                        'expenseFieldType' => $field,
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
                    'fields' => array_map(function ($expenseFieldType) {
                        return ExpenseFieldTypeSerializer::serialize($expenseFieldType);
                    }, $fields),
                ];
            }
        }
        $currentExpenseReport = $event && $user ? $expenseReportRepository->getExpenseReportByEventAndUser($event->getId(), $user->getId()) : null;

        // prefill the form with the current expense report data
        if ($currentExpenseReport
            && \in_array($currentExpenseReport->getStatus(),
                [ExpenseReportEnum::STATUS_DRAFT, ExpenseReportEnum::STATUS_REJECTED], true
            )
        ) {
            // serialize the current expense report
            $currentExpenseReport = $expenseReportSerializer->serialize($currentExpenseReport);

            $expenseReportFormGroups['refundRequired'] = $currentExpenseReport['refundRequired'] ? 1 : 0;
            // for each expense group
            foreach ($currentExpenseReport['expenseGroups'] as $groupSlug => $expenseGroup) {
                // set the selected expense type
                if (!empty($expenseGroup['selectedType'])) {
                    $expenseReportFormGroups[$groupSlug]['selectedType'] = $expenseGroup['selectedType'];
                }

                // for each expense type
                foreach ($expenseGroup as $expense) {
                    // ignore values that are not expenses
                    if (!\is_array($expense)) {
                        continue;
                    }

                    // for each field
                    $newFields = [];
                    foreach ($expense['fields'] as $field) {
                        // set the value from the current expense report if existing
                        $newField = $field->jsonSerialize();
                        // add the field type flags to this field
                        $relation = $expenseTypeFieldTypeRepository->findOneBy([
                            'expenseType' => $expense['expenseType']->getId(),
                            'expenseFieldType' => $field->getFieldType()->getId(),
                        ]);
                        $newField['fieldTypeId'] = $field->getFieldType()->getId();
                        $newField['flags']['needsJustification'] = $relation->getNeedsJustification();
                        $newField['flags']['isMandatory'] = $relation->isMandatory();
                        $newField['flags']['isUsedForTotal'] = $relation->isUsedForTotal();
                        $newField['flags']['displayOrder'] = $relation->getDisplayOrder();
                        $newField['name'] = $field->getFieldType()->getName();
                        $newField['slug'] = $field->getFieldType()->getSlug();
                        $newFields[] = $newField;
                    }
                    $targetExpenseTypeIndex = array_search($expense['expenseType']->getId(), array_column($expenseReportFormGroups[$groupSlug]['expenseTypes'], 'expenseTypeId'), true);
                    $expenseReportFormGroups[$groupSlug]['expenseTypes'][$targetExpenseTypeIndex]['fields'] = $newFields;
                }
            }
        }

        $twig->getExtension(JavascriptGlobalsExtension::class)->registerGlobal(
            'enums', ['expenseReportStatuses' => ExpenseReportEnum::getConstants()]
        );
        $twig->getExtension(JavascriptGlobalsExtension::class)->registerGlobal(
            'currentEventId', $event->getId()
        );

        $apiBaseUrl = false;
        if ($this->getParameter('router_context_host')) {
            $apiBaseUrl = $this->getParameter('router_context_scheme')
                . '://' . $this->getParameter('router_context_host')
                . ($this->getParameter('router_context_port') ? ':' . $this->getParameter('router_context_port') : '');
        }

        $twig->getExtension(JavascriptGlobalsExtension::class)->registerGlobal(
            'apiBaseUrl', $apiBaseUrl
        );

        return [
            'event' => $event,
            'participations' => $participationRepository->getSortedParticipations($event, null, null),
            'filiations' => $user ? $repository->getFiliations($user) : null,
            'empietements' => $participationRepository->getEmpietements($event),
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

        foreach ($event->getParticipations() as $participation) {
            if ($participation->getUser() === $event->getUser()) {
                // mail already sent
                continue;
            }

            $mailer->send($participation->getUser(), 'transactional/sortie-publiee-inscrit', [
                'author_url' => $this->generateUrl('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'voir-profil/' . $event->getUser()->getId() . '.html',
                'author_nickname' => $event->getUser()->getNickname(),
                'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'event_name' => $event->getTitre(),
                'role' => $participation->getRole(),
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

        foreach ($request->request->all('id_evt_join', []) as $participationId) {
            $status = $request->request->get('status_evt_join_' . $participationId);
            $role = $request->request->get('role_evt_join_' . $participationId);

            if (null === $status) {
                // FIX ME Log something
                continue;
            }

            $status = (int) $status;

            if (null === $participation = $event->getParticipationById($participationId)) {
                continue;
            }

            if ($status < 0) {
                $em->remove($participation);

                continue;
            }

            if ($status === $participation->getStatus() && (null === $role || $role === $participation->getRole())) {
                continue;
            }

            // there can be no role passed in the request
            if ($role) {
                $participation->setRole($role);
            }

            $participation
                ->setStatus($status)
                ->setLastchangeWhen(time())
                ->setLastchangeWho($user)
            ;

            if (!\in_array($status, [EventParticipation::STATUS_VALIDE, EventParticipation::STATUS_REFUSE], true)) {
                continue;
            }

            if ($event->isFinished() || 'on' === $request->request->get('disablemails')) {
                continue;
            }

            if ($participation->getUser()->getNomade()) {
                $statusName = '';
                if (EventParticipation::STATUS_NON_CONFIRME === $status) {
                    $statusName = 'En attente';
                }
                if (EventParticipation::STATUS_VALIDE === $status) {
                    $statusName = 'Inscrit';
                }
                if (EventParticipation::STATUS_REFUSE === $status) {
                    $statusName = 'Refusé';
                }

                $this->addFlash('warning', sprintf('%s %s est un adhérent nomade. Il n\'a pas d\'email et ' .
                    'doit être prévenu par téléphone de son nouveau statut : %s. Son téléphone: %s', $participation->getUser()->getFirstname(), $participation->getUser()->getLastname(), $statusName, $participation->getUser()->getTel()));

                continue;
            }

            $toMail = null !== $participation->getAffiliantUserJoin() ? $participation->getAffiliantUserJoin() : $participation->getUser();

            if (!$toMail) {
                continue;
            }

            switch ($participation->getRole()) {
                case EventParticipation::ROLE_ENCADRANT:
                case EventParticipation::ROLE_COENCADRANT:
                    $roleName = $participation->getRole() . '(e)';
                    break;
                case EventParticipation::ROLE_BENEVOLE:
                case EventParticipation::ROLE_STAGIAIRE:
                    $roleName = $participation->getRole();
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

            if (EventParticipation::STATUS_VALIDE === $status) {
                $mailer->send($toMail, 'transactional/sortie-participation-confirmee', $context);
            }
            if (EventParticipation::STATUS_REFUSE === $status) {
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

        if (!\in_array($status, ['*', EventParticipation::STATUS_VALIDE, EventParticipation::STATUS_ABSENT, EventParticipation::STATUS_NON_CONFIRME, EventParticipation::STATUS_REFUSE], true)) {
            throw new BadRequestException(sprintf('Invalid status "%s".', $status));
        }

        $participations = $event
            ->getParticipations(null, '*' === $status ? null : $status)
            ->map(fn (EventParticipation $participation) => $participation->getUser())
            ->toArray();

        $mailer->send($participations, 'transactional/message-sortie', [
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
    public function removeParticipant(Request $request, EventParticipation $participation, EntityManagerInterface $em, Mailer $mailer)
    {
        $event = $participation->getEvt();

        if (!$this->isCsrfTokenValid('remove_participant', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('PARTICIPANT_ANNULATION', $participation)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à celà.');
        }

        $em->remove($participation);
        $em->flush();

        $user = $this->getUser();

        if ($participation->isStatusValide()) {
            $mailer->send($event->getUser(), 'transactional/sortie-desinscription', [
                'username' => $participation->getUser()->getFirstname() . ' ' . $participation->getUser()->getLastname(),
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
            $event->getTitre(),
            $event->getCode(),
            null,
            null,
            $event->getRdv(),
            $event->getLat(),
            $event->getLong(),
            $event->getDescription(),
            $event->getJoinStart(),
            $event->getJoinMax(),
            $event->getNgensMax()
        );
        $newEvent->setMassif($event->getMassif());
        $newEvent->setTarif($event->getTarif());
        $newEvent->setTarifDetail($event->getTarifDetail());
        $newEvent->setDenivele($event->getDenivele());
        $newEvent->setDistance($event->getDistance());
        $newEvent->setMatos($event->getMatos());
        $newEvent->setDifficulte($event->getDifficulte());
        $newEvent->setItineraire($event->getItineraire());
        $newEvent->setNeedBenevoles($event->getNeedBenevoles());

        $em->persist($newEvent);

        foreach ($event->getParticipations() as $participation) {
            if ($participation->getUser() === $newEvent->getUser()) {
                continue;
            }

            $join = $newEvent->addParticipation($participation->getUser(), $participation->getRole(), $participation->getStatus());
            $em->persist($join);
        }

        $em->flush();

        return $this->redirect(sprintf('/creer-une-sortie/%s/update-%d.html', $newEvent->getCommission()->getCode(), $newEvent->getId()));
    }
}
