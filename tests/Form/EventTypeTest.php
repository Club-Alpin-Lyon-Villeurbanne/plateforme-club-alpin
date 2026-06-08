<?php

namespace App\Tests\Form;

use App\Entity\Evt;
use App\Entity\User;
use App\Form\EventType;
use App\Tests\WebTestCase;
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
}
