<?php

namespace App\Tests\TestHelpers;

class FfcamTestHelper
{
    private const TEMPLATE = '%s;6900;%s;99;A1;;%s;%s;M;%s;%s;;LE BELVEDERE;12 RUE DES LILAS;;69001;LYON;0;0;0000-00-00;0;;0;;0;;0472000001 0630000001;%s;%s;04.72.00.00.01;0000-00-00;;contact;RANDONNEE,SKI ALPIN,SKI NORDIQUE;;0;;;;;;;;;;;;;;;;;;;;;;;;;A;0;3;;;O;,';

    public static function generateFile(array $members, ?string $filePath = null): string
    {
        if (!$filePath) {
            $filePath = tempnam(sys_get_temp_dir(), 'ffcam_');
        }

        $content = '';

        foreach ($members as $member) {
            $cafnum = $member['cafnum'] ?? rand(100000000000, 999999999999);
            $shortCafnum = substr($cafnum, 4);
            $lastname = $member['lastname'] ?? 'DUPONT';
            $firstname = $member['firstname'] ?? 'JEAN';
            $adhesionDate = $member['adhesionDate'] ?? '0000-00-00';
            $birthday = $member['birthday'] ?? '1990-01-01';
            $tel = $member['tel'] ?? '0687000001';
            $email = $member['email'] ?? 'test-email@clubalpinlyon.fr';

            $content .= sprintf(
                self::TEMPLATE,
                $cafnum,
                $shortCafnum,
                $birthday,
                $adhesionDate,
                $lastname,
                $firstname,
                $tel,
                $email,
            ) . "\n";
        }

        $isoContent = mb_convert_encoding($content, 'ISO-8859-1', 'UTF-8');

        file_put_contents($filePath, $isoContent);

        return $filePath;
    }
}
