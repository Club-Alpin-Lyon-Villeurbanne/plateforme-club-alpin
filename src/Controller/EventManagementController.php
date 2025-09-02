<?php

namespace App\Controller;

use App\Repository\EvtRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class EventManagementController extends AbstractController
{
    public function __construct(protected string $maxTimestampForLegalValidation)
    {
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    #[Route(path: '/validation-des-sorties.html', name: 'legal_manage_events', methods: ['GET'], priority: 12)]
    #[Template('sortie/gestion-sorties.html.twig')]
    public function legalManageEvents(Request $request, EvtRepository $eventRepository): array
    {
        $perPage = 30;
        $dateMax = strtotime($this->maxTimestampForLegalValidation);
        $page = $request->query->getInt('page', 1);
        $total = $eventRepository->getEventsToLegalValidateCount($dateMax);
        $pages = ceil($total / $perPage);
        $first = $perPage * ($page - 1);

        return [
            'events' => $eventRepository->getEventsToLegalValidate($dateMax, $first, $perPage),
            'title' => 'Validation des sorties en tant que sortie officielle du CAF',
            'total' => $total,
            'per_page' => $perPage,
            'pages' => $pages,
            'page' => $page,
            'page_url' => $this->generateUrl('legal_manage_events'),
            'to_include' => 'validation-des-sorties-main',
        ];
    }
}
