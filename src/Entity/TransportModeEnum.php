<?php

declare(strict_types=1);

namespace App\Entity;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum TransportModeEnum: string implements TranslatableInterface
{
    case PUBLIC_TRAIN = 'train';
    case PUBLIC_COACH = 'car_ligne';
    case DEDICATED_COACH = 'car_affrete';
    case MINIVAN = 'minibus';
    case THERMIC_CARPOOLING = 'covoiturage_thermique';
    case ELECTRIC_CARPOOLING = 'covoiturage_electrique';
    case BIKE_OR_WALK = 'velo_marche';
    case PLANE = 'avion';

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
