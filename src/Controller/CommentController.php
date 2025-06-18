<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Mailer\Mailer;
use App\UserRights;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;

class CommentController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     */
    #[Route(path: '/{type}/commenter/{article}', name: 'add_comment', requirements: ['type' => '[a-z0-9-]+', 'article' => '\d+'], methods: ['POST'], priority: '10')]
    public function addComment(Request $request, EntityManagerInterface $em, UserRights $userRights, Mailer $mailer, ?string $type, ?Article $article): RedirectResponse
    {
        $errors = 0;
        $articleViewRoute = $this->generateUrl('article_view', ['code' => $article->getCode(), 'id' => $article->getId()]) . '#comments';

        if (!$this->getUser()) {
            throw new AccessDeniedHttpException('Seuls les adhérents connectés peuvent commenter pour le moment');
        }

        if (!$this->isCsrfTokenValid('article_comment', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$userRights->allowed('article_comment')) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à commenter.');
        }

        if (!$type) {
            ++$errors;
            $this->addFlash('error', 'Parent type non défini.');
        }
        if (!$article) {
            ++$errors;
            $this->addFlash('error', 'Parent non défini.');
        }
        if (Article::STATUS_PUBLISHED !== $article->getStatus()) {
            ++$errors;
            $this->addFlash('error', 'L\'article visé ne semble pas publié.');
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

        if (empty($errors)) {
            $comment = new Comment();
            $comment
                ->setParentType($type)
                ->setParent($article->getId())
                ->setUser($this->getUser())
                ->setTsp(time())
                ->setStatus(1)
                ->setName('')
                ->setEmail('')
                ->setCont($content)
            ;
            $em->persist($comment);
            $em->flush();

            // prévenir l'auteur de l'article
            $mailer->send($article->getUser(), 'transactional/article-comment', [
                'article_name' => $article->getTitre(),
                'article_url' => $articleViewRoute,
                'message' => $content,
            ], [], null, $this->getUser()->getEmail());

            $this->addFlash('info', 'Commentaire déposé');
        }

        return $this->redirect($articleViewRoute);
    }
}
