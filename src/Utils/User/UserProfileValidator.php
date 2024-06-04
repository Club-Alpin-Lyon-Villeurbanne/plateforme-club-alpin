<?php

namespace App\Utils\User;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class UserProfileValidator
{
    public const DISPLAY_LABELS = [
        'tel' => 'votre numéro de téléphone',
        'tel2' => 'votre numéro de téléphone n°2',
        'photo' => 'votre photo de profil',
    ];

    public function __construct(
        private Security $security,
        private string $legacyFtpPath
    ) {
    }

    /**
     * Validates that a logged-in User has completed his profile (no missing properties). Checks by default 'tel', 'tel2' and
     * 'photo'.
     *
     * @param array $context the list of additional property names to check
     *
     * @return array the list of incomplete properties (by name, associated with its label)
     */
    public function validateUserProfile(array $context = []): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof UserInterface) {
            return [];
        }

        $defaultProperties = ['tel', 'tel2'];
        $properties = array_merge($defaultProperties, $context);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $missingProperties = [];

        foreach ($properties as $property) {
            $value = $propertyAccessor->getValue($user, $property);
            if (empty($value)) {
                $missingProperties[$property] = self::DISPLAY_LABELS[$property];
            }
        }

        if (!$this->photoExists($user)) {
            $missingProperties['photo'] = self::DISPLAY_LABELS['photo'];
        }

        return $missingProperties;
    }

    /**
     * Returns true if the User profile is not missing any properties.
     */
    public function isUserProfileComplete(): bool
    {
        return 0 !== \count($this->validateUserProfile());
    }

    /**
     * Formats an array of errors as string.
     *
     * @param array $errors the array of errors
     *
     * @return string the formatted string
     */
    public static function getErrorsAsString(array $errors): string
    {
        if (1 === \count($errors)) {
            return $errors[0];
        }

        return implode(', ', \array_slice($errors, 0, -1)).' et '.$errors[\count($errors) - 1];
    }

    /**
     * Checks if a User pĥoto exists.
     *
     * @return bool True if the profile photo image exists, false otherwise
     */
    private function photoExists(UserInterface $user): bool
    {
        /* $legacyFtpPath = "%kernel.project_dir%/public/ftp/" */
        /** @var User $user */
        $photoPath = $this->legacyFtpPath.'user/'.$user->getId().'/min-profil.jpg';

        return file_exists($photoPath);
    }
}
