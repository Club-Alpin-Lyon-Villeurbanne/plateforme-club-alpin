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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
