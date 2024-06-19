<?php

namespace App\Bridge\Twig;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use Psr\Container\ContainerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TwigExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $locator;
    private string $maxTimestampForLegalValidation;

    public function __construct(ContainerInterface $locator, string $maxTimestampForLegalValidation)
    {
        $this->locator = $locator;
        $this->maxTimestampForLegalValidation = $maxTimestampForLegalValidation;
    }

    public static function getSubscribedServices(): array
    {
        return [
            SluggerInterface::class,
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('slugify', [$this, 'slugify']),
            new TwigFilter('random_item', [$this, 'getRandomItem']),
            new TwigFilter('is_legal_validable', [$this, 'isLegalValidable']),
            new TwigFilter('paiement_title', [$this, 'getPaiementTitle']),
            new TwigFilter('intldate', [$this, 'formatDate']),
            new TwigFilter('participation_status_name', [$this, 'getParticipationStatusName']),
            new TwigFilter('participation_role_name', [$this, 'getParticipationRoleName']),
            new TwigFilter('temoin_event', [$this, 'getTemoinPlacesSortie']),
            new TwigFilter('temoin_event_title', [$this, 'getTemoinPlacesSortieTitle']),
        ];
    }

    public function getTemoinPlacesSortie(Evt $event)
    {
        if ($event->getCycleParent()) {
            return '';
        }
        if ($event->getCancelled()) {
            return 'off';
        }
        if ($event->hasStarted()) {
            return 'off';
        }
        if (!$event->joinHasStarted()) {
            return '';
        }
        if ($event->getJoinMax() <= \count($event->getParticipations())) {
            return 'off';
        }

        return 'on';
    }

    public function getTemoinPlacesSortieTitle(Evt $event)
    {
        if ($event->getCycleParent()) {
            return 'Les inscriptions pour cette sortie ont lieu dans la première sortie du cycle';
        }
        if ($event->getCancelled()) {
            return 'Cette sortie est annulée';
        }
        if ($event->hasStarted()) {
            return 'Les inscriptions sont terminées';
        }
        if (!$event->joinHasStarted()) {
            return sprintf('Les inscriptions pour cette sortie commenceront le %s', date('d/m/y', $event->getJoinStart()));
        }
        if ($event->getJoinMax() <= \count($event->getParticipations())) {
            return sprintf('Les %d places libres ont été réservées', $event->getJoinMax());
        }

        return sprintf('%d places restantes', $event->getJoinMax() - \count($event->getParticipations()));
    }

    public function getParticipationStatusName(?EventParticipation $participation): string
    {
        if (!$participation) {
            return '';
        }

        switch ($participation->getStatus()) {
            case EventParticipation::STATUS_NON_CONFIRME:
                return 'Non confirmé';
            case EventParticipation::STATUS_VALIDE:
                return 'Validé';
            case EventParticipation::STATUS_REFUSE:
                return 'Refusé';
            case EventParticipation::STATUS_ABSENT:
                return 'Absent';
        }

        return '';
    }

    public function getParticipationRoleName(?EventParticipation $participation): string
    {
        if (!$participation) {
            return '';
        }

        switch ($participation->getRole()) {
            case EventParticipation::ROLE_BENEVOLE:
                return 'Bénévole';
            case EventParticipation::ROLE_STAGIAIRE:
                return 'Stagiaire';
            case EventParticipation::ROLE_COENCADRANT:
                return 'Co-encadrant(e)';
            case EventParticipation::ROLE_ENCADRANT:
                return 'Encadrant(e)';
            default:
                return 'Participant(e)';
        }

        return '';
    }

    public function formatDate($date, string $format = 'd/MM/YYYY')
    {
        return (new \IntlDateFormatter(
            'fr-FR',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            'Europe/Paris',
            \IntlDateFormatter::GREGORIAN,
            $format
        ))->format($date);
    }

    public function isLegalValidable(Evt $event)
    {
        $time = strtotime($this->maxTimestampForLegalValidation);

        return $event->getTsp() < $time;
    }

    public function slugify(string $string)
    {
        return $this->locator->get(SluggerInterface::class)->slug($string);
    }

    public function getRandomItem(array $items)
    {
        if (empty($items)) {
            return null;
        }

        return $items[array_rand($items)];
    }

    public function getPaiementTitle(Evt $event, User $user)
    {
        $title = $this->locator->get(SluggerInterface::class)->slug($event->getTitre());
        $compl = ' du ' . date('d-m-Y', $event->getTsp()) . ' ' . $user->getFirstname() . ' ' . $user->getLastname();
        $size_compl = \strlen($compl);

        return substr($title, 0, 64 - $size_compl) . $compl;
    }
}
