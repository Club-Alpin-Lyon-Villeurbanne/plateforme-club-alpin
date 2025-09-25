<?php

namespace App\Twig;

use App\Entity\Evt;
use App\Entity\User;
use App\Service\UserLicenseHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UserExtension extends AbstractExtension
{
    public function __construct(protected UserLicenseHelper $userLicenseHelper)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('age_user', [$this, 'getUserAge']),
            new TwigFunction('is_license_valid_for_event', [$this, 'isLicenseValidForEvent']),
        ];
    }

    public function getUserAge(User $user): ?int
    {
        $age = null;

        if (!empty($user->getBirthday())) {
            $birthdate = new \DateTime();
            $birthdate->setTimestamp($user->getBirthday());
            $diff = $birthdate->diff(new \DateTime());
            $age = $diff->y;
        }

        return $age;
    }

    public function isLicenseValidForEvent(User $user, Evt $event): bool
    {
        return $this->userLicenseHelper->isLicenseValidForEvent($user, $event);
    }
}
