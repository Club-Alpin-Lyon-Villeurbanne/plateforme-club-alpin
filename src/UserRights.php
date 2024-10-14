<?php

namespace App;

use App\Entity\Commission;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Legacy\LegacyContainer;
use App\Repository\CommissionRepository;
use App\Repository\UserRightRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Service\ResetInterface;

class UserRights implements ResetInterface
{
    private AuthorizationCheckerInterface $authChecker;
    private TokenStorageInterface $tokenStorage;
    private UserRightRepository $userRightRepository;
    private CommissionRepository $commissionRepository;
    private CacheInterface $cache;

    public function __construct(
        AuthorizationCheckerInterface $authChecker,
        TokenStorageInterface $tokenStorage,
        UserRightRepository $userRightRepository,
        CommissionRepository $commissionRepository,
        CacheInterface $cache
    ) {
        $this->authChecker = $authChecker;
        $this->tokenStorage = $tokenStorage;
        $this->userRightRepository = $userRightRepository;
        $this->commissionRepository = $commissionRepository;
        $this->cache = $cache;
    }

    public function allowedOnCommission(string $code, Commission $commission): bool
    {
        return $this->allowed($code, 'commission:' . $commission->getCode());
    }

    public function reset(): void
    {
        $this->cache->delete('user_rights_' . $this->getCurrentUserId());
    }

    public function allowed(string $codeUserright, string $param = ''): bool
    {
        $userRights = $this->loadRights();

        if (!isset($userRights[$codeUserright])) {
            return false;
        }

        if ($userRights[$codeUserright] === true) {
            return true;
        }

        if (empty($param)) {
            return true;
        }

        $allowedParams = explode('|', $userRights[$codeUserright]);
        return in_array($param, $allowedParams) || in_array(basename($param), $allowedParams);
    }

    public function getAllCommissionCodes(): array
    {
        return $this->commissionRepository->findAllCommissionCodes();
    }

    public function getCommissionListForRight(string $right): array
    {
        $userRights = $this->loadRights();

        if (!isset($userRights[$right])) {
            return [];
        }

        if ($userRights[$right] === true) {
            return $this->getAllCommissionCodes();
        }

        return array_map('basename', explode('|', $userRights[$right]));
    }

    private function loadRights(): array
    {
        $userId = $this->getCurrentUserId();

        return $this->cache->get('user_rights_' . $userId, function (ItemInterface $item) use ($userId) {
            $item->expiresAfter(3600); // Cache for 1 hour

            $user = $this->tokenStorage->getToken()?->getUser();

            if (!$user instanceof User || $user->getDoitRenouveler()) {
                return $this->getVisitorRights();
            }

            $userRights = $this->userRightRepository->findRightsByUser($user->getId());

            if (!$user->hasAttribute(UserAttr::SALARIE)) {
                $userRights = array_merge($userRights, $this->userRightRepository->getRightsByUserType('adherent'));
            }

            if ($this->isAdmin()) {
                return array_fill_keys(array_keys($userRights), true);
            }

            return $userRights;
        });
    }

    private function getVisitorRights(): array
    {

        $visitorRights = $this->userRightRepository->getRightsByUserType('visiteur');
        $visitorRightCodes = array_column($visitorRights, 'code_userright');
        return array_fill_keys($visitorRightCodes, true);
    }

    private function isAdmin(): bool
    {
        return !$this->authChecker->isGranted('IS_IMPERSONATOR') && $this->authChecker->isGranted('ROLE_ADMIN');
    }

    private function getCurrentUserId(): ?int
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        return $user instanceof User ? $user->getId() : null;
    }
}