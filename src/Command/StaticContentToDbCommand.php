<?php

namespace App\Command;

use App\Entity\ContentHtml;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
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
            ->addArgument(
                'exclusions',
                InputArgument::IS_ARRAY,
                'Noms des fichiers à exclure (séparés par des espaces, sans extension, ex: adresse-fiche-sortie)'
            )
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Mode test à blanc (ne sauvegarde pas en bdd)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repository = $this->entityManager->getRepository(ContentHtml::class);
        $io = new SymfonyStyle($input, $output);

        if ($dryRun = $input->getOption('dry-run')) {
            $io->note('Commande lancée en mode test à blanc (pas de sauvegarde en bdd)');
        }

        $exclusions = array_map(function ($filename) {
            return $filename . '.html.twig';
        }, $input->getArgument('exclusions'));
        $excluded = count($exclusions);

        // rechercher tous les fichiers .html.twig du dossier templates/content_html
        $finder = new Finder();
        $finder
            ->files()
            ->in($this->kernelProjectDir.'/templates/content_html/')
            ->name('*.html.twig')
            ->notName($exclusions)
            ->sortByName(true)
        ;

        $imported = $updated = $created = 0;

        foreach ($finder as $file) {
            // rendu html du contenu twig
            try {
                $content = $this->environment->render('content_html/'.$file->getFilename());
            } catch (\Throwable $e) {
                $this->logger->error($e->getMessage());
                $io->error(sprintf('Erreur dans le rendu du template %s', $file->getRelativePathname()));
                continue;
            }

            // récupération du code contenu à partir du nom du fichier
            $filename = substr($file->getFilename(), 0, -strlen('.html.twig'));

            // recherche du contenu en bdd
            /** @var ContentHtml $contentHtml */
            $contentHtml = $repository->findOneBy(['code' => $filename, 'current' => 1]);

            // si le code est en bdd, on met à jour son contenu, sinon on le crée
            if ($contentHtml) {
                $contentHtml->setContenu($content);
                ++$updated;
            } else {
                $io->info(sprintf('Code %s non trouvé en bdd, insertion du nouveau contenu', $filename));
                $contentHtml = new ContentHtml();
                $contentHtml
                    ->setCode($filename)
                    ->setContenu($content)
                    ->setDate((new \DateTimeImmutable())->getTimestamp())
                    ->setLang('fr')
                    ->setLinkedtopage('')
                    ->setVis(true)
                    ->setCurrent(true)
                ;
                ++$created;
                if (!$dryRun) {
                    $this->entityManager->persist($contentHtml);
                }
            }

            ++$imported;

            if (!$dryRun) {
                if (($imported % self::MAX_BATCH_FILES) === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            }
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $io->success(
            sprintf(
                '%d fichiers statiques migrés en base de données (%d mis à jour, %d créés, %d exclus).',
                $imported,
                $updated,
                $created,
                $excluded
            )
        );

        return Command::SUCCESS;
    }
}
