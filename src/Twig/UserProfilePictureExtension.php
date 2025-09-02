<?php

namespace App\Twig;

use App\Entity\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UserProfilePictureExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('user_image', [$this, 'getUserImage']),
        ];
    }

    public function getUserImage(?User $user, string $style): string
    {
        switch ($style) {
            case 'pic':
            case 'min':
                $style .= '-';
                break;
            default:
                $style = '';
                break;
        }

        // If user is not provided (e.g., visitor not logged in), fallback to default image
        $userId = $user?->getId() ?? 0;
        $rel = '/ftp/user/' . $userId . '/' . $style . 'profil.jpg';
        if (!file_exists(__DIR__ . '/../../public' . $rel)) {
            $rel = '/ftp/user/0/' . $style . 'profil.jpg';
        }

        return $rel;
    }
}
