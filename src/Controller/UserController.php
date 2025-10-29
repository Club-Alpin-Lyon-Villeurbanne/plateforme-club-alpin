<?php

namespace App\Controller;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Entity\Usertype;
use App\Form\NomadeType;
use App\Legacy\LegacyContainer;
use App\Mailer\Mailer;
use App\Repository\UserAttrRepository;
use App\Repository\UserRepository;
use App\Security\SecurityConstants;
use App\UserRights;
use App\Utils\NicknameGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserController extends AbstractController
{
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

        $eventParticipants = [];
        $participations = $event->getParticipations(null, null);
        foreach ($participations as $participation) {
            $eventParticipants[] = $participation->getUser();
        }
        $show = $request->query->get('show') ?: 'valid';

        return [
            'event' => $event,
            'users' => $userRepository->findUsersToRegister($eventParticipants, $show),
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
            $destinataires[] = $event->getUser();
            foreach ($event->getEncadrants() as $encadrant) {
                $destinataires[] = $encadrant->getUser();
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
                            'profile_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'user-full/' . $cetinscrit->getId() . '.html',
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

        $myNomads = $userRepository->getNomads($this->getUser());

        if (!$nomad) {
            $nomad = new User();
        }

        $form = $this->createForm(NomadeType::class, $nomad, [
            'existing_users' => $myNomads,
        ]);

        $form->handleRequest($request);
        $errors = 0;

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $request->request->all();
            $userData = $data['nomade'] ?? [];
            $formData = $data['form'] ?? [];
            $formData = array_merge($userData, $formData);

            /* @var User $nomad */
            if (!empty($formData['id_user'])) {
                $nomad = $userRepository->find($formData['id_user']);
                $nomad->setEmail($formData['email'] ?? null);
                $nomad->setTel($formData['tel'] ?? null);
                $nomad->setTel2($formData['tel2'] ?? null);
            } else {
                $nomad = $form->getData();

                $existingUserWithSameEmail = null;
                if (!empty($nomad->getEmail())) {
                    $existingUserWithSameEmail = $userRepository->findOneBy(['email' => $nomad->getEmail()]);
                }
                if ($existingUserWithSameEmail instanceof User) {
                    ++$errors;
                    $form->get('email')->addError(new FormError('Un utilisateur existe déjà avec cette adresse e-mail.'));
                }
                $existingUserWithSameCafnum = $userRepository->findOneBy(['cafnum' => $nomad->getCafnum()]);
                if ($existingUserWithSameCafnum instanceof User) {
                    ++$errors;
                    $form->get('cafnum')->addError(new FormError('Un utilisateur existe déjà avec ce numéro de licence.'));
                }

                $nomad
                    ->setNickname(NicknameGenerator::generateNickname($nomad->getFirstname(), $nomad->getLastname()))
                    ->setNomade(true)
                    ->setValid(true)
                    ->setManuel(false)
                    ->setNomadeParent($this->getUser()->getId())
                    ->setDoitRenouveler(false)
                    ->setAlerteRenouveler(false)
                    ->setCreatedAt(new \DateTime())
                    ->setUpdatedAt(new \DateTime())
                    ->setCookietoken('')
                    ->setAlertSortiePrefix('')
                    ->setAlertArticlePrefix('')
                ;
            }
            $birthdate = \DateTimeImmutable::createFromFormat('d/m/Y', $formData['birthdate']);
            if ($birthdate instanceof \DateTimeImmutable) {
                $nomad->setBirthdate($birthdate);
            }
            // forcer null pour éviter de pêter la contrainte d'unicité
            if (empty($nomad->getEmail())) {
                $nomad->setEmail(null);
            }

            if (empty($errors)) {
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
        }

        return [
            'form' => $form,
            'nomads' => $myNomads,
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

    #[Route('/users/data/{show}', name: 'users_data')]
    public function data(
        Request $request,
        UserRights $userRights,
        UserRepository $userRepository,
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

        $recordsFiltered = $userRepository->getUsersCount($show, $searchText);
        $recordsTotal = $userRepository->getUsersCount($show);
        $data = $userRepository->getUsers($show, $start, $length, $searchText, $order);

        $img_lock = '<img src="/img/base/lock_gray.png" alt="caché"  title="Vous devez disposer de droits supérieurs pour afficher cette information" />';

        $results = [];
        /** @var User $user */
        foreach ($data as $user) {
            $tools = '';
            // view user
            if ($this->isGranted(SecurityConstants::ROLE_ADMIN)) {
                $tools .= '<a href="/includer.php?p=pages/adherents-consulter.php&amp;id_user=' . $user->getId() . '" class="fancyframe" title="Consulter cet adhérent"><img src="/img/base/report.png" alt="consulter" /></a> ';
            }
            // gestion des droits
            if ($this->isGranted(SecurityConstants::ROLE_ADMIN)) {
                $tools .= '<a href="/includer.php?admin=true&amp;p=pages/admin-users-droits.php&amp;id_user=' . $user->getId() . '&amp;nom=' . urlencode($user->getFullName()) . '" class="fancyframe" title="Voir / Attribuer des responsabilités à cet utilisateur"><img src="/img/base/user_star.png" alt="droits" /></a> ';
            } elseif ($userRights->allowed('user_giveright_1') || $userRights->allowed('user_giveright_2') || $userRights->allowed('user_givepresidence')) {
                $tools .= '<a href="/includer.php?p=pages/adherents-droits.php&amp;id_user=' . $user->getId() . '&amp;nom=' . urlencode($user->getFullName()) . '" class="fancyframe" title="Voir / Attribuer des responsabilités à cet utilisateur"><img src="/img/base/user_star.png" alt="droits" /></a> ';
            }
            // edit user
            if ($userRights->allowed('user_edit_notme')) {
                $tools .= '<a href="/includer.php?p=pages/adherents-modifier.php&amp;id_user=' . $user->getId() . '" class="fancyframe" title="Modifier cet adhérent"><img src="/img/base/user_edit.png" alt="modifier" /></a> ';
            }
            // impersonate user
            if ($this->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
                $tools .= ($user->getValid() && !empty($user->getEmail())) ? ' <a href="/profil.html?_switch_user=' . urlencode($user->getEmail()) . '" title="Impersonifier l\'utilisateur"><img src="/img/base/user_go.png" alt="impersonifier" /></a> ' : '';
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
            $cafnum = $user->getCafnum() . '<br />';
            if ($user->getNomade()) {
                $cafnum .= '<img src="/img/base/nomade_user.png" alt="NOMADE" title="Utilisateur nomade" />';
            }
            if ($user->getManuel()) {
                $cafnum .= '<img src="/img/base/user_manuel.png" alt="MANUEL" title="Utilisateur créé manuellement" />';
            }

            // adhésion
            $joinDate = $user->getJoinDate();
            if ($user->getDoitRenouveler()) {
                $renew = '<span  style="color:red" title="' . ($userRights->allowed('user_read_private') ? (!empty($joinDate) ? $joinDate->format('d/m/Y') : '') : '') . '">Licence expirée</span>';
            } else {
                $renew = ($userRights->allowed('user_read_private') ? (!empty($joinDate) ? $joinDate->format('d/m/Y') : '-') : $img_lock);
            }

            // compte activé ?
            if ($user->getValid()) {
                $valid = 'oui';
            } else {
                $valid = '<span style="color: darkorange; font-weight: bold;" title="Les comptes non activés ne reçoivent pas les e-mails">non</span>';
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

            $results[] = [
                'id' => $user->getId(),
                'tools' => $tools,
                'cafnum' => $cafnum,
                'lastname' => strtoupper($user->getLastname()),
                'firstname' => ucfirst($user->getFirstname()),
                'renew' => $renew,
                'nickname' => '<a href="/includer.php?p=includes/fiche-profil.php&amp;id_user=' . $user->getId() . '" class="fancyframe userlink">' . $user->getNickname() . '</a>',
                'age' => $age,
                'tel' => $tel,
                'email' => $email,
                'active' => $valid,
                'cp' => $user->getCp(),
                'ville' => $user->getVille(),
                'license' => $license,
                'valid' => $user->getValid(),
                'deleted' => $deleted,
            ];
        }

        return new JsonResponse([
            'draw' => $request->query->getInt('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $results,
        ]);
    }
}
