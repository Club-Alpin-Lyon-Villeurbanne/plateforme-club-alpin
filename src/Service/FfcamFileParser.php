<?php

namespace App\Service;

use App\Entity\User;

class FfcamFileParser
{
    // La FFCAM livre des valeurs parfois plus longues que nos colonnes (ville sur 33 caractères,
    // adresse concaténée depuis 4 champs...). Sans troncature, le flush échoue, ferme
    // l'EntityManager et la synchro s'interrompt pour tous les adhérents suivants du fichier.
    private const COLUMN_MAX_LENGTHS = [
        'firstname' => 50,
        'lastname' => 50,
        'civ' => 10,
        'cp' => 10,
        'ville' => 50,
        'adresse' => 100,
        'tel' => 100,
        'tel2' => 100,
        'email' => 200,
    ];

    /**
     * @throws \Exception
     */
    public function parse(string $filePath, string $fileType = 'annual'): \Generator
    {
        if (!$handle = @fopen($filePath, 'r')) {
            throw new \Exception("Can't open '$filePath'");
        }

        $lineNumber = 0;
        while (($line = fgets($handle)) !== false) {
            ++$lineNumber;
            try {
                if ('discovery' === $fileType) {
                    yield $this->parseDiscoveryLine($line, $lineNumber);
                } else {
                    yield $this->parseLine($line, $lineNumber);
                }
            } catch (\Exception $err) {
                \Sentry\captureException($err);
                continue;
            }
        }

        fclose($handle);
    }

    private function parseLine(string $line, int $lineNumber): User
    {
        $line = mb_convert_encoding($line, 'UTF-8', 'ISO-8859-1');
        $line = stripslashes($line);
        $line = explode(';', $line);

        $this->validateLine($line, $lineNumber);

        $user = new User();

        $firstname = ucfirst($this->normalizeNames(trim($line[10])));
        $lastname = strtoupper($this->normalizeNames(trim($line[9])));

        $birthdate = new \DateTimeImmutable($line[6]);

        $isLicenceExpired = '0000-00-00' === $line[7];
        $joinDate = $isLicenceExpired ? null : new \DateTimeImmutable($line[7]);

        $radiationDate = null;
        $radiationReason = trim($line[31]);
        if ('0000-00-00' !== $line[30]) {
            $radiationDate = \DateTimeImmutable::createFromFormat('Y-m-d', $line[30]);
        }

        $email = null;
        if (!empty(trim($line[28]))) {
            $email = $this->truncate(strtolower(trim($line[28])), 'email');
        }

        $user
            ->setCafnum(trim($line[0]))
            ->setFirstname($this->truncate($firstname, 'firstname'))
            ->setLastname($this->truncate($lastname, 'lastname'))
            ->setBirthdate($birthdate)
            ->setCiv($this->truncate($this->normalizeNames(str_replace('MLLE', 'MME', trim($line[8]))), 'civ'))
            ->setCafnumParent((int) $line[5] > 0 ? trim($line[1] . $line[5]) : null)
            ->setTel($this->truncate(trim($line[27]) ?: trim($line[29]), 'tel'))
            ->setTel2($this->truncate(trim($line[26]), 'tel2'))
            ->setAdresse($this->truncate(trim($line[11] . " \n" . $line[12] . " \n" . $line[13] . " \n" . $line[14]), 'adresse'))
            ->setCp($this->truncate($this->normalizeNames(trim($line[15])), 'cp'))
            ->setVille($this->truncate($this->normalizeNames(trim($line[16])), 'ville'))
            ->setDoitRenouveler($isLicenceExpired)
            ->setAlerteRenouveler($isLicenceExpired)
            ->setJoinDate($joinDate)
            ->setRadiationDate($radiationDate)
            ->setRadiationReason($radiationReason ?: null)
            ->setEmail($email)
            ->setValidityDuration(null)
            ->setDiscoveryEndDatetime(null)
            ->setNomade(false)
            ->setProfileType(User::PROFILE_CLUB_MEMBER)
        ;

        return $user;
    }

    /**
     * @throws \Exception
     */
    private function parseDiscoveryLine(string $line, int $lineNumber): User
    {
        $line = mb_convert_encoding($line, 'UTF-8', 'ISO-8859-1');
        $line = stripslashes($line);
        $line = explode(';', $line);

        $this->validateDiscoveryLine($line, $lineNumber);

        $user = new User();

        $firstname = ucfirst($this->normalizeNames(trim($line[7])));
        $lastname = strtoupper($this->normalizeNames(trim($line[6])));

        $birthdate = new \DateTimeImmutable(trim($line[4]));
        $joinDate = new \DateTimeImmutable(trim($line[2]) . ' ' . trim($line[3]));      // pas couvert par l'assurance avant l'heure indiquée
        $duration = (int) trim($line[1]);
        $dayDuration = $duration / 24;
        $endDate = (clone $joinDate)->modify('+' . ($dayDuration - 1) . ' day');        // durée 24h = fin le jour même ; durée 48h = fin le lendemain (j+1) ; durée 72h = fin le surlendemain (j+2)
        $endDate = $endDate->setTime(23, 59, 59);                            // couvert par l'assurance jusqu'à minuit

        $doitRenouveler = false;
        if ($endDate < new \DateTimeImmutable()) {
            $doitRenouveler = true;
        }

        $email = null;
        if (!empty(trim($line[16]))) {
            $email = $this->truncate(strtolower(trim($line[16])), 'email');
        }

        $user
            ->setCafnum(trim($line[0]))
            ->setFirstname($this->truncate($firstname, 'firstname'))
            ->setLastname($this->truncate($lastname, 'lastname'))
            ->setBirthdate($birthdate)
            ->setCiv($this->truncate($this->normalizeNames(str_replace('MLLE', 'MME', trim($line[5]))), 'civ'))
            ->setCafnumParent(null)
            ->setTel($this->truncate(trim($line[17]) ?: trim($line[14]), 'tel'))
            ->setTel2($this->truncate(trim($line[18]), 'tel2'))
            ->setAdresse($this->truncate(trim($line[8]) . " \n" . trim($line[9]) . " \n" . trim($line[10]) . " \n" . trim($line[11]), 'adresse'))
            ->setCp($this->truncate($this->normalizeNames(trim($line[12])), 'cp'))
            ->setVille($this->truncate($this->normalizeNames(trim($line[13])), 'ville'))
            ->setDoitRenouveler($doitRenouveler)
            ->setAlerteRenouveler($doitRenouveler)
            ->setJoinDate($joinDate)
            ->setRadiationDate(null)
            ->setRadiationReason(null)
            ->setEmail($email)
            ->setValidityDuration($duration)
            ->setDiscoveryEndDatetime($endDate)
            ->setNomade(false)
            ->setProfileType(User::PROFILE_DISCOVERY)
        ;

        return $user;
    }

    /**
     * @throws \Exception
     */
    private function validateDiscoveryLine(array $line, int $lineNumber): void
    {
        if (\count($line) < 24) {
            throw new \Exception("Can't process line $lineNumber : Invalid format. Expected : 24 columns. Got: " . \count($line));
        }

        $fullCafNum = $line[0];
        $duration = $line[1];
        $birthday = $line[4];
        $joinDate = $line[2];
        $joinHour = $line[3];

        if (
            empty($fullCafNum)
            || empty($duration)
            || !preg_match('#[0-9]{4}-[0-9]{2}-[0-9]{2}#', $birthday)
            || !preg_match('#[0-9]{4}-[0-9]{2}-[0-9]{2}#', $joinDate)
            || !preg_match('#[0-9]{2}:[0-9]{2}#', $joinHour)
        ) {
            throw new \Exception("Can't process line $lineNumber : Multiple values are wrong");
        }
    }

    private function validateLine(array $line, int $lineNumber): void
    {
        if (\count($line) < 33) {
            throw new \Exception("Can't process line $lineNumber : Invalid format. Expected : 33 columns. Got: " . \count($line));
        }

        $fullCafNum = $line[0];
        $clubNumber = $line[1];
        $cafNum = $line[2];
        $birthday = $line[6];

        if (
            !is_numeric($fullCafNum)
            || !is_numeric($clubNumber)
            || !is_numeric($cafNum)
            || !preg_match('#[0-9]{4}-[0-9]{2}-[0-9]{2}#', $birthday)
        ) {
            throw new \Exception("Can't process line $lineNumber : Multiple values are wrong");
        }
    }

    private function truncate(string $value, string $field): string
    {
        return mb_substr($value, 0, self::COLUMN_MAX_LENGTHS[$field]);
    }

    private function normalizeNames(string $name): string
    {
        return ucwords(mb_strtolower($name), ' -');
    }
}
