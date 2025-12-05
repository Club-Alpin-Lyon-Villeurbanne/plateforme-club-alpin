<?php

namespace App\Service;

use App\Entity\User;

class FfcamFileParser
{
    /**
     * @return \Generator<User>
     */
    public function parse(string $filePath): \Generator
    {
        if (!$handle = @fopen($filePath, 'r')) {
            throw new \Exception("Can't open '$filePath'");
        }

        $lineNumber = 0;
        while (($line = fgets($handle)) !== false) {
            ++$lineNumber;
            try {
                yield $this->parseLine($line, $lineNumber);
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
        ;

        return $user;
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
