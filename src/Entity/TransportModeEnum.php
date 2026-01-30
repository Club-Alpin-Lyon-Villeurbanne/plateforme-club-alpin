<?php

namespace App\Entity;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum TransportModeEnum: string implements TranslatableInterface
{
    case PUBLIC_TRAIN = 'train';
    case PUBLIC_COACH = 'public_coach';
    case DEDICATED_COACH = 'dedicated_coach';
    case MINIVAN = 'minivan';
    case THERMIC_CARPOOLING = 'thermic_carpooling';
    case ELECTRIC_CARPOOLING = 'electric_carpooling';
    case BIKE_OR_WALK = 'bike_walk';
    case PLANE = 'plane';

    public static function getAsArray(): array
    {
        return self::cases();
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::PUBLIC_TRAIN => 'Transports en commun ferroviaire',
            self::PUBLIC_COACH => 'Transports en commun routier',
            self::DEDICATED_COACH => 'Car affrété',
            self::MINIVAN => 'Minibus',
            self::THERMIC_CARPOOLING => 'Covoiturage thermique',
            self::ELECTRIC_CARPOOLING => 'Covoiturage électrique',
            self::BIKE_OR_WALK => 'Vélo / pédestre',
            self::PLANE => 'Avion',
        };
    }
}
