<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    private string $adminPassord;

    public function __construct(string $adminPassord)
    {
        $this->adminPassord = $adminPassord;
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [EntityManagerInterface::class]);
    }

    /**
     * @Route(
     *     name="admin_login",
     *     path="/admin/",
     *     methods={"GET", "POST"}
     * )
     *
     * @Security("is_granted('ROLE_USER')")
     *
     * @Template
     */
    public function index(Request $request)
    {
        if ('POST' === $request->getMethod()) {
            if (!$this->isCsrfTokenValid('admin_authenticate', $request->request->get('_csrf_token'))) {
                return [
                    'error' => 'CSRF token invalide',
                ];
            }

            if ('caflyon' !== $request->request->get('username') || $request->request->get('password') !== $this->adminPassord) {
                return [
                    'error' => 'Identifiants invalides',
                ];
            }

            $request->getSession()->set('admin_caf', true);

            return $this->redirect($this->generateUrl('legacy_root'));
        }

        return [];
    }

    /**
     * @Route(
     *     name="admin_logout",
     *     path="/admin/logout",
     *     methods={"GET"}
     * )
     *
     * @Security("is_granted('ROLE_USER')")
     *
     * @Template
     */
    public function adminLogout(Request $request)
    {
        $request->getSession()->remove('admin_caf');

        return $this->redirect($this->generateUrl('legacy_root'));
    }
}
