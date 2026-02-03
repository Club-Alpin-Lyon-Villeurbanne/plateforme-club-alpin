<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\FormationValidationGroupeCompetence;
use App\Entity\FormationValidationNiveauPratique;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Form\EventType;
use App\Helper\RoleHelper;
use App\Helper\SlugHelper;
use App\Legacy\LegacyContainer;
use App\Mailer\Mailer;
use App\Messenger\Message\SortiePubliee;
use App\Repository\CommissionRepository;
use App\Repository\EventParticipationRepository;
use App\Repository\EventUnrecognizedPayerRepository;
use App\Repository\ExpenseReportRepository;
use App\Repository\FormationValidationGroupeCompetenceRepository;
use App\Repository\FormationValidationNiveauPratiqueRepository;
use App\Repository\UserAttrRepository;
use App\Repository\UserRepository;
use App\Service\HelloAssoService;
use App\Service\UserLicenseHelper;
use App\Twig\JavascriptGlobalsExtension;
use App\UserRights;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Utils\ExcelExport;
use App\Utils\PdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class SortieController extends AbstractController
{
    public function __construct(
        protected SlugHelper $slugHelper,
        protected float $defaultLat,
        protected float $defaultLong,
        protected string $defaultAppointmentPlace,
        protected string $editoLineLink,
        protected string $imageRightLink,
    ) {
    }

    #[Route(path: '/creer-une-sortie', name: 'creer_sortie', methods: ['GET', 'POST'])]
    #[Route(path: '/modifier-une-sortie/{event}', name: 'modifier_sortie', requirements: ['event' => '\d+'], methods: ['GET', 'POST'])]
    #[Route(path: '/sortie/{event}/duplicate/{mode}', name: 'sortie_duplicate', requirements: ['event' => '\d+', 'mode' => 'full|empty'], methods: ['POST'], priority: '10')]
    #[Template('sortie/formulaire.html.twig')]
    public function create(
        Request $request,
        ManagerRegistry $doctrine,
        CommissionRepository $commissionRepository,
        UserRights $userRights,
        Mailer $mailer,
        ?Evt $event = null,
        ?string $mode = null,
    ): array|RedirectResponse {
        /** @var User $user */
        $user = $this->getUser();
        $isUpdate = true;
        $isDuplicate = false;
        $commission = $commissionRepository->findOneBy(['code' => $request->query->get('commission')]);

        if (!$event instanceof Evt) {
            $event = new Evt(
                $user,
                $commission,
                null,
                null,
                null,
                null,
                $this->defaultAppointmentPlace,
                $this->defaultLat,
                $this->defaultLong,
                null,
                null,
                null,
                new \DateTimeImmutable()
            );
            $event->setJoinStartDate(new \DateTimeImmutable());
            $isUpdate = false;
        } elseif (!empty($mode)) {
            $commission = $event->getCommission();
            $event = $this->duplicate($request, $event, $mode);
            $isUpdate = false;
            $isDuplicate = true;
        }

        if (!$isUpdate && !$this->isGranted('SORTIE_CREATE', $commission)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à créer de sortie.');
        }
        if ($isUpdate && !$this->isGranted('SORTIE_UPDATE', $event)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à modifier cette sortie.');
        }

        $originalEntityData = [];
        $currentEncadrants = $currentCoencadrants = $currentStagiaires = $currentBenevoles = null;
        if ($isUpdate) {
            $originalEntityData['ngensMax'] = $event->getngensMax();
            $originalEntityData['place'] = $event->getPlace();
            $originalEntityData['encadrants'] = [];
            $currentEncadrants = $event->getEncadrants([EventParticipation::ROLE_ENCADRANT]);
            foreach ($currentEncadrants as $currentEncadrant) {
                $originalEntityData['encadrants'][$currentEncadrant->getUser()->getId()] = $currentEncadrant->getRole();
            }
            $currentStagiaires = $event->getEncadrants([EventParticipation::ROLE_STAGIAIRE]);
            $currentCoencadrants = $event->getEncadrants([EventParticipation::ROLE_COENCADRANT]);
            $currentBenevoles = $event->getEncadrants([EventParticipation::ROLE_BENEVOLE]);
            $originalEntityData['hasPaymentForm'] = $event->hasPaymentForm();
            $originalEntityData['paymentAmount'] = $event->getPaymentAmount();
        }

        $form = $this->createForm(EventType::class, $event, ['is_edit' => $isUpdate, 'editoLineLink' => $this->editoLineLink, 'imageRightLink' => $this->imageRightLink, 'user' => $user]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();
            $data = $request->request->all();

            /** @var Evt $event */
            $event = $form->getData();
            $eventData = $data['event'] ?? [];
            $formData = $data['form'] ?? [];
            $formData = array_merge($eventData, $formData);

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
            $newEncadrants = [];
            foreach ($rolesMap as $role => $roleName) {
                if (!empty($formData[$roleName])) {
                    foreach ($formData[$roleName] as $participantId) {
                        $newEncadrants[$roleName][$participantId] = $role;
                        $participant = $entityManager->getRepository(User::class)->find($participantId);
                        // si ce participant est déjà inscrit, on met à jour son statut de participation
                        if ($participation = $event->getParticipation($participant)) {
                            $participation
                                ->setRole($role)
                                ->setStatus(EventParticipation::STATUS_VALIDE)
                            ;
                        } else {
                            $event->addParticipation($participant, $role, EventParticipation::STATUS_VALIDE);
                        }
                    }
                }
            }
            // retirer les encadrants qui ne sont plus cochés
            if ($isUpdate && !empty($currentEncadrants)) {
                foreach ($currentEncadrants as $currentEncadrant) {
                    if (!in_array($currentEncadrant->getUser()->getId(), $formData['encadrants'], false)) {
                        $event->removeParticipation($currentEncadrant);
                    }
                }
            }
            // retirer les stagiaires qui ne sont plus cochés
            if ($isUpdate && !empty($currentStagiaires)) {
                if (empty($formData['initiateurs'])) {
                    $formData['initiateurs'] = [];
                }
                foreach ($currentStagiaires as $currentStagiaire) {
                    if (!in_array($currentStagiaire->getUser()->getId(), $formData['initiateurs'], false)) {
                        $event->removeParticipation($currentStagiaire);
                    }
                }
            }
            // retirer les coencadrants qui ne sont plus cochés
            if ($isUpdate && !empty($currentCoencadrants)) {
                if (empty($formData['coencadrants'])) {
                    $formData['coencadrants'] = [];
                }
                foreach ($currentCoencadrants as $currentEncadrant) {
                    if (!in_array($currentEncadrant->getUser()->getId(), $formData['coencadrants'], false)) {
                        $event->removeParticipation($currentEncadrant);
                    }
                }
            }
            // retirer les bénévoles d'encadrement qui ne sont plus cochés
            if ($isUpdate && !empty($currentBenevoles)) {
                if (empty($formData['benevoles'])) {
                    $formData['benevoles'] = [];
                }
                foreach ($currentBenevoles as $currentBenevole) {
                    if (!in_array($currentBenevole->getUser()->getId(), $formData['benevoles'], false)) {
                        $event->removeParticipation($currentBenevole);
                    }
                }
            }

            // champs obligatoires selon la commission
            $event->setDifficulte($formData['difficulte']);
            $event->setDenivele($formData['denivele']);
            $event->setDistance($formData['distance']);

            if (!$isUpdate) {
                $event->setCode($this->slugHelper->generateSlug($event->getTitre()));
            } else {
                $event->setUpdatedAt(new \DateTime());

                // sortie dépubliée à l'édition (si certains champs sont modifiés seulement)
                if (Evt::STATUS_PUBLISHED_VALIDE === $event->getStatus()
                    && ($originalEntityData['ngensMax'] !== $event->getngensMax()
                    || $originalEntityData['place'] !== $event->getPlace()
                    || $originalEntityData['hasPaymentForm'] !== $event->hasPaymentForm()
                    || $originalEntityData['paymentAmount'] !== $event->getPaymentAmount()
                    || $originalEntityData['encadrants'] !== $newEncadrants['encadrants'])) {
                    $event->setStatus(Evt::STATUS_PUBLISHED_UNSEEN);
                } elseif (!$event->isDraft()) {
                    // on envoie directement le mail de mise à jour de sortie
                    $this->sendUpdateNotificationEmail($mailer, $event, false);
                }
            }

            // champs auto
            if (empty($event->getJoinStartDate())) {
                $event->setJoinStartDate(new \DateTimeImmutable());
            }
            if (empty($event->getRdv())) {
                $event->setRdv('');
            }
            if (empty($event->getPlace())) {
                $event->setPlace('');
            }
            if (null === $event->getJoinMax() || $event->getJoinMax() < 0) {
                $event->setJoinMax($event->getNgensMax());
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
            'current_commission' => $commission,
            'form_action' => $isUpdate ? $this->generateUrl('modifier_sortie', ['event' => $event->getId()]) : $this->generateUrl('creer_sortie'),
            'is_duplicate' => $isDuplicate,
        ];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route(path: '/sortie/{code}-{id}.html', name: 'sortie', requirements: ['id' => '\d+', 'code' => '[a-z0-9-]+'], methods: ['GET'], priority: '10')]
    #[Template('sortie/sortie.html.twig')]
    public function sortie(
        Evt $event,
        UserRepository $repository,
        EventParticipationRepository $participationRepository,
        EventUnrecognizedPayerRepository $unrecognizedPayerRepository,
        FormationValidationNiveauPratiqueRepository $formationNiveauRepository,
        FormationValidationGroupeCompetenceRepository $formationCompetenceValidationRepository,
        ExpenseReportRepository $expenseReportRepository,
        Environment $twig,
        $baseUrl = '/',
    ) {
        if (!$this->isGranted('SORTIE_VIEW', $event)) {
            throw new AccessDeniedHttpException('Not found');
        }

        /** @var User $user */
        $user = $this->getUser();

        $twig->getExtension(JavascriptGlobalsExtension::class)->registerGlobal(
            'currentEventId',
            $event->getId()
        );
        $twig->getExtension(JavascriptGlobalsExtension::class)->registerGlobal(
            'apiBaseUrl',
            $baseUrl
        );

        $unrecognizedPayersEmails = $unrecognizedPayerRepository->getAllPayerEmailForEvent($event);

        // l'utilisateur connecté peut-il voir le lien de paiement hello asso ?
        $currentUserAccepted = false;
        $currentUserHasPaid = false;
        $myParticipation = $participationRepository->findOneBy(['user' => $user, 'evt' => $event]);
        if ($myParticipation && EventParticipation::STATUS_VALIDE === $myParticipation->getStatus()) {
            $currentUserAccepted = true;
            $currentUserHasPaid = $myParticipation->hasPaid();
        }
        if ($user && \in_array($user->getEmail(), $unrecognizedPayersEmails, true)) {
            $currentUserHasPaid = true;
        }

        // affichage des compétences des participants
        $participations = $event->getParticipations(null, null);
        $nivRefs = [];
        $groupesCompRefs = [];

        foreach ($participations as $participation) {
            // niveaux de pratique
            $niveaux = $formationNiveauRepository->getAllNiveauxByUser($participation->getUser(), $event->getCommission());
            /** @var FormationValidationNiveauPratique $niveau */
            foreach ($niveaux as $niveau) {
                $nivRefs[$niveau->getNiveauReferentiel()->getId()] = $niveau->getNiveauReferentiel();
            }

            // groupes de compétences
            $groupesComps = $formationCompetenceValidationRepository->getAllGroupesCompetencesByUser($participation->getUser(), $event->getCommission());
            /** @var FormationValidationGroupeCompetence $groupesComp */
            foreach ($groupesComps as $groupesComp) {
                $groupesCompRefs[$groupesComp->getCompetence()->getId()] = $groupesComp->getCompetence();
            }
        }

        // Date cutoff: September 30 of current year if after Dec 1, otherwise September 30 of previous year
        $currentMonth = date('m');
        $currentYear = date('Y');
        $cutoffYear = $currentMonth >= 12 ? $currentYear : $currentYear - 1;
        $cutoffDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $cutoffYear . '-' . UserLicenseHelper::LICENSE_TOLERANCY_PERIOD_END);

        // Check if user has a viewable expense report (submitted, approved, or accounted)
        $hasViewableExpenseReport = false;
        if ($user) {
            $expenseReport = $expenseReportRepository->getExpenseReportByEventAndUser($event->getId(), $user->getId());
            if ($expenseReport && \in_array($expenseReport->getStatus(), [
                ExpenseReportStatusEnum::SUBMITTED,
                ExpenseReportStatusEnum::APPROVED,
                ExpenseReportStatusEnum::ACCOUNTED,
            ], true)) {
                $hasViewableExpenseReport = true;
            }
        }

        return [
            'event' => $event,
            'participations' => $participationRepository->getSortedParticipations($event, null, null),
            'unrecognized_payers' => $unrecognizedPayerRepository->findBy(['event' => $event, 'hasPaid' => true], ['lastname' => 'asc']),
            'filiations' => $user ? $repository->getFiliations($user) : null,
            'empietements' => $participationRepository->getEmpietements($event),
            'current_commission' => $event->getCommission(),
            'encoded_coord' => urlencode($event->getLat() . ',' . $event->getLong()),
            'geovelo_encoded_coord' => urlencode($event->getLong() . ',' . $event->getLat()),
            'current_user_has_paid' => $currentUserHasPaid,
            'current_user_accepted' => $currentUserAccepted,
            'accepted_participations' => $participationRepository->getSortedParticipations($event),
            'is_event_after_cutoff' => $event->getEndDate() >= $cutoffDate,
            'cutoff_year' => $cutoffYear,
            'has_viewable_expense_report' => $hasViewableExpenseReport,
            'groupes_competences' => $groupesCompRefs,
            'niveaux' => $nivRefs,
        ];
    }

    #[Route(path: '/feuille-de-sortie/evt-{id}.html', name: 'feuille_sortie', requirements: ['id' => '\d+'], methods: ['GET'], priority: '11')]
    #[Template('sortie/feuille-sortie.html.twig')]
    public function sortieDetails(
        Request $request,
        Evt $event,
        UserAttrRepository $userAttrRepository,
    ): array {
        if (!$this->isGranted('FICHE_SORTIE', $event)) {
            throw new AccessDeniedHttpException('Not allowed');
        }

        return $this->eventDetails($request, $event, $userAttrRepository);
    }

    #[Route(path: '/sortie/{id}/validate', name: 'sortie_validate', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function sortieValidate(
        Request $request,
        Evt $event,
        HelloAssoService $helloAssoService,
        EntityManagerInterface $em,
        Mailer $mailer,
        MessageBusInterface $messageBus,
        LoggerInterface $logger,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('sortie_validate', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('SORTIE_VALIDATE', $event)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE)->setStatusWho($this->getUser());

        // créer la campagne hello asso si nécessaire
        if ($event->hasPaymentForm() && !$event->getHelloAssoFormSlug()) {
            try {
                $haFormData = $helloAssoService->createFormForEvent($event);
                $event->setHelloAssoFormSlug($haFormData['formSlug']);
                $event->setPaymentUrl($haFormData['publicUrl']);

                // publier la campagne
                $helloAssoService->publishFormForEvent($event);
            } catch (\Exception $exception) {
                $logger->error('Unable to create or publish HelloAsso form: ' . $exception->getMessage());
            }
        }

        $em->flush();

        $messageBus->dispatch(new SortiePubliee($event->getId()));

        $mailer->send($event->getUser(), 'transactional/sortie-publiee', [
            'event_name' => $event->getTitre(),
            'commission' => $event->getCommission()->getTitle(),
            'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'event_date' => $event->getStartDate()->format('d/m/Y'),
        ]);

        $this->sendUpdateNotificationEmail($mailer, $event, $event->getCreatedAt() === $event->getUpdatedAt());

        $this->addFlash('info', 'La sortie est approuvée');

        return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
    }

    #[Route(path: '/sortie/{id}/update-inscriptions', name: 'sortie_update_inscription', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function sortieUpdateInscriptions(
        #[CurrentUser] User $user,
        Request $request,
        Evt $event,
        EntityManagerInterface $em,
        Mailer $mailer,
        RoleHelper $roleHelper,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('sortie_update_inscriptions', $request->request->get('csrf_token_inscriptions'))) {
            $this->addFlash('error', 'Jeton de validation invalide.');

            return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
        }

        if (!$this->isGranted('SORTIE_INSCRIPTIONS_MODIFICATION', $event)) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à cela.');

            return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
        }

        // reste-t-il assez de place ?
        $nbPeopleMax = $event->getNgensMax();
        $currentParticipantNb = $event->getParticipationsCount();
        $availableSpotNb = $nbPeopleMax - $currentParticipantNb;
        if ($availableSpotNb < 0) {
            $availableSpotNb = 0;
        }

        $flush = true;
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

            $currentStatus = $participation->getStatus();
            if (EventParticipation::STATUS_VALIDE === $status && EventParticipation::STATUS_VALIDE !== $currentStatus) {
                ++$currentParticipantNb;
            }

            // there can be no role passed in the request
            if ($role) {
                $participation->setRole($role);
            }

            $participation
                ->setStatus($status)
                ->setUpdatedAt(new \DateTime())
                ->setLastchangeWho($user)
            ;

            // reste-t-il assez de place ?
            if ($currentParticipantNb > $nbPeopleMax && EventParticipation::STATUS_VALIDE === $status) {
                $this->addFlash('error', 'Vous ne pouvez pas valider plus de participants que de places disponibles (' . $availableSpotNb . '). Vous pouvez augmenter le nombre maximum de places pour ensuite rajouter des personnes.');
                $flush = false;

                // s'il n'y a plus de place, inutile de parcourir le reste, on sort de la boucle
                break;
            }

            if (!\in_array($status, [EventParticipation::STATUS_VALIDE, EventParticipation::STATUS_REFUSE, EventParticipation::STATUS_ABSENT], true)) {
                continue;
            }

            if ($event->isFinished() && !\in_array($status, [EventParticipation::STATUS_ABSENT], true)) {
                continue;
            }

            /** @var User $toMail */
            $toMail = null !== $participation->getAffiliantUserJoin() ? $participation->getAffiliantUserJoin() : $participation->getUser();

            if (!$toMail->getEmail()) {
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
                if (EventParticipation::STATUS_ABSENT === $status) {
                    $statusName = 'Absent';
                }

                $this->addFlash('warning', sprintf('%s %s n\'a pas d\'email et ' .
                    'doit être prévenu par téléphone de son nouveau statut : %s. Son téléphone : %s', $participation->getUser()->getFirstname(), $participation->getUser()->getLastname(), $statusName, $participation->getUser()->getTel()));

                continue;
            }

            $roleName = strtolower($roleHelper->getParticipationRoleName($participation));

            $context = [
                'role' => $roleName,
                'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'event_name' => $event->getTitre(),
                'commission' => $event->getCommission()->getTitle(),
                'event_date' => $event->getStartDate()->format('d/m/Y'),
            ];
            if ($event->hasPaymentForm() && $event->hasPaymentSendMail()) {
                $context['hello_asso_url'] = $event->getPaymentUrl();
            }

            $template = match ($status) {
                EventParticipation::STATUS_VALIDE => 'transactional/sortie-participation-confirmee',
                EventParticipation::STATUS_REFUSE => 'transactional/sortie-participation-declinee',
                EventParticipation::STATUS_ABSENT => 'transactional/sortie-participation-absent',
            };

            $replyTo = EventParticipation::STATUS_ABSENT === $status ? $user->getEmail() : null;
            $mailer->send($toMail, $template, $context, replyTo: $replyTo);
        }

        if ($flush) {
            $em->flush();
        }

        return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
    }

    #[Route(path: '/sortie/{id}/refus', name: 'sortie_refus', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function sortieRefus(Request $request, Evt $event, EntityManagerInterface $em, Mailer $mailer): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('sortie_refus', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('SORTIE_VALIDATE', $event)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        $event->setStatus(Evt::STATUS_PUBLISHED_REFUSE)->setStatusWho($this->getUser());
        $em->flush();

        $mailer->send($event->getUser(), 'transactional/sortie-refusee', [
            'message' => $request->request->get('msg', '...'),
            'event_name' => $event->getTitre(),
            'commission' => $event->getCommission()->getTitle(),
            'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'event_date' => $event->getStartDate()->format('d/m/Y'),
        ]);

        $this->addFlash('info', 'La sortie est refusée');

        return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
    }

    #[Route(path: '/sortie/{id}/legal-validate', name: 'sortie_legal_validate', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function sortieLegalValidate(Request $request, Evt $event, EntityManagerInterface $em, Mailer $mailer): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('sortie_legal_validate', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('SORTIE_LEGAL_VALIDATION', $event)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        $event
            ->setStatusLegal(Evt::STATUS_LEGAL_VALIDE)
            ->setStatusLegalWho($this->getUser())
            ->setLegalStatusChangeDate(new \DateTimeImmutable())
        ;
        $em->flush();

        $mailer->send($event->getUser(), 'transactional/sortie-president-validee', [
            'event_name' => $event->getTitre(),
            'commission' => $event->getCommission()->getTitle(),
            'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'event_date' => $event->getStartDate()->format('d/m/Y'),
        ]);

        $this->addFlash('info', 'La sortie est validée légalement');

        return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
    }

    #[Route(path: '/sortie/{id}/legal-refus', name: 'sortie_legal_refus', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function sortieLegalRefus(Request $request, Evt $event, EntityManagerInterface $em, Mailer $mailer): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('sortie_legal_refus', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('SORTIE_LEGAL_VALIDATION', $event)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        $event
            ->setStatusLegal(Evt::STATUS_LEGAL_REFUSE)
            ->setStatusLegalWho($this->getUser())
            ->setLegalStatusChangeDate(new \DateTimeImmutable())
        ;
        $em->flush();

        $this->addFlash('info', 'La sortie n\'est pas validée légalement');

        $mailer->send($event->getUser(), 'transactional/sortie-president-refusee', [
            'event_name' => $event->getTitre(),
            'commission' => $event->getCommission()->getTitle(),
            'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'event_date' => $event->getStartDate()->format('d/m/Y'),
        ]);

        return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
    }

    #[Route(path: '/sortie/{id}/supprimer', name: 'delete_event', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        Evt $event,
        EntityManagerInterface $em,
    ): RedirectResponse {
        if (!$this->isGranted('SORTIE_DELETE', $event)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        if (!$this->isCsrfTokenValid('delete_event', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        $em->remove($event);
        $em->flush();

        return $this->redirect($this->generateUrl('profil_sorties_self'));
    }

    #[Route(path: '/sortie/{id}/annuler', name: 'cancel_event', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cancel(
        Request $request,
        Evt $event,
        EntityManagerInterface $em,
        Mailer $mailer,
    ): RedirectResponse {
        if (!$this->isGranted('SORTIE_CANCEL', $event)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        if (!$this->isCsrfTokenValid('cancel_event', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        $message = $request->request->get('msg');
        if (empty($message)) {
            $this->addFlash('error', 'Veuillez indiquer la raison de l\'annulation');

            return $this->redirectToRoute('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]);
        }

        $event
            ->setCancelled(true)
            ->setCancellationDate(new \DateTimeImmutable())
            ->setCancelledWho($this->getUser())
        ;

        // message aux participants si la sortie est annulée alors qu'elle était publiée
        if ($event->isPublicStatusValide()) {
            /** @var User */
            $user = $this->getUser();
            $participants = $event->getParticipations(null, null);

            foreach ($participants as $participant) {
                // désinscription des (pré-)inscrits de la sortie (hors encadrement)
                if (in_array($participant->getRole(), [EventParticipation::ROLE_MANUEL, EventParticipation::ROLE_INSCRIT, EventParticipation::BENEVOLE], true)) {
                    $event->removeParticipation($participant);
                }

                $mailer->send($participant->getUser(), 'transactional/sortie-annulation', [
                    'event_name' => $event->getTitre(),
                    'commission' => $event->getCommission()->getTitle(),
                    'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    'event_date' => $event->getStartDate()->format('d/m/Y'),
                    'cancel_user_name' => $user->getNickname(),
                    'cancel_user_url' => $this->generateUrl('user_full', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    'message' => $message,
                ]);
            }
        }
        $em->flush();

        $this->addFlash('info', 'La sortie est annulée');

        return $this->redirectToRoute('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]);
    }

    #[Route(path: '/sortie/{id}/uncancel', name: 'sortie_uncancel', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function sortieUncancel(Request $request, Evt $event, EntityManagerInterface $em)
    {
        if (!$this->isCsrfTokenValid('sortie_uncancel', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('SORTIE_UNCANCEL', $event)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        $event
            ->setCancelled(false)
            ->setCancellationDate(null)
            ->setCancelledWho(null);
        $em->flush();

        $this->addFlash('info', 'La sortie est ré-activée');

        return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
    }

    #[Route(path: '/sortie/{id}/contact-participants', name: 'contact_participants', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function contactParticipants(Request $request, Evt $event, Mailer $mailer): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('contact_participants', $request->request->get('csrf_token_contact'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('SORTIE_CONTACT_PARTICIPANTS', $event)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        $receivers = $request->request->all('contact_participant');
        $participations = $event
            ->getParticipations(null, null)
            ->filter(function ($participation) use ($receivers) {
                return \in_array($participation->getId(), $receivers, false);
            })
            ->map(fn (EventParticipation $participation) => $participation->getUser())
            ->toArray()
        ;

        /** @var User */
        $user = $this->getUser();
        $replyToMode = $request->request->get('reply_to_option');
        $replyToAddresses = [];
        if ('everyone' === $replyToMode) {
            foreach ($event->getEncadrants() as $joined) {
                $replyToAddresses[] = $joined->getUser()->getEmail();
            }
        } elseif ('me_only' === $replyToMode) {
            $replyToAddresses = $user->getEmail();
        }

        $mailer->send($participations, 'transactional/message-sortie', [
            'objet' => $request->request->get('objet'),
            'message_author' => sprintf('%s %s', $user->getFirstname(), strtoupper($user->getLastname())),
            'url_sortie' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'name_sortie' => $event->getTitre(),
            'commission' => $event->getCommission()->getTitle(),
            'date_sortie' => $event->getStartDate()?->format('d/m/Y'),
            'message' => $request->request->get('message'),
            'message_author_url' => $this->generateUrl('user_full', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ], [], $user, $replyToAddresses);

        $this->addFlash('info', 'Votre message a bien été envoyé.');

        return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
    }

    #[Route(path: '/sortie/remove-participant/{id}', name: 'sortie_remove_participant', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function removeParticipant(Request $request, EventParticipation $participation, EntityManagerInterface $em, Mailer $mailer): RedirectResponse
    {
        $event = $participation->getEvt();

        if (!$this->isCsrfTokenValid('remove_participant', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('PARTICIPANT_ANNULATION', $participation)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        $em->remove($participation);
        $em->flush();

        /** @var User */
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
                    'event_date' => $event->getStartDate()->format('d/m/Y'),
                    'reason_explanation' => $reason,
                    'user' => $user,
                    'profile_url' => $this->generateUrl('user_full', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ], [], null, $user->getEmail());
            }
        }

        $this->addFlash('info', 'La participation est annulée');

        return $this->redirect($this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]));
    }

    protected function duplicate(Request $request, Evt $event, string $mode = 'empty'): Evt
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
            $event->getJoinMax(),
            $event->getNgensMax(),
            new \DateTimeImmutable()
        );
        $newEvent->setMassif($event->getMassif());
        $newEvent->setPlace($event->getPlace());
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
        $newEvent->setJoinStartDate(new \DateTimeImmutable());
        $newEvent->setAutoAccept($event->isAutoAccept());
        $newEvent->setIsDraft(true);

        // dupliquer les participants ?
        if ('full' === $mode) {
            foreach ($event->getParticipations() as $participation) {
                $newEvent->addParticipation($participation->getUser(), $participation->getRole(), $participation->getStatus());
            }
        }

        return $newEvent;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route(path: '/sortie/{id}/printPDF', name: 'sortie_pdf', requirements: ['id' => '\d+'])]
    public function generatePdf(
        PdfGenerator $pdfGenerator,
        Request $request,
        Environment $twig,
        UserAttrRepository $userAttrRepository,
        Evt $event,
    ): Response {
        if (!$this->isGranted('FICHE_SORTIE', $event)) {
            throw new AccessDeniedHttpException('Not allowed');
        }

        $eventData = $this->eventDetails($request, $event, $userAttrRepository, true);
        $html = $twig->render('sortie/feuille-sortie.html.twig', $eventData);

        return $pdfGenerator->generatePdf($html, $this->getFilename($event->getTitre()) . '.pdf');
    }

    #[Route(path: '/sortie/{id}/printXLSX', name: 'sortie_xlsx', requirements: ['id' => '\d+'])]
    public function generateXLSX(ExcelExport $excelExport, Evt $event, EventParticipationRepository $participationRepository): Response
    {
        $datas = $participationRepository->getSortedParticipations($event, null, EventParticipation::STATUS_VALIDE, true);

        $rsm = [' ', 'PARTICIPANTS (PRÉNOM, NOM)', 'LICENCE', 'AGE', 'TÉL.', 'TÉL. SECOURS', 'EMAIL'];

        return $excelExport->export($this->slugHelper->generateSlug($event->getTitre(), 20), $datas, $rsm, $this->getFilename($event->getTitre()));
    }

    #[Route(path: '/sortie/{id}/rejoindre', name: 'join_event', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function join(
        Request $request,
        Evt $event,
        EntityManagerInterface $em,
        Mailer $mailer,
        UserRepository $userRepository,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('join_event', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('JOIN_SORTIE', $event)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        $data = $request->request->all();
        /** @var User */
        $user = $this->getUser();
        $filiations = $userRepository->getFiliations($user);
        $affiliatedUserIds = [];
        foreach ($filiations as $filiation) {
            $affiliatedUserIds[] = $filiation->getId();
        }
        // on s'ajoute dans la liste pour gérer les mises à jour
        $affiliatedUserIds[] = $user->getId();

        $errTab = [];
        $hasFiliations = false;
        $affiliatedJoiningUsers = [];
        $affiliatedLeavingUsers = [];
        $is_covoiturage = null;
        $paymentUrl = null;

        // affiliés qu'on inscrit
        $idUsersFiliations = !empty($data['id_user_filiation']) ? array_map('intval', $data['id_user_filiation']) : [];
        if (isset($data['filiations']) && 'on' == $data['filiations']) {
            $hasFiliations = true;
            foreach ($idUsersFiliations as $id_user_tmp) {
                $affiliatedJoiningUsers[] = $userRepository->find($id_user_tmp);
            }
        }

        // affiliés qu'on désinscrit
        foreach ($affiliatedUserIds as $affiliatedUserId) {
            if (!\in_array($affiliatedUserId, $idUsersFiliations, true)) {
                $affiliatedLeavingUsers[] = $userRepository->find($affiliatedUserId);
            }
        }

        $joinMessage = $data['message'];

        // CGUs
        if (!isset($data['confirm']) || 'on' != $data['confirm']) {
            $errTab[] = "Merci de cocher la case &laquo; J'ai lu les conditions...&raquo;";
        }

        // sortie publiée ?
        if (Evt::STATUS_PUBLISHED_VALIDE !== $event->getStatus()) {
            $errTab[] = 'Cette sortie ne semble pas publiée, les préinscriptions sont impossibles';
        }

        // verification du timing de la sortie
        if ($event->hasStarted()) {
            $errTab[] = 'Cette sortie a déjà démarré';
        }

        // verification du timing de la sortie : inscriptions
        if (!$event->joinHasStarted()) {
            $errTab[] = 'Les demandes d\'inscription ne sont pas encore ouvertes';
        }

        if (empty($errTab)) {
            $role_evt_join = EventParticipation::ROLE_INSCRIT;

            // Bénévole
            if (isset($data['jeveuxetrebenevole']) && 'on' == $data['jeveuxetrebenevole']) {
                $role_evt_join = EventParticipation::BENEVOLE;
            }

            // si filiations : création du tableau des joints et vérifications
            if ($hasFiliations) {
                if (!\count($idUsersFiliations)) {
                    $errTab[] = 'Merci de choisir au moins une personne à inscrire';
                }
            }

            // pour chaque id envoyé
            foreach ($idUsersFiliations as $id_user_tmp) {
                // sauf moi-meme
                if ($id_user_tmp != $user->getId()) {
                    // vérification que c'est bien mon affilié
                    if (!\in_array($id_user_tmp, $affiliatedUserIds, true)) {
                        $errTab[] = "ID '" . (int) $id_user_tmp . "' invalide pour l'inscription d'un adhérent affilié";
                    }
                }
            }

            // SI PAS DE PB, INTÉGRATION BDD
            if (empty($errTab)) {
                $status_evt_join = EventParticipation::STATUS_NON_CONFIRME;
                $auto_accept = false;
                $nbNewJoins = 1;
                if ($hasFiliations) {
                    $nbNewJoins = \count($idUsersFiliations);
                }

                // vérification nombre de places restantes
                $ngens_max = $event->getNgensMax();
                $current_participants = $event->getParticipationsCount();

                // Si auto_accept est activé, vérifier qu'on n'a pas atteint la limite
                if ($event->isAutoAccept()) {
                    if ($ngens_max && $ngens_max > 0) {
                        // Vérifier si on peut accepter assez d'inscriptions
                        if (($current_participants + $nbNewJoins) <= $ngens_max) {
                            $status_evt_join = EventParticipation::STATUS_VALIDE;
                            $auto_accept = true;
                        }
                    } else {
                        // Si pas de limite définie, accepter automatiquement
                        $status_evt_join = EventParticipation::STATUS_VALIDE;
                        $auto_accept = true;
                    }
                    // Si on a atteint la limite, ne pas accepter automatiquement
                }

                // normal
                $inscrits = [];
                if (!$hasFiliations) {
                    if (!$user->getDoitRenouveler()) {
                        $event->addParticipation($user, $role_evt_join, $status_evt_join);
                        $inscrits[] = $user;
                    } else {
                        $this->addFlash('error', 'Votre licence a expiré. Veuillez renouveler votre adhésion avant de vous inscrire à une sortie.');
                    }
                }
                // filiations
                else {
                    foreach ($affiliatedJoiningUsers as $affiliatedJoiningUser) {
                        // si déjà inscrit => on ne fait rien
                        $joined = $event->getParticipation($affiliatedJoiningUser);
                        if (!$joined) {
                            if (!$affiliatedJoiningUser->getDoitRenouveler()) {
                                $event->addParticipation($affiliatedJoiningUser, $role_evt_join, $status_evt_join);
                                $inscrits[] = $affiliatedJoiningUser;
                            } else {
                                $this->addFlash('error', sprintf('La licence de %s a expiré. L\'adhésion doit être renouvelée avant l\'inscription.', $affiliatedJoiningUser->getFullName()));
                            }
                        }
                    }
                    foreach ($affiliatedLeavingUsers as $affiliatedLeavingUser) {
                        $participation = $event->getParticipation($affiliatedLeavingUser);
                        if ($participation) {
                            $event->removeParticipation($participation);
                        }
                    }
                }
                $em->flush();

                // E-MAIL À L'ORGANISATEUR ET AUX ENCADRANTS
                $destinataires = [];
                $destinataires[] = $event->getUser();
                foreach ($event->getEncadrants() as $encadrant) {
                    $destinataires[] = $encadrant->getUser();
                }

                // infos sur la sortie
                $evtUrl = $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                $evtName = $event->getTitre();
                $evtDate = $event->getStartDate()->format('d/m/Y');
                $commissionTitle = $event->getCommission()->getTitle();
                if ($event->hasPaymentForm() && $event->hasPaymentSendMail()) {
                    $paymentUrl = $event->getPaymentUrl();
                }

                // infos sur le nouvel inscrit (et ses affiliés)
                $mailer->send($destinataires, 'transactional/sortie-demande-inscription', [
                    'role' => $role_evt_join,
                    'event_name' => $evtName,
                    'event_url' => $evtUrl,
                    'event_date' => $evtDate,
                    'auto_accept' => $auto_accept,
                    'commission' => $commissionTitle,
                    'inscrits' => array_map(function ($cetinscrit) {
                        return [
                            'firstname' => ucfirst($cetinscrit->getFirstname()),
                            'lastname' => strtoupper($cetinscrit->getLastname()),
                            'nickname' => $cetinscrit->getNickname(),
                            'email' => $cetinscrit->getEmail(),
                            'profile_url' => $this->generateUrl('user_full', ['id' => $cetinscrit->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                        ];
                    }, $inscrits),
                    'firstname' => ucfirst($user->getFirstname()),
                    'lastname' => strtoupper($user->getLastname()),
                    'nickname' => $user->getNickname(),
                    'message' => $joinMessage,
                    'covoiturage' => $is_covoiturage,
                ], [], $user, $user);

                // E-MAIL AU PRE-INSCRIT
                // inscription auto-acceptée
                if ($auto_accept) {
                    $params = [
                        'role' => $role_evt_join,
                        'event_name' => $evtName,
                        'event_url' => $evtUrl,
                        'event_date' => $evtDate,
                        'commission' => $commissionTitle,
                    ];
                    if ($event->hasPaymentForm() && $event->hasPaymentSendMail()) {
                        $params['hello_asso_url'] = $event->getPaymentUrl();
                    }
                    $mailer->send($user, 'transactional/sortie-participation-confirmee', $params);
                } elseif ($hasFiliations) {
                    $mailer->send($user, 'transactional/sortie-demande-inscription-confirmation', [
                        'role' => $role_evt_join,
                        'event_name' => $evtName,
                        'event_url' => $evtUrl,
                        'event_date' => $evtDate,
                        'commission' => $commissionTitle,
                        'inscrits' => array_map(function ($cetinscrit) {
                            return [
                                'firstname' => ucfirst($cetinscrit->getFirstname()),
                                'lastname' => strtoupper($cetinscrit->getLastname()),
                                'nickname' => $cetinscrit->getNickname(),
                                'email' => $cetinscrit->getEmail(),
                            ];
                        }, $inscrits),
                        'covoiturage' => $is_covoiturage,
                    ]);
                } else {
                    // inscription simple de moi à moi
                    $mailer->send($user, 'transactional/sortie-demande-inscription-confirmation', [
                        'role' => $role_evt_join,
                        'event_name' => $evtName,
                        'event_url' => $evtUrl,
                        'event_date' => $evtDate,
                        'commission' => $commissionTitle,
                        'inscrits' => [
                            [
                                'firstname' => ucfirst($user->getFirstname()),
                                'lastname' => strtoupper($user->getLastname()),
                                'nickname' => $user->getNickname(),
                                'email' => $user->getEmail(),
                            ],
                        ],
                        'covoiturage' => $is_covoiturage,
                    ]);
                }
            }
        }

        return $this->redirectToRoute('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]);
    }

    protected function getFilename(string $eventTitle): string
    {
        return $this->slugHelper->generateSlug($eventTitle, 20) . '.' . date('Y-m-d.H-i-s');
    }

    protected function sendUpdateNotificationEmail(Mailer $mailer, ?Evt $event = null, bool $isNewEvent = true): void
    {
        foreach ($event->getParticipations() as $participation) {
            if ($participation->getUser() === $event->getUser()) {
                // mail already sent
                continue;
            }

            if ($isNewEvent) {
                $mailer->send($participation->getUser(), 'transactional/sortie-publiee-inscrit', [
                    'author_url' => $this->generateUrl('user_full', ['id' => $event->getUser()->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    'author_nickname' => $event->getUser()->getNickname(),
                    'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    'event_name' => $event->getTitre(),
                    'commission' => $event->getCommission()->getTitle(),
                    'event_date' => $event->getStartDate()->format('d/m/Y'),
                    'role' => $participation->getRole(),
                ], [], null, $event->getUser()->getEmail());
            } else {
                $mailer->send($participation->getUser(), 'transactional/sortie-modifiee', [
                    'event_url' => $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    'event_name' => $event->getTitre(),
                    'commission' => $event->getCommission()->getTitle(),
                    'event_date' => $event->getStartDate()->format('d/m/Y'),
                ], [], null, $event->getUser()->getEmail());
            }
        }
    }

    protected function eventDetails(
        Request $request,
        Evt $event,
        UserAttrRepository $userAttrRepository,
        bool $isPdf = false
    ): array {
        $nAccepteesCalc = $event->getParticipationsCount();
        $sortedParticipants = [
            EventParticipation::ROLE_ENCADRANT => [],
            EventParticipation::ROLE_STAGIAIRE => [],
            EventParticipation::ROLE_COENCADRANT => [],
            EventParticipation::ROLE_BENEVOLE => [],
            EventParticipation::BENEVOLE => [],
            EventParticipation::ROLE_INSCRIT => [],
        ];
        $participants = $event->getParticipations();
        foreach ($participants as $participant) {
            $role = $participant->getRole();
            if (EventParticipation::ROLE_INSCRIT === $participant->getRole() || EventParticipation::ROLE_MANUEL === $participant->getRole()) {
                $role = EventParticipation::ROLE_INSCRIT;
            }
            $sortedParticipants[$role][$participant->getUser()->getFullName()] = $participant;
        }
        foreach ($sortedParticipants as $role => $participants) {
            ksort($sortedParticipants[$role]);
        }
        $allParticipants = array_merge(
            $sortedParticipants[EventParticipation::ROLE_ENCADRANT],
            $sortedParticipants[EventParticipation::ROLE_STAGIAIRE],
            $sortedParticipants[EventParticipation::ROLE_COENCADRANT],
            $sortedParticipants[EventParticipation::ROLE_BENEVOLE],
            $sortedParticipants[EventParticipation::BENEVOLE],
            $sortedParticipants[EventParticipation::ROLE_INSCRIT],
        );

        return [
            'event' => $event,
            'nbAcceptes' => $nAccepteesCalc,
            'logo' => LegacyContainer::get('legacy_content_inline')->getLogo(),
            'presidents' => $userAttrRepository->listAllManagement([UserAttr::PRESIDENT]),
            'vicepresidents' => $userAttrRepository->listAllManagement([UserAttr::VICE_PRESIDENT]),
            'participants' => $allParticipants,
            'totalLines' => $nAccepteesCalc + 5,
            'hideBlankLines' => ('y' === $request->query->get('hide_blank')),
            'pdf' => $isPdf,
        ];
    }
}
