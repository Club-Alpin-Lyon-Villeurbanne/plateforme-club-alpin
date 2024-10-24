<?php

namespace App\Utils\User;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class UserProfileValidator
{
    private const DISPLAY_LABELS = [
        'tel' => 'votre numéro de téléphone',
        'tel2' => 'votre numéro de téléphone de secours',
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
     * @return array the list of incomplete properties (by name, associated with its label)
     */
    public function validateUserProfile(): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof UserInterface) {
            return [];
        }

        $properties = ['tel', 'tel2'];

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $missingProperties = ['internal' => [], 'external' => []];

        foreach ($properties as $property) {
            $value = $propertyAccessor->getValue($user, $property);
            if (empty($value)) {
                $missingProperties['external'][] = self::DISPLAY_LABELS[$property];
            }
        }

        if (!$this->photoExists($user)) {
            $missingProperties['internal'][] = self::DISPLAY_LABELS['photo'];
        }

        $missingProperties['message'] = self::getErrorsAsString($missingProperties);

        return $missingProperties;
    }

    /**
     * Returns true if the User profile is not missing any properties.
     */
    public function isUserProfileIncomplete(): bool
    {
        return 0 < \count($this->validateUserProfile()['internal']) || 0 < \count($this->validateUserProfile()['external']);
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
        $internalError = $errors['internal'];
        $externalErrors = $errors['external'];

        $error = 'Merci de renseigner ';

        if ($internalError) {
            $error .= sprintf('%s dans votre <a href="/profil/infos.html">espace personnel</a>', $internalError[0]);
            if ($externalErrors) {
                $error .= ', ';
            }
        }

        if ($externalErrors) {
            if (1 === \count($externalErrors)) {
                $error .= $externalErrors[0];
            } else {
                $error .= implode(', ', \array_slice($externalErrors, 0, -1)) . ' et ' . $externalErrors[\count($externalErrors) - 1];
            }
            $error .= ' dans votre <a href="https://extranet-clubalpin.com/monespace/" target="_blank">espace licencié FFCAM</a>';
        }

        $error .= '.';

        return $error;
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
        $photoPath = $this->legacyFtpPath . 'user/' . $user->getId() . '/min-profil.jpg';

        return file_exists($photoPath);
    }
}
