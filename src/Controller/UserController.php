<?php

namespace App\Controller;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use App\Form\NomadeType;
use App\Legacy\LegacyContainer;
use App\Mailer\Mailer;
use App\Repository\UserRepository;
use App\Utils\NicknameGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserController extends AbstractController
{
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

    #[Route(path: '/ajouter-nomade/{event}', name: 'event_nomad_add', requirements: ['event' => '\d+'], methods: ['GET', 'POST'])]
    #[Template('user/nomad-add.html.twig')]
    public function nomadAdd(
        Request $request,
        Evt $event,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ?User $nomad = null,
    ): array|Response {
        if (!$this->isGranted('EVENT_JOINING_ADD', $event)) {
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

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $request->request->all();
            $userData = $data['nomade'] ?? [];
            $formData = $data['form'] ?? [];
            $formData = array_merge($userData, $formData);

            /* @var User $nomad */
            if (!empty($formData['id_user'])) {
                $nomad = $userRepository->find($formData['id_user']);
            } else {
                $nomad = $form->getData();
                $nomad
                    ->setNickname(NicknameGenerator::generateNickname($nomad->getFirstname(), $nomad->getLastname()))
                    ->setNomade(true)
                    ->setValid(true)
                    ->setManuel(false)
                    ->setNomadeParent($this->getUser()->getId())
                    ->setDoitRenouveler(false)
                    ->setAlerteRenouveler(false)
                    ->setCookietoken('')
                    ->setAuthContact('none')
                    ->setAlertSortiePrefix('')
                    ->setAlertArticlePrefix('')
                ;
                $entityManager->persist($nomad);
            }

            $event->addParticipation($nomad, $formData['role_evt_join']);
            $entityManager->flush();

            $this->addFlash('success', 'Le "nomade" a bien été inscrit à la sortie.');

            return new Response(
                '<script>
                    window.parent.location.reload();
                </script>'
            );
        }

        return [
            'form' => $form,
            'nomads' => $myNomads,
        ];
    }
}
