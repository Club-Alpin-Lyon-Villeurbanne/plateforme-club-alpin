<?php

namespace App\Service;

use App\Entity\User;

class FfcamFileParser
{
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
            $email = strtolower(trim($line[28]));
        }

        $user
            ->setCafnum(trim($line[0]))
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setBirthdate($birthdate)
            ->setCiv($this->normalizeNames(str_replace('MLLE', 'MME', trim($line[8]))))
            ->setCafnumParent((int) $line[5] > 0 ? trim($line[1] . $line[5]) : null)
            ->setTel(trim($line[27]))
            ->setTel2(trim($line[26]))
            ->setAdresse(trim($line[11] . " \n" . $line[12] . " \n" . $line[13] . " \n" . $line[14]))
            ->setCp($this->normalizeNames(trim($line[15])))
            ->setVille($this->normalizeNames(trim($line[16])))
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
            $email = strtolower(trim($line[16]));
        }

        $user
            ->setCafnum(trim($line[0]))
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setBirthdate($birthdate)
            ->setCiv($this->normalizeNames(str_replace('MLLE', 'MME', trim($line[5]))))
            ->setCafnumParent(null)
            ->setTel(trim($line[14]))
            ->setTel2(trim($line[18]))
            ->setAdresse(trim($line[8]) . " \n" . trim($line[9]) . " \n" . trim($line[10]) . " \n" . trim($line[11]))
            ->setCp($this->normalizeNames(trim($line[12])))
            ->setVille($this->normalizeNames(trim($line[13])))
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

    private function normalizeNames(string $name): string
    {
        return ucwords(mb_strtolower($name), ' -');
    }
}
