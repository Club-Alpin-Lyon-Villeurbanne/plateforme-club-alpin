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
        private readonly UserLicenseHelper $userLicenseHelper,
        private readonly ?FfcamSyncReportMailer $syncReportMailer = null,
    ) {
        $today = new \DateTime();
        $endDate = new \DateTime($today->format('Y') . '-' . UserLicenseHelper::LICENSE_TOLERANCY_PERIOD_END);

        $this->hasTolerancyPeriodPassed = $today > $endDate;
    }

    public function synchronize(?string $ffcamFilePath = null): void
    {
        $startTime = new \DateTime();
        $licenseExpirationDate = $this->userLicenseHelper->getLicenseExpirationTimestamp();

        if (!$this->isFileValid($ffcamFilePath)) {
            $this->logger->warning("File {$ffcamFilePath} not found. Can't import new members");
            $blockedCount = $this->userRepository->blockExpiredAccounts($licenseExpirationDate);
            $filiationsRemoved = $this->userRepository->removeExpiredFiliations();

            return;
        }

        $stats = $this->processMembers($this->fileParser->parse($ffcamFilePath));

        $this->archiveFile($ffcamFilePath, $stats);
        $this->logResults($ffcamFilePath, $stats);

        $blockedCount = $this->userRepository->blockExpiredAccounts($licenseExpirationDate);
        $filiationsRemoved = $this->userRepository->removeExpiredFiliations();

        $stats['blocked'] = $blockedCount;
        $stats['filiations_removed'] = $filiationsRemoved;

        $endTime = new \DateTime();

        // Envoyer le mail de récapitulatif si le service est disponible
        if ($this->syncReportMailer) {
            $this->syncReportMailer->sendSyncReport($stats, $startTime, $endTime);
        }
    }

    private function isFileValid(string $filePath): bool
    {
        return file_exists($filePath) && is_file($filePath);
    }

    private function processMembers(\Generator $members): array
    {
        $stats = ['inserted' => 0, 'updated' => 0, 'merged' => 0, 'errors' => 0, 'warnings' => 0, 'error_details' => [], 'merged_details' => [], 'warning_details' => []];

        /** @var User $parsedUser */
        foreach ($members as $parsedUser) {
            try {
                $this->logger->info("Processing CAF member {$parsedUser->getCafnum()}");

                $existingUser = $this->userRepository->findOneByLicenseNumber($parsedUser->getCafnum());

                $potentialDuplicate = $this->userRepository->findDuplicateUser(
                    $parsedUser->getLastname(),
                    $parsedUser->getFirstname(),
                    $parsedUser->getBirthdate(),
                    $parsedUser->getCafnum(),
                    $parsedUser->getEmail() ?: null
                );

                // merge user
                if ($potentialDuplicate) {
                    $oldCafNum = $potentialDuplicate->getCafnum();
                    $this->logger->info(sprintf(
                        'Found duplicate member %s %s (old license: %s, new license: %s)',
                        $parsedUser->getLastname(),
                        $parsedUser->getFirstname(),
                        $oldCafNum,
                        $parsedUser->getCafnum()
                    ));

                    $this->memberMerger->mergeNewMember($oldCafNum, $parsedUser);
                    ++$stats['merged'];

                    // Stocker les détails de la fusion (tous pour debug)
                    $stats['merged_details'][] = [
                        'old_cafnum' => $oldCafNum,
                        'new_cafnum' => $parsedUser->getCafnum(),
                        'name' => sprintf('%s %s', $parsedUser->getFirstname(), $parsedUser->getLastname())
                    ];
                } elseif ($existingUser instanceof User) {
                    // update user

                    // vérif email
                    if (!empty($parsedUser->getEmail())) {
                        $duplicateEmailUser = $this->userRepository->findDuplicateEmailUser(
                            $parsedUser->getEmail(),
                            $parsedUser->getCafnum()
                        );
                        if ($duplicateEmailUser instanceof User) {
                            $errorMessage = sprintf('Email %s is already used by another member (Cafnum: %s)', $parsedUser->getEmail(), $duplicateEmailUser->getCafnum());
                            $this->logger->warning($errorMessage);
                            ++$stats['errors'];
                            $stats['error_details'][] = [
                                'cafnum' => $parsedUser->getCafnum(),
                                'message' => $errorMessage,
                            ];

                            // email unique même si faux
                            $parsedUser->setEmail('doublon.' . $parsedUser->getCafnum() . '-' . $parsedUser->getEmail());
                        }
                    } else {
                        $warningMessage = sprintf('FFCAM email is empty for member Cafnum %s', $existingUser->getCafnum());
                        $this->logger->warning($warningMessage);
                        ++$stats['warnings'];
                        $stats['warning_details'][] = $warningMessage;
                    }

                    if (
                        $parsedUser->getEmail() !== $existingUser->getEmail()
                        && empty($existingUser->getRadiationDate())
                    ) {
                        $warningMessage = sprintf('FFCAM email (%s) is different from database (%s) for member Cafnum %s', $parsedUser->getEmail(), $existingUser->getEmail(), $existingUser->getCafnum());
                        $this->logger->warning($warningMessage);
                        ++$stats['warnings'];
                        $stats['warning_details'][] = $warningMessage;
                    }

                    $this->updateExistingUser($existingUser, $parsedUser);
                    $this->entityManager->persist($existingUser);
                    ++$stats['updated'];
                } else {
                    // new user
                    $parsedUser->setCreatedAt(new \DateTime());
                    $parsedUser->setValid(false);
                    $this->entityManager->persist($parsedUser);
                    ++$stats['inserted'];
                }

                try {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                } catch (\Exception $exception) {
                    $this->logger->error('Flush error: ' . $exception->getMessage());
                    ++$stats['errors'];

                    // Clear l'entity manager pour continuer le traitement
                    $this->entityManager->clear();
                }
            } catch (\Exception $exception) {
                $cafnum = $parsedUser->getCafnum() ?? 'inconnu';
                $errorMessage = $exception->getMessage();

                $this->logger->error(sprintf(
                    'Error processing member Cafnum %s: %s',
                    $cafnum,
                    $errorMessage
                ));

                ++$stats['errors'];

                // Stocker les détails de l'erreur (tous pour debug)
                $stats['error_details'][] = [
                    'cafnum' => $cafnum,
                    'message' => $errorMessage,
                ];

                // Continue avec le prochain membre
                continue;
            }
        }

        return $stats;
    }

    private function updateExistingUser(User $existingUser, User $parsedUser): void
    {
        $existingUser
            ->setFirstname($parsedUser->getFirstname())
            ->setLastname($parsedUser->getLastname())
            ->setBirthdate($parsedUser->getBirthdate())
            ->setCiv($parsedUser->getCiv())
            ->setCafnumParent($parsedUser->getCafnumParent())
            ->setTel($parsedUser->getTel())
            ->setTel2($parsedUser->getTel2())
            ->setEmail($parsedUser->getEmail())
            ->setAdresse($parsedUser->getAdresse())
            ->setCp($parsedUser->getCp())
            ->setVille($parsedUser->getVille())
            ->setNickname($parsedUser->getNickname())
            ->setDoitRenouveler($parsedUser->getDoitRenouveler() && $this->hasTolerancyPeriodPassed)
            ->setAlerteRenouveler($parsedUser->getAlerteRenouveler() && !$this->hasTolerancyPeriodPassed)
            ->setUpdatedAt(new \DateTime())
            ->setManuel(false)
            ->setNomade(false)
            ->setRadiationDate($parsedUser->getRadiationDate())
            ->setRadiationReason($parsedUser->getRadiationReason())
        ;

        // Si l'utilisateur est radié
        if (null !== $parsedUser->getRadiationDate()) {
            $existingUser
                ->setDoitRenouveler(true)
            ;
        }

        // Ne pas effacer la date d'adhésion quand l'adhésion parsée est expirée (valeur nulle).
        // Conserver la date d'adhésion existante sauf si une nouvelle adhésion valide est fournie.
        if (null !== $parsedUser->getJoinDate()) {
            $existingUser->setJoinDate($parsedUser->getJoinDate());
        }
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
            'Members synchronization finished. New members : %d, Updated members : %d, Merged members : %d',
            $stats['inserted'],
            $stats['updated'],
            $stats['merged'] ?? 0
        ));

        try {
            $this->entityManager->getConnection()->executeQuery(
                "INSERT INTO `caf_log_admin` (`code_log_admin`, `desc_log_admin`, `ip_log_admin`, `date_log_admin`)
                VALUES ('import-ffcam', :description, '127.0.0.1', :date)",
                [
                    'description' => sprintf(
                        'INSERT: %d, UPDATE: %d, MERGE: %d, fichier %s',
                        $stats['inserted'],
                        $stats['updated'],
                        $stats['merged'] ?? 0,
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
