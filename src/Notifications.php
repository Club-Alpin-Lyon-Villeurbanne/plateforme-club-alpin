<?php

namespace App;

use App\Repository\ArticleRepository;
use App\Repository\CommissionRepository;
use App\Repository\EvtRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

class Notifications
{
    public function __construct(
        protected EvtRepository $evtRepository,
        protected ArticleRepository $cafArticleRepository,
        protected UserRights $userRights,
        protected CommissionRepository $commissionRepository,
        protected readonly string $maxTimestampForLegalValidation
    ) {
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getValidationSortie(): int
    {
        $validate = $this->userRights->allowed('evt_validate');
        $validateAll = $this->userRights->allowed('evt_validate_all');

        $commissions = [];
        if ($validate && !$validateAll) {
            $commissionCodes = $this->userRights->getCommissionListForRight('evt_validate');
            $commissions = $this->commissionRepository->findBy(['code' => $commissionCodes]);
        }

        return $this->evtRepository->getEventsToPublishCount($commissions);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getValidationSortiePresident(): int
    {
        if (!$this->userRights->allowed('evt_legal_accept')) {
            return 0;
        }

        $dateMax = (int) strtotime($this->maxTimestampForLegalValidation);

        return $this->evtRepository->getEventsToLegalValidateCount($dateMax);
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

    public function getAll(): int
    {
        return $this->getValidationArticle() + $this->getValidationSortie() + $this->getValidationSortiePresident();
    }
}
