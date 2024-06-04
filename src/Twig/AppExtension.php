<?php

declare(strict_types=1);

namespace App\Twig;

use App\Utils\User\UserProfileValidator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly UserProfileValidator $userProfileValidator
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('validate_user_profile', [$this, 'validateUserProfile']),
        ];
    }

    public function validateUserProfile(): array
    {
        return $this->userProfileValidator->validateUserProfile();
    }
}
