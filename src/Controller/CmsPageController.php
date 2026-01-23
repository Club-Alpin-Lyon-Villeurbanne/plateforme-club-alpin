<?php

namespace App\Controller;

use App\Entity\Page;
use App\Security\SecurityConstants;
use App\Service\CmsContentService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class CmsPageController extends AbstractController
{
    #[Route(path: '/pages/{code}.html', name: 'page_view', requirements: ['code' => '[a-z0-9-]+'], methods: ['GET'], priority: '10')]
    #[Template('cms/page.html.twig')]
    public function view(Page $page, CmsContentService $contentService): array
    {
        $isPageAccessible = false;
        // page accessible ou non selon profil
        if (
            $this->isGranted(SecurityConstants::ROLE_ADMIN)
            || $this->isGranted(SecurityConstants::ROLE_CONTENT_MANAGER) && !$page->getSuperadmin()
            || $page->getVis() && !$page->getAdmin() && !$page->getSuperadmin()
        ) {
            $isPageAccessible = true;
        }

        if (!$isPageAccessible) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        return [
            'page' => $page,
            'meta_title' => $page->getDefaultName() ?: $contentService->getMeta('meta-title-' . $page->getCode()),
            'meta_desc' => $contentService->getMeta('meta-description-' . $page->getCode()) ?: $contentService->getMeta('site-meta-description'),
        ];
    }
}
