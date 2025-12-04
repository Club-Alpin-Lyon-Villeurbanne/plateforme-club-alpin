<?php

namespace App\Controller;

use App\Entity\AlertType;
use App\Entity\MediaUpload;
use App\Entity\User;
use App\Form\UserType;
use App\Messenger\Message\ArticlePublie;
use App\Repository\CommissionRepository;
use App\Repository\EvtRepository;
use App\Repository\UserRepository;
use App\Service\UserRightService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/profil', methods: ['GET'])]
class ProfilController extends AbstractController
{
    #[Route(path: '/alertes', name: 'profil_alertes')]
    #[IsGranted('ROLE_USER')]
    #[Template('profil/alertes.html.twig')]
    public function alertes()
    {
        return [
        ];
    }

    #[Route(path: '/alertes', name: 'profil_alertes_update', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function alertesUpdate(Request $request, CommissionRepository $commissionRepository, EntityManagerInterface $em)
    {
        if (!$this->isCsrfTokenValid('profil_alertes_update', $request->request->get('csrf_token'))) {
            $this->addFlash('error', 'Jeton de validation invalide.');

            return $this->redirect($this->generateUrl('profil_alertes'));
        }

        $user = $this->getUser();
        $params = $request->request->all();

        foreach ($commissionRepository->findVisible() as $commission) {
            $user->setAlertStatus(AlertType::Sortie, $commission->getCode(), ($params['sorties'][$commission->getCode()] ?? '0') === '1');
            $user->setAlertStatus(AlertType::Article, $commission->getCode(), ($params['articles'][$commission->getCode()] ?? '0') === '1');
        }

        $user->setAlertStatus(AlertType::Article, ArticlePublie::ACTU_CLUB_RUBRIQUE, false);
        $user->setAlertSortiePrefix($params['sortie-prefix-input']);
        $user->setAlertArticlePrefix($params['article-prefix-input']);

        $em->flush();

        $this->addFlash('success', 'Configuration des alertes mise à jour.');

        return $this->redirect($this->generateUrl('profil_alertes'));
    }

    #[Route(path: '/sorties/next', name: 'profil_sorties_next')]
    #[IsGranted('ROLE_USER')]
    #[Template('profil/sorties.html.twig')]
    public function sortiesNext(Request $request, EvtRepository $evtRepository)
    {
        $perPage = 30;
        $page = $request->query->getInt('page', 1);
        $total = $evtRepository->getUserUpcomingEventsCount($this->getUser());
        $pages = ceil($total / $perPage);
        $first = $perPage * ($page - 1);

        return [
            'events' => $evtRepository->getUserUpcomingEvents($this->getUser(), $first, $perPage),
            'total' => $total,
            'per_page' => $perPage,
            'pages' => $pages,
            'page' => $page,
            'page_url' => $this->generateUrl('profil_sorties_next'),
            'include_name' => 'profil-sorties-next',
        ];
    }

    #[Route(path: '/sorties/prev', name: 'profil_sorties_prev')]
    #[IsGranted('ROLE_USER')]
    #[Template('profil/sorties.html.twig')]
    public function sortiesPrev(Request $request, EvtRepository $evtRepository)
    {
        $perPage = 30;
        $page = $request->query->getInt('page', 1);
        $total = $evtRepository->getUserPastEventsCount($this->getUser());
        $pages = ceil($total / $perPage);
        $first = $perPage * ($page - 1);

        return [
            'events' => $evtRepository->getUserPastEvents($this->getUser(), $first, $perPage),
            'total' => $total,
            'per_page' => $perPage,
            'pages' => $pages,
            'page' => $page,
            'page_url' => $this->generateUrl('profil_sorties_prev'),
            'include_name' => 'profil-sorties-prev',
        ];
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    #[Route(path: '/sorties/self', name: 'profil_sorties_self')]
    #[IsGranted('ROLE_USER')]
    #[Template('profil/sorties.html.twig')]
    public function sortiesSelf(Request $request, EvtRepository $evtRepository): array
    {
        $perPage = 30;
        $page = $request->query->getInt('page', 1);
        $total = $evtRepository->getUserCreatedEventsCount($this->getUser());
        $pages = ceil($total / $perPage);
        $first = $perPage * ($page - 1);

        return [
            'events' => $evtRepository->getUserCreatedEvents($this->getUser(), $first, $perPage),
            'total' => $total,
            'per_page' => $perPage,
            'pages' => $pages,
            'page' => $page,
            'page_url' => $this->generateUrl('profil_sorties_self'),
            'include_name' => 'profil-sorties-self',
        ];
    }

    #[Route(path: '/mon-compte', name: 'my_profile', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    #[Template('profil/mine.html.twig')]
    public function myProfile(
        Request $request,
        EntityManagerInterface $manager,
        UserRepository $userRepository,
        UserRightService $userRightService,
    ): array {
        /** @var User $user */
        $user = $this->getUser();
        $parent = $userRepository->findOneBy(['cafnum' => $user->getCafnumParent()]);
        $filiations = $userRepository->findBy(['cafnumParent' => $user->getCafnum()]);
        $roles = $userRightService->getRightsToDisplay($user);

        $form = $this->createForm(UserType::class, $user);

        // e-mail google drive
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $user->setUpdatedAt(new \DateTime('now'));

            // photo de profil
            $mediaUploadId = $form->get('mediaUploadId')->getData();
            if ($mediaUploadId) {
                $mediaUpload = $manager->getRepository(MediaUpload::class)->find($mediaUploadId);
                if ($mediaUpload && $mediaUpload->getUploadedBy() === $this->getUser()) {
                    $user->setProfilePicture($mediaUpload);
                    $mediaUpload->setUsed(true);
                    $manager->persist($mediaUpload);
                } else {
                    $this->addFlash('error', "Le média uploadé n'existe pas ou n'est pas lié à votre compte.");
                }
            }

            $manager->persist($user);
            $manager->flush();
        }

        return [
            'user' => $user,
            'form' => $form->createView(),
            'filiations' => $filiations,
            'parent' => $parent,
            'club_roles' => $roles['club'],
            'comm_roles' => $roles['commission'],
        ];
    }

    #[Route(path: '/supprimer-ma-photo', name: 'remove_my_profile_picture', methods: ['GET', 'POST'])]
    public function removeProfilPicture(Filesystem $filesystem, EntityManagerInterface $manager): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $filesystem->remove($this->getParameter('kernel.project_dir') . '/public/ftp/uploads/files/' . $user->getProfilePicture()->getFilename());
        $manager->remove($user->getProfilePicture());
        $user->setProfilePicture(null);
        $manager->persist($user);
        $manager->flush();

        $this->addFlash('success', 'Photo de profil supprimée avec succès !');

        return $this->redirectToRoute('my_profile');
    }
}
