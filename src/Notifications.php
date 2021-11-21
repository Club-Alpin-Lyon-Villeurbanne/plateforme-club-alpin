<?php

namespace App;

use App\Repository\CafArticleRepository;
use App\Repository\CafDestinationRepository;
use App\Repository\CafEvtRepository;

class Notifications
{
    private CafEvtRepository $cafEvtRepository;
    private CafArticleRepository $cafArticleRepository;
    private CafDestinationRepository $cafDestinationRepository;
    private UserRights $userRights;

    public function __construct(CafDestinationRepository $cafDestinationRepository, CafEvtRepository $cafEvtRepository, CafArticleRepository $cafArticleRepository, UserRights $userRights)
    {
        $this->cafDestinationRepository = $cafDestinationRepository;
        $this->cafEvtRepository = $cafEvtRepository;
        $this->cafArticleRepository = $cafArticleRepository;
        $this->userRights = $userRights;
    }

    public function getValidationSortie(): int
    {
        if ($this->userRights->allowed('evt_validate_all')) {
            return $this->cafEvtRepository->getUnvalidatedEvt();
        }

        if ($this->userRights->allowed('evt_validate')) {
            $commissions = $this->userRights->getCommissionListForRight('evt_validate');

            return $this->cafEvtRepository->getUnvalidatedEvt($commissions);
        }

        return 0;
    }

    public function getValidationSortiePresident(): int
    {
        if (!$this->userRights->allowed('evt_legal_accept')) {
            return 0;
        }

        return $this->cafEvtRepository->getUnvalidatedPresidentEvt();
    }

    public function getValidationArticle(): int
    {
        if ($this->userRights->allowed('article_validate_all')) {
            return $this->cafArticleRepository->getUnvalidatedArticle();
        }

        if ($this->userRights->allowed('article_validate')) {
            $commissions = $this->userRights->getCommissionListForRight('article_validate');

            return $this->cafArticleRepository->getUnvalidatedArticle($commissions);
        }

        return 0;
    }

    public function getDestinations(): int
    {
        return $this->cafDestinationRepository->getCountFutureUnpublishedDestinations();
    }

    public function getAll(): int
    {
        return $this->getDestinations() + $this->getValidationArticle() + $this->getValidationSortie() + $this->getValidationSortiePresident();
    }
}
