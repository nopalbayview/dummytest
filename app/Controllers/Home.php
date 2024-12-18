<?php

namespace App\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Home extends BaseController
{
    public function index(): string
    {
        return view('welcome_message');
    }

    public function excelDump()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Attendance Summary')
            ->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells('A1:E1');

        $sheet->setCellValue('A2', 'No')
            ->setCellValue('B2', 'NIK')
            ->setCellValue('C2', 'Employee Name')
            ->setCellValue('D2', 'Attendance Status')
            ->setCellValue('E2', 'Remarks');

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(30);

        $borderArray = [
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN],
                'bottom' => ['borderStyle' => Border::BORDER_THIN],
                'left' => ['borderStyle' => Border::BORDER_THIN],
                'right' => ['borderStyle' => Border::BORDER_THIN],
            ]
        ];
        $sheet->getStyle('A2:E2')->applyFromArray($borderArray);
        $sheet->getStyle('A2:E2')->getFont()->setBold(true);

        $data = [
            [1, '123456', 'John Doe', 'Present', 'On time'],
            [2, '654321', 'Jane Smith', 'Absent', 'Sick Leave'],
            [3, '789123', 'Jim Brown', 'Present', 'Late'],
        ];

        $row = 3;
        foreach ($data as $index => $rowData) {
            $sheet->setCellValue("A$row", $rowData[0])
                ->setCellValue("B$row", $rowData[1])
                ->setCellValue("C$row", $rowData[2])
                ->setCellValue("D$row", $rowData[3])
                ->setCellValue("E$row", $rowData[4]);

            $sheet->getStyle("A$row:E$row")->applyFromArray($borderArray);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'attendance_summary.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
