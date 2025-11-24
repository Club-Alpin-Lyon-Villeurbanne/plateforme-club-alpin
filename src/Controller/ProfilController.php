<?php

namespace App\Controller;

use App\Entity\AlertType;
use App\Messenger\Message\ArticlePublie;
use App\Repository\CommissionRepository;
use App\Repository\EvtRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}
