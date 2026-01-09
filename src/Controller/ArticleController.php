<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\MediaUpload;
use App\Form\ArticleType;
use App\Helper\SlugHelper;
use App\Mailer\Mailer;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use App\Repository\EvtRepository;
use App\UserRights;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ArticleController extends AbstractController
{
    public function __construct(
        protected string $editoLineLink,
        protected string $imageRightLink,
    ) {
    }

    #[Route('/article/new', name: 'article_new')]
    #[Route('/article/{id}/edit', name: 'article_edit', requirements: ['id' => '\d+'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SlugHelper $slugHelper,
        EvtRepository $evtRepository,
        ?Article $article = null
    ): Response {
        if (!$article && !$this->isGranted('ARTICLE_CREATE')) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à créer un article');

            return $this->redirectToRoute('profil_articles');
        }

        if ($article && !$this->isGranted('ARTICLE_UPDATE', $article)) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à modifier cet article');

            return $this->redirectToRoute('profil_articles');
        }

        if (!$article) {
            $article = new Article();
        }

        $form = $this->createForm(ArticleType::class, $article, ['editoLineLink' => $this->editoLineLink, 'imageRightLink' => $this->imageRightLink]);

        if (!$article->getId() && 'cr' == $form->get('articleType')) {
            $evtId = $request->query->get('evt_article');
            if ($evtId) {
                $evt = $evtRepository->find($evtId);
                if ($evt) {
                    $article->setEvt($evt);
                }
            }
        }

        $form->handleRequest($request);

        $errors = [];

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $isNew = !$article->getId();

                if ($isNew) {
                    $article->setUser($this->getUser());
                    $article->setCode($slugHelper->generateSlug($article->getTitre(), 50));
                } else {
                    $article->setStatus(0);
                    $article->setLastEditWho($this->getUser());
                }

                $article->setUpdatedAt(new \DateTime());

                // brouillon ?
                $data = $request->request->all();
                $articleData = $data['article'] ?? [];
                $formData = $data['form'] ?? [];
                $formData = array_merge($articleData, $formData);
                $isDraft = false;
                if (\in_array('articleDraftSave', array_keys($formData), true)) {
                    $isDraft = true;
                }
                $article->setTopubly(!$isDraft);

                if ('cr' === $formData['articleType']) {
                    if (!empty($formData['evt'])) {
                        $article->setEvt($evtRepository->find($formData['evt']));
                    } else {
                        $errors[] = 'Si cet article est un compte rendu de sortie, veuillez sélectionner la sortie liée.';
                    }

                    // pas de CR de sortie à la une
                    $article->setUne(false);
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

                if (empty($article->getCont())) {
                    $errors[] = 'Veuillez renseigner le contenu de votre article.';
                }

                if (empty($errors)) {
                    $entityManager->persist($article);
                    $entityManager->flush();

                    $this->addFlash('success', $isNew ? 'Article créé avec succès' : 'Article modifié avec succès');

                    return $this->redirectToRoute('profil_articles');
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
            'current_commission' => $article->getCommission(),
            'comments' => $commentRepository->findByArticle($article),
            'article_url' => $this->generateUrl('article_view', ['id' => $article->getId(), 'code' => $article->getCode()], UrlGeneratorInterface::ABSOLUTE_URL),
            'event_url' => $article->getEvt() ? $this->generateUrl('sortie', ['id' => $article->getEvt()->getId(), 'code' => $article->getEvt()->getCode()], UrlGeneratorInterface::ABSOLUTE_URL) : '',
        ];
    }

    #[Route('/article/{id}/commenter', name: 'article_comment', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function addComment(Request $request, EntityManagerInterface $em, UserRights $userRights, Mailer $mailer, ?Article $article = null): RedirectResponse
    {
        $errors = 0;
        $type = Comment::ARTICLE_TYPE;

        if (!$this->getUser()) {
            throw new AccessDeniedHttpException('Seuls les adhérents connectés peuvent commenter pour le moment');
        }

        if (!$this->isCsrfTokenValid('article_comment', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$userRights->allowed('article_comment')) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à commenter.');
        }

        if (!$article) {
            ++$errors;
            $this->addFlash('error', 'Parent non défini.');
        }
        if (Article::STATUS_PUBLISHED !== $article->getStatus()) {
            ++$errors;
            $this->addFlash('error', 'L\'article visé ne semble pas publié.');
        }
        if (!$article->isAllowComments()) {
            ++$errors;
            $this->addFlash('error', 'Les commentaires sont désactivés sur cet article.');
        }
        if ('unlocked' !== $request->request->get('unlock1')) {
            ++$errors;
            $this->addFlash('error', 'L\'antispam n\'a pas autorisé l\'envoi. Merci de cliquer sur le bouton &laquo;OK&raquo; pour envoyer le message.');
        }

        $content = trim(stripslashes($request->request->get('cont_comment')));
        if (\strlen($content) < 10) {
            ++$errors;
            $this->addFlash('error', 'Par souci de pertinence, les commentaires doivent comporter au moins 10 caractères.');
        }

        $articleViewRoute = $this->generateUrl('article_view', ['code' => $article->getCode(), 'id' => $article->getId()], UrlGeneratorInterface::ABSOLUTE_URL) . '#comments';
        if (empty($errors)) {
            $comment = new Comment();
            $comment
                ->setParentType($type)
                ->setParent($article->getId())
                ->setUser($this->getUser())
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime())
                ->setStatus(1)
                ->setName('')
                ->setEmail('')
                ->setCont($content)
            ;
            $em->persist($comment);
            $em->flush();

            // prévenir l'auteur de l'article
            /** @var User $user */
            $user = $this->getUser();
            $mailer->send($article->getUser(), 'transactional/article-comment', [
                'article_name' => $article->getTitre(),
                'article_url' => $articleViewRoute,
                'message' => $content,
            ], [], null, $user->getEmail());

            $this->addFlash('info', 'Commentaire déposé');
        }

        return $this->redirect($articleViewRoute);
    }

    #[Route(path: '/article/{id}/depublier', name: 'unpublish_article', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function unpublish(
        Request $request,
        Article $article,
        EntityManagerInterface $manager,
    ): RedirectResponse {
        if (!$this->isGranted('ARTICLE_UNPUBLISH', $article)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        if (!$this->isCsrfTokenValid('article_unpublish', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        $article
            ->setTopubly(0)
            ->setStatus(Article::STATUS_PENDING)
            ->setValidationDate(null)
        ;
        $manager->persist($article);
        $manager->flush();

        $this->addFlash('info', 'L\'article est dépublié');

        return $this->redirectToRoute('profil_articles');
    }

    #[Route(path: '/article/{id}/supprimer', name: 'delete_article', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        Article $article,
        EntityManagerInterface $manager,
        Filesystem $filesystem,
    ): RedirectResponse {
        if (!$this->isGranted('ARTICLE_DELETE', $article)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        if (!$this->isCsrfTokenValid('article_delete', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (Article::STATUS_PUBLISHED === $article->getStatus()) {
            $this->addFlash('error', 'Impossible de supprimer un article publié. Veuillez d\'abord le dépublier.');

            return $this->redirectToRoute('profil_articles');
        }

        // suppression des medias associés
        try {
            $filesystem->remove($this->getParameter('kernel.project_dir') . '/public/ftp/articles/' . $article->getId());
            $media = $article->getMediaUpload();
            if ($media) {
                $filesystem->remove($this->getParameter('kernel.project_dir') . '/public/ftp/uploads/files/' . $media->getFilename());
                $manager->remove($media);
            }

            $manager->remove($article);
            $manager->flush();

            $this->addFlash('info', 'L\'article est supprimé');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression');
        }

        return $this->redirectToRoute('profil_articles');
    }
}
