<?php

namespace App\Tests\Legacy;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ImagePathsTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        require_once __DIR__ . '/../../legacy/app/fonctions.php';
    }

    // ==========================================
    // userImg() - images profil
    // ==========================================

    public function testUserImgDefaultPath(): void
    {
        // User sans photo -> image par défaut
        $path = userImg(999999);
        $this->assertEquals('/ftp/user/0/profil.jpg', $path);
    }

    public function testUserImgPicStyle(): void
    {
        $path = userImg(999999, 'pic');
        $this->assertEquals('/ftp/user/0/pic-profil.jpg', $path);
    }

    public function testUserImgMinStyle(): void
    {
        $path = userImg(999999, 'min');
        $this->assertEquals('/ftp/user/0/min-profil.jpg', $path);
    }

    public function testUserImgInvalidStyleReturnsDefault(): void
    {
        $path = userImg(999999, 'invalid');
        $this->assertEquals('/ftp/user/0/profil.jpg', $path);
    }

    // ==========================================
    // comPicto() - pictos commissions
    // ==========================================

    public function testComPictoDefaultPath(): void
    {
        $path = comPicto(999999);
        $this->assertEquals('/ftp/commission/0/picto.png', $path);
    }

    public function testComPictoLightStyle(): void
    {
        $path = comPicto(999999, 'light');
        $this->assertEquals('/ftp/commission/0/picto-light.png', $path);
    }

    public function testComPictoDarkStyle(): void
    {
        $path = comPicto(999999, 'dark');
        $this->assertEquals('/ftp/commission/0/picto-dark.png', $path);
    }

    // ==========================================
    // comFd() - fonds commissions
    // ==========================================

    public function testComFdReturnsPath(): void
    {
        $path = comFd(123);
        $this->assertEquals('/ftp/commission/123/bigfond.jpg', $path);
    }

    public function testComFdEmptyForNull(): void
    {
        $this->assertEquals('', comFd(null));
    }

    public function testComFdEmptyForZero(): void
    {
        $this->assertEquals('', comFd(0));
    }
}
