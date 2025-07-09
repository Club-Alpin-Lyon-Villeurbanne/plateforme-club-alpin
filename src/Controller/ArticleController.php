<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\MediaUpload;
use App\Form\ArticleType;
use App\Repository\EvtRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ArticleController extends AbstractController
{
    #[Route('/article/new', name: 'article_new')]
    #[Route('/article/{id}/edit', name: 'article_edit')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        EvtRepository $evtRepository,
        ?Article $article = null
    ): Response {
        if (!$article && !$this->isGranted('ARTICLE_CREATE')) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à créer un article');

            return $this->redirect('/profil/articles.html');
        }

        if ($article && !$this->isGranted('ARTICLE_UPDATE', $article)) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à modifier cet article');

            return $this->redirect('/profil/articles.html');
        }

        if (!$article) {
            $article = new Article();
        }

        if (!$article->getId() && $request->query->get('compterendu')) {
            $evtId = $request->query->get('evt_article');
            if ($evtId) {
                $evt = $evtRepository->find($evtId);
                if ($evt) {
                    $article->setEvt($evt);
                }
            }
        }

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        $errors = [];

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $isNew = !$article->getId();

                if ($isNew) {
                    $article->setUser($this->getUser());
                    $article->setTspCrea(time());
                    $article->setCode($this->generateArticleCode($article->getTitre(), $slugger));
                } else {
                    $article->setStatus(0);
                }

                $article->setTsp(time());
                $article->setTspLastedit(new \DateTime());
                $article->setTopubly($form->get('topubly')->getData() ? 1 : 0);

                if ($form->get('isCompteRendu')->getData() && !$article->getEvt()) {
                    $errors[] = 'Si cet article est un compte rendu de sortie, veuillez sélectionner la sortie liée.';
                }

                $mediaUploadId = $form->get('mediaUploadId')->getData();
                if ($mediaUploadId) {
                    $mediaUpload = $entityManager->getRepository(MediaUpload::class)->find($mediaUploadId);
                    if ($mediaUpload && $mediaUpload->getUploadedBy() === $this->getUser()) {
                        $article->setMediaUpload($mediaUpload);
                        $mediaUpload->setUsed(true);
                        $entityManager->persist($mediaUpload);
                    } else {
                        $errors[] = "Le média uploadé n'existe pas ou n'est pas lié à votre compte.";
                    }
                }

                if ($article->getUne() && !$article->getMediaUpload()) {
                    $errors[] = 'Une image est obligatoire pour les articles à la une.';
                }

                if (empty($errors)) {
                    $entityManager->persist($article);
                    $entityManager->flush();

                    $this->addFlash('success', $isNew ? 'Article créé avec succès' : 'Article modifié avec succès');

                    return $this->redirect('/profil/articles.html');
                }
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
            }
        }

        return $this->render('article/edit.html.twig', [
            'form' => $form->createView(),
            'errors' => $errors,
            'article' => $article,
            'edit_mode' => null !== $article->getId(),
        ]);
    }

    private function generateArticleCode(string $title, SluggerInterface $slugger): string
    {
        $code = $slugger->slug(strtolower($title));

        return substr($code, 0, 50);
    }
}
