<?php

namespace App\Entity;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum TransportModeEnum: string implements TranslatableInterface
{
    case TRAIN = 'train';
    case THERMIC_COACH = 'thermic_coach';
    case BIKE = 'bike';
    case WALK = 'walk';
    case THERMIC_CARPOOLING = 'thermic_carpooling';
    case ELECTRIC_CARPOOLING = 'electric_carpooling';
    // case THERMIC_INDIVIDUAL_CAR = 'thermic_individual_car';
    // case ELECTRIC_INDIVIDUAL_CAR = 'electric_individual_car';
    case PLANE = 'plane';

    public static function getAsArray(): array
    {
        return self::cases();
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::TRAIN => 'Train',
            self::THERMIC_COACH => 'Autocar thermique',
            self::BIKE => 'Vélo',
            self::WALK => 'Marche',
            self::THERMIC_CARPOOLING => 'Voiture thermique (covoiturage)',
            self::ELECTRIC_CARPOOLING => 'Voiture électrique (covoiturage)',
            // self::THERMIC_INDIVIDUAL_CAR  => 'Voiture thermique (individuelle)',
            // self::ELECTRIC_INDIVIDUAL_CAR => 'Voiture électrique (individuelle)',
            self::PLANE => 'Avion',
        };
    }
}
