<?php

namespace App\Controller;

use App\Repository\CommissionRepository;
use App\Repository\EvtRepository;
use App\Trait\PaginationControllerTrait;
use App\UserRights;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class EventManagementController extends AbstractController
{
    use PaginationControllerTrait;

    public function __construct(
        protected UserRights $userRights,
        protected CommissionRepository $commissionRepository,
        protected EvtRepository $eventRepository,
        protected string $maxTimestampForLegalValidation)
    {
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    #[Route(path: '/gestion-des-sorties.html', name: 'manage_events', methods: ['GET'], priority: 12)]
    #[Template('sortie/gestion-sorties.html.twig')]
    public function manageEvents(Request $request): array
    {
        $validate = $this->userRights->allowed('evt_validate');
        $validateAll = $this->userRights->allowed('evt_validate_all');

        if (!$validate && !$validateAll) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        // commissions pour lesquelles on a des droits
        $commissions = [];
        if ($validate && !$validateAll) {
            $commissionCodes = $this->userRights->getCommissionListForRight('evt_validate');
            $commissions = $this->commissionRepository->findBy(['code' => $commissionCodes]);
        }

        $total = $this->eventRepository->getEventsToPublishCount($commissions);
        $paginationParams = $this->getPaginationParams($request, $total);

        return array_merge([
            'events' => $this->eventRepository->getEventsToPublish($commissions, $paginationParams['first'], $paginationParams['per_page']),
            'title' => 'Approbation des sorties',
            'page_url' => $this->generateUrl('manage_events'),
            'to_include' => 'gestion-des-sorties-main',
            'action' => 'approbation',
        ], $paginationParams);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    #[Route(path: '/validation-des-sorties.html', name: 'legal_manage_events', methods: ['GET'], priority: 12)]
    #[Template('sortie/gestion-sorties.html.twig')]
    public function legalManageEvents(Request $request): array
    {
        if (!$this->userRights->allowed('evt_legal_accept')) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        $dateMax = (int) strtotime($this->maxTimestampForLegalValidation);
        $total = $this->eventRepository->getEventsToLegalValidateCount($dateMax);
        $paginationParams = $this->getPaginationParams($request, $total);

        return array_merge([
            'events' => $this->eventRepository->getEventsToLegalValidate($dateMax, $paginationParams['first'], $paginationParams['per_page']),
            'title' => 'Validation des sorties en tant que sortie officielle du CAF',
            'page_url' => $this->generateUrl('legal_manage_events'),
            'to_include' => 'validation-des-sorties-main',
            'action' => 'validation',
        ], $paginationParams);
    }
}
