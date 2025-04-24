<?php

namespace App\Utils;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExport
{
    /**
     * Calculate years between a given date and now.
     *
     * @param string|int|\DateTime $date
     */
    private function getYearsSinceDate($date): int
    {
        try {
            if (is_numeric($date)) {
                // Handle Unix timestamp
                $date = (new \DateTime())->setTimestamp((int) $date);
            } elseif (\is_string($date)) {
                $date = new \DateTime($date);
            } elseif (!$date instanceof \DateTime) {
                throw new \InvalidArgumentException('Invalid date format');
            }
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid date format', 0, $e);
        }

        $now = new \DateTime();

        if ($date > $now) {
            throw new \InvalidArgumentException('Future dates are not allowed');
        }

        return $date->diff($now)->y;
    }

    public function export(string $title, $datas, $rsm): Response
    {
        $streamedResponse = new StreamedResponse();

        $streamedResponse->setCallback(function () use ($title, $datas, $rsm) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle($title);

            // Génération de l'en-tête
            $count = 1;
            $sheet->fromArray($rsm, null, 'A' . $count++);

            // Génération des données
            foreach ($datas as $data) {
                $user = $data['liste']->getUser();
                $name = $user->getCiv() . ' ' . ucfirst(strtolower($user->getFirstname())) . ' ' . strtoupper($user->getLastname());

                $status = match ($data['liste']->getStatus()) {
                    0 => 'Non Confirmé',
                    1 => 'Validé',
                    2 => 'Refusé',
                    3 => 'Absent',
                    default => ' '
                };

                $array = [
                    $count - 1,
                    $name,
                    $status,
                    $data['liste']->getRole() ?? ' ',
                    $user->getCafnum() ?? ' ',
                    $user->getBirthday() ? $this->getYearsSinceDate($user->getBirthday()) : ' ',
                    $user->getDateAdhesion() ?? ' ',
                    $user->getTel() ? preg_replace('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4 $5', $user->getTel()) : ' ',
                    $user->getTel2() ? preg_replace('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4 $5', $user->getTel2()) : ' ',
                    $user->getEmail() ?? ' ',
                ];

                $sheet->fromArray($array, null, 'A' . $count);
                ++$count;
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        // Configuration des headers
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . $title . '.xlsx"');

        return $streamedResponse;
    }
}
