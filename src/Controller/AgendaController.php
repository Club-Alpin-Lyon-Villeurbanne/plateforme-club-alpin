<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Commission;
use App\Entity\Evt;
use App\Repository\ArticleRepository;
use App\Repository\CommissionRepository;
use App\Repository\EvtRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AgendaController extends AbstractController
{
    #[Route('/agenda', name: 'agenda', priority: 10)]
    #[Route('/agenda/{code}', name: 'commission_agenda', priority: 10)]
    #[Template('agenda/index.html.twig')]
    public function index(
        Request $request,
        EvtRepository $evtRepository,
        CommissionRepository $commissionRepository,
        ArticleRepository $articleRepository,
        ?Commission $commission = null,
    ): array {
        $year = (int) ($request->query->get('year') ?: date('Y'));
        $month = (int) ($request->query->get('month') ?: date('m'));

        if ($year <= 2000) {
            $year = (int) date('Y');
        }
        if ($month < 1 || $month > 12) {
            $month = (int) date('m');
        }

        $nDays = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
        $startDate = new \DateTimeImmutable("$year-$month-01 00:00:00");
        $endDate = new \DateTimeImmutable("$year-$month-$nDays 23:59:59");

        $events = $evtRepository->getEventsByDateRange(
            $startDate,
            $endDate,
            $commission,
        );

        $agendaTab = $this->buildAgendaTab(
            $events,
            $nDays,
            $month,
            $year,
        );

        $maxYear = (int) date('Y') + 2;
        $minYear = (int) date('Y') - 3;

        $commissions = $commissionRepository->findBy(['vis' => true], ['ordre' => 'ASC']);

        // Calculate first day of month weekday
        $firstDayOfMonth = (int) date('w', mktime(0, 0, 0, $month, 1, $year));

        return [
            'agenda' => $agendaTab,
            'month' => $month,
            'year' => $year,
            'nb_days' => $nDays,
            'nb_events' => $this->countMonthEvents($agendaTab),
            'min_year' => $minYear,
            'max_year' => $maxYear,
            'commissions' => $commissions,
            'current_commission' => $commission,
            'current_commission_code' => $commission?->getCode(),
            'current_url' => $this->generateUrl(
                $request->attributes->get('_route'),
                $commission ? ['code' => $commission->getCode()] : [],
            ),
            'first_day_weekday' => $firstDayOfMonth,
            'articles' => $articleRepository->getPublishedArticlesForRightColumn($commission),
        ];
    }

    /**
     * @param Evt[] $events
     *
     * @return array<int, array<string, array<Evt>>>
     */
    private function buildAgendaTab(
        array $events,
        int $nDays,
        int $month,
        int $year,
    ): array {
        $agendaTab = [];

        for ($i = 1; $i <= $nDays; ++$i) {
            $agendaTab[$i] = ['debut' => [], 'courant' => []];
        }

        foreach ($events as $event) {
            if (!$event->getStartDate() || !$event->getEndDate()) {
                continue;
            }

            $eventStart = $event->getStartDate();
            $eventEnd = $event->getEndDate();
            $eventTsp = $eventStart->getTimestamp();

            $tmpStartD = (int) $eventStart->format('d');
            $tmpStartM = (int) $eventStart->format('m');
            $tmpStartY = (int) $eventStart->format('Y');
            $tmpEndD = (int) $eventEnd->format('d');
            $tmpEndM = (int) $eventEnd->format('m');
            $tmpEndY = (int) $eventEnd->format('Y');

            // Check if event starts in this month
            if ($tmpStartM === $month && $tmpStartY === $year) {
                $dayCount = 0;
                if ($tmpStartD . $tmpStartM !== $tmpEndD . $tmpEndM) {
                    $dayCount = 1;
                }
                $agendaTab[$tmpStartD]['debut'][] = ['event' => $event, 'day_count' => $dayCount];
            }

            // Handle multi-day events
            if ($tmpStartD . $tmpStartM !== $tmpEndD . $tmpEndM) {
                // Determine start day for continuous display
                $i = ($tmpStartM !== $month || $tmpStartY !== $year) ? 1 : $tmpStartD + 1;

                while ($i <= $nDays) {
                    $tmpDay = mktime(23, 59, 59, $month, $i, $year);
                    $dayCount = (int) ceil(($tmpDay - $eventTsp) / 86400);

                    // Stop if we've passed the end date
                    if ($tmpEndM === $month && $tmpEndY === $year && $i > $tmpEndD) {
                        break;
                    }

                    $agendaTab[$i]['courant'][] = ['event' => $event, 'day_count' => $dayCount];

                    ++$i;
                }
            }
        }

        return $agendaTab;
    }

    /**
     * @param array<int, array<string, array<Evt>>> $agendaTab
     */
    private function countMonthEvents(array $agendaTab): int
    {
        $count = 0;
        foreach ($agendaTab as $day) {
            $count += \count($day['debut']);
        }

        return $count;
    }
}
