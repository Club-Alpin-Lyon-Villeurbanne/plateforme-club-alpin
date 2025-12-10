<?php

namespace App\Service;

use App\Entity\Commission;

class FfcamSkillsService
{
    public function getSkilledCommissions(): array
    {
        return [
            'randonnee',
            'alpinisme',
            'ski-de-randonnee',
            'snowboard-alpin',
            'marche-nordique',
            'snowboard-rando',
            'canyon',
            'escalade',
            'raquette',
            'ski-de-randonnee',
            'ski-de-piste',
            'ski-de-fond',
            'trail',
            'via-ferrata',
            'vtt',
            'environnement',
        ];
    }

    public function getBrevets(?Commission $commission): array
    {
        if (!$commission instanceof Commission) {
            return [];
        }

        return match ($commission->getCode()) {
            'alpinisme' => ['BF3-AL-AL', 'BFM-AL-GV', 'BFM-AL-CG', 'BF2-AL-GVE', 'BF2-AL-GV', 'BF2-AL-CG', 'BF1-AL-AL'],
            'randonnee' => ['BF3-FC-CO', 'BF3-RA-RM', 'BFM-RA-RM', 'BF2-RA-RAL', 'BF1-RA-RM'],
            'ski-de-randonnee' => ['BF3-SN-NA', 'BRV-BFRN10'],
            'snowboard-alpin' => ['BF3-SN-NA', 'BRV-BFSU10', 'BRV-BFST10'],
            'marche-nordique' => ['BF3-SN-NA', 'BRV-QFMN10'],
            'snowboard-rando' => ['BF3-SN-NA', 'BF3-FC-CO', 'BF3-SN-SWA', 'BF2-SN-SWA', 'BF1-SN-SW'],
            'via-ferrata' => ['BF2-ES-VF'],
            default => [],
        };
    }

    public function getFormations(?Commission $commission): array
    {
        if (!$commission instanceof Commission) {
            return [];
        }

        return match ($commission->getCode()) {
            'alpinisme' => ['STG-FAL10', 'STG-FAL20', 'STG-FAM10', 'STG-FAT10', 'STG-UFGV10', 'STG-FCG10'],
            'randonnee' => ['STG-FRA10', 'STG-FRD10', 'FOR-CIRM10', 'FOR-CIRA50', 'STG-FRD20', 'STG-FRM10'],
            'snowboard-alpin' => ['STG-UFSG20'],
            'snowboard-rando' => ['FOR-CISB10', 'FOR-FSB10', 'STG-FSB10', 'STG-FSL10', 'STG-FSU10'],
            'via-ferrata' => ['STG-UFVF10', 'STG-UFVF60'],
            default => [],
        };
    }

    public function getNiveaux(?Commission $commission): array
    {
        if (!$commission instanceof Commission) {
            return [];
        }

        return match ($commission->getCode()) {
            'alpinisme' => [],
            'randonnee' => [],
            default => [],
        };
    }
}
