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
    // Conservé pour la rétro-compatibilité d'hydratation Doctrine (anciennes sorties
    // pouvant encore avoir mode_transport='avion' avant que la migration tourne).
    // N'est plus proposé dans le formulaire ni pris en compte par le calcul carbone.
    case PLANE = 'avion';

    /**
     * Valeurs des modes pour lesquels un nombre de véhicules est pertinent.
     * Source unique de vérité consommée par le PHP (requiresVehicleCount, formulaire)
     * et par le JS via data-attribute sur #nb-vehicules-wrapper.
     */
    public const VALUES_REQUIRING_VEHICLE_COUNT = [
        'minibus',
        'car_affrete',
        'covoiturage_thermique',
        'covoiturage_electrique',
    ];

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

    public function requiresVehicleCount(): bool
    {
        return \in_array($this->value, self::VALUES_REQUIRING_VEHICLE_COUNT, true);
    }

    public function isObsolete(): bool
    {
        return match ($this) {
            self::PLANE => true,
            default => false,
        };
    }
}
