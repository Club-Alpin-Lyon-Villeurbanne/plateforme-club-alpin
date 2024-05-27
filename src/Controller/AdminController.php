<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bridge\Twig\Attribute\Template;

class AdminController extends AbstractController
{
    private string $adminPassword;

    public function __construct(string $adminPassword)
    {
        $this->adminPassword = $adminPassword;
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [EntityManagerInterface::class]);
    }

    
    #[Route(name: 'admin_login', path: '/admin/', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    #[Template('admin/index.html.twig')]
    public function index(Request $request)
    {
        if ('POST' === $request->getMethod()) {
            if (!$this->isCsrfTokenValid('admin_authenticate', $request->request->get('_csrf_token'))) {
                return [
                    'error' => 'CSRF token invalide',
                ];
            }

            if ('caflyon' !== $request->request->get('username') || $request->request->get('password') !== $this->adminPassword) {
                return [
                    'error' => 'Identifiants invalides',
                ];
            }

            $request->getSession()->set('admin_caf', true);

            return $this->redirect($this->generateUrl('legacy_root'));
        }

        return [];
    }

    
    #[Route(name: 'admin_logout', path: '/admin/logout', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function adminLogout(Request $request)
    {
        $request->getSession()->remove('admin_caf');

        return $this->redirect($this->generateUrl('legacy_root'));
    }
}
