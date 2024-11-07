<?php

namespace App\Controller;

use App\Entity\AlertType;
use App\Messenger\Message\ArticlePublie;
use App\Repository\CommissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
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

        $user->setAlertStatus(AlertType::Article, ArticlePublie::ACTU_CLUB_RUBRIQUE, ($params['articles'][ArticlePublie::ACTU_CLUB_RUBRIQUE] ?? '0') === '1');
        $user->setAlertSortiePrefix($params['sortie-prefix-input']);
        $user->setAlertArticlePrefix($params['article-prefix-input']);

        $em->flush();

        $this->addFlash('success', 'Configuration des alertes mise à jour.');

        return $this->redirect($this->generateUrl('profil_alertes'));
    }
}
