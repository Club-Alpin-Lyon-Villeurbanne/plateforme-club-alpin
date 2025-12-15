<?php

namespace App\Command;

use App\Entity\Commission;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Repository\CommissionRepository;
use App\Repository\UserAttrRepository;
use Doctrine\ORM\EntityManagerInterface;
use Google\Client;
use Google\Service\Directory;
use Google\Service\Directory\Group;
use Google\Service\Directory\Member;
use Google\Service\Drive;
use Google\Service\Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsCommand(name: 'google-groups-sync')]
#[Autoconfigure]
class GoogleGroupsSync extends Command
{
    private const PREFIX_GROUP_NAME = 'Commission';
    private const PREFIX_GROUP_EMAIL = 'commission-';
    private const DOMAIN_EXT = '@clubalpinlyon.fr';
    private const ALL_COMMISSIONS_ADRESSE = 'toutes-les-commissions' . self::DOMAIN_EXT;
    private const ALL_RESPONSABLES = 'responsables-de-commission' . self::DOMAIN_EXT;

    private Directory $googleGroupsService;
    private Drive $googleDriveService;
    private Client $client;
    private AsciiSlugger $slugger;
    private bool $dryRun = true;
    private ?OutputInterface $output;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CommissionRepository $commissionRepository,
        private readonly UserAttrRepository $userAttrRepository,
        #[Autowire('%env(json:GOOGLE_AUTH_CONFIG)%')] private readonly ?array $googleAuthConfig,
        ?string $name = null,
    ) {
        $this->slugger = new AsciiSlugger();
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addOption('execute', null, InputOption::VALUE_NONE, 'Execute les changement. Dry-run par défaut.')
            ->addOption('commission', null, InputOption::VALUE_OPTIONAL, 'Juste sur une commission.')
        ;
    }

    /**
     * @throws Exception
     * @throws \Google\Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        if (!$this->googleAuthConfig) {
            $output->writeln('<error>Missing configuration. Please configure GOOGLE_AUTH_CONFIG env var.</error>');

            return 1;
        }

        if ($input->getOption('execute')) {
            $this->dryRun = false;
        }

        $this->client = new Client();
        $this->client->setAuthConfig($this->googleAuthConfig);

        if ($this->dryRun) {
            $this->client->addScope(Directory::ADMIN_DIRECTORY_GROUP_READONLY);
            $this->client->addScope(Directory::ADMIN_DIRECTORY_GROUP_MEMBER_READONLY);
            $this->client->addScope(Drive::DRIVE_READONLY);
        } else {
            $this->client->addScope(Directory::ADMIN_DIRECTORY_GROUP);
            $this->client->addScope(Directory::ADMIN_DIRECTORY_GROUP_MEMBER);
            $this->client->addScope(Drive::DRIVE);
        }

        $this->client->setSubject('admin' . self::DOMAIN_EXT);

        $this->googleGroupsService = new Directory($this->client);
        $this->googleDriveService = new Drive($this->client);

        $onlyCommission = $input->getOption('commission');
        $foundCommission = false;

        foreach ($this->commissionRepository->findVisible() as $commission) {
            if ($onlyCommission && $commission->getCode() !== $onlyCommission) {
                continue;
            }
            $foundCommission = true;

            $output->writeln(sprintf('Processing commission <info>%s</info>', $commission->getCode()));
            $this->processCommission($commission);
            $output->writeln('Processing done');
            $output->writeln('');
        }

        $this->addResponsablesCommissionGroup();

        if ($onlyCommission && !$foundCommission) {
            $output->writeln(sprintf('<error>Commission "%s" non trouvée</error>', $onlyCommission));

            return 1;
        }

        return 0;
    }

    /**
     * @throws Exception
     */
    private function addResponsablesCommissionGroup(): void
    {
        $groupKey = $this->upsertGoogleGroup(self::ALL_RESPONSABLES, 'Responsables de Commissions');

        try {
            $existingMembers = $this->getCommissionGoogleGroupMembers($groupKey);
        } catch (Exception $e) {
            if (!$this->dryRun) {
                throw $e;
            }
            $this->output->writeln("\t🚨 Unable to retrieve existing members of <info>$groupKey</info>");
            $existingMembers = [];
        }

        foreach ($this->userAttrRepository->listAllResponsables() as $commissionMember) {
            $type = 'MEMBER';
            $email = $this->getUserEmail($commissionMember->getUser());

            if (!$email) {
                $user = $commissionMember->getUser();
                $this->output->writeln("\t🚨 No email found for <info>" . $user->getFirstname() . ' ' . $user->getLastname() . '</info>');
                continue;
            }

            $this->upsertMemberToGoogleGroup($existingMembers, $groupKey, $email, $type);

            unset($existingMembers[$email]);
        }

        $this->upsertMemberToGoogleGroup($existingMembers, $groupKey, 'publics-eloignes@clubalpinlyon.fr', 'MEMBER');
        $this->upsertMemberToGoogleGroup($existingMembers, $groupKey, 'escalade@clubalpinlyon.fr', 'MEMBER');
        unset($existingMembers['publics-eloignes@clubalpinlyon.fr'], $existingMembers['escalade@clubalpinlyon.fr']);

        foreach ($existingMembers as $emailToRemove => $_) {
            if (!$this->dryRun) {
                $this->output->writeln("\t☑️ Removing Google Group Member <info>$emailToRemove</info>");
                $this->googleGroupsService->members->delete($groupKey, $emailToRemove);
            } else {
                $this->output->writeln("\t💨 Desinscription du membre du Google Group <info>$emailToRemove</info>");
            }
            unset($existingMembers[$emailToRemove]);
        }
    }

    /**
     * @throws Exception
     */
    private function processCommission(Commission $commission): void
    {
        if (\in_array($commission->getCode(), [
            'vie-du-club',
            'formation',
            'jeunes',
        ], true)) {
            $this->output->writeln("\t🚨 Skipping <info>" . $commission->getCode() . '</info>');

            return;
        }

        $groupKey = $this->upsertCommissionGoogleGroup($commission);

        try {
            $existingMembers = $this->getCommissionGoogleGroupMembers($groupKey);
        } catch (Exception $e) {
            if (!$this->dryRun) {
                throw $e;
            }
            $this->output->writeln("\t🚨 Unable to retrieve existing members of <info>$groupKey</info>");
            $existingMembers = [];
        }

        foreach ($this->userAttrRepository->listAllEncadrants($commission) as $commissionMember) {
            $type = 'MEMBER';
            $email = $this->getUserEmail($commissionMember->getUser());

            if (!$email) {
                $user = $commissionMember->getUser();
                $this->output->writeln("\t🚨 No email found for <info>" . $user->getFirstname() . ' ' . $user->getLastname() . '</info>');
                continue;
            }

            $this->upsertMemberToGoogleGroup($existingMembers, $groupKey, $email, $type);

            unset($existingMembers[$email]);
        }

        // Hack parce que cette commission fonctionne pas comme les autres
        if ('escalade' === $commission->getCode()) {
            $this->upsertMemberToGoogleGroup($existingMembers, $groupKey, 'escalade-jeunes@clubalpinlyon.fr', 'MEMBER');
            $this->upsertMemberToGoogleGroup($existingMembers, $groupKey, 'escalade@clubalpinlyon.fr', 'MEMBER');
            unset($existingMembers['escalade-jeunes@clubalpinlyon.fr'], $existingMembers['escalade@clubalpinlyon.fr']);
        }

        foreach ($existingMembers as $emailToRemove => $_) {
            if (!$this->dryRun) {
                $this->output->writeln("\t☑️ Removing Google Group Member <info>$emailToRemove</info>");
                $this->googleGroupsService->members->delete($groupKey, $emailToRemove);
            } else {
                $this->output->writeln("\t💨 Desinscription du membre du Google Group <info>$emailToRemove</info>");
            }
            unset($existingMembers[$emailToRemove]);
        }

        $this->upsertDriveAndAccesses($commission);

        $this->upsertGoogleGroup(self::ALL_COMMISSIONS_ADRESSE, 'Toutes les commissions');

        try {
            $existingCommissionsAll = $this->getCommissionGoogleGroupMembers(self::ALL_COMMISSIONS_ADRESSE);
        } catch (Exception $e) {
            if (!$this->dryRun) {
                throw $e;
            }
            $this->output->writeln("\t🚨 Unable to retrieve existing members of <info>" . self::ALL_COMMISSIONS_ADRESSE . '</info>');
            $existingCommissionsAll = [];
        }

        $this->upsertMemberToGoogleGroup($existingCommissionsAll, self::ALL_COMMISSIONS_ADRESSE, $groupKey);
    }

    private function normalizeEmail(string $email): string
    {
        $email = mb_strtolower(trim($email));

        if (!str_ends_with($email, '@gmail.com') && !str_ends_with($email, '@googlemail.com')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);

        // remove dots
        $local = str_replace('.', '', $local);

        // remove +tag
        $local = preg_replace('/\+.*/', '', $local);

        return $local . '@' . $domain;
    }

    private function upsertMemberToGoogleGroup(array $existingMembers, string $groupKey, string $email, string $type = 'MEMBER'): void
    {
        if (!isset($existingMembers[$email])) {
            $member = new Member();
            $member->setEmail($email);
            $member->setRole($type); // Possible roles: MEMBER, OWNER, MANAGER

            if (!$this->dryRun) {
                $this->output->writeln("\t☑️ ️Inserting new Google Group Member <comment>$type</comment> <info>$email</info> to <info>$groupKey</info>");
                $this->googleGroupsService->members->insert($groupKey, $member);
            } else {
                $this->output->writeln("\t💨 Ajout d'un membre au Google Group <comment>$type</comment> <info>$email</info> à <info>$groupKey</info>");
            }
        } else {
            try {
                $member = $this->googleGroupsService->members->get($groupKey, $email);
            } catch (Exception $e) {
                $this->output->writeln("\t🚨 No Google Account found for email <info>$email</info>, impossible de verifier le role ; utilisateur.ice avec acces OK, role a verifier");

                return;
            }

            $currentRole = $member->getRole();
            if ($currentRole !== $type) {
                $oldType = $currentRole;
                $member->setRole($type);

                if (!$this->dryRun) {
                    $this->output->writeln("\t☑️ Updating Google Group Member from <comment>$oldType</comment> to <comment>$type</comment> <info>$email</info>");
                    $this->googleGroupsService->members->update($groupKey, $email, $member);
                } else {
                    $this->output->writeln("\t💨 Mise a jour des access de <comment>$oldType</comment> a <comment>$type</comment> <info>$email</info>");
                }
            } else {
                $this->output->writeln("\t👌 Google Group Member <comment>$type</comment> <info>$email</info> OK");
            }
        }
    }

    private function upsertDriveAndAccesses(Commission $commission): void
    {
        $this->output->writeln('');
        $this->output->writeln("\tCheck du Google drive...");

        if ($commission->getGoogleDriveId()) {
            $drive = $this->googleDriveService->drives->get($commission->getGoogleDriveId());
            $this->output->writeln("\t👌 Google Drive OK <info>" . $drive->getName() . '</info> ' . $drive->getId());
        } else {
            if (!$this->dryRun) {
                $this->output->writeln("\t☑️ Creating a Google Drive for <info>Commission " . $commission->getTitle() . '</info>');
                $drive = $this->googleDriveService->drives->create(uniqid('', true), new Drive\Drive([
                    'name' => sprintf('%s %s', self::PREFIX_GROUP_NAME, $commission->getTitle()),
                ]));

                $commission->setGoogleDriveId($drive->getId());
                $this->em->flush();
            } else {
                $this->output->writeln("\t💨 Would have create a Google Drive for <info>Commission " . $commission->getTitle() . '</info>');
            }
        }

        $googleGroupEmail = $this->getGoogleGroupKey($commission);

        if (!$this->dryRun || $commission->getGoogleDriveId()) {
            // Le google group de commission est grant "writer"
            if (!$this->hasCommissionAccessOnDrive($commission, $commission->getGoogleDriveId())) {
                if (!$this->dryRun) {
                    $this->output->writeln("\t☑️ Granting access to GoogleDrive <info>Commission " . $commission->getTitle() . '</info> to <info>' . $googleGroupEmail . '</info>');
                    try {
                        $this->googleDriveService->permissions->create(
                            $commission->getGoogleDriveId(),
                            new Drive\Permission([
                                'type' => 'group',
                                'role' => 'writer', // organizer / fileOrganizer / writer / commenter / reader
                                'emailAddress' => $googleGroupEmail,
                            ]),
                            ['supportsAllDrives' => true, 'sendNotificationEmail' => false]
                        );
                    } catch (Exception $e) {
                        $this->output->writeln("\t🚨 Erreur en ajoutant l'acces");
                    }
                } else {
                    $this->output->writeln("\t💨 Ajout de l'acces au GoogleDrive <info>Commission " . $commission->getTitle() . '</info> au Groupe <info>' . $googleGroupEmail . '</info>');
                }
            } else {
                $this->output->writeln("\t👌 googleDriveService conf for <info>Commission " . $commission->getTitle() . '</info> OK');
            }

            // Les responsables de commission sont grant "fileOrganizer"
            foreach ($this->userAttrRepository->listAllEncadrants($commission, [UserAttr::RESPONSABLE_COMMISSION]) as $responsable) {
                $responsableEmail = $this->getUserEmail($responsable->getUser());
                if (!$this->hasUserOrganizerAccessOnDrive($responsable->getUser(), $commission->getGoogleDriveId())) {
                    if (!$this->dryRun) {
                        $this->output->writeln("\t☑️ Granting access to GoogleDrive <info>Commission " . $commission->getTitle() . '</info> as <info>fileOrganizer</info> to <info>' . $responsableEmail . '</info>');
                        try {
                            $this->googleDriveService->permissions->create(
                                $commission->getGoogleDriveId(),
                                new Drive\Permission([
                                    'type' => 'user',
                                    'role' => 'fileOrganizer', // organizer / fileOrganizer / writer / commenter / reader
                                    'emailAddress' => $responsableEmail,
                                ]),
                                ['supportsAllDrives' => true, 'sendNotificationEmail' => false]
                            );
                        } catch (Exception $e) {
                            $this->output->writeln("\t🚨 Erreur en ajoutant l'acces à <info>" . $responsableEmail . "</info>. L'email n'est peut etre pas associee a un compte Google</info>");
                        }
                    } else {
                        $this->output->writeln("\t💨 Ajout de l'acces au GoogleDrive <info>Commission " . $commission->getTitle() . '</info> à <info>' . $responsableEmail . '</info> en tant que <info>fileOrganizer</info>');
                    }
                } else {
                    $this->output->writeln("\t👌 Access to GoogleDrive <info>Commission " . $commission->getTitle() . '</info> for <info>' . $responsableEmail . '</info> OK');
                }
            }
        } else {
            $this->output->writeln("\t💨 Ajout de l'acces au GoogleDrive <info>Commission " . $commission->getTitle() . '</info> à <info>' . $googleGroupEmail . '</info>');
        }

        // On donne ici acces à des drive communs a tout le monde
        // l'acces est en "commenter"
        foreach ([
            '0ABmumnXKow7GUk9PVA' => 'Comité Directeur',
            '0APMNn5p0ZtzYUk9PVA' => 'Communication',
            '0AHy7L5mm5zkVUk9PVA' => 'Documents communs',
            '0AHv2wqi4rW54Uk9PVA' => 'Formation',
        ] as $driveId => $driveName) {
            if (!$this->hasCommissionAccessOnDrive($commission, $driveId)) {
                if (!$this->dryRun) {
                    $this->output->writeln("\t☑️ Granting access to GoogleDrive <info>" . $driveName . '</info> to <info>' . $googleGroupEmail . '</info> as <info>commenter</info>');
                    try {
                        $this->googleDriveService->permissions->create(
                            $driveId,
                            new Drive\Permission([
                                'type' => 'group',
                                'role' => 'commenter', // organizer / fileOrganizer / writer / commenter / reader
                                'emailAddress' => $googleGroupEmail,
                            ]),
                            ['supportsAllDrives' => true, 'sendNotificationEmail' => false]
                        );
                    } catch (Exception $e) {
                        $this->output->writeln("\t🚨 Erreur en ajoutant l'acces");
                    }
                } else {
                    $this->output->writeln("\t💨 Ajout de l'acces au GoogleDrive <info>" . $driveName . '</info> à <info>' . $googleGroupEmail . '</info> en tant que <info>commenter</info>');
                }
            } else {
                $this->output->writeln("\t👌 Access to GoogleDrive <info>" . $driveName . '</info> to <info>' . $googleGroupEmail . '</info> as <info>commenter</info> OK');
            }
        }
    }

    private function hasCommissionAccessOnDrive(Commission $commission, string $driveId): bool
    {
        $commissionGroup = $this->getGoogleGroupKey($commission);
        $nextPageToken = null;

        do {
            $list = $this->googleDriveService->permissions->listPermissions($driveId, [
                'supportsAllDrives' => true,
                'pageToken' => $nextPageToken,
                'pageSize' => 10,
                'fields' => 'nextPageToken, permissions(emailAddress, id, type, role, domain)',
            ]);
            foreach ($list->getPermissions() as $perm) {
                /* @var \Google\Service\Drive\Permission $perm */
                if ('group' === $perm->getType()) {
                    if ($perm->emailAddress === $commissionGroup) {
                        return true;
                    }
                }
            }
        } while ($nextPageToken = $list->getNextPageToken());

        return false;
    }

    private function hasUserOrganizerAccessOnDrive(User $user, string $driveId): bool
    {
        $nextPageToken = null;

        do {
            $list = $this->googleDriveService->permissions->listPermissions($driveId, [
                'supportsAllDrives' => true,
                'pageToken' => $nextPageToken,
                'pageSize' => 10,
                'fields' => 'nextPageToken, permissions(emailAddress, id, type, role, domain)',
            ]);
            foreach ($list->getPermissions() as $perm) {
                /* @var \Google\Service\Drive\Permission $perm */
                if ('user' === $perm->getType()) {
                    if (mb_strtolower($perm->emailAddress) === $this->getUserEmail($user)) {
                        return true;
                    }
                }
            }
        } while ($nextPageToken = $list->getNextPageToken());

        return false;
    }

    private function getCommissionGoogleGroupMembers(string $groupEmail): array
    {
        $members = array_map(fn (Member $member) => $this->normalizeEmail($member->getEmail() ?? ''), $this->googleGroupsService->members->listMembers($groupEmail)->getMembers());

        // return a map, more easy to process by the algo
        return array_combine($members, $members);
    }

    private function upsertCommissionGoogleGroup(Commission $commission): string
    {
        $name = sprintf('%s %s', self::PREFIX_GROUP_NAME, $commission->getTitle());
        $groupEmail = $this->getGoogleGroupKey($commission);

        return $this->upsertGoogleGroup($groupEmail, $name);
    }

    private function upsertGoogleGroup(string $groupEmail, string $name): string
    {
        $existingGroups = $this->listgoogleGroupsService();

        if (!\in_array($groupEmail, $existingGroups, true)) {
            $newGroup = new Group();
            $newGroup->setEmail($groupEmail);
            $newGroup->setName($name);

            if (!$this->dryRun) {
                $this->output->writeln("\t☑️ ️Inserting new Google Group <info>$groupEmail</info>");
                $this->googleGroupsService->groups->insert($newGroup);
            } else {
                $this->output->writeln("\t💨 Creation du Google Group <info>$groupEmail</info>");
            }
        } else {
            $group = $this->googleGroupsService->groups->get($groupEmail);

            if ($group->getName() !== $name) {
                $oldName = $group->getName();
                if (!$this->dryRun) {
                    $this->output->writeln("\t☑️ ️Updating Google Group name <info>$groupEmail</info> from <info>$oldName</info> to <info>$name</info>");
                    $group->setName($name);
                    $this->googleGroupsService->groups->update($groupEmail, $group);
                } else {
                    $this->output->writeln("\t💨 Mise a jour du nom du Google Group <info>$groupEmail</info> de <info>$oldName</info> vers <info>$name</info>");
                }
            } else {
                $this->output->writeln("\t👌 Google Group OK <info>$groupEmail</info>");
            }
        }

        return $groupEmail;
    }

    private function getGoogleGroupKey(Commission $commission): string
    {
        return match ($commission->getCode()) {
            'environnement' => 'commission-environnement-durable' . self::DOMAIN_EXT,
            'vtt' => 'commission-vdm' . self::DOMAIN_EXT,
            default => self::PREFIX_GROUP_EMAIL . strtolower($this->slugger->slug($commission->getCode())) . self::DOMAIN_EXT,
        };
    }

    private function listgoogleGroupsService(): array
    {
        return array_map(
            static fn (Group $g) => $g->email,
            // on query sur l'ensemble l'ensemble de l'organisation associée au compte Google Workspace via 'customer' => 'my_customer'
            $this->googleGroupsService->groups->listGroups(['customer' => 'my_customer'])->getGroups()
        );
    }

    private function getUserEmail(User $user): string
    {
        $email = '';
        if (!empty($user->getEmail())) {
            $email = $user->getEmail();
        }
        if (!empty($user->getGdriveEmail())) {
            $email = $user->getGdriveEmail();
        }

        return $this->normalizeEmail($email);
    }
}
