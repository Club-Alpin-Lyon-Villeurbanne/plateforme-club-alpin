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
            'ski-randonnee-nordique',
        ];
    }

    public function getBrevets(?Commission $commission): array
    {
        if (!$commission instanceof Commission) {
            return [];
        }

        return match ($commission->getCode()) {
            'randonnee' => ['BF3-FC-CO', 'BF3-RA-RM', 'BFM-RA-RM', 'BF2-RA-RAL', 'BF1-RA-RM'],
            'alpinisme' => ['BF3-AL-AL', 'BFM-AL-GV', 'BFM-AL-CG', 'BF2-AL-GVE', 'BF2-AL-GV', 'BF2-AL-CG', 'BF1-AL-AL'],
            'snowboard-alpin' => ['BF3-SN-NA', 'BRV-BFSU10', 'BRV-BFST10'],
            'marche-nordique' => ['BRV-QFMN10'],
            'snowboard-rando' => ['BF3-SN-NA', 'BF3-FC-CO', 'BF3-SN-SWA', 'BF2-SN-SWA', 'BF1-SN-SW'],
            'canyon' => ['BF3-CA-CA', 'BFM-CA-CA', 'BF1-CA-CA'],
            'escalade' => ['BF3-ES-ES', 'BFM-ES-PF', 'BFM-ES-GVE', 'BFM-ES-GV', 'BF2-ES-VF', 'BF2-ES-PS', 'BF2-ES-GVE', 'BF2-AL-GV', 'BF2-AL-CG', 'BF1-ES-SNE', 'BF1-ES-SAE'],
            'raquette' => ['BF3-SN-NA', 'BF3-FC-CO', 'BF2-SN-RQ'],
            'ski-de-randonnee' => ['BF3-SN-NA', 'BF3-FC-CO', 'BF3-SN-SA', 'BF2-SN-SA', 'BF1-SN-SR'],
            'ski-de-piste' => ['BF3-SN-NA', 'BRV-BFSA10', 'BRV-BFST10'],
            'ski-de-fond' => ['BRV-BFSF13', 'BRV-BFSF10'],
            'trail' => ['BF3-RA-TR', 'BF1-RA-TR'],
            'via-ferrata' => ['BF2-ES-VF'],
            'vtt' => ['BF3-VM-VM', 'BF1-VM-VM'],
            'ski-randonnee-nordique' => ['BF3-SN-NA', 'BRV-BFRN10'],
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
            'canyon' => ['STG-FCA00', 'FOR-CICA10', 'FOR-CICA20', 'FOR-CICA30', 'STG-FCA30'],
            'escalade' => ['STG-FEA10', 'STG-FEB10', 'STG-FES10', 'STG-FES20', 'FOR-CIEN10', 'FOR-CIEA10', 'STG-FES30', 'STG-FES50'],
            'raquette' => ['STG-FRQ00', 'FOR-CIRQ20', 'STG-FRQ10'],
            'ski-de-randonnee' => ['FOR-CISM40', 'STG-FSM10', 'STG-FSM20', 'STG-FSM40'],
            'ski-de-piste' => ['STG-UFSG20', 'STG-FSA10'],
            'ski-de-fond' => ['STG-FSF10'],
            'trail' => ['FOR-CITR10'],
            'via-ferrata' => ['STG-UFVF10', 'STG-UFVF60'],
            'vtt' => ['STG-FVM10', 'STG-FVM20', 'FOR-CIVM10'],
            'environnement' => ['FOR-CIFC10', 'FOR-CIFC20', 'FOR-CIFC30', 'FOR-CIFC40'],
            default => [],
        };
    }
}
