<?php

namespace App\Tests\Controller;

use App\Entity\Commission;
use App\Entity\FormationReferentielBrevet;
use App\Entity\FormationReferentielFormation;
use App\Entity\FormationValidationBrevet;
use App\Entity\FormationValidationFormation;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Tests\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class CommissionControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private ?int $droitId = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = $this->getContainer()->get('doctrine')->getManager();

        // la base de test n'attribue le droit commission_list à aucun type d'adhérent
        $connection = $this->em->getConnection();
        $inserted = $connection->executeStatement(
            'INSERT INTO caf_usertype_attr (type_usertype_attr, right_usertype_attr, details_usertype_attr)
             SELECT ut.id_usertype, ur.id_userright, \'\' FROM caf_usertype ut, caf_userright ur
             WHERE ut.code_usertype = ? AND ur.code_userright = ?',
            [UserAttr::RESPONSABLE_COMMISSION, 'commission_list']
        );
        $this->assertSame(1, $inserted);
        $this->droitId = (int) $connection->lastInsertId();
    }

    protected function tearDown(): void
    {
        if (null !== $this->droitId) {
            $this->em->getConnection()->executeStatement('DELETE FROM caf_usertype_attr WHERE id_usertype_attr = ?', [$this->droitId]);
        }
        parent::tearDown();
    }

    public function testLaPageAfficheLesBrevetsEtFormationsRattachesQueLeStaffDetient(): void
    {
        $commission = $this->createCommission();
        $responsable = $this->signup();
        $this->addAttribute($responsable, UserAttr::RESPONSABLE_COMMISSION, 'commission:' . $commission->getCode());
        $adherent = $this->signup();

        $brevetDuStaff = $this->createBrevet($commission);
        $brevetHorsStaff = $this->createBrevet($commission);
        $brevetHorsCommission = $this->createBrevet(null);
        $this->validerBrevet($responsable, $brevetDuStaff, '2020-03-01', '2024-05-15');
        $this->validerBrevet($adherent, $brevetHorsStaff, '2021-01-01');
        $this->validerBrevet($responsable, $brevetHorsCommission, '2022-01-01');

        $formationDuStaff = $this->createFormation($commission);
        $formationHorsStaff = $this->createFormation($commission);
        $formationHorsCommission = $this->createFormation(null);
        $this->validerFormation($responsable, $formationDuStaff, '2023-06-10');
        $this->validerFormation($adherent, $formationHorsStaff, '2023-06-11');
        $this->validerFormation($responsable, $formationHorsCommission, '2023-06-12');
        $this->em->flush();

        $this->signin($responsable);
        $this->em->clear();
        $this->client->request('GET', '/commissions/' . $commission->getId() . '/brevets');

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString($brevetDuStaff->getCodeBrevet(), $content);
        $this->assertStringContainsString('15/05/2024', $content, 'le recyclage prime sur l\'obtention');
        $this->assertStringNotContainsString('01/03/2020', $content);
        $this->assertStringNotContainsString($brevetHorsStaff->getCodeBrevet(), $content);
        $this->assertStringNotContainsString($brevetHorsCommission->getCodeBrevet(), $content);
        $this->assertStringContainsString($formationDuStaff->getCodeFormation(), $content);
        $this->assertStringContainsString('10/06/2023', $content);
        $this->assertStringNotContainsString($formationHorsStaff->getCodeFormation(), $content);
        $this->assertStringNotContainsString($formationHorsCommission->getCodeFormation(), $content);
    }

    public function testLePsc1RattacheALaCommissionNeDoublePasSaColonneFixe(): void
    {
        $commission = $this->createCommission();
        $responsable = $this->signup();
        $this->addAttribute($responsable, UserAttr::RESPONSABLE_COMMISSION, 'commission:' . $commission->getCode());

        $psc1 = $this->em->getRepository(FormationReferentielFormation::class)->findOneBy(['codeFormation' => 'STG-PSC1'])
            ?? (new FormationReferentielFormation())->setCodeFormation('STG-PSC1')->setIntitule('PSC1');
        $this->em->persist($psc1);
        $commission->addFormation($psc1);
        $this->validerFormation($responsable, $psc1, '2023-06-10');
        $this->em->flush();

        $this->signin($responsable);
        $this->em->clear();
        $this->client->request('GET', '/commissions/' . $commission->getId() . '/brevets');

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $this->assertSame(1, substr_count($content, 'STG-PSC1'));
        $this->assertSame(1, substr_count($content, '10/06/2023'));
    }

    public function testLeLienVersLesBrevetsNApparaitQuePourUneCommissionAvecDesRattachements(): void
    {
        $avecBrevets = $this->createCommission();
        $sansRattachement = $this->createCommission();
        $responsable = $this->signup();
        $this->addAttribute($responsable, UserAttr::RESPONSABLE_COMMISSION, 'commission:' . $avecBrevets->getCode());
        $this->addAttribute($responsable, UserAttr::RESPONSABLE_COMMISSION, 'commission:' . $sansRattachement->getCode());
        $this->createBrevet($avecBrevets);
        $this->em->flush();

        $this->signin($responsable);
        $this->em->clear();
        $crawler = $this->client->request('GET', '/commissions');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('a[href="/commissions/' . $avecBrevets->getId() . '/brevets"]'));
        $this->assertCount(0, $crawler->filter('a[href="/commissions/' . $sansRattachement->getId() . '/brevets"]'));
    }

    private function createBrevet(?Commission $commission): FormationReferentielBrevet
    {
        $brevet = (new FormationReferentielBrevet())
            ->setCodeBrevet('BRV-TEST-' . bin2hex(random_bytes(6)))
            ->setIntitule('Brevet de test');
        $this->em->persist($brevet);
        $commission?->addBrevet($brevet);

        return $brevet;
    }

    private function createFormation(?Commission $commission): FormationReferentielFormation
    {
        $formation = (new FormationReferentielFormation())
            ->setCodeFormation('STG-TEST-' . bin2hex(random_bytes(6)))
            ->setIntitule('Formation de test');
        $this->em->persist($formation);
        $commission?->addFormation($formation);

        return $formation;
    }

    private function validerBrevet(User $user, FormationReferentielBrevet $brevet, string $obtention, ?string $recyclage = null): void
    {
        $validation = (new FormationValidationBrevet())
            ->setUser($user)
            ->setBrevet($brevet)
            ->setDateObtention(new \DateTimeImmutable($obtention))
            ->setDateRecyclage($recyclage ? new \DateTimeImmutable($recyclage) : null)
            ->setDateEdition(null)
            ->setDateFormationContinue(null)
            ->setDateMigration(null);
        $validation->setCreatedAt(new \DateTime())->setUpdatedAt(new \DateTime());
        $this->em->persist($validation);
    }

    private function validerFormation(User $user, FormationReferentielFormation $formation, string $date): void
    {
        $validation = (new FormationValidationFormation())
            ->setUser($user)
            ->setFormation($formation)
            ->setValide(true)
            ->setDateValidation(new \DateTime($date));
        $validation->setCreatedAt(new \DateTime())->setUpdatedAt(new \DateTime());
        $this->em->persist($validation);
    }
}
