<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\MetabaseService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MetabaseController extends AbstractController
{
    #[Route('/stats/metabase', name: 'stats_metabase')]
    #[IsGranted('ROLE_USER')]
    #[Template('pages/stats-metabase.html.twig')]
    public function metabase(MetabaseService $metabase): array
    {
        $iframeUrl = $metabase->generateDashboardUrl(6);

        return [
            'iframeUrl' => $iframeUrl,
        ];
    }
}
