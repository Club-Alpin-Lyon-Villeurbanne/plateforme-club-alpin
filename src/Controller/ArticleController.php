<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\MediaUpload;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use App\Repository\EvtRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
                } else {
                    $article->setLastEditWho($this->getUser());
                }

                $article->setTsp(time());
                $article->setTspLastedit(new \DateTime());

                if ($isNew) {
                    $article->setCode($this->generateArticleCode($article->getTitre(), $slugger));
                }

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

    #[Route(path: '/article/{code}-{id}.html', name: 'article_view', requirements: ['id' => '\d+', 'code' => '[a-z0-9-]+'], methods: ['GET'], priority: '10')]
    #[Template('article/article.html.twig')]
    public function article(Article $article, ArticleRepository $articleRepository, CommentRepository $commentRepository): array
    {
        if (!$this->isGranted('ARTICLE_VIEW', $article)) {
            throw new AccessDeniedHttpException('Not found');
        }

        // maj nb vues
        if (!$this->isGranted('ARTICLE_UPDATE', $article)) {
            $articleRepository->updateViews($article);
        }

        return [
            'article' => $article,
            'current_commission' => $article->getCommission()?->getCode(),
            'comments' => $commentRepository->findByArticle($article),
            'article_url' => $this->generateUrl('article_view', ['id' => $article->getId(), 'code' => $article->getCode()], UrlGeneratorInterface::ABSOLUTE_URL),
            'event_url' => $article->getEvt() ? $this->generateUrl('sortie', ['id' => $article->getEvt()->getId(), 'code' => $article->getEvt()->getCode()]) : '',
        ];
    }

    private function generateArticleCode(string $title, SluggerInterface $slugger): string
    {
        $code = $slugger->slug(strtolower($title));

        return substr($code, 0, 50);
    }
}
