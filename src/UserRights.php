<?php

namespace App;

use App\Entity\Commission;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Repository\CommissionRepository;
use App\Repository\UserRightRepository;
use App\Security\SecurityConstants;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Service\ResetInterface;

class UserRights implements ResetInterface
{
    public function __construct(
        private AuthorizationCheckerInterface $authChecker,
        private TokenStorageInterface $tokenStorage,
        private UserRightRepository $userRightRepository,
        private CommissionRepository $commissionRepository,
        private ?array $cachedUserRights = null
    ) {
    }

    public function allowedOnCommission(string $code, Commission $commission): bool
    {
        return $this->allowed($code, 'commission:' . $commission->getCode());
    }

    public function reset(): void
    {
        $this->cachedUserRights = null;
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
        if (null !== $this->cachedUserRights) {
            return $this->cachedUserRights;
        }

        $user = $this->tokenStorage->getToken() ? $this->tokenStorage->getToken()->getUser() : null;

        if (!$user instanceof User || $user->getDoitRenouveler()) {
            return $this->getVisitorRights();
        }

        $userRights = $this->userRightRepository->findRightsByUser($user->getId());

        if (!$user->hasAttribute(UserAttr::SALARIE)) {
            $rightsByUserType = $this->userRightRepository->getRightsByUserType('adherent');
            foreach ($rightsByUserType as $row) {
                $userRights[$row['code_userright']] = 'true';
            }
        }

        if ($this->isAdmin()) {
            return array_fill_keys(array_keys($userRights), true);
        }

        return $this->cachedUserRights = $userRights;
    }

    private function getVisitorRights(): array
    {

        $visitorRights = $this->userRightRepository->getRightsByUserType('visiteur');
        $visitorRightCodes = array_column($visitorRights, 'code_userright');
        return array_fill_keys($visitorRightCodes, true);
    }

    private function isAdmin(): bool
    {
        return !$this->authChecker->isGranted('IS_IMPERSONATOR') && $this->authChecker->isGranted(SecurityConstants::ROLE_ADMIN);
    }
}