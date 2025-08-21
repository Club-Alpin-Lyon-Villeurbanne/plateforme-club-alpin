<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Service\MailerLiteService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:mailerlite:sync',
    description: 'Synchronise les nouveaux adhérents avec MailerLite pour l\'envoi de mails de bienvenue',
)]
class MailerLiteSyncCommand extends Command
{
    public function __construct(
        private readonly MailerLiteService $mailerLiteService,
        private readonly UserRepository $userRepository,
        private readonly string $mailerLiteEnabled
    ) {
        parent::__construct();
    }
    
    protected function configure(): void
    {
        $this
            ->addOption('days', 'd', InputOption::VALUE_REQUIRED, 'Nombre de jours en arrière pour chercher les nouveaux membres', 7)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Mode simulation, n\'envoie pas vraiment à MailerLite')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force la synchronisation même si MailerLite est désactivé')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        if ($this->mailerLiteEnabled !== 'true' && !$input->getOption('force')) {
            $io->warning('MailerLite est désactivé. Utilisez --force pour forcer la synchronisation.');
            return Command::SUCCESS;
        }
        
        $days = (int) $input->getOption('days');
        $dryRun = $input->getOption('dry-run');
        
        $io->title('Synchronisation des nouveaux adhérents avec MailerLite');
        
        // Récupérer les nouveaux membres des X derniers jours
        $since = new \DateTime();
        $since->modify("-{$days} days");
        $sinceTimestamp = $since->getTimestamp();
        
        $io->info(sprintf('Recherche des nouveaux membres depuis le %s', $since->format('Y-m-d H:i:s')));
        
        $newMembers = $this->userRepository->createQueryBuilder('u')
            ->where('u.tsInsert >= :since')
            ->andWhere('u.manuel = false')
            ->andWhere('u.nomade = false')
            ->andWhere('u.email IS NOT NULL')
            ->andWhere('u.email != :empty')
            ->setParameter('since', $sinceTimestamp)
            ->setParameter('empty', '')
            ->orderBy('u.tsInsert', 'DESC')
            ->getQuery()
            ->getResult();
        
        $count = count($newMembers);
        
        if ($count === 0) {
            $io->success('Aucun nouveau membre avec email trouvé.');
            return Command::SUCCESS;
        }
        
        $io->info(sprintf('%d nouveau(x) membre(s) avec email trouvé(s)', $count));
        
        if ($dryRun) {
            $io->note('Mode simulation activé - aucune donnée ne sera envoyée à MailerLite');
            
            $rows = [];
            foreach ($newMembers as $user) {
                $rows[] = [
                    $user->getId(),
                    $user->getCafnum(),
                    $user->getFirstname() . ' ' . $user->getLastname(),
                    $user->getEmail(),
                    date('Y-m-d H:i:s', $user->getTsInsert()),
                ];
            }
            
            $io->table(
                ['ID', 'N° CAF', 'Nom', 'Email', 'Date inscription'],
                $rows
            );
            
            return Command::SUCCESS;
        }
        
        // Synchroniser avec MailerLite en utilisant l'import en masse
        $io->section('Synchronisation avec MailerLite');
        $io->text('Envoi des données à MailerLite...');
        
        $results = $this->mailerLiteService->syncNewMembers($newMembers);
        
        // Afficher les résultats
        $io->section('Résultats');
        $io->table(
            ['Statut', 'Nombre'],
            [
                ['Importés', $results['imported']],
                ['Mis à jour', $results['updated']],
                ['Échecs', $results['failed']],
                ['Sans email', $results['skipped']],
                ['Total', $results['total']],
            ]
        );
        
        $successCount = $results['imported'] + $results['updated'];
        
        if ($successCount > 0) {
            $io->success(sprintf('%d membre(s) synchronisé(s) avec succès (%d importés, %d mis à jour) !', 
                $successCount, $results['imported'], $results['updated']));
        }
        
        if ($results['failed'] > 0) {
            $io->warning(sprintf('%d membre(s) n\'ont pas pu être synchronisé(s)', $results['failed']));
        }
        
        if ($results['skipped'] > 0) {
            $io->note(sprintf('%d membre(s) ignoré(s) car sans adresse email', $results['skipped']));
        }
        
        return Command::SUCCESS;
    }
}