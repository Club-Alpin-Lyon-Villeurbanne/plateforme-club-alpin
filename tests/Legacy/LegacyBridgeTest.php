<?php

namespace App\Tests\Legacy;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests des fonctions bridges legacy vers Symfony.
 *
 * NOTE: Ces tests sont désactivés pour les mêmes raisons que LegacyPagesTest.
 * Les fonctions bridges (user, getUser, allowed, isGranted) utilisent LegacyContainer
 * qui dépend d'un kernel global.
 *
 * @see LegacyPagesTest pour plus de détails
 */
class LegacyBridgeTest extends KernelTestCase
{
    /**
     * Test minimal pour vérifier que le fichier de test est chargé.
     */
    public function testLegacyBridgeTestFileExists(): void
    {
        $this->assertTrue(true);
    }

    // ==========================================
    // TODO: Activer ces tests après refactoring
    // ==========================================
    //
    // Ces tests fonctionneront quand LegacyContainer sera refactoré
    // pour ne plus dépendre d'un kernel global.
    //
    // public function testUserReturnsFalseWhenNotLoggedIn(): void
    // public function testGetUserReturnsUserWhenLoggedIn(): void
    // public function testAllowedEvtJoinForMember(): void
    // etc.
}
