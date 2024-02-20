<?php

namespace App\Command;

use App\Entity\ContentHtml;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

#[AsCommand(
    name: 'app:static-content-to-db',
    description: 'Importe le contenu des fichiers twig statiques dans la base de données (table caf_content_html)',
)]
class StaticContentToDbCommand extends Command
{

    private const MAX_BATCH_FILES = 20;

    public function __construct(
        private readonly Environment            $environment,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface        $logger,
        private readonly string                 $kernelProjectDir,

    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Mode test à blanc (ne sauvegarde pas en bdd)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($dryRun = $input->getOption('dry-run')) {
            $io->note('Commande lancée en mode debug');
        }

        $importedFiles = $notImportedFiles = 0;
        $filesystem = new Filesystem();

        $q = $this->entityManager->createQuery('select ch from App\Entity\ContentHtml ch where ch.current = 1');
        /** @var ContentHtml $contentHtml */
        foreach ($q->toIterable() as $contentHtml) {
            $template = sprintf('content_html/%s.html.twig', $contentHtml->getCode());

            if (!$filesystem->exists($this->kernelProjectDir.'/templates/'.$template)) {
                $io->error(sprintf('Template "%s" introuvable.', $template));
                continue;
            }

            try {
                $content = $this->environment->render($template);
                ++$importedFiles;
                if (!$dryRun) {
                    $contentHtml->setContenu($content);
                    $this->logger->info(sprintf('Importation en bdd du template "%s"', $template));
                    if (($importedFiles % self::MAX_BATCH_FILES) === 0) {
                        $this->entityManager->flush();
                        $this->entityManager->clear();
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->error(sprintf('Template "%s" introuvable.', $template), ['exception' => new \RuntimeException(sprintf('Template "%s" introuvable.', $template), $e->getCode(), $e)]);
                ++$notImportedFiles;
            }
        }
        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $io->success(sprintf('Fichiers statiques migrés en base de données (%d trouvés, %d non trouvés).', $importedFiles, $notImportedFiles));

        return Command::SUCCESS;
    }
}
