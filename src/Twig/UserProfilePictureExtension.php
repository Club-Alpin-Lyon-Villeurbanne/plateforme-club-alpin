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

    public function getUserImage(User $user, string $style): string
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

        $rel = '/ftp/user/' . $user->getId() . '/' . $style . 'profil.jpg';
        if (!file_exists(__DIR__ . '/../../public' . $rel)) {
            $rel = '/ftp/user/0/' . $style . 'profil.jpg';
        }

        return $rel;
    }
}
