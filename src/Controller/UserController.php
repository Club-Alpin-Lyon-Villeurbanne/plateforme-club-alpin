<?php

namespace App\Controller;

use App\Entity\Evt;
use App\Entity\User;
use App\Form\NomadeType;
use App\Repository\UserRepository;
use App\Utils\NicknameGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route(path: '/ajouter-nomade/{event}', name: 'event_nomad_add', requirements: ['event' => '\d+'], methods: ['GET', 'POST'], priority: '15')]
    #[Template('user/nomad-add.html.twig')]
    public function nomadAdd(
        Request $request,
        Evt $event,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ?User $nomad = null,
    ): array|RedirectResponse {
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
            /** @var User $nomad */
            $nomad = $form->getData();
            $data = $request->request->all();
            $userData = $data['nomade'] ?? [];
            $formData = $data['form'] ?? [];
            $formData = array_merge($userData, $formData);
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
            $event->addParticipation($nomad, $formData['role_evt_join']);
            $entityManager->flush();

            $this->addFlash('success', 'Le membre nomade a bien été inscrit à la sortie.');

            return $this->redirectToRoute('sortie', ['id' => $event->getId(), 'code' => $event->getCode()]);
            /* @todo fermer la modale et rafraichir la page de derrière */
        }

        return [
            'form' => $form,
            'nomads' => $myNomads,
        ];
    }
}
