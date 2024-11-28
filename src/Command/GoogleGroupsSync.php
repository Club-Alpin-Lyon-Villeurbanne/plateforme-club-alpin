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
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
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

        foreach ($this->commissionRepository->findVisible() as $commission) {
            $output->writeln(sprintf('Processing commission <info>%s</info>', $commission->getCode()));
            $this->processCommission($commission);
            $output->writeln('Processing done');
            $output->writeln('');
        }

        return 0;
    }

    private function processCommission(Commission $commission)
    {
        $groupKey = $this->upsertCommissionGoogleGroup($commission);

        try {
            $existingMembers = $this->getCommissionGoogleGroupMembers($groupKey);
        } catch (\Google\Service\Exception $e) {
            if (!$this->dryRun) {
                throw $e;
            }
            $this->output->writeln("\t🚨 Unable to retrieve existing members of <info>$groupKey</info>");
            $existingMembers = [];
        }

        foreach ($this->userAttrRepository->listAllEncadrants($commission) as $commissionMember) {
            $type = UserAttr::RESPONSABLE_COMMISSION === $commissionMember->getUserType()?->getCode() ? 'MANAGER' : 'MEMBER';
            $email = $commissionMember->getUser()->getEmail();

            if (!isset($existingMembers[$email])) {
                $member = new Member();
                $member->setEmail($email);
                $member->setRole($type); // Possible roles: MEMBER, OWNER, MANAGER

                if (!$this->dryRun) {
                    $this->output->writeln("\t☑️ ️Inserting new Google Group Member <comment>$type</comment> <info>$email</info>");
                    $this->googleGroupsService->members->insert($groupKey, $member);
                } else {
                    $this->output->writeln("\t💨 Would have insert a new Google Group Member <comment>$type</comment> <info>$email</info>");
                }
            } else {
                $member = $this->googleGroupsService->members->get($groupKey, $email);

                $currentRole = $member->getRole();
                if ($currentRole !== $type) {
                    $oldType = $currentRole;
                    $member->setRole($type);

                    if (!$this->dryRun) {
                        $this->output->writeln("\t☑️ Updating Google Group Member from <comment>$oldType</comment> to <comment>$type</comment> <info>$email</info>");
                        $this->googleGroupsService->members->update($groupKey, $email, $member);
                    } else {
                        $this->output->writeln("\t💨 Would have update Google Group Member from <comment>$oldType</comment> to <comment>$type</comment> <info>$email</info>");
                    }
                } else {
                    $this->output->writeln("\t👌 Google Group Member <comment>$type</comment> <info>$email</info> OK");
                }
            }

            unset($existingMembers[$email]);
        }

        foreach ($existingMembers as $emailToRemove => $_) {
            if (!$this->dryRun) {
                $this->output->writeln("\t☑️ Removing Google Group Member <info>$emailToRemove</info>");
                $this->googleGroupsService->members->delete($groupKey, $emailToRemove);
            } else {
                $this->output->writeln("\t💨 Would have remove Google Group Member <info>$emailToRemove</info>");
            }
            unset($existingMembers[$emailToRemove]);
        }

        $this->upsertDriveAndAccesses($commission);
    }

    private function upsertDriveAndAccesses(Commission $commission)
    {
        if ($commission->getgoogleDriveServiceId()) {
            $drive = $this->googleDriveService->drives->get($commission->getgoogleDriveServiceId());
        } else {
            if (!$this->dryRun) {
                $this->output->writeln("\t☑️ Creating a Google Drive for <info>Commission " . $commission->getTitle() . '</info>');
                $drive = $this->googleDriveService->drives->create(uniqid('', true), new Drive\Drive([
                    'name' => sprintf('%s %s', self::PREFIX_GROUP_NAME, $commission->getTitle()),
                ]));

                $commission->setgoogleDriveServiceId($drive->getId());
                $this->em->flush();
            } else {
                $this->output->writeln("\t💨 Would have create a Google Drive for <info>Commission " . $commission->getTitle() . '</info>');
            }
        }

        $googleGroupEmail = $this->getGoogleGroupKey($commission);

        if (!$this->dryRun || $commission->getgoogleDriveServiceId()) {
            // Le google group de commission est grant "writer"
            if (!$this->hasCommissionAccessOnDrive($commission, $commission->getgoogleDriveServiceId())) {
                if (!$this->dryRun) {
                    $this->output->writeln("\t☑️ Granting access to GoogleDrive <info>Commission " . $commission->getTitle() . '</info> to <info>' . $googleGroupEmail . '</info>');
                    $this->googleDriveService->permissions->create(
                        $commission->getgoogleDriveServiceId(),
                        new Drive\Permission([
                            'type' => 'group',
                            'role' => 'writer', // organizer / fileOrganizer / writer / commenter / reader
                            'emailAddress' => $googleGroupEmail,
                        ]),
                        ['supportsAllDrives' => true]
                    );
                } else {
                    $this->output->writeln("\t💨 Would have grant access to GoogleDrive <info>Commission " . $commission->getTitle() . '</info> to <info>' . $googleGroupEmail . '</info>');
                }
            } else {
                $this->output->writeln("\t👌 googleDriveService conf for <info>Commission " . $commission->getTitle() . '</info> OK');
            }

            // Les responsables de commission sont grant "organizer"
            foreach ($this->userAttrRepository->listAllEncadrants($commission, [UserAttr::RESPONSABLE_COMMISSION]) as $responsable) {
                if (!$this->hasUserOrganizerAccessOnDrive($responsable->getUser(), $commission->getgoogleDriveServiceId())) {
                    if (!$this->dryRun) {
                        $this->output->writeln("\t☑️ Granting access to GoogleDrive <info>Commission " . $commission->getTitle() . '</info> as <info>Organizer</info> to <info>' . $responsable->getUser()->getEmail() . '</info>');
                        $this->googleDriveService->permissions->create(
                            $commission->getgoogleDriveServiceId(),
                            new Drive\Permission([
                                'type' => 'user',
                                'role' => 'organizer', // organizer / fileOrganizer / writer / commenter / reader
                                'emailAddress' => $responsable->getUser()->getEmail(),
                            ]),
                            ['supportsAllDrives' => true]
                        );
                    } else {
                        $this->output->writeln("\t💨 Would have grant access to GoogleDrive <info>Commission " . $commission->getTitle() . '</info> as <info>Organizer</info> to <info>' . $responsable->getUser()->getEmail() . '</info>');
                    }
                } else {
                    $this->output->writeln("\t👌 Access to GoogleDrive <info>Commission " . $commission->getTitle() . '</info> for <info>' . $responsable->getUser()->getEmail() . '</info> OK');
                }
            }
        } else {
            $this->output->writeln("\t💨 Would have grant access to GoogleDrive <info>Commission " . $commission->getTitle() . '</info> to <info>' . $googleGroupEmail . '</info>');
        }

        // On donne ici acces à des drive communs a tout le monde
        // l'acces est en "commenter"
        foreach ([
            '0ABmumnXKow7GUk9PVA' => 'Comité Directeur',
            '0APMNn5p0ZtzYUk9PVA' => 'Communication',
            '0AHy7L5mm5zkVUk9PVA' => 'Documents communs',
            '0AHv2wqi4rW54Uk9PVA' => 'Formation',
            '0AKrtpUzdq5WaUk9PVA' => 'Matériel',
        ] as $driveId => $driveName) {
            if (!$this->hasCommissionAccessOnDrive($commission, $driveId)) {
                if (!$this->dryRun) {
                    $this->output->writeln("\t☑️ Granting access to GoogleDrive <info>" . $driveName . '</info> to <info>' . $googleGroupEmail . '</info> as <info>commenter</info>');
                    $this->googleDriveService->permissions->create(
                        $driveId,
                        new Drive\Permission([
                            'type' => 'group',
                            'role' => 'commenter', // organizer / fileOrganizer / writer / commenter / reader
                            'emailAddress' => $googleGroupEmail,
                        ]),
                        ['supportsAllDrives' => true]
                    );
                } else {
                    $this->output->writeln("\t💨 Would have grant access to GoogleDrive <info>" . $driveName . '</info> to <info>' . $googleGroupEmail . '</info> as <info>commenter</info>');
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
                    if ($perm->emailAddress === $user->getEmail()) {
                        return true;
                    }
                }
            }
        } while ($nextPageToken = $list->getNextPageToken());

        return false;
    }

    private function getCommissionGoogleGroupMembers(string $groupEmail)
    {
        $members = array_map(static fn (Member $member) => $member->getEmail(), $this->googleGroupsService->members->listMembers($groupEmail)->getMembers());

        // return a map, more easy to process by the algo
        return array_combine($members, $members);
    }

    private function upsertCommissionGoogleGroup(Commission $commission): string
    {
        $existingGroups = $this->listgoogleGroupsService();
        $groupEmail = $this->getGoogleGroupKey($commission);

        if (!\in_array($groupEmail, $existingGroups, true)) {
            $newGroup = new Group();
            $newGroup->setEmail($groupEmail);
            $newGroup->setName(sprintf('%s %s', self::PREFIX_GROUP_NAME, $commission->getTitle()));

            if (!$this->dryRun) {
                $this->output->writeln("\t☑️ ️Inserting new Google Group <info>$groupEmail</info>");
                $this->googleGroupsService->groups->insert($newGroup);
            } else {
                $this->output->writeln("\t💨 Would have insert a new Google Group <info>$groupEmail</info>");
            }
        } else {
            $group = $this->googleGroupsService->groups->get($groupEmail);
            $name = sprintf('%s %s', self::PREFIX_GROUP_NAME, $commission->getTitle());

            if ($group->getName() !== $name) {
                $oldName = $group->getName();
                if (!$this->dryRun) {
                    $this->output->writeln("\t☑️ ️Updating Google Group name <info>$groupEmail</info> from <info>$oldName</info> to <info>$name</info>");
                    $group->setName($name);
                    $this->googleGroupsService->groups->update($groupEmail, $group);
                } else {
                    $this->output->writeln("\t💨 Would have updated Google Group name <info>$groupEmail</info> from <info>$oldName</info> to <info>$name</info>");
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
            'randonnee' => 'commission_rando' . self::DOMAIN_EXT,
            'raquette' => 'commission_raquettes' . self::DOMAIN_EXT,
            'snowboard-alpin' => 'commission_snowboard' . self::DOMAIN_EXT,
            'ski-de-fond' => 'commission_skidefond' . self::DOMAIN_EXT,
            'vtt' => 'commission-vdm' . self::DOMAIN_EXT,
            'snowboard-rando' => 'commission_snowrando' . self::DOMAIN_EXT,
            default => self::PREFIX_GROUP_EMAIL . strtolower($this->slugger->slug($commission->getTitle())) . self::DOMAIN_EXT,
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
}
