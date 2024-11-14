<?php

namespace App\Utils;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExport
{
    public function export(string $title, $datas, $rsm)
    {
        $streamedResponse = new StreamedResponse();
        $streamedResponse->setCallback(function () use ($title, $datas, $rsm) {

            // Generating SpreadSheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle($title);

            // Generating First Row with column name
            $count = 1;
            $sheet->fromArray($rsm, null, 'A' . $count++);

            // Generating other rows with datas
            foreach ($datas as $data) {
                $user = $data["liste"]->getUser();
                $name = mb_convert_encoding($user->getCiv() . " " . strtoupper($user->getLastname()) . " " . ucfirst(strtolower($user->getFirstname())), "UTF-8");
                switch ($data["liste"]->getStatus()) {
                    case (0):
                        $status = mb_convert_encoding("Non Comfirmé", "UTF-8");
                        break;
                    case (1):
                        $status = mb_convert_encoding("Validé", "UTF-8");
                        break;
                    case (2):
                        $status = mb_convert_encoding("Refusé", "UTF-8");
                        break;
                    case (3):
                        $status = mb_convert_encoding("Absent", "UTF-8");
                        break;
                    default:
                        $status = " ";
                        break;
                }

                $array = [
                    $count - 1,
                    $name ? $name : " ",
                    $status,
                    $data["liste"]->getRole() ? mb_convert_encoding($data["liste"]->getRole(), "UTF-8") : " ",
                    $user->getCafnum() ? mb_convert_encoding($user->getCafnum(), "UTF-8") : " ",
                    $user->getBirthday() ? getYearsSinceDate($user->getBirthday()) : " ",
                    $user->getDateAdhesion() ? mb_convert_encoding($user->getDateAdhesion(), "UTF-8") : " ",
                    $user->getTel() ? mb_convert_encoding(preg_replace('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4 $5', $user->getTel()), "UTF-8") : " ",
                    $user->getTel2() ? mb_convert_encoding(preg_replace('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4 $5', $user->getTel2()), "UTF-8") : " ",
                    $user->getEmail() ? mb_convert_encoding($user->getEmail(), "UTF-8") : " ",
                ];
                $sheet->fromArray($array, null, 'A' . $count);
                $count++;
            }

            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            $sheet->getColumnDimension('C')->setAutoSize(true);
            $sheet->getColumnDimension('D')->setAutoSize(true);
            $sheet->getColumnDimension('E')->setAutoSize(true);
            $sheet->getColumnDimension('F')->setAutoSize(true);
            $sheet->getColumnDimension('G')->setAutoSize(true);
            $sheet->getColumnDimension('H')->setAutoSize(true);
            $sheet->getColumnDimension('I')->setAutoSize(true);
            $sheet->getColumnDimension('J')->setAutoSize(true);

            // Write and send created spreadsheet
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');

            // This exit(); is required to prevent errors while opening the generated .xlsx
            exit();
        });

        // Puting headers on response and sending it
        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . $title . '.xlsx"');
        $streamedResponse->send();

        return;
    }
}
