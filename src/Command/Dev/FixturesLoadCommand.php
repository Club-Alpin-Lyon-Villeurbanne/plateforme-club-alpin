<?php

namespace App\Command\Dev;

use App\DataFixtures\DevData;
use App\Entity\User;
use App\Entity\Usertype;
use App\Repository\CommissionRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[AsCommand(name: 'caf:fixtures:load')]
class FixturesLoadCommand extends Command
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container, ?string $name = null)
    {
        parent::__construct($name);
        $this->container = $container;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->container->get('doctrine')->getManager();
        $commissionRepository = $this->container->get(CommissionRepository::class);
        (new DevData())->load($em);

        $userTypeRepo = $em->getRepository(Usertype::class);
        $comm = $commissionRepository->findVisibleCommission('sorties-familles');

        // admin is also developpeur
        $mainUser = $em
            ->getRepository(User::class)
            ->findOneByEmail('admin@test-clubalpinlyon.fr')
        ;
        $mainUser->addAttribute($userTypeRepo->findOneByCode('developpeur'));
        $mainUser->addAttribute($userTypeRepo->findOneByCode('administrateur'));
        // président
        $em
            ->getRepository(User::class)
            ->findOneByEmail('president@test-clubalpinlyon.fr')
            ->addAttribute($userTypeRepo->findOneByCode('president'))
        ;
        // président suppléant
        $em
            ->getRepository(User::class)
            ->findOneByEmail('vp@test-clubalpinlyon.fr')
            ->addAttribute($userTypeRepo->findOneByCode('vice-president'))
        ;
        // accueil / salarié
        $em
            ->getRepository(User::class)
            ->findOneByEmail('accueil@test-clubalpinlyon.fr')
            ->addAttribute($userTypeRepo->findOneByCode('salarie'))
        ;
        // responsable de commission
        $em
            ->getRepository(User::class)
            ->findOneByEmail('resp.comm@test-clubalpinlyon.fr')
            ->addAttribute($userTypeRepo->findOneByCode('responsable-commission'), 'commission:' . $comm->getCode())
        ;
        // encadrant
        $em
            ->getRepository(User::class)
            ->findOneByEmail('encadrant@test-clubalpinlyon.fr')
            ->addAttribute($userTypeRepo->findOneByCode('encadrant'), 'commission:' . $comm->getCode())
        ;
        // initiateur stagiaire
        $em
            ->getRepository(User::class)
            ->findOneByEmail('stagiaire@test-clubalpinlyon.fr')
            ->addAttribute($userTypeRepo->findOneByCode('stagiaire'), 'commission:' . $comm->getCode())
        ;
        // co-encadrant
        $em
            ->getRepository(User::class)
            ->findOneByEmail('coencadrant@test-clubalpinlyon.fr')
            ->addAttribute($userTypeRepo->findOneByCode('coencadrant'), 'commission:' . $comm->getCode())
        ;
        // bénévole d'encadrement
        $em
            ->getRepository(User::class)
            ->findOneByEmail('benevole.encadrement@test-clubalpinlyon.fr')
            ->addAttribute($userTypeRepo->findOneByCode('benevole_encadrement'), 'commission:' . $comm->getCode())
        ;
        // rédacteur
        $em
            ->getRepository(User::class)
            ->findOneByEmail('redacteur@test-clubalpinlyon.fr')
            ->addAttribute($userTypeRepo->findOneByCode('redacteur'), 'commission:' . $comm->getCode())
        ;

        $em->flush();

        return 0;
    }
}
