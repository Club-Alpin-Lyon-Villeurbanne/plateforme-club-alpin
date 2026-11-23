<?php

namespace App\Tests\Form;

use App\Entity\Evt;
use App\Entity\User;
use App\Form\EventType;
use App\Tests\WebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class EventTypeTest extends WebTestCase
{
    private function buildForm(Evt $event, User $user): FormInterface
    {
        return self::getContainer()->get(FormFactoryInterface::class)->create(EventType::class, $event, [
            'is_edit' => false,
            'editoLineLink' => '',
            'imageRightLink' => '',
            'user' => $user,
            'csrf_protection' => false,
        ]);
    }

    private function newEvent(User $user): Evt
    {
        return new Evt($user, $this->createCommission(), null, null, null, null, 'RDV', 45.75, 4.85, null, null, null, new \DateTimeImmutable());
    }

    /** Régression Sentry CLUBALPINLYONFR-1E6 : marqueur non placé (lat/long vides) → 500. */
    public function testSubmitWithEmptyCoordinatesIsInvalidWithoutCrashing(): void
    {
        $user = $this->signup();
        $event = $this->newEvent($user);
        $form = $this->buildForm($event, $user);

        $form->submit(['lat' => '', 'long' => ''], false);

        self::assertFalse($form->isValid());
        self::assertSame(45.75, (float) $event->getLat());
        self::assertSame(4.85, (float) $event->getLong());
        self::assertStringContainsString('marqueur', strtolower((string) $form->getErrors(true)));
    }

    public function testValidCoordinatesAreWrittenToEntity(): void
    {
        $user = $this->signup();
        $event = $this->newEvent($user);
        $form = $this->buildForm($event, $user);

        $form->submit(['lat' => '46.12345678', 'long' => '6.87654321'], false);

        self::assertSame(46.12345678, (float) $event->getLat());
        self::assertSame(6.87654321, (float) $event->getLong());
    }

    public function testEmptyCommuneIsRejectedWhenNotAbroad(): void
    {
        $user = $this->signup();
        $event = $this->newEvent($user);
        $form = $this->buildForm($event, $user);

        // case décochée : un navigateur n'envoie pas le champ
        $form->submit(['place' => '', 'lat' => '45.7', 'long' => '4.8'], false);

        self::assertFalse($event->isEtranger());
        self::assertStringContainsString('commune de départ est obligatoire', strtolower((string) $form->getErrors(true)));
    }

    public function testAbroadMakesCommuneOptional(): void
    {
        $user = $this->signup();
        $event = $this->newEvent($user);
        $form = $this->buildForm($event, $user);

        $form->submit(['etranger' => '1', 'place' => '', 'lat' => '45.7', 'long' => '4.8'], false);

        self::assertTrue($event->isEtranger());
        self::assertStringNotContainsString('commune de départ est obligatoire', strtolower((string) $form->getErrors(true)));
    }

    public function testAbroadWithFrenchCommuneIsRejected(): void
    {
        $user = $this->signup();
        $event = $this->newEvent($user);
        $form = $this->buildForm($event, $user);

        // case cochée + commune renseignée : incohérence, on refuse (il faut choisir l'un ou l'autre)
        $form->submit(['etranger' => '1', 'place' => '69510 Messimy', 'lat' => '45.7', 'long' => '4.8'], false);

        self::assertTrue($event->isEtranger());
        self::assertStringContainsString('ne doit pas indiquer de commune', strtolower((string) $form->getErrors(true)));
    }

    public function testAbroadClearsResidualFrenchDeparture(): void
    {
        $user = $this->signup();
        $event = $this->newEvent($user);
        // sortie initialement française : commune + coordonnées de départ renseignées
        $event->setPlace('69510 Messimy');
        $event->setLatDepart(45.78);
        $event->setLongDepart(4.66);
        $form = $this->buildForm($event, $user);

        // bascule en étranger sans commune : le point de départ français résiduel doit être effacé
        $form->submit(['etranger' => '1', 'place' => '', 'lat' => '45.7', 'long' => '4.8'], false);

        self::assertTrue($event->isEtranger());
        self::assertSame('', $event->getPlace());
        self::assertSame(0.0, (float) $event->getLatDepart());
        self::assertSame(0.0, (float) $event->getLongDepart());
    }

    #[DataProvider('champsPublics')]
    public function testLienWhatsappRefuseDansUnChampPublic(string $champ): void
    {
        $user = $this->signup();
        $form = $this->buildForm($this->newEvent($user), $user);

        $form->submit([$champ => '<p>Rejoignez le groupe : https://Chat.WhatsApp.com/AbCdEfGhIjKlMnOpQrStUv</p>'], false);

        self::assertStringContainsString('Détails pratiques', (string) $form->get($champ)->getErrors());
    }

    public static function champsPublics(): iterable
    {
        yield ['description'];
        yield ['matos'];
        yield ['itineraire'];
    }

    public function testLienWhatsappAccepteDansLesDetailsPratiques(): void
    {
        $user = $this->signup();
        $event = $this->newEvent($user);
        $form = $this->buildForm($event, $user);

        $form->submit([
            'description' => '<p>Le groupe WhatsApp est indiqué dans les détails pratiques.</p>',
            'details_caches' => 'https://chat.whatsapp.com/AbCdEfGhIjKlMnOpQrStUv',
        ], false);

        self::assertCount(0, $form->get('description')->getErrors());
        self::assertCount(0, $form->get('details_caches')->getErrors());
        self::assertSame('https://chat.whatsapp.com/AbCdEfGhIjKlMnOpQrStUv', $event->getDetailsCaches());
    }
}
