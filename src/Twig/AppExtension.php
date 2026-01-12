<?php

declare(strict_types=1);

namespace App\Twig;

use App\Utils\User\UserProfileValidator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigTest;

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
            new TwigFunction('is_user_profile_incomplete', [$this, 'isUserProfileIncomplete']),
            new TwigFunction('display_user_profile_warning', [$this, 'displayUserProfileWarning'], ['is_safe' => ['html']]),
        ];
    }

    public function getTests(): array
    {
        return [
            new TwigTest('instanceof', [$this, 'isInstanceof']),
        ];
    }

    public function validateUserProfile(): array
    {
        return $this->userProfileValidator->validateUserProfile();
    }

    public function isUserProfileIncomplete(): bool
    {
        return $this->userProfileValidator->isUserProfileIncomplete();
    }

    public function displayUserProfileWarning(): string
    {
        return $this->validateUserProfile()['message'];
    }

    public function isInstanceof($var, string $class): bool
    {
        return $var instanceof $class;
    }
}
