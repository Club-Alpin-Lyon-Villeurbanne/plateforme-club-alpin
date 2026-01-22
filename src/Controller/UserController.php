<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Commission;
use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\FormationValidationGroupeCompetence;
use App\Entity\FormationValidationNiveauPratique;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Entity\Usertype;
use App\Form\NomadeType;
use App\Form\UserContactType;
use App\Form\UserFullType;
use App\Mailer\Mailer;
use App\Repository\ArticleRepository;
use App\Repository\EvtRepository;
use App\Repository\FormationReferentielGroupeCompetenceRepository;
use App\Repository\FormationReferentielNiveauPratiqueRepository;
use App\Repository\FormationValidationGroupeCompetenceRepository;
use App\Repository\FormationValidationNiveauPratiqueRepository;
use App\Repository\UserAttrRepository;
use App\Repository\UserNotificationRepository;
use App\Repository\UserRepository;
use App\Security\SecurityConstants;
use App\Service\EmailMarketingSyncService;
use App\UserRights;
use App\Utils\NicknameGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @throws TransportExceptionInterface
     */
    #[Route(path: '/fiche-profil/{id}', name: 'user_profile', requirements: ['id' => '\d+'], priority: 10)]
    #[Template('user/profile.html.twig')]
    public function profile(
        User $user,
        Request $request,
        EntityManagerInterface $manager,
        Mailer $mailer,
        EvtRepository $eventRepository,
        ArticleRepository $articleRepository,
    ): array {
        if ($user->isDeleted()) {
            throw new NotFoundHttpException('Cet adhérent est introuvable');
        }

        $userData = $this->getUserData($user, $request, $manager);
        $this->handleUserContactForm($user, $request, $mailer, $userData);

        $userArticles = $articleRepository->findBy(
            [
                'user' => $user,
                'status' => Article::STATUS_PUBLISHED,
            ],
            ['updatedAt' => 'DESC'],
            6
        );
        $userEvents = $eventRepository->getUserEvents($user, 0, 3, [Evt::STATUS_PUBLISHED_VALIDE]);

        return array_merge($userData, [
            'user' => $user,
            'user_articles' => $userArticles,
            'user_events' => $userEvents,
            'nb_events' => $eventRepository->getUserEventsCount($user, [Evt::STATUS_PUBLISHED_VALIDE]),
            'nb_articles' => $articleRepository->getUserArticlesCount($user),
        ]);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route(path: '/adherents/consulter/{id}', name: 'user_view', requirements: ['id' => '\d+'])]
    #[Template('user/view.html.twig')]
    public function view(
        Request $request,
        User $user,
        UserRights $userRights,
        UserRepository $userRepository,
        EvtRepository $eventRepository,
        EntityManagerInterface $manager,
    ): array {
        if (!$this->isGranted(SecurityConstants::ROLE_ADMIN) && !$userRights->allowed('user_read_private')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les droits nécessaires pour accéder à cette page');
        }

        $userData = $this->getUserData($user, $request, $manager);
        $parent = $userRepository->findOneBy(['cafnum' => $user->getCafnumParent()]);
        $filiations = $userRepository->findBy(['cafnumParent' => $user->getCafnum()]);

        $userArticles = $manager->getRepository(Article::class)->findBy(
            [
                'user' => $user,
                'status' => Article::STATUS_PUBLISHED,
            ],
            ['updatedAt' => 'DESC'],
        );
        $userEvents = $eventRepository->getUserEvents($user, 0, 200, [Evt::STATUS_PUBLISHED_VALIDE], [EventParticipation::STATUS_VALIDE]);

        return array_merge($userData, [
            'user' => $user,
            'filiations' => $filiations,
            'parent' => $parent,
            'user_articles' => $userArticles,
            'user_events' => $userEvents,
        ]);
    }

    /**
     * @throws NonUniqueResultException
     * @throws TransportExceptionInterface
     * @throws NoResultException
     */
    #[Route(path: '/user-full/{id}.html', name: 'user_full', requirements: ['id' => '\d+'], priority: 10)]
    #[IsGranted('ROLE_USER')]
    #[Template('user/full.html.twig')]
    public function full(
        User $user,
        UserRights $userRights,
        EntityManagerInterface $manager,
        Request $request,
        EvtRepository $eventRepository,
        Mailer $mailer,
        FormationReferentielNiveauPratiqueRepository $referentielNiveauPratiqueRepository,
        FormationReferentielGroupeCompetenceRepository $referentielGroupeCompetenceRepository,
        FormationValidationNiveauPratiqueRepository $formationNiveauRepository,
        FormationValidationGroupeCompetenceRepository $formationCompetenceValidationRepository,
    ): array {
        if ($user->isDeleted()) {
            throw new NotFoundHttpException('Cet adhérent est introuvable');
        }
        if (!$userRights->allowed('user_read_limited') && !$userRights->allowed('user_read_private')) {
            throw $this->createAccessDeniedException('Désolé. Vous n\'avez pas les droits requis pour afficher cette page');
        }

        $userData = $this->getUserData($user, $request, $manager);
        $this->handleUserContactForm($user, $request, $mailer, $userData);

        $userArticles = $manager->getRepository(Article::class)->findBy(
            [
                'user' => $user,
                'status' => Article::STATUS_PUBLISHED,
            ],
            ['updatedAt' => 'DESC'],
        );
        $userEvents = $eventRepository->getUserEvents($user, 0, 200, [Evt::STATUS_PUBLISHED_VALIDE]);

        $commissions = [];
        $nivRefs = [];
        $groupesCompRefs = [];

        // niveaux de pratique
        $niveaux = $formationNiveauRepository->getAllNiveauxByUser($user);
        /** @var FormationValidationNiveauPratique $niveau */
        foreach ($niveaux as $niveau) {
            $nivRefs[$niveau->getNiveauReferentiel()->getId()] = $niveau->getNiveauReferentiel();

            $commissionsNiveau = $referentielNiveauPratiqueRepository->getCommissionsByReferentiel($niveau->getNiveauReferentiel());
            foreach ($commissionsNiveau as $commission) {
                $commissions[$commission->getId()] = $commission;
            }
        }

        // groupes de compétences
        $groupesComps = $formationCompetenceValidationRepository->getAllGroupesCompetencesByUser($user);
        /** @var FormationValidationGroupeCompetence $groupesComp */
        foreach ($groupesComps as $groupesComp) {
            $groupesCompRefs[$groupesComp->getCompetence()->getId()] = $groupesComp->getCompetence();

            $commissionsGroupeComp = $referentielGroupeCompetenceRepository->getCommissionsByReferentiel($groupesComp->getCompetence());
            foreach ($commissionsGroupeComp as $commission) {
                $commissions[$commission->getId()] = $commission;
            }
        }

        return array_merge($userData, [
            'user' => $user,
            'user_articles' => $userArticles,
            'user_events' => $userEvents,
            'nb_events' => $eventRepository->getUserEventsCount($user, [Evt::STATUS_PUBLISHED_VALIDE]),
            'nb_articles' => count($userArticles),
            'niveaux' => $nivRefs,
            'commissions' => $commissions,
            'groupes_competences' => $groupesCompRefs,
        ]);
    }

    #[Route(path: '/statuts-adherents', name: 'user_status_list', methods: ['GET'])]
    #[Template('user/status-list.html.twig')]
    public function userStatusList(EntityManagerInterface $manager, UserAttrRepository $userAttrRepository, UserRights $userRights): array
    {
        if (!$userRights->allowed('user_see_status_list')) {
            throw $this->createAccessDeniedException('Not allowed');
        }

        $ignoredRoles = [UserAttr::VISITEUR, UserAttr::ADHERENT, UserAttr::DEVELOPPEUR];
        $listedRoles = [];
        $listedUsers = [];
        $users = [];
        $counts = [];

        $roles = $manager->getRepository(Usertype::class)->findBy([], ['hierarchie' => 'ASC']);
        foreach ($roles as $role) {
            if (!\in_array($role->getCode(), $ignoredRoles, true)) {
                $listedRoles[$role->getCode()] = $role->getTitle();
                $users[$role->getCode()] = $userAttrRepository->listAllUsersByRole($role);
            }
        }

        foreach ($users as $roleCode => $roleUsers) {
            /** @var UserAttr $userAttr */
            foreach ($roleUsers as $userAttr) {
                $listedUsers[$roleCode][$userAttr->getUser()->getId()] = $userAttr->getUser()->getNickname();
            }
        }
        foreach ($listedRoles as $code => $role) {
            $counts[$code] = \count($listedUsers[$code]);
        }

        return [
            'roles' => $listedRoles,
            'users' => $listedUsers,
            'counts' => $counts,
        ];
    }

    #[Route(path: '/selectionner-manuel/{event}', name: 'event_manual_add_select', requirements: ['event' => '\d+'], methods: ['GET', 'POST'])]
    #[Template('user/manual-add-select.html.twig')]
    public function manualAddSelect(
        Request $request,
        Evt $event,
        UserRepository $userRepository,
    ): array|Response {
        if (!$this->isGranted('EVENT_JOINING_ADD', $event)) {
            throw new AccessDeniedHttpException('Not allowed');
        }

        if ($request->isMethod('POST') && !$this->isCsrfTokenValid('event_manual_add_select', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        $show = $request->query->get('show') ?: 'allvalid';

        return [
            'event' => $event,
            'show' => $show,
        ];
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route(path: '/ajouter-manuel/{event}', name: 'event_manual_add', requirements: ['event' => '\d+'], methods: ['GET', 'POST'])]
    #[Template('user/manual-add.html.twig')]
    public function manualAdd(
        Request $request,
        Evt $event,
        UserRepository $userRepository,
        Mailer $mailer,
        EntityManagerInterface $em,
    ): array|Response {
        if (!$this->isCsrfTokenValid('event_manual_add', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('EVENT_JOINING_ADD', $event)) {
            throw new AccessDeniedHttpException('Not allowed');
        }

        $data = $request->request->all();

        if (empty($data['id_user'])) {
            $this->addFlash('error', 'Veuillez sélectionner le.s adhérent.s à inscrire');

            return $this->redirectToRoute('event_manual_add_select', ['event' => $event->getId()]);
        }

        // traitement
        if (!empty($data['role_evt_join'])) {
            // verification de la validité de la sortie
            if (!$event->isPublicStatusValide()) {
                $this->addFlash('error', 'Cette sortie ne semble pas publiée, les préinscriptions sont impossibles');

                return new Response(
                    '<script>
                        window.parent.location.reload();
                    </script>'
                );
            }

            // comptage des participants actuels
            $nbPeopleMax = $event->getNgensMax();
            $currentParticipantNb = $event->getParticipationsCount();

            $isCurrentUserEncadrant = false;
            foreach ($event->getEncadrants(EventParticipation::ROLES_ENCADREMENT_ETENDU) as $eventParticipation) {
                if ($eventParticipation->getUser() === $this->getUser()) {
                    $isCurrentUserEncadrant = true;
                    break;
                }
            }

            // reste-t-il assez de place ?
            if ((\count($data['id_user']) + $currentParticipantNb) > $nbPeopleMax) {
                $availableSpotNb = $nbPeopleMax - $currentParticipantNb;
                if ($availableSpotNb < 0) {
                    $availableSpotNb = 0;
                }
                $this->addFlash('error', 'Vous ne pouvez pas inscrire plus de participants que de places disponibles (' . $availableSpotNb . '). Vous pouvez augmenter le nombre maximum de places pour ensuite rajouter des personnes.');

                return new Response(
                    '<script>
                        window.parent.location.reload();
                    </script>'
                );
            }

            // liste des encadrants
            $destinataires = [];
            // pour ne pas avoir 2 fois l'organisateur, on met l'id user en clé
            $destinataires[$event->getUser()->getId()] = $event->getUser();
            foreach ($event->getEncadrants() as $encadrant) {
                $destinataires[$encadrant->getUser()->getId()] = $encadrant->getUser();
            }

            // infos sur la sortie
            $evtUrl = $this->generateUrl('sortie', ['code' => $event->getCode(), 'id' => $event->getId()]);
            $evtName = $event->getTitre();
            $evtDate = $event->getStartDate()->format('d/m/Y');
            $commissionTitle = $event->getCommission()->getTitle();

            // enregistrement
            $inscrits = [];
            foreach ($data['id_user'] as $key => $userId) {
                $user = $userRepository->find($userId);

                if ($user->isDeleted()) {
                    $this->addFlash('error', 'Le compte de ' . $user->getFullName() . ' est supprimé. Impossible de l\'inscrire.');
                    continue;
                }

                if (!$user->getDoitRenouveler()) {
                    $role = $data['role_evt_join'][$key] ?? EventParticipation::ROLE_MANUEL;
                    $status = EventParticipation::STATUS_NON_CONFIRME;
                    if ($this->getUser() === $event->getUser() || $isCurrentUserEncadrant) {
                        $status = EventParticipation::STATUS_VALIDE;
                    }

                    $event->addParticipation($user, $role, $status);
                    $inscrits[] = $user;

                    // envoi des emails
                    // envoi du mail à l'adhérent
                    $mailer->send($user, 'transactional/sortie-inscription', [
                        'role' => 'manuel' === $role ? null : $role,
                        'event_name' => $evtName,
                        'event_url' => $evtUrl,
                        'event_date' => $evtDate,
                        'commission' => $commissionTitle,
                        'status' => EventParticipation::STATUS_VALIDE === $status ? 'accepté' : 'pré-inscrit',
                    ]);
                } else {
                    $this->addFlash('error', 'La licence de ' . $user->getFullName() . ' a expiré. L\'adhésion doit être renouvelée avant l\'inscription.');
                }
            }
            $em->flush();

            // envoi des mails aux encadrants
            foreach ($destinataires as $destinataire) {
                $mailer->send($destinataire, 'transactional/sortie-inscription-manuelle', [
                    'role' => 'manuel',
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
                            'profile_url' => $this->generateUrl('user_full', ['id' => $cetinscrit->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                        ];
                    }, $inscrits),
                    'firstname' => ucfirst($this->getUser()->getFirstname()),
                    'lastname' => strtoupper($this->getUser()->getLastname()),
                    'nickname' => $this->getUser()->getNickname(),
                ], [], null, $this->getUser()->getEmail());
            }

            $this->addFlash('success', 'Les adhérents sélectionnés ont bien été inscrits à la sortie.');

            return new Response(
                '<script>
                    window.parent.location.reload();
                </script>'
            );
        }

        return [
            'user_ids' => $data['id_user'],
            'user_civ' => $data['civ_user'],
            'user_lastnames' => $data['lastname_user'],
            'user_firstnames' => $data['firstname_user'],
            'user_nicknames' => $data['nickname_user'],
            'event' => $event,
        ];
    }

    #[Route(path: '/ajouter-nomade/{event}', name: 'event_nomad_add', requirements: ['event' => '\d+'], methods: ['GET', 'POST'], priority: '15')]
    #[Template('user/nomad-add.html.twig')]
    public function nomadAdd(
        Request $request,
        Evt $event,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ?User $nomad = null,
    ): array|Response {
        if (!$this->isGranted('EVENT_NOMAD_JOINING_ADD', $event)) {
            throw new AccessDeniedHttpException('Not allowed');
        }

        $nomads = $userRepository->getNomads();

        if (!$nomad) {
            $nomad = new User();
        }

        $form = $this->createForm(NomadeType::class, $nomad, [
            'existing_users' => $nomads,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $request->request->all();
            $userData = $data['nomade'] ?? [];
            $formData = $data['form'] ?? [];
            $formData = array_merge($userData, $formData);

            $nomad = $userRepository->find($formData['id_user']);

            if (!$nomad instanceof User) {
                $this->addFlash('error', 'Le non-adhérent n\'existe pas.');

                return new Response(
                    '<script>
                    window.parent.location.reload();
                </script>'
                );
            }

            // forcer null pour éviter de pêter la contrainte d'unicité
            if (empty($nomad->getEmail())) {
                $nomad->setEmail(null);
            }
            $entityManager->persist($nomad);

            $event->addParticipation($nomad, EventParticipation::ROLE_MANUEL);
            $entityManager->flush();

            $this->addFlash('success', 'Le non-adhérent a bien été inscrit à la sortie.');

            return new Response(
                '<script>
                    window.parent.location.reload();
                </script>'
            );
        }

        return [
            'form' => $form,
            'nomads' => $nomads,
        ];
    }

    #[Route(path: '/adherents/{show}', name: 'user_list')]
    #[Template('user/index.html.twig')]
    public function index(
        UserRights $userRights,
        ?string $show = null,
    ): array {
        if (!$userRights->allowed('user_see_all')) {
            throw $this->createAccessDeniedException('Not allowed');
        }

        if (!$show) {
            $show = 'allvalid';
        }

        return [
            'show' => $show,
        ];
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    #[Route('/users/data/{page}/{show}', name: 'users_data')]
    public function data(
        Request $request,
        UserRights $userRights,
        UserRepository $userRepository,
        EvtRepository $eventRepository,
        string $page = 'users-list',
        ?string $show = null
    ): JsonResponse {
        if (!$userRights->allowed('user_see_all')) {
            throw $this->createAccessDeniedException('Not allowed');
        }

        if (!$show) {
            $show = 'allvalid';
        }
        $start = $request->query->getInt('start', 0);
        $length = $request->query->getInt('length', 100);
        $searchText = $request->query->all()['search']['value'] ?? null;
        $order = $request->query->all()['order'] ?? null;
        $eventId = $request->query->getInt('event', 0);
        $usersToIgnore = $this->getEventParticipants($eventId, $eventRepository);

        $recordsFiltered = $userRepository->getUsersCount($show, $searchText, $usersToIgnore);
        $recordsTotal = $userRepository->getUsersCount($show, '', $usersToIgnore);
        $data = $userRepository->getUsers($show, $start, $length, $searchText, $order, $usersToIgnore);

        $img_lock = '<img src="/img/base/lock_gray.png" alt="caché"  title="Vous devez disposer de droits supérieurs pour afficher cette information" />';

        $results = [];
        /** @var User $user */
        foreach ($data as $user) {
            $tools = '';
            // view user
            if ($this->isGranted(SecurityConstants::ROLE_ADMIN) || $userRights->allowed('user_read_private')) {
                $tools .= '<a href="' . $this->generateUrl('user_view', ['id' => $user->getId()]) . '" class="fancyframe" title="Consulter cet utilisateur"><img src="/img/base/report.png" alt="consulter" /></a> ';
            }
            // gestion des droits
            if (
                $this->isGranted(SecurityConstants::ROLE_ADMIN)
                || $userRights->allowed('user_giveright_1')
                || $userRights->allowed('user_giveright_2')
                || $userRights->allowed('user_giveright_3')
                || $userRights->allowed('comm_lier_encadrant')
                || $userRights->allowed('user_givepresidence')
                || $userRights->allowed('comm_delier_encadrant')
                || $userRights->allowed('comm_delier_responsable')
            ) {
                $tools .= '<a href="' . $this->generateUrl('user_right_manage', ['user' => $user->getId()]) . '" class="fancyframe" title="Voir / Attribuer des responsabilités à cet adhérent"><img src="/img/base/user_star.png" alt="droits" /></a> ';
            }
            // edit user
            if ($userRights->allowed('user_edit_notme')) {
                $tools .= '<a href="' . $this->generateUrl('user_update', ['id' => $user->getId()]) . '" class="fancyframe" title="Modifier cet utilisateur"><img src="/img/base/user_edit.png" alt="modifier" /></a> ';
            }
            // impersonate user
            if ($this->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
                $tools .= (!empty($user->getEmail())) ? ' <a href="' . $this->generateUrl('my_profile') . '?_switch_user=' . urlencode($user->getEmail()) . '" title="Impersonifier cet utilisateur"><img src="/img/base/user_go.png" alt="impersonifier" /></a> ' : '';
            }

            // âge
            if ($userRights->allowed('user_read_private')) {
                if (!empty($user->getBirthdate())) {
                    $birthdate = $user->getBirthdate();
                    $age = $birthdate->diff(new \DateTime())->y . ' ans';
                } else {
                    $age = '...';
                }
            } else {
                $age = $img_lock;
            }

            // tél x 2
            if ($userRights->allowed('user_read_private')) {
                $tel = $user->getTel();
                if ($this->isGranted(SecurityConstants::ROLE_ADMIN)) {
                    $tel .= '<br />' . $user->getTel2();
                }
            } else {
                $tel = $img_lock;
            }

            // cafnum + infos
            $cafnum = $user->getCafnum() . ' ';
            if (User::PROFILE_DISCOVERY === $user->getProfileType()) {
                $cafnum .= '<span title="Carte découverte">🧐</span>';
            } elseif (User::PROFILE_OTHER_CLUB_MEMBER === $user->getProfileType()) {
                $cafnum .= '<span title="Adhérent d\'un autre club">🌍</span>';
            } elseif (User::PROFILE_EXTERNAL_PERSON === $user->getProfileType()) {
                $cafnum .= '<span title="Personne externe">✍️</span>';
            }

            // date licence
            $joinDate = $user->getJoinDate();
            $formattedDate = (!empty($joinDate) ? $joinDate->format('d/m/Y') : '');
            if (User::PROFILE_DISCOVERY === $user->getProfileType()) {
                $formattedDate = (!empty($joinDate) ? $joinDate->format('d/m/Y H:i:s') : '');
                $formattedDate .= (!empty($user->getDiscoveryEndDatetime()) ? ' au ' . $user->getDiscoveryEndDatetime()->format('d/m/Y H:i:s') : '');
            }
            if ($user->getDoitRenouveler()) {
                $renew = '<span  style="color:red" title="' . ($userRights->allowed('user_read_private') ? $formattedDate : '') . '">Licence expirée</span>';
            } else {
                $renew = ($userRights->allowed('user_read_private') ? $formattedDate : $img_lock);
            }

            // e-mail
            if (!empty($user->getEmail())) {
                $email = ($userRights->allowed('user_read_private') ? '<a href="mailto:' . $user->getEmail() . '" title="Contact direct">' . $user->getEmail() . '</a>' : $img_lock);
            } else {
                $email = '';
            }

            // licence
            $license = ($user->getDoitRenouveler() ? 'expirée' : 'valide') . ' ' . (!$user->getDoitRenouveler() && $user->getAlerteRenouveler() ? '<span style="color:red">* Doit renouveler</span>' : '');

            // compte supprimé ?
            if ($user->isDeleted()) {
                $deleted = 'oui';
            } else {
                $deleted = 'non';
            }

            // case à cocher
            $check = '<img src="/img/label-up.png" class="tick" alt="CHECKED" title="" />
                        <img src="/img/label-down.png" class="cross" alt="OFF" title="" />
                        <input type="checkbox" name="id_user[]" value="' . $user->getId() . '" />
                        <input type="hidden" disabled="disabled" name="civ_user[]" value="' . $user->getCiv() . '" />
                        <input type="hidden" disabled="disabled" name="lastname_user[]" value="' . $user->getLastname() . '" />
                        <input type="hidden" disabled="disabled" name="firstname_user[]" value="' . $user->getFirstname() . '" />
                        <input type="hidden" disabled="disabled" name="nickname_user[]" value="' . $user->getNickname() . '" />';
            // email renseigné ?
            $emailInfo = '';
            if (!empty($user->getEmail())) {
                $emailInfo = 'oui';
            } else {
                $emailInfo = '<span style="color: darkorange;" title="Ce compte ne peut pas recevoir les e-mails">non</span>';
            }

            $resultLine = [
                'id' => $user->getId(),
                'cafnum' => $cafnum,
                'lastname' => strtoupper($user->getLastname()),
                'firstname' => ucfirst($user->getFirstname()),
                'renew' => $renew,
                'nickname' => '<a href="' . $this->generateUrl('user_profile', ['id' => $user->getId()]) . '" class="fancyframe userlink" title="Voir la fiche">' . $user->getNickname() . '</a>',
                'age' => $age,
                'tel' => $tel,
                'email' => $email,
            ];
            if ('users-list' === $page) {
                $resultLine = array_merge($resultLine, [
                    'tools' => $tools,
                    'cp' => $user->getCp(),
                    'ville' => $user->getVille(),
                    'license' => $license,
                    'deleted' => $deleted,
                ]);
            } elseif ('manual-add' === $page) {
                $resultLine = array_merge($resultLine, [
                    'check' => $check,
                    'email_info' => $emailInfo,
                ]);
            }

            $results[] = $resultLine;
        }

        return new JsonResponse([
            'draw' => $request->query->getInt('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $results,
        ]);
    }

    #[Route(path: '/adherents-creer.html', name: 'user_create', priority: 10)]
    #[Route(path: '/adherents/modifier/{id}.html', name: 'user_update', requirements: ['id' => '\d+'], priority: 10)]
    #[Template('user/form.html.twig')]
    public function update(
        Request $request,
        UserRights $userRights,
        EntityManagerInterface $manager,
        EmailMarketingSyncService $emailMarketingService,
        ?User $user = null,
    ): array|Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $isUpdate = true;

        if (!$user instanceof User) {
            $user = new User();
            $user->setNomadeParent($currentUser->getId());
            $isUpdate = false;
        }

        if (
            !$isUpdate && !$userRights->allowed('user_create_manually')
            || $isUpdate && !$this->isGranted(SecurityConstants::ROLE_ADMIN) && !$userRights->allowed('user_edit_notme')
        ) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les droits nécessaires pour accéder à cette page');
        }

        $form = $this->createForm(UserFullType::class, $user, ['is_edit' => $isUpdate]);

        $form->handleRequest($request);
        $hasErrors = false;
        if ($form->isSubmitted() && $form->isValid()) {
            $routeTarget = 'external';
            /** @var User $user */
            $user = $form->getData();

            if (!$isUpdate) {
                // vérification anti doublon de licence
                $requestedCafnum = $user->getCafnum();
                $existingUserWithCafnum = $manager->getRepository(User::class)->findOneBy(['cafnum' => $requestedCafnum]);
                if ($existingUserWithCafnum instanceof User && ($existingUserWithCafnum->getId() !== $user->getId() || !$isUpdate)) {
                    $form->get('cafnum')->addError(new FormError('Un compte existe déjà avec ce numéro de licence.'));
                    $hasErrors = true;
                }

                // vérification anti doublon d'email
                $requestedEmail = $user->getEmail();
                $existingUserWithEmail = $manager->getRepository(User::class)->findOneBy(['email' => $requestedEmail]);
                if ($existingUserWithEmail instanceof User && ($existingUserWithEmail->getId() !== $user->getId() || !$isUpdate)) {
                    $form->get('email')->addError(new FormError('Un compte existe déjà avec cette adresse e-mail.'));
                    $hasErrors = true;
                }

                // détection type de profil
                $clubPrefix = $this->getParameter('club_cafnum_prefix');
                if (str_starts_with($user->getCafnum(), 'D') || str_starts_with($user->getCafnum(), 'd') || str_starts_with($user->getCafnum(), $clubPrefix)) {
                    $form->get('cafnum')->addError(new FormError('Vous ne devez pas ajouter d\'adhérent du club ou de carte découverte par ce formulaire.'));
                    $hasErrors = true;
                } elseif (is_numeric($user->getCafnum())) {
                    $routeTarget = 'nomade';
                    $user->setProfileType(User::PROFILE_OTHER_CLUB_MEMBER);
                } else {
                    $user->setProfileType(User::PROFILE_EXTERNAL_PERSON);
                }
            }

            if (!$hasErrors) {
                // tout est bon
                if (!$isUpdate) {
                    $user->setCreatedAt(new \DateTime());
                    $nickname = NicknameGenerator::generateNickname($user->getFirstname(), $user->getLastname(), $user->getCafnum());
                    $user->setNickname($nickname);
                    $user->setManuel(true);
                    $user->setNomade(true);
                    $user->setDoitRenouveler(true);     // sécurité pour éviter trop d'accès
                    $user->setBirthdate(\DateTimeImmutable::createFromMutable($user->getBirthdate()));
                }
                $user->setUpdatedAt(new \DateTime());

                $manager->persist($user);
                $manager->flush();

                if (!$isUpdate) {
                    // Synchroniser avec MailerLite après création manuelle
                    try {
                        $emailMarketingService->syncUsers($user);
                    } catch (\Exception $exception) {
                        // Log l'erreur mais ne pas bloquer la création
                        // Les logs seront automatiquement gérés par le service EmailMarketingSyncService
                    }
                }

                $this->addFlash('success', $isUpdate ? 'L\'utilisateur a bien été modifié.' : 'L\'utilisateur a bien été créé.');

                if ($isUpdate) {
                    return new Response(
                        '<script>
                            window.parent.location.reload();
                        </script>'
                    );
                }

                return $this->redirectToRoute('user_list', ['show' => $routeTarget]);
            }
        }

        return [
            'user' => $user,
            'is_update' => $isUpdate,
            'form' => $form,
            'title' => $isUpdate ? 'Modifier un utilisateur' : 'Créer un utilisateur',
            'template' => $isUpdate ? 'base-light.html.twig' : 'base-wide.html.twig',
        ];
    }

    #[Route(path: '/adherents/supprimer/{id}', name: 'user_delete_confirm', requirements: ['id' => '\d+'], methods: ['GET'], priority: 10)]
    #[Template('user/delete-confirm.html.twig')]
    public function deleteConfirm(
        User $user,
        UserRights $userRights,
    ): array {
        if (!$this->isGranted(SecurityConstants::ROLE_ADMIN) || !$userRights->allowed('user_delete')) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        return [
            'user' => $user,
        ];
    }

    #[Route(path: '/adherents/desactiver/{id}', name: 'user_disable', requirements: ['id' => '\d+'], methods: ['POST'], priority: 10)]
    public function disable(
        Request $request,
        User $user,
        UserRights $userRights,
        EntityManagerInterface $manager,
        UserNotificationRepository $userNotificationRepository,
        UserAttrRepository $userAttrRepository,
    ): Response {
        if (!$this->isGranted(SecurityConstants::ROLE_ADMIN) || !$userRights->allowed('user_delete')) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        if (!$this->isCsrfTokenValid('delete_user', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        // nettoyage des tables liées
        $userNotificationRepository->deleteByUser($user);
        $userAttrRepository->deleteByUser($user);

        // nettoyage image de profil
        if (null !== $user->getProfilePicture()) {
            $filesystem = new Filesystem();
            $imagePath = $this->getParameter('public_dir') . '/ftp/uploads/files/' . $user->getProfilePicture()->getFilename();
            $filesystem->remove($imagePath);
        }

        $user->setIsDeleted(true);
        $manager->persist($user);
        $manager->flush();

        $this->addFlash('success', 'L\'utilisateur a bien été supprimé.');

        return new Response(
            '<script>
                window.parent.location.reload();
            </script>'
        );
    }

    protected function getEventParticipants(int $eventId, EvtRepository $eventRepository): array
    {
        if (empty($eventId)) {
            return [];
        }

        $event = $eventRepository->find($eventId);
        if (!$event instanceof Evt) {
            return [];
        }

        $eventParticipants = [];
        $participations = $event->getParticipations(null, null);
        foreach ($participations as $participation) {
            $eventParticipants[] = $participation->getUser();
        }

        return $eventParticipants;
    }

    /**
     * @throws NonUniqueResultException
     * @throws TransportExceptionInterface
     */
    protected function handleUserContactForm(
        User $user,
        Request $request,
        Mailer $mailer,
        array $userData,
    ): void {
        $form = $userData['form'];
        $form->handleRequest($request);

        if ($this->getUser() && $form->isSubmitted() && $form->isValid()) {
            $data = $request->request->all();
            $formData = $data['user_contact'] ?? [];

            $nom = $this->getUser()->getFullname() . ' (' . $this->getUser()->getNickname() . ')';
            $shortName = $this->getUser()->getFullname();
            $email = $this->getUser()->getEmail();
            $eventLink = $articleLink = '';

            $event = $userData['event'] ?? null;
            if ($event instanceof Evt) {
                $eventLink = $this->generateUrl('sortie', ['id' => $event->getId(), 'code' => $event->getCode()], UrlGeneratorInterface::ABSOLUTE_URL);
            }

            $article = $userData['article'] ?? null;
            if ($article instanceof Article) {
                $articleLink = $this->generateUrl('article_view', ['id' => $article->getId(), 'code' => $article->getCode()], UrlGeneratorInterface::ABSOLUTE_URL);
            }

            $mailer->send($user->getEmail(), 'transactional/contact-form', [
                'contact_name' => $nom,
                'contact_shortname' => $shortName,
                'contact_email' => $email,
                'contact_url' => $this->generateUrl('user_full', ['id' => $this->getUser()->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'contact_objet' => $formData['objet'],
                'message' => $formData['message'],
                'eventName' => $event instanceof Evt ? $event->getTitre() : '',
                'eventLink' => $event instanceof Evt ? $eventLink : '',
                'commission' => $event instanceof Evt ? $event->getCommission()->getTitle() : '',
                'articleTitle' => $article instanceof Article ? $article->getTitre() : '',
                'articleLink' => $article instanceof Article ? $articleLink : '',
            ], [], null, $email);

            $this->addFlash('success', 'Votre message a bien été envoyé.');
        }
    }

    /**
     * @throws NonUniqueResultException
     */
    protected function getUserData(
        User $user,
        Request $request,
        EntityManagerInterface $manager,
    ): array {
        $id_event = $id_article = 0;
        if (!empty($request->get('id_event'))) {
            $id_event = $request->get('id_event');
        }
        if (!empty($request->get('id_article'))) {
            $id_article = $request->get('id_article');
        }

        $article = $manager->getRepository(Article::class)->find($id_article);
        $event = $manager->getRepository(Evt::class)->find($id_event);

        $defaultObject = '';
        if ($article) {
            $defaultObject = $article->getTitre();
        } elseif ($event) {
            $defaultObject = $event->getTitre();
        }

        $form = $this->createForm(UserContactType::class, null, [
            'user_id' => $user->getId(),
            'article_id' => $id_article,
            'event_id' => $id_event,
            'default_object' => $defaultObject,
        ]);

        ['absences' => $absences, 'presences' => $presences] = $manager
            ->getRepository(EventParticipation::class)
            ->getEventPresencesAndAbsencesOfUser($user->getId())
        ;
        $total = $presences + $absences;
        $fiabilite = $total > 0 ? round(($presences / $total) * 100) : 100;

        $userRights = $user->getAttributes();
        $roles = [
            'club' => [],
            'commission' => [],
        ];
        foreach ($userRights as $userRight) {
            $commissionCode = $userRight->getCommission();
            if (empty($commissionCode)) {
                $roles['club'][] = $userRight;
            } else {
                $commission = $manager->getRepository(Commission::class)->findOneBy(['code' => $commissionCode]);
                if (!in_array($commission->getTitle(), array_keys($roles['commission']), true)) {
                    $roles['commission'][$commission->getTitle()] = $userRight;
                }
            }
        }

        return [
            'fiabilite' => $fiabilite,
            'nb_absences' => $absences,
            'total_sorties' => $total,
            'club_roles' => $roles['club'],
            'comm_roles' => $roles['commission'],
            'id_event' => $id_event,
            'id_article' => $id_article,
            'default_object' => $defaultObject,
            'style' => count($form->getErrors(true, true)) > 0 ? '' : 'display: none',
            'reversed_style' => count($form->getErrors(true, true)) > 0 ? 'display: none' : '',
            'form' => $form,
            'event' => $event,
            'article' => $article,
        ];
    }
}
