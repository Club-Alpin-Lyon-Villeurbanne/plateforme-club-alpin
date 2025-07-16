<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use App\Form\EventType;
use App\Legacy\LegacyContainer;
use App\Mailer\Mailer;
use App\Messenger\Message\SortiePubliee;
use App\Repository\CommissionRepository;
use App\Repository\EventParticipationRepository;
use App\Repository\UserRepository;
use App\Twig\JavascriptGlobalsExtension;
use App\UserRights;
use App\Utils\ExcelExport;
use App\Utils\PdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\String\Slugger\SluggerInterface;
use Twig\Environment;

class SortieController extends AbstractController
{
    public function __construct(protected float $defaultLat, protected float $defaultLong)
    {
    }

    #[Route(path: '/creer-une-sortie', name: 'creer_sortie', methods: ['GET', 'POST'])]
    #[Route(path: '/modifier-une-sortie/{event}', name: 'modifier_sortie', requirements: ['event' => '\d+'], methods: ['GET', 'POST'])]
    #[Template('sortie/formulaire.html.twig')]
    public function create(
        Request $request,
        ManagerRegistry $doctrine,
        SluggerInterface $slugger,
        CommissionRepository $commissionRepository,
        UserRights $userRights,
        ?Evt $event = null,
        ?Commission $commission = null,
    ): array|RedirectResponse {
        $user = $this->getUser();
        $isUpdate = true;
        if (!$event instanceof Evt) {
            $event = new Evt(
                $user,
                $commission,
                null,
                null,
                null,
                null,
                null,
                $this->defaultLat,
                $this->defaultLong,
                null,
                null,
                null,
                null
            );
            $event->setJoinStart((new \DateTime())->getTimestamp());
            $isUpdate = false;
        }

        if (!$this->isGranted('SORTIE_UPDATE', $event)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à modifier cette sortie.');
        }

        $form = $this->createForm(EventType::class, $event);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();
            $data = $request->request->all();

            /** @var Evt $event */
            $event = $form->getData();
            $eventData = $data['event'] ?? [];
            $formData = $data['form'] ?? [];
            $formData = array_merge($eventData, $formData);

            if (!$isUpdate) {
                $event->setCode(strtolower(substr($slugger->slug($event->getTitre(), '-'), 0, 30)));
            } elseif (Evt::STATUS_PUBLISHED_VALIDE === $event->getStatus()) {
                // sortie dépubliée à l'édition
                $event->setStatus(Evt::STATUS_PUBLISHED_UNSEEN);
                $event->setTspEdit((new \DateTime())->getTimestamp());
            }

            // brouillon ?
            $isDraft = false;
            if (\in_array('eventDraftSave', array_keys($formData), true)) {
                $isDraft = true;
            }
            $event->setIsDraft($isDraft);

            // encadrants & co
            $rolesMap = [
                EventParticipation::ROLE_ENCADRANT => 'encadrants',
                EventParticipation::ROLE_STAGIAIRE => 'initiateurs',
                EventParticipation::ROLE_COENCADRANT => 'coencadrants',
                EventParticipation::ROLE_BENEVOLE => 'benevoles',
            ];
            if ($isUpdate) {
                // les bénévoles ne sont pas modifiables mais il ne faut pas les "perdre" à l'édition
                unset($rolesMap[EventParticipation::ROLE_BENEVOLE]);
            }
            foreach ($rolesMap as $role => $roleName) {
                $event->clearRoleParticipations($role);
                if (!empty($formData[$roleName])) {
                    foreach ($formData[$roleName] as $participantId) {
                        $participant = $entityManager->getRepository(User::class)->find($participantId);
                        // si ce participant est déjà inscrit, on met à jour son statut de participation
                        if ($participation = $event->getParticipation($participant)) {
                            $event->removeParticipation($participation);
                        }
                        $event->addParticipation($participant, $role, EventParticipation::STATUS_VALIDE);
                    }
                }
            }

            // anciens timestamps
            $event->setTsp(\DateTime::createFromFormat('Y-m-d\TH:i', $formData['eventStartDate'])?->getTimestamp());
            $event->setTspEnd(\DateTime::createFromFormat('Y-m-d\TH:i', $formData['eventEndDate'])?->getTimestamp());
            if ($formData['joinStartDate']) {
                $event->setJoinStart(\DateTime::createFromFormat('Y-m-d\TH:i', $formData['joinStartDate'])?->getTimestamp());
            }

            // champs auto
            if (empty($event->getJoinStart())) {
                $event->setJoinStart(time());
            }
            if (empty($event->getRdv())) {
                $event->setRdv('');
            }

            $entityManager->persist($event);
            $entityManager->flush();

            return $this->redirectToRoute('profil_sorties_self');
        }

        $availableCommissions = array_filter(
            iterator_to_array($commissionRepository->findVisible()),
            fn (Commission $commission) => $userRights->allowedOnCommission('evt_create', $commission),
        );

        return [
            'form' => $form,
            'title' => $isUpdate ? 'Modifier une sortie' : 'Proposer une sortie',
            'is_update' => $isUpdate,
            'commission' => $isUpdate ? $event->getCommission()->getTitle() : '',
            'event' => $event,
            'commissions' => $availableCommissions,
            'current_commission' => $commission?->getCode() ?? '',
        ];
    }

    #[Route(path: '/sortie/{code}-{id}.html', name: 'sortie', requirements: ['id' => '\d+', 'code' => '[a-z0-9-]+'], methods: ['GET'], priority: '10')]
    #[Template('sortie/sortie.html.twig')]
    public function sortie(
        Evt $event,
        UserRepository $repository,
        EventParticipationRepository $participationRepository,
        Environment $twig,
        $baseUrl = '/',
    ) {
        if (!$this->isGranted('SORTIE_VIEW', $event)) {
            throw new AccessDeniedHttpException('Not found');
        }

        $user = $this->getUser();

        $twig->getExtension(JavascriptGlobalsExtension::class)->registerGlobal(
            'currentEventId',
            $event->getId()
        );
        $twig->getExtension(JavascriptGlobalsExtension::class)->registerGlobal(
            'apiBaseUrl',
            $baseUrl
        );

        return [
            'event' => $event,
            'participations' => $participationRepository->getSortedParticipations($event, null, null),
            'filiations' => $user ? $repository->getFiliations($user) : null,
            'empietements' => $participationRepository->getEmpietements($event),
            'current_commission' => $event->getCommission()->getCode(),
        ];
    }

    #[Route(name: 'sortie_validate', path: '/sortie/{id}/validate', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function sortieValidate(
        Request $request,
        Evt $event,
        EntityManagerInterface $em,
        Mailer $mailer,
        MessageBusInterface $messageBus,
    ) {
        if (!$this->isCsrfTokenValid('sortie_validate', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('SORTIE_VALIDATE', $event)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à celà.');
        }

        $event->setStatus(Evt::STATUS_LEGAL_VALIDE)->setStatusWho($this->getUser());
        $em->flush();

        $messageBus->dispatch(new SortiePubliee($event->getId()));

        $mailer->send($event->getUser(), 'transactional/sortie-publiee', [
            'event_name' => $event->getTitre(),
            'commission' => $event->getCommission()->getTitle(),
            'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'event_date' => date('d/m/Y', $event->getTsp()),
        ]);

        foreach ($event->getParticipations() as $participation) {
            if ($participation->getUser() === $event->getUser()) {
                // mail already sent
                continue;
            }

            if ($event->getTspCrea() === $event->getTspEdit()) {
                $mailer->send($participation->getUser(), 'transactional/sortie-publiee-inscrit', [
                    'author_url' => $this->generateUrl('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'voir-profil/' . $event->getUser()->getId() . '.html',
                    'author_nickname' => $event->getUser()->getNickname(),
                    'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    'event_name' => $event->getTitre(),
                    'commission' => $event->getCommission()->getTitle(),
                    'event_date' => $event->getTsp() ? date('d/m/Y', $event->getTsp()) : '',
                    'role' => $participation->getRole(),
                ], [], null, $event->getUser()->getEmail());
            } else {
                $mailer->send($participation->getUser(), 'transactional/sortie-modifiee', [
                    'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    'event_name' => $event->getTitre(),
                    'commission' => $event->getCommission()->getTitle(),
                    'event_date' => $event->getTsp() ? date('d/m/Y', $event->getTsp()) : '',
                ], [], null, $event->getUser()->getEmail());
            }
        }

        $this->addFlash('info', 'La sortie est publiée');

        return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
    }

    #[Route(name: 'sortie_update_inscription', path: '/sortie/{id}/update-inscriptions', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function sortieUpdateInscriptions(#[CurrentUser] User $user, Request $request, Evt $event, EntityManagerInterface $em, Mailer $mailer)
    {
        if (!$this->isCsrfTokenValid('sortie_update_inscriptions', $request->request->get('csrf_token'))) {
            $this->addFlash('error', 'Jeton de validation invalide.');

            return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
        }

        if (!$this->isGranted('SORTIE_INSCRIPTIONS_MODIFICATION', $event)) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à celà.');

            return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
        }

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
            if (!$role) {
                $role = 'inscrit';
            }
            $participation->setRole($role);

            $participation
                ->setStatus($status)
                ->setLastchangeWhen(time())
                ->setLastchangeWho($user)
            ;

            if (!\in_array($status, [EventParticipation::STATUS_VALIDE, EventParticipation::STATUS_REFUSE, EventParticipation::STATUS_ABSENT], true)) {
                continue;
            }

            if (($event->isFinished() && !\in_array($status, [EventParticipation::STATUS_ABSENT], true)) || 'on' === $request->request->get('disablemails')) {
                continue;
            }

            if ($participation->getUser()->getNomade()) {
                $statusName = '';
                if (EventParticipation::STATUS_NON_CONFIRME === $status) {
                    $statusName = 'En attente';
                }
                if (EventParticipation::STATUS_VALIDE === $status) {
                    $statusName = 'Accepté';
                }
                if (EventParticipation::STATUS_REFUSE === $status) {
                    $statusName = 'Refusé';
                }

                if (!$participation->getUser()->getEmail()) {
                    $this->addFlash('warning', sprintf('%s %s est un adhérent nomade. Il n\'a pas d\'email et ' .
                        'doit être prévenu par téléphone de son nouveau statut : %s. Son téléphone: %s', $participation->getUser()->getFirstname(), $participation->getUser()->getLastname(), $statusName, $participation->getUser()->getTel()));

                    continue;
                }
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
                'commission' => $event->getCommission()->getTitle(),
                'event_date' => $event->getTsp() ? date('d/m/Y', $event->getTsp()) : '',
            ];

            $template = match ($status) {
                EventParticipation::STATUS_VALIDE => 'transactional/sortie-participation-confirmee',
                EventParticipation::STATUS_REFUSE => 'transactional/sortie-participation-declinee',
                EventParticipation::STATUS_ABSENT => 'transactional/sortie-participation-absent',
            };

            $replyTo = EventParticipation::STATUS_ABSENT === $status ? $user->getEmail() : null;
            $mailer->send($toMail, $template, $context, replyTo: $replyTo);
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
            'commission' => $event->getCommission()->getTitle(),
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
            'commission' => $event->getCommission()->getTitle(),
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
            'commission' => $event->getCommission()->getTitle(),
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

        $replyToMode = $request->request->get('reply_to_option');
        $replyToAddresses = [];
        if ('everyone' === $replyToMode) {
            foreach ($event->getEncadrants() as $joined) {
                $replyToAddresses[] = $joined->getUser()->getEmail();
                $participations[] = $joined->getUser()->getEmail();
            }
        } elseif ('me_only' === $replyToMode) {
            $replyToAddresses = $this->getUser()->getEmail();
            $participations[] = $this->getUser()->getEmail();
        }

        $mailer->send($participations, 'transactional/message-sortie', [
            'objet' => $request->request->get('objet'),
            'message_author' => sprintf('%s %s', $this->getUser()->getFirstname(), strtoupper($this->getUser()->getLastname())),
            'url_sortie' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'name_sortie' => $event->getTitre(),
            'commission' => $event->getCommission()->getTitle(),
            'date_sortie' => $event->getTsp() ? date('d/m/Y', $event->getTsp()) : '',
            'message' => $request->request->get('message'),
            'message_author_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'user-full/' . $this->getUser()->getId() . '.html',
        ], [], $this->getUser(), $replyToAddresses);

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

        if ($participation->isStatusValide() || $participation->isStatusEnAttente()) {
            // notifier les encadrants
            $encadrants = $event->getEncadrants();
            $reason = $request->request->get('cancel_reason') ?? '';
            foreach ($encadrants as $encadrant) {
                $mailer->send($encadrant->getUser(), 'transactional/sortie-desinscription', [
                    'username' => $participation->getUser()->getFirstname() . ' ' . $participation->getUser()->getLastname(),
                    'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    'event_name' => $event->getTitre(),
                    'commission' => $event->getCommission()->getTitle(),
                    'event_date' => $event->getTsp() ? date('d/m/Y', $event->getTsp()) : '',
                    'reason_explanation' => $reason,
                    'user' => $user,
                    'profile_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'user-full/' . $user->getId() . '.html',
                ], [], null, $user->getEmail());
            }
        }

        $this->addFlash('info', 'La participation est annulée');

        return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
    }

    #[Route(path: '/sortie/{id}/duplicate/{mode}', name: 'sortie_duplicate', requirements: ['id' => '\d+', 'mode' => 'full|empty'], methods: ['POST'], priority: '10')]
    public function sortieDuplicate(Request $request, Evt $event, EntityManagerInterface $em, string $mode = 'empty'): RedirectResponse
    {
        if (!$this->isGranted('SORTIE_DUPLICATE', $event)) {
            throw new AccessDeniedHttpException('Not allowed');
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
            null,
            $event->getJoinMax(),
            $event->getNgensMax()
        );
        $newEvent->setMassif($event->getMassif());
        $newEvent->setTarif($event->getTarif());
        $newEvent->setTarifDetail($event->getTarifDetail());
        $newEvent->setDetailsCaches($event->getDetailsCaches());
        $newEvent->setDenivele($event->getDenivele());
        $newEvent->setDistance($event->getDistance());
        $newEvent->setMatos($event->getMatos());
        $newEvent->setDifficulte($event->getDifficulte());
        $newEvent->setItineraire($event->getItineraire());
        $newEvent->setNeedBenevoles($event->getNeedBenevoles());
        $newEvent->setGroupe($event->getGroupe());
        $newEvent->setJoinStart(time());

        if ('full' === $mode) {
            foreach ($event->getParticipations() as $participation) {
                $join = $newEvent->addParticipation($participation->getUser(), $participation->getRole(), $participation->getStatus());
                $em->persist($join);
            }
        }
        $em->persist($newEvent);
        $em->flush();

        return $this->redirectToRoute('modifier_sortie', ['event' => $newEvent->getId()]);
    }

    #[Route(name: 'sortie_pdf', path: '/sortie/{id}/printPDF', requirements: ['id' => '\d+'])]
    public function generatePdf(PdfGenerator $pdfGenerator, SluggerInterface $slugger, Evt $event): Response
    {
        $legacyDir = __DIR__ . '/../../legacy/';
        $path = 'index.php';
        $_GET['p1'] = 'feuille-de-sortie';
        $_GET['p2'] = 'evt-' . $event->getId();
        $_GET['titre_evt'] = $event->getTitre();
        $_GET['tsp_evt'] = $event->getTsp();

        ob_start();
        require $this->getParameter('kernel.project_dir') . '/legacy/' . $path;
        $html = ob_get_clean();

        return $pdfGenerator->generatePdf($html, $this->getFilename($event->getTitre(), $slugger) . '.pdf');
    }

    #[Route(name: 'sortie_xlsx', path: '/sortie/{id}/printXLSX', requirements: ['id' => '\d+'])]
    public function generateXLSX(ExcelExport $excelExport, Evt $event, EventParticipationRepository $participationRepository, SluggerInterface $slugger): Response
    {
        $datas = $participationRepository->getSortedParticipations($event, null, EventParticipation::STATUS_VALIDE, true);

        $rsm = [' ', 'PARTICIPANTS (PRÉNOM, NOM)', 'LICENCE', 'AGE', 'TÉL.', 'TÉL. SECOURS', 'EMAIL'];

        return $excelExport->export(substr($slugger->slug($event->getTitre(), '-'), 0, 20), $datas, $rsm, $this->getFilename($event->getTitre(), $slugger));
    }

    protected function getFilename(string $eventTitle, SluggerInterface $slugger): string
    {
        return substr($slugger->slug($eventTitle, '-'), 0, 20) . '.' . date('Y-m-d.H-i-s');
    }
}
