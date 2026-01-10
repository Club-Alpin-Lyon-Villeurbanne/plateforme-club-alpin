<?php

namespace App\Controller;

use App\Repository\CommissionRepository;
use App\Repository\EvtRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class IcsController extends AbstractController
{
    public function __construct(private string $sitename)
    {
    }

    #[Route(name: 'ics_global', path: '/calendrier.ics', methods: ['GET'])]
    public function globalCalendar(
        EvtRepository $evtRepository,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $events = $evtRepository->getUpcomingEventsForIcs(null);

        return $this->generateIcsResponse(
            $events,
            sprintf('%s - Toutes les sorties', $this->sitename),
            $urlGenerator
        );
    }

    #[Route(name: 'ics_commission', path: '/calendrier/{code}.ics', methods: ['GET'])]
    public function commissionCalendar(
        string $code,
        CommissionRepository $commissionRepository,
        EvtRepository $evtRepository,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $commission = $commissionRepository->findVisibleCommission($code);
        if (!$commission) {
            throw new NotFoundHttpException(sprintf('Commission "%s" introuvable', $code));
        }

        $events = $evtRepository->getUpcomingEventsForIcs($commission);

        return $this->generateIcsResponse(
            $events,
            sprintf('%s - %s', $this->sitename, $commission->getTitle()),
            $urlGenerator
        );
    }

    /**
     * @param array<\App\Entity\Evt> $events
     */
    private function generateIcsResponse(array $events, string $calendarName, UrlGeneratorInterface $urlGenerator): Response
    {
        $ics = $this->generateIcsContent($events, $calendarName, $urlGenerator);

        return new Response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="calendrier.ics"',
            'Cache-Control' => 'max-age=3600',
        ]);
    }

    /**
     * @param array<\App\Entity\Evt> $events
     */
    private function generateIcsContent(array $events, string $calendarName, UrlGeneratorInterface $urlGenerator): string
    {
        $domain = parse_url($urlGenerator->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL), PHP_URL_HOST) ?? 'club-alpin';

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//' . $this->escapeIcs($this->sitename) . '//Sorties//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:' . $this->escapeIcs($calendarName),
            'X-WR-TIMEZONE:Europe/Paris',
        ];

        foreach ($events as $event) {
            $lines = array_merge($lines, $this->generateEventBlock($event, $urlGenerator, $domain));
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * @return array<string>
     */
    private function generateEventBlock(\App\Entity\Evt $event, UrlGeneratorInterface $urlGenerator, string $domain): array
    {
        $uid = sprintf('evt-%d@%s', $event->getId(), $domain);
        $eventUrl = $urlGenerator->generate('sortie', [
            'code' => $event->getCode(),
            'id' => $event->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        // Description enrichie - échapper chaque partie avant de joindre avec \n
        $descParts = [];
        if ($event->getCommission()) {
            $descParts[] = 'Commission : ' . $this->escapeIcs($event->getCommission()->getTitle());
        }
        if ($event->getDifficulte()) {
            $descParts[] = 'Difficulté : ' . $this->escapeIcs($event->getDifficulte());
        }
        if ($event->getMassif()) {
            $descParts[] = 'Massif : ' . $this->escapeIcs($event->getMassif());
        }
        if ($event->getTarif()) {
            $descParts[] = sprintf('Tarif : %.2f €', $event->getTarif());
        }
        $descParts[] = '';
        $descParts[] = 'Détails : ' . $eventUrl;
        // Joindre avec \n (littéral ICS pour retour à la ligne)
        $description = implode('\n', $descParts);

        $dtstamp = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Ymd\THis\Z');

        // Gestion de updatedAt (peut être DateTime via Gedmo TimestampableEntity)
        $updatedAt = $event->getUpdatedAt();
        $lastModified = $updatedAt
            ? (clone $updatedAt)->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\THis\Z')
            : $dtstamp;

        // DTEND : si null, utiliser startDate (événement ponctuel)
        $endDate = $event->getEndDate() ?? $event->getStartDate();

        $lines = [
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . $dtstamp,
            'LAST-MODIFIED:' . $lastModified,
            'DTSTART;TZID=Europe/Paris:' . $event->getStartDate()->format('Ymd\THis'),
            'DTEND;TZID=Europe/Paris:' . $endDate->format('Ymd\THis'),
            'SUMMARY:' . $this->escapeIcs($event->getTitre()),
            'DESCRIPTION:' . $description,
            'URL:' . $eventUrl,
        ];

        if ($event->getRdv()) {
            $lines[] = 'LOCATION:' . $this->escapeIcs($event->getRdv());
        }

        if ($event->getLat() && $event->getLong()) {
            $lines[] = sprintf('GEO:%s;%s', $event->getLat(), $event->getLong());
        }

        $lines[] = 'END:VEVENT';

        return $lines;
    }

    /**
     * Échappe les caractères spéciaux pour ICS (RFC 5545).
     */
    private function escapeIcs(string $text): string
    {
        // Échapper backslash en premier, puis virgules/points-virgules, puis retours à la ligne
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace([',', ';'], ['\\,', '\\;'], $text);

        return str_replace(["\r\n", "\r", "\n"], '\n', $text);
    }
}
