<?php

namespace App\Twig;

use App\Entity\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UserExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('age_user', [$this, 'getUserAge']),
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
}
