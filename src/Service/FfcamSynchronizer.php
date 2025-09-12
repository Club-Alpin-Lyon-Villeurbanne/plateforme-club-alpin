<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils\MemberMerger;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class FfcamSynchronizer
{
    private bool $hasTolerancyPeriodPassed;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly FfcamFileParser $fileParser,
        private readonly MemberMerger $memberMerger,
    ) {
        $today = new \DateTime();
        $startDate = new \DateTime($today->format('Y') . '-08-25');
        $endDate = new \DateTime($today->format('Y') . '-09-30');

        $this->hasTolerancyPeriodPassed = !($today >= $startDate && $today <= $endDate);
    }

    public function synchronize(?string $ffcamFilePath = null): void
    {
        if (!$this->isFileValid($ffcamFilePath)) {
            $this->logger->warning("File {$ffcamFilePath} not found. Can't import new members");
            $this->userRepository->blockExpiredAccounts();
            $this->userRepository->removeExpiredFiliations();

            return;
        }

        $stats = $this->processMembers($this->fileParser->parse($ffcamFilePath));

        $this->archiveFile($ffcamFilePath, $stats);
        $this->logResults($ffcamFilePath, $stats);

        $this->userRepository->blockExpiredAccounts();
        $this->userRepository->removeExpiredFiliations();
    }

    private function isFileValid(string $filePath): bool
    {
        return file_exists($filePath) && is_file($filePath);
    }

    private function processMembers(\Generator $members): array
    {
        $stats = ['inserted' => 0, 'updated' => 0, 'merged' => 0];
        $batchSize = 20;
        $i = 0;

        foreach ($members as $parsedUser) {
            $this->logger->info("Processing CAF member {$parsedUser->getCafnum()}");

            $existingUser = $this->userRepository->findOneByLicenseNumber($parsedUser->getCafnum());

            if ($existingUser) {
                $this->updateExistingUser($existingUser, $parsedUser);
                ++$stats['updated'];
                continue;
            }

            $potentialDuplicate = $this->userRepository->findDuplicateUser(
                $parsedUser->getLastname(),
                $parsedUser->getFirstname(),
                $parsedUser->getBirthday(),
                $parsedUser->getCafnum()
            );

            if ($potentialDuplicate) {
                $this->logger->info(sprintf(
                    'Found duplicate member %s %s (old license: %s, new license: %s)',
                    $parsedUser->getLastname(),
                    $parsedUser->getFirstname(),
                    $potentialDuplicate->getCafnum(),
                    $parsedUser->getCafnum()
                ));

                $this->memberMerger->mergeNewMember($potentialDuplicate->getCafnum(), $parsedUser);
                ++$stats['merged'];
            } else {
                $parsedUser->setTsInsert(time());
                $parsedUser->setValid(false);
                $this->entityManager->persist($parsedUser);
                ++$stats['inserted'];
            }

            if (0 === ++$i % $batchSize) {
                try {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                } catch (\Exception $exception) {
                    $this->logger->error($exception->getMessage());
                }
            }
        }

        try {
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $stats;
    }

    private function updateExistingUser(User $existingUser, User $parsedUser): void
    {
        $existingUser
            ->setFirstname($parsedUser->getFirstname())
            ->setLastname($parsedUser->getLastname())
            ->setBirthday($parsedUser->getBirthday())
            ->setCiv($parsedUser->getCiv())
            ->setCafnumParent($parsedUser->getCafnumParent())
            ->setTel($parsedUser->getTel())
            ->setTel2($parsedUser->getTel2())
            ->setAdresse($parsedUser->getAdresse())
            ->setCp($parsedUser->getCp())
            ->setVille($parsedUser->getVille())
            ->setNickname($parsedUser->getNickname())
            ->setDoitRenouveler($parsedUser->getDoitRenouveler() && $this->hasTolerancyPeriodPassed)
            ->setAlerteRenouveler($parsedUser->getAlerteRenouveler())
            ->setTsUpdate(time())
            ->setManuel(false)
            ->setNomade(false)
        ;

        // Ne pas effacer la date d'adhésion quand l'adhésion parsée est expirée (valeur nulle).
        // Conserver la date d'adhésion existante sauf si une nouvelle adhésion valide est fournie.
        if (null !== $parsedUser->getDateAdhesion()) {
            $existingUser->setDateAdhesion($parsedUser->getDateAdhesion());
        }

        $this->entityManager->persist($existingUser);
    }

    private function archiveFile(string $filePath, array $stats): void
    {
        if ($stats['inserted'] > 0 || $stats['updated'] > 0) {
            $zip = new \ZipArchive();
            $filename = $filePath . '_' . date('Y-m-d') . '.zip';
            $zip->open($filename, \ZipArchive::CREATE);
            $zip->addFile($filePath, basename($filePath));
            $zip->close();
        }
    }

    private function logResults(string $filePath, array $stats): void
    {
        $this->logger->info(sprintf(
            'Members synchronization finished. New members : %d, Updated members : %d',
            $stats['inserted'],
            $stats['updated']
        ));

        try {
            $this->entityManager->getConnection()->executeQuery(
                "INSERT INTO `caf_log_admin` (`code_log_admin`, `desc_log_admin`, `ip_log_admin`, `date_log_admin`)
                VALUES ('import-ffcam', :description, '127.0.0.1', :date)",
                [
                    'description' => sprintf(
                        'INSERT: %d, UPDATE: %d, fichier %s',
                        $stats['inserted'],
                        $stats['updated'],
                        basename($filePath)
                    ),
                    'date' => time(),
                ]
            );
        } catch (\Exception $exc) {
            \Sentry\captureException($exc);
        }
    }
}
