<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Models\Department;
use App\Models\Group;
use App\Models\ScheduleLesson;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExcelExportService
{
    public function exportScheduleByDepartment(int $departmentId, Carbon $dateFrom, Carbon $dateTo, int $versionId): string
    {
        $department = Department::find($departmentId);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $dateStr = $dateFrom->format('d.m.Y');

        // 1. Желтая шапка
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', "Кафедра " . mb_strtoupper($department->name));
        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2', "Расписание учебных занятий на {$dateStr} г.");

        $styleHeader = [
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFFF00']],
        ];
        $sheet->getStyle('A1:D2')->applyFromArray($styleHeader);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // 2. Заголовки колонок
        $row = 3;
        $sheet->setCellValue("A{$row}", 'ГРУППА');
        $sheet->setCellValue("B{$row}", 'Дисц/МДК');
        $sheet->setCellValue("C{$row}", 'Преподаватель');
        $sheet->setCellValue("D{$row}", 'Кабинет');

        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '00B050']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // 3. Данные по группам
        $groups = Group::where('department_id', $departmentId)->orderBy('name')->get();
        $row++;

        $greenStyle = [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '00B050']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        foreach ($groups as $group) {
            $lessons = ScheduleLesson::with(['discipline', 'teacher', 'room'])
                ->where('version_id', $versionId)
                ->where('group_id', $group->id)
                ->where('date', $dateFrom->format('Y-m-d'))
                ->where('status', '!=', 'cancelled')
                ->orderBy('lesson_number')
                ->get();

            $startRow = $row;

            if ($group->isOnPractice($dateFrom)) {
                $sheet->setCellValue("B{$row}", 'Практика');
                $sheet->mergeCells("B{$row}:D{$row}");
                $row++;
            } elseif ($lessons->isEmpty()) {
                $sheet->setCellValue("B{$row}", 'Нет занятий');
                $row++;
            } else {
                foreach ($lessons as $lesson) {
                    $sheet->setCellValue("B{$row}", $lesson->discipline?->short_name ?? $lesson->discipline?->name);
                    $sheet->setCellValue("C{$row}", $lesson->teacher?->short_name);
                    $sheet->setCellValue("D{$row}", $lesson->room?->number);
                    $row++;
                }
            }

            // Объединяем ячейку с названием группы
            $endRow = $row - 1;
            $sheet->mergeCells("A{$startRow}:A{$endRow}");
            $sheet->setCellValue("A{$startRow}", "ГРУППА\n{$group->name}");
            $sheet->getStyle("A{$startRow}:A{$endRow}")->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Применяем зеленый фон и границы
            $sheet->getStyle("A{$startRow}:D{$endRow}")->applyFromArray($greenStyle);
        }

        // Автоширина
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setWidth(15);

        $fileName = "schedule_{$department->short_name}_{$dateStr}.xlsx";
        $filePath = storage_path("app/public/exports/{$fileName}");

        if (!file_exists(storage_path('app/public/exports'))) {
            mkdir(storage_path('app/public/exports'), 0777, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return "/storage/exports/{$fileName}";
    }
}