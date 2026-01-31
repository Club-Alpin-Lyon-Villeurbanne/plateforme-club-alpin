<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\ResetPasswordType;
use App\Form\SetPasswordType;
use App\Mailer\Mailer;
use App\Repository\UserRepository;
use App\Security\RecaptchaValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

class LoginController extends AbstractController
{
    #[Route(path: '/login', name: 'login')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirect($this->generateUrl('legacy_root'));
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login/index.html.twig', [
            'controller_name' => 'LoginController',
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(name: 'session_password_lost', path: '/password-lost', methods: ['GET', 'POST'])]
    #[Template('login/password_lost.html.twig')]
    public function passwordLostAction(Request $request, UserRepository $userRepository, LoginLinkHandlerInterface $loginLinkHandler, Mailer $mailer, RecaptchaValidator $recaptchaValidator)
    {
        if ($this->isGranted('ROLE_USER')) {
            if ($request->query->has('target') && $this->isValidRedirect($request->query->get('target'))) {
                return $this->redirect($request->query->get('target'));
            }

            return $this->redirect($this->generateUrl('legacy_root'));
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->add('submit', SubmitType::class, ['label' => 'Ré-initialiser', 'attr' => ['class' => 'nice2']]);
        if ($form->handleRequest($request)->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();

            // If user is unknown, let's act as password reset was done
            $user = $userRepository->findUserByEmail($form->get('email')->getData());
            if (!$user) {
                return [
                    'username' => $email,
                    'form' => null,
                    'password_reset' => true,
                ];
            }

            // empêcher les non-adhérents d'utiliser cette fonctionnalité qui leur permet "d'activer" un compte
            // alors qu'ils ne sont pas censés en avoir
            // (et le conservent quand ils prennent une licence annuelle, ce qui pose des problèmes)
            if (User::PROFILE_CLUB_MEMBER !== $user->getProfileType() || $user->isDeleted() || $user->isLocked()) {
                $this->addFlash('error', 'Vous ne pouvez pas utiliser cette fonctionnalité car vous n\'avez pas de licence annuelle du club.');

                return [
                    'username' => $email,
                    'form' => $form->createView(),
                    'password_reset' => false,
                ];
            }

            $loginLinkDetails = $loginLinkHandler->createLoginLink($user);
            $loginLink = $loginLinkDetails->getUrl();

            $mailer->send($email, 'transactional/password-lost', ['email' => $email, 'login_link' => $loginLink]);

            return [
                'username' => $email,
                'form' => $form->createView(),
                'password_reset' => true,
            ];
        }

        return [
            'username' => null,
            'form' => $form->createView(),
            'password_reset' => false,
        ];
    }

    /**
     * Password is changed without validating the existing password (for instance the magic link).
     * If the user is logged in using the remember me token, they dont pass the IS_AUTHENTICATED_FULLY
     * Therefore, this should be used only after magic link authentication.
     */
    #[Route(name: 'account_set_password', path: '/password', methods: ['GET', 'POST'])]
    #[IsGranted(attribute: new Expression(
        'is_granted("IS_AUTHENTICATED_FULLY") and is_granted("ROLE_USER")'
    ))]
    #[Template('login/set_password.html.twig')]
    public function setPasswordAction(Request $request, PasswordHasherFactoryInterface $hasherFactory, Mailer $mailer, EntityManagerInterface $em)
    {
        /** @var User $user */
        $user = $this->getUser();

        $url = $request->getSession()->get('user_password.target', $this->generateUrl('legacy_root'));

        $form = $this->createForm(SetPasswordType::class);
        $form->add('submit', SubmitType::class, ['label' => 'Valider', 'attr' => ['class' => 'nice2']]);
        if ($form->handleRequest($request)->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasherFactory->getPasswordHasher('login_form')->hash(
                $form->get('password')->getData()
            ));

            $em->flush();
            $this->addFlash('success', 'Mot de passe mis à jour avec succès!');
            $mailer->send($user, 'transactional/set_password-account-confirmation');

            $request->getSession()->remove('user_password.target');

            return $this->redirect($url);
        }

        return [
            'form' => $form->createView(),
            'username' => $user->getEmail(),
        ];
    }

    /**
     * Password is changed with validating the existing password.
     * It requires any user authentication.
     */
    #[Route(name: 'account_change_password', path: '/change-password', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    #[Template('login/change_password.html.twig')]
    public function changePasswordAction(Request $request, PasswordHasherFactoryInterface $hasherFactory, Mailer $mailer, EntityManagerInterface $em)
    {
        $url = $this->generateUrl('app_logout');
        $form = $this->createForm(ChangePasswordType::class);
        $form->add('submit', SubmitType::class, ['label' => 'Mettre à jour', 'attr' => ['class' => 'nice2']]);
        if ($form->handleRequest($request)->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $user->setMdp(
                $hasherFactory->getPasswordHasher('login_form')->hash(
                    $form->get('password')->getData()
                )
            );

            $em->flush();

            $this->addFlash('success', 'Mot de passe mis à jour avec succès !');
            $mailer->send($user, 'transactional/set_password-account-confirmation');

            return $this->redirect($url);
        }

        return [
            'form' => $form->createView(),
        ];
    }

    private function isValidRedirect($redirection)
    {
        return str_starts_with($redirection, '/')
            && !\in_array($redirection, [
                $this->generateUrl('legacy_root'),
                $this->generateUrl('login'),
                $this->generateUrl('session_logout'),
                $this->generateUrl('session_password_lost'),
            ], true);
    }
}
