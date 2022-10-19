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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

class LoginController extends AbstractController
{
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [EntityManagerInterface::class]);
    }

    /**
     * @Route("/login", name="login")
     */
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

    /**
     * @Route(
     *     name="session_password_lost",
     *     path="/password-lost",
     *     methods={"GET", "POST"}
     * )
     *
     * @Template
     */
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
     *
     * @Route(
     *     name="account_set_password",
     *     path="/password",
     *     methods={"GET", "POST"}
     * )
     * @Security("is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_USER')")
     *
     * @Template
     */
    public function setPasswordAction(Request $request, PasswordHasherFactoryInterface $hasherFactory, Mailer $mailer)
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

            $this->get(EntityManagerInterface::class)->flush();
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
     *
     * @Route(
     *     name="account_change_password",
     *     path="/change-password",
     *     methods={"GET", "POST"}
     * )
     * @Security("is_granted('ROLE_USER')")
     *
     * @Template
     */
    public function changePasswordAction(Request $request, PasswordHasherFactoryInterface $hasherFactory, Mailer $mailer)
    {
        $url = $request->getSession()->get('user_password.target', $this->generateUrl('legacy_root'));
        $form = $this->createForm(ChangePasswordType::class);
        $form->add('submit', SubmitType::class, ['label' => 'Ré-initialiser', 'attr' => ['class' => 'nice2']]);
        if ($form->handleRequest($request)->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $user->setMdp(
                $hasherFactory->getPasswordHasher('login_form')->hash(
                    $form->get('password')->getData()
                )
            );

            $this->get(EntityManagerInterface::class)->flush();

            $this->addFlash('success', 'Mot de passe mis à jour avec succès!');
            $mailer->send($user, 'transactional/set_password-account-confirmation');

            return $this->redirect($url);
        }

        return [
            'form' => $form->createView(),
        ];
    }

    private function isValidRedirect($redirection)
    {
        return 0 === strpos($redirection, '/')
            && !\in_array($redirection, [
                $this->generateUrl('legacy_root'),
                $this->generateUrl('login'),
                $this->generateUrl('session_logout'),
                $this->generateUrl('session_password_lost'),
//                $this->generateUrl('2fa_login'),
//                $this->generateUrl('2fa_login_check'),
            ], true);
    }
}
