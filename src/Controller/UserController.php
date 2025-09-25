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
use App\UserRights;
use App\Utils\NicknameGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
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
                $this->addFlash('error', 'Cette sortie ne semble pas publiée, les inscriptions sont impossibles');

                return new Response(
                    '<script>
                        window.parent.location.reload();
                    </script>'
                );
            }

            // comptage des participants actuels
            $nbJoinMax = $event->getNgensMax();
            $currentParticipantNb = $event->getParticipationsCount();

            $isCurrentUserEncadrant = false;
            foreach ($event->getEncadrants(EventParticipation::ROLES_ENCADREMENT_ETENDU) as $eventParticipation) {
                if ($eventParticipation->getUser() === $this->getUser()) {
                    $isCurrentUserEncadrant = true;
                    break;
                }
            }

            // reste-t-il assez de place ?
            if ((\count($_POST['id_user']) + $currentParticipantNb) > $nbJoinMax) {
                $availableSpotNb = $nbJoinMax - $currentParticipantNb;
                if ($availableSpotNb < 0) {
                    $availableSpotNb = 0;
                }
                $this->addFlash('error', 'Vous ne pouvez pas inscrire plus de participants que de places disponibles (' . $availableSpotNb . '). Vous pouvez augmenter le nombre maximum de places pour ensuite rajouter des personnes.');
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
            $evtDate = date('d/m/Y', $event->getTsp());
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
                    $role = $data['role_evt_join'][$key] ?? 'manuel';
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
                    ->setCreated(time())
                    ->setTsInsert(time())
                    ->setTsUpdate(time())
                    ->setCookietoken('')
                    ->setAuthContact('none')
                    ->setAlertSortiePrefix('')
                    ->setAlertArticlePrefix('')
                ;
            }
            $birthdate = \DateTime::createFromFormat('d/m/Y', $formData['birthdate']);
            if ($birthdate instanceof \DateTime) {
                $nomad->setBirthday($birthdate->getTimestamp());
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
}
