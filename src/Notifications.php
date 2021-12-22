<?php

namespace App;

use App\Repository\ArticleRepository;
use App\Repository\DestinationRepository;
use App\Repository\EvtRepository;

class Notifications
{
    private EvtRepository $evtRepository;
    private ArticleRepository $cafArticleRepository;
    private DestinationRepository $cafDestinationRepository;
    private UserRights $userRights;

    public function __construct(DestinationRepository $cafDestinationRepository, EvtRepository $evtRepository, ArticleRepository $cafArticleRepository, UserRights $userRights)
    {
        $this->cafDestinationRepository = $cafDestinationRepository;
        $this->evtRepository = $evtRepository;
        $this->cafArticleRepository = $cafArticleRepository;
        $this->userRights = $userRights;
    }

    public function getValidationSortie(): int
    {
        if ($this->userRights->allowed('evt_validate_all')) {
            return $this->evtRepository->getUnvalidatedEvt();
        }

        if ($this->userRights->allowed('evt_validate')) {
            $commissions = $this->userRights->getCommissionListForRight('evt_validate');

            return $this->evtRepository->getUnvalidatedEvt($commissions);
        }

        return 0;
    }

    public function getValidationSortiePresident(): int
    {
        if (!$this->userRights->allowed('evt_legal_accept')) {
            return 0;
        }

        return $this->evtRepository->getUnvalidatedPresidentEvt();
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
