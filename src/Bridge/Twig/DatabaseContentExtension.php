<?php

namespace App\Bridge\Twig;

use App\Entity\Commission;
use App\Entity\User;
use App\Notifications;
use App\Repository\CommissionRepository;
use App\Repository\ContentInlineRepository;
use App\Repository\EvtRepository;
use App\Repository\PartenaireRepository;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DatabaseContentExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public static function getSubscribedServices()
    {
        return [
            PartenaireRepository::class,
            EvtRepository::class,
            ContentInlineRepository::class,
            CommissionRepository::class,
            Notifications::class,
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('db_content', [$this, 'getDbContent']),
            new TwigFunction('commission_title', [$this, 'getCommissionTitle']),
            new TwigFunction('commission_picto', [$this, 'getCommissionPicto']),
            new TwigFunction('list_commissions', [$this, 'getCommissions']),
            new TwigFunction('get_commission', [$this, 'getCommission']),
            new TwigFunction('list_events', [$this, 'getEvents']),
            new TwigFunction('list_partenaires', [$this, 'getPartenaires']),
            new TwigFunction('user_picto', [$this, 'getUserPicto']),
            new TwigFunction('fond_commission', [$this, 'getFondCommission']),
            new TwigFunction('notifications_counter', [$this, 'getNotificationsCounter']),
            new TwigFunction('notifications_counter_destinations', [$this, 'getNotificationsDestinations']),
            new TwigFunction('notifications_counter_articles', [$this, 'getNotificationsValidationArticle']),
            new TwigFunction('notifications_counter_sorties', [$this, 'getNotificationsValidationSortie']),
            new TwigFunction('notifications_counter_sorties_president', [$this, 'getNotificationsValidationSortiePresident']),
        ];
    }

    public function getFondCommission(?string $code)
    {
        if ($code && $commission = $this->locator->get(CommissionRepository::class)->findVisibleCommission($code)) {
            $id = $commission->getId();
        } else {
            $id = 0;
        }

        $rel = '/ftp/commission/'.$id.'/bigfond.jpg';

        if (!file_exists(__DIR__.'/../../../public/'.$rel)) {
            $rel = '/ftp/commission/0/bigfond.jpg';
        }

        return $rel;
    }

    public function getPartenaires(): iterable
    {
        return $this->locator->get(PartenaireRepository::class)->findEnabled();
    }

    public function getEvents(?Commission $commission): iterable
    {
        return $this->locator->get(EvtRepository::class)->getUpcomingEvents($commission);
    }

    public function getNotificationsCounter(): int
    {
        return $this->locator->get(Notifications::class)->getAll();
    }

    public function getNotificationsDestinations(): int
    {
        return $this->locator->get(Notifications::class)->getDestinations();
    }

    public function getNotificationsValidationArticle(): int
    {
        return $this->locator->get(Notifications::class)->getValidationArticle();
    }

    public function getNotificationsValidationSortie(): int
    {
        return $this->locator->get(Notifications::class)->getValidationSortie();
    }

    public function getNotificationsValidationSortiePresident(): int
    {
        return $this->locator->get(Notifications::class)->getValidationSortiePresident();
    }

    public function getDbContent(string $type): ?string
    {
        foreach ($this->locator->get(ContentInlineRepository::class)->findAll() as $cafContentInline) {
            if ($cafContentInline->getCode() === $type) {
                return $cafContentInline->getContenu();
            }
        }

        return null;
    }

    public function getCommissionTitle(?string $code): ?string
    {
        if (!$code) {
            return null;
        }

        if ($commission = $this->locator->get(CommissionRepository::class)->findVisibleCommission($code)) {
            return $commission->getTitle();
        }

        return null;
    }

    public function getCommissions(): iterable
    {
        return iterator_to_array($this->locator->get(CommissionRepository::class)->findVisible());
    }

    public function getCommission(?string $code): ?Commission
    {
        return $this->locator->get(CommissionRepository::class)->findVisibleCommission($code);
    }

    public function getCommissionPicto(string $code = null, string $style = null): string
    {
        if ($code && $commission = $this->locator->get(CommissionRepository::class)->findVisibleCommission($code)) {
            $id = $commission->getId();
        } else {
            $id = 0;
        }

        switch ($style) {
            case 'light':
            case 'dark':
                $style = '-'.$style;
                break;
            default:
                $style = '';
                break;
        }

        $rel = '/ftp/commission/'.$id.'/picto'.$style.'.png';

        if (!file_exists(__DIR__.'/../../../public/'.$rel)) {
            $rel = '/ftp/commission/0/picto'.$style.'.png';
        }

        return $rel;
    }

    public function getUserPicto(User $user, $style = '')
    {
        switch ($style) {
            case 'pic':
            case 'min':
                $style = $style.'-';
                break;
            default:
                $style = '';
                break;
        }

        $rel = '/ftp/user/'.$user->getId().'/'.$style.'profil.jpg';

        if (!file_exists(__DIR__.'/../../../public'.$rel)) {
            $rel = '/ftp/user/0/'.$style.'profil.jpg';
        }

        return $rel.'?ct='.time();
    }
}
