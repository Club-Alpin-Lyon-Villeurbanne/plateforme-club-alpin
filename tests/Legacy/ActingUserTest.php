<?php

namespace App\Tests\Legacy;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ActingUserTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $GLOBALS['kernel'] = self::$kernel;

        require_once __DIR__ . '/../../legacy/app/fonctions.php';
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['kernel']);
        parent::tearDown();
    }

    public function testSansConnexionIlNYAPasDAuteur(): void
    {
        self::getContainer()->get('security.token_storage')->setToken(null);

        $this->assertNull(getActingUser());
    }

    public function testLAuteurEstLaPersonneConnectee(): void
    {
        $adherent = new User();
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken($adherent, 'main', []));

        $this->assertSame($adherent, getActingUser());
    }

    public function testEnImpersonificationLAuteurEstCeluiQuiImpersonifie(): void
    {
        $developpeur = new User();
        $adherent = new User();
        $jetonInitial = new UsernamePasswordToken($developpeur, 'main', []);

        self::getContainer()->get('security.token_storage')->setToken(new SwitchUserToken($adherent, 'main', [], $jetonInitial));

        $this->assertSame($adherent, getUser());
        $this->assertSame($developpeur, getActingUser());
    }
}
