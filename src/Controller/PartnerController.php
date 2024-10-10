<?php

namespace App\Controller;

use App\Entity\Partenaire;
use App\Repository\PartenaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PartnerController extends AbstractController
{
    #[Route('/partner/delete/{id}', name: 'partner_delete', methods: ['POST'])]
    public function delete(Request $request, Partenaire $partner, EntityManagerInterface $entityManager, PartenaireRepository $partenaireRepositor): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Vos droits ne sont pas assez élevés pour accéder à cette page');
        }

        if ($this->isCsrfTokenValid('delete'.$partner->getId(), $request->request->get('_token'))) {
            $uploaddir = $this->getParameter('kernel.project_dir') . '/public/ftp/partenaires/';
            $imagePath = $uploaddir . $partner->getImage();

            $entityManager->remove($partner);
            $entityManager->flush();

            if (is_file($imagePath)) {
                unlink($imagePath);
            }

            $this->addFlash('success', 'Partenaire supprimé !');
        }

        return new Response('Partner deleted', 200);
    }

    #[Route('/partner/{id}/confirm-delete', name: 'partner_confirm_delete', methods: ['GET'])]
    public function confirmDelete(Partenaire $partner): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Vos droits ne sont pas assez élevés pour accéder à cette page');
        }

        return $this->render('partners/delete.html.twig', [
            'partner' => $partner,
        ]);
    }
}