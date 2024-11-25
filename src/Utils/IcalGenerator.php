<?php

namespace App\Utils;

use App\Entity\Evt;

enum Status
{
    case Confirmed;
    case Cancelled;

    public function eventStatus(): string
    {
        return match ($this) {
            Status::Confirmed => 'CONFIRMED',
            Status::Cancelled => 'CANCELLED',
        };
    }
}

class IcalGenerator
{
    public function eventUpsert(Evt $evt, string $evtUrl): ?string
    {
        return $this->generateFromEvent($evt, $evtUrl, Status::Confirmed);
    }

    public function eventCancel(Evt $evt, string $evtUrl): ?string
    {
        return $this->generateFromEvent($evt, $evtUrl, Status::Cancelled);
    }

    private function generateFromEvent(Evt $evt, string $evtUrl, Status $status): ?string
    {
        $tspStart = $evt->getTsp();
        $tspEnd = $evt->getTspEnd();
        if (!($tspStart && $tspEnd)) {
            return null;
        }

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//ClubAlpin//CAF Lyon Villeurbanne//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:' . $this->uid($evt),
            'STATUS:' . $status->eventStatus(),
            'LAST-MODIFIED:' . $this->formatDateTime(),
            'DTSTART:' . $this->formatDateTime($tspStart),
            'DTEND:' . $this->formatDateTime($tspEnd),
            'SUMMARY:' . $this->escape($evt->getTitre()),
        ];

        if ('' !== $evtUrl) {
            $lines[] = 'URL:' . $evtUrl;
        }

        if ($description = $evt->getDescription()) {
            $lines[] = 'DESCRIPTION:' . $this->escape($description);
        }

        if ($lat = $evt->getLat() && $long = $evt->getLong()) {
            $lines[] = 'GEO:' . $this->escape(str_replace(',', '.', $lat) . ';' . str_replace(',', '.', $long));
        }

        if ($venue = $evt->getRdv()) {
            $lines[] = 'LOCATION:' . $this->escape($venue);
        }

        array_push($lines,
            'BEGIN:VALARM',
            'ACTION:DISPLAY',
            'TRIGGER:-PT1H',
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR'
        );

        return implode("\r\n", $lines);
    }

    private function formatDateTime(?int $tsp = null): string
    {
        return date('Ymd\THis\Z', $tsp);
    }

    private function escape(string $value): string
    {
        $value = str_replace(["\r\n", "\n"], '\\n', $value);

        return addcslashes($value, ',;');
    }

    private function uid(Evt $evt): string
    {
        if (!$evt->getId()) {
            throw new \RuntimeException('Evt entity must be flushed before using the iCalGenerator.');
        }

        return 'evt-' . $evt->getId() . '@caf';
    }
}
