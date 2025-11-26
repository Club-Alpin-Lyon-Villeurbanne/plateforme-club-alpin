<?php

namespace App\Controller;

use App\Entity\Partenaire;
use App\Form\PartnerType;
use App\Security\SecurityConstants;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\String\Slugger\SluggerInterface;

class PartnerController extends AbstractController
{
    #[Route('/partner/delete/{id}', name: 'partner_delete', methods: ['POST'])]
    public function delete(Request $request, Partenaire $partner, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
            throw new AccessDeniedException('Vos droits ne sont pas assez élevés pour accéder à cette page');
        }

        if ($this->isCsrfTokenValid('delete' . $partner->getId(), $request->request->get('_token'))) {
            $uploaddir = $this->getParameter('kernel.project_dir') . '/public/ftp/partenaires/';
            $imagePath = $uploaddir . $partner->getImage();

            $entityManager->remove($partner);
            $entityManager->flush();

            if (is_file($imagePath)) {
                unlink($imagePath);
            }
        }

        return new Response('Partner deleted', 200);
    }

    #[Route('/partner/{id}/confirm-delete', name: 'partner_confirm_delete', methods: ['GET'])]
    public function confirmDelete(Partenaire $partner): Response
    {
        if (!$this->isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
            throw new AccessDeniedException('Vos droits ne sont pas assez élevés pour accéder à cette page');
        }

        return $this->render('partners/delete.html.twig', [
            'partner' => $partner,
        ]);
    }

    #[Route('/partner/edit/{id}', name: 'partner_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ?int $id, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        if (!$this->isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
            throw new AccessDeniedException('Vos droits ne sont pas assez élevés pour accéder à cette page');
        }

        if (-1 === $id || null === $id) {
            $partner = new Partenaire();
        } else {
            $partner = $entityManager->getRepository(Partenaire::class)->find($id);
            if (!$partner) {
                throw new NotFoundHttpException('Partenaire non trouvé');
            }
        }

        $form = $this->createForm(PartnerType::class, $partner);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $imageFile = $form->get('imageFile')->getData();

                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), \PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/ftp/partenaires/',
                            $newFilename
                        );
                    } catch (FileException $e) {
                        return new JsonResponse(['errors' => ['Erreur lors du téléchargement de l\'image']], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }

                    $partner->setImage($newFilename);
                }

                if (-1 === $id || null === $id) {
                    $entityManager->persist($partner);
                }
                $entityManager->flush();

                $message = -1 === $id || null === $id ? 'Partenaire ajouté avec succès' : 'Partenaire modifié avec succès';

                return new JsonResponse(['message' => $message], Response::HTTP_OK);
            }
            // Form is not valid, return errors
            $errors = $this->getFormErrors($form);

            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        // If the form is not submitted, render the form view
        return $this->render('partners/edit.html.twig', [
            'partner' => $partner,
            'form' => $form->createView(),
        ]);
    }

    private function getFormErrors($form): array
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        foreach ($form->all() as $childForm) {
            if ($childForm->isSubmitted() && !$childForm->isValid()) {
                $errors[$childForm->getName()] = $this->getFormErrors($childForm);
            }
        }

        return $errors;
    }
}
