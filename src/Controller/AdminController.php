<?php

namespace App\Controller;

use App\Security\SecurityConstants;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminController extends AbstractController
{
    private string $adminPassword;
    private string $contentManagerPassword;

    public function __construct(string $adminPassword, string $contentManagerPassword)
    {
        $this->adminPassword = $adminPassword;
        $this->contentManagerPassword = $contentManagerPassword;
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [EntityManagerInterface::class]);
    }

    #[Route(name: 'admin_login', path: '/admin/', methods: ['GET', 'POST'])]
    #[IsGranted(SecurityConstants::ROLE_USER)]
    #[Template('admin/index.html.twig')]
    public function index(Request $request)
    {
        if  ('POST' === $request->getMethod()) {
            if (!$this->isCsrfTokenValid(SecurityConstants::CSRF_ADMIN_TOKEN_ID, $request->request->get('_csrf_token'))) {
                return [
                    'error' => 'CSRF token invalide',
                ];
            }

            $username = $request->request->get('username');
            $password = $request->request->get('password');

            if (SecurityConstants::ADMIN_USERNAME === $username && $password === $this->adminPassword) {
                $request->getSession()->set(SecurityConstants::SESSION_USER_ROLE_KEY, SecurityConstants::ROLE_ADMIN);
            } elseif (SecurityConstants::CONTENT_MANAGER_USERNAME === $username && $password === $this->contentManagerPassword) {
                $request->getSession()->set(SecurityConstants::SESSION_USER_ROLE_KEY, SecurityConstants::ROLE_CONTENT_MANAGER);
            } else {
                return [
                    'error' => 'Identifiants invalides',
                ];
            }

            return $this->redirect($this->generateUrl('legacy_root'));
        }

        return [];
    }

    #[Route(name: 'admin_logout', path: '/admin/logout', methods: ['GET'])]
    #[IsGranted(SecurityConstants::ROLE_USER)]
    public function adminLogout(Request $request)
    {
        $request->getSession()->remove(SecurityConstants::SESSION_USER_ROLE_KEY);

        return $this->redirect($this->generateUrl('legacy_root'));
    }
}