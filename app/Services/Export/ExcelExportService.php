<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Models\Department;
use App\Models\Group;
use App\Models\ScheduleLesson;
use App\Models\ScheduleVersion;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelExportService
{
    private const COLS_PER_GROUP = 4;   // name | disc | teacher | room

    private const GROUPS_PER_ROW = 3;   // 3 groups side by side

    private const MIN_ROWS_PER_BLOCK = 7; // minimum rows per horizontal group block

    // ── Основной экспорт: сетка по кафедрам ───────────────────────────────

    /**
     * Экспорт расписания на конкретную дату: 1 лист = 1 кафедра.
     * Формат: 3 группы в строке, под каждой — Дис/МДК, Преподаватель, Кабинет.
     */
    public function exportDepartmentGridByDate(int $versionId, string $date): string
    {
        ScheduleVersion::findOrFail($versionId);
        $carbonDate = Carbon::parse($date);
        $dateLabel = $carbonDate->translatedFormat('d.m.Y');

        $departments = Department::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        $sheetIndex = 0;
        foreach ($departments as $department) {
            $groups = Group::where('department_id', $department->id)
                ->where('is_active', true)
                ->orderBy('current_course')
                ->orderBy('name')
                ->get();

            if ($groups->isEmpty()) {
                continue;
            }

            $sheetTitle = mb_substr($department->short_name ?? $department->name, 0, 31);
            $worksheet = new Worksheet($spreadsheet, $sheetTitle);
            $spreadsheet->addSheet($worksheet, $sheetIndex++);

            $this->fillDepartmentSheet($worksheet, $department->name, $groups, $versionId, $carbonDate, $dateLabel);
        }

        if ($spreadsheet->getSheetCount() === 0) {
            $worksheet = new Worksheet($spreadsheet, 'Расписание');
            $spreadsheet->addSheet($worksheet, 0);
            $worksheet->setCellValue('A1', 'Нет данных для экспорта');
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $this->saveSpreadsheet($spreadsheet, "schedule_{$carbonDate->format('Y-m-d')}.xlsx");
    }

    private function fillDepartmentSheet(
        Worksheet $sheet,
        string $departmentName,
        Collection $groups,
        int $versionId,
        Carbon $date,
        string $dateLabel
    ): void {
        $totalColumns = self::COLS_PER_GROUP * self::GROUPS_PER_ROW;
        $lastColLetter = Coordinate::stringFromColumnIndex($totalColumns);

        // ── Шапка ────────────────────────────────────────────────────────
        $sheet->mergeCells("A1:{$lastColLetter}1");
        $sheet->setCellValue('A1', 'Кафедра '.mb_strtoupper($departmentName));

        $sheet->mergeCells("A2:{$lastColLetter}2");
        $sheet->setCellValue('A2', "Расписание учебных занятий на {$dateLabel} г.");

        $sheet->getStyle("A1:{$lastColLetter}2")->applyFromArray([
            'font' => ['bold' => true, 'size' => 13],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFFF00']],
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(18);

        // ── Заголовки колонок (строка 3) ──────────────────────────────────
        $colHeaderStyle = [
            'font' => ['bold' => true, 'size' => 9],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '92D050']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        for ($g = 0; $g < self::GROUPS_PER_ROW; $g++) {
            $baseCol = $g * self::COLS_PER_GROUP + 1;

            $nameColLetter = Coordinate::stringFromColumnIndex($baseCol);
            $discColLetter = Coordinate::stringFromColumnIndex($baseCol + 1);
            $teacherColLetter = Coordinate::stringFromColumnIndex($baseCol + 2);
            $roomColLetter = Coordinate::stringFromColumnIndex($baseCol + 3);

            $sheet->setCellValue("{$nameColLetter}3", '');
            $sheet->setCellValue("{$discColLetter}3", 'Дис/МДК');
            $sheet->setCellValue("{$teacherColLetter}3", 'Преподаватель');
            $sheet->setCellValue("{$roomColLetter}3", 'Кабинет');

            $sheet->getColumnDimension($nameColLetter)->setWidth(10);
            $sheet->getColumnDimension($discColLetter)->setWidth(22);
            $sheet->getColumnDimension($teacherColLetter)->setWidth(16);
            $sheet->getColumnDimension($roomColLetter)->setWidth(13);
        }

        $sheet->getStyle("A3:{$lastColLetter}3")->applyFromArray($colHeaderStyle);
        $sheet->getRowDimension(3)->setRowHeight(18);

        // ── Данные групп, по 3 в строке ───────────────────────────────────
        // Блок всегда 7 строк — по одной на каждую возможную пару (1–7).
        // Пара ставится в строку (currentRow + lessonNumber - 1), не последовательно.
        $blockHeight = self::MIN_ROWS_PER_BLOCK; // всегда 7
        $currentRow = 4;
        $groupChunks = $groups->chunk(self::GROUPS_PER_ROW);

        foreach ($groupChunks as $chunk) {
            $chunkGroups = $chunk->values();

            // Загружаем занятия для всех групп блока
            $lessonsByGroup = [];
            foreach ($chunkGroups as $group) {
                $lessonsByGroup[$group->id] = $group->isOnPractice($date)
                    ? collect()
                    : ScheduleLesson::with(['discipline', 'teacher', 'room.building'])
                        ->where('version_id', $versionId)
                        ->where('group_id', $group->id)
                        ->whereDate('date', $date->format('Y-m-d'))
                        ->where('status', '!=', 'cancelled')
                        ->orderBy('lesson_number')
                        ->get();
            }

            $blockEndRow = $currentRow + $blockHeight - 1;

            // Рендерим каждую группу в своей колонке
            for ($g = 0; $g < self::GROUPS_PER_ROW; $g++) {
                $group = $chunkGroups[$g] ?? null;
                $baseCol = $g * self::COLS_PER_GROUP + 1;

                $nameColLetter = Coordinate::stringFromColumnIndex($baseCol);
                $discColLetter = Coordinate::stringFromColumnIndex($baseCol + 1);
                $teacherColLetter = Coordinate::stringFromColumnIndex($baseCol + 2);
                $roomColLetter = Coordinate::stringFromColumnIndex($baseCol + 3);
                $blockRange = "{$nameColLetter}{$currentRow}:{$roomColLetter}{$blockEndRow}";

                if ($group) {
                    // Название группы — объединено по всем 7 строкам
                    $sheet->mergeCells("{$nameColLetter}{$currentRow}:{$nameColLetter}{$blockEndRow}");
                    $sheet->setCellValue("{$nameColLetter}{$currentRow}", "ГРУППА\n{$group->name}");
                    $sheet->getStyle("{$nameColLetter}{$currentRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 8],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E8F5E9']],
                    ]);

                    if ($group->isOnPractice($date)) {
                        $sheet->mergeCells("{$discColLetter}{$currentRow}:{$roomColLetter}{$blockEndRow}");
                        $sheet->setCellValue("{$discColLetter}{$currentRow}", 'Практика');
                        $sheet->getStyle("{$discColLetter}{$currentRow}")->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                            ->setVertical(Alignment::VERTICAL_CENTER);
                    } else {
                        $lessons = $lessonsByGroup[$group->id] ?? collect();

                        foreach ($lessons as $lesson) {
                            // Строка = currentRow + (номер пары - 1), т.е. пара 1 → строка 0, пара 3 → строка 2
                            $offset = max(0, ($lesson->lesson_number ?? 1) - 1);
                            $lessonRow = $currentRow + $offset;

                            // Дисциплина: short_name или обрезанное name
                            $discName = $lesson->discipline?->short_name
                                ?? $lesson->discipline?->name ?? '';
                            if (mb_strlen($discName) > 25) {
                                $discName = mb_substr($discName, 0, 23).'…';
                            }

                            // Преподаватель
                            $teacher = $lesson->teacher?->short_name ?? '';

                            // Кабинет + корпус
                            $roomNum = $lesson->room?->number ?? '';
                            $buildingName = $lesson->room?->building?->short_name
                                ?? $lesson->room?->building?->name ?? '';
                            $roomCell = $buildingName
                                ? "{$roomNum}\n({$buildingName})"
                                : $roomNum;

                            $sheet->setCellValue("{$discColLetter}{$lessonRow}", $discName);
                            $sheet->setCellValue("{$teacherColLetter}{$lessonRow}", $teacher);
                            $sheet->setCellValue("{$roomColLetter}{$lessonRow}", $roomCell);

                            $sheet->getStyle("{$roomColLetter}{$lessonRow}")
                                ->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
                            $sheet->getStyle("{$discColLetter}{$lessonRow}:{$teacherColLetter}{$lessonRow}")
                                ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                        }
                    }
                }

                // Границы на весь блок
                $sheet->getStyle($blockRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'AAAAAA']],
                        'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '333333']],
                    ],
                    'font' => ['size' => 9],
                ]);
            }

            // Высота строк блока — чуть выше для комфортного чтения
            for ($r = $currentRow; $r <= $blockEndRow; $r++) {
                $sheet->getRowDimension($r)->setRowHeight(18);
            }

            $currentRow = $blockEndRow + 1;

            // Разделитель между блоками
            $sheet->getRowDimension($currentRow)->setRowHeight(4);
            $currentRow++;
        }

        // ── Параметры страницы ────────────────────────────────────────────
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.5)->setRight(0.5);
    }

    // ── Вспомогательные ───────────────────────────────────────────────────

    private function saveSpreadsheet(Spreadsheet $spreadsheet, string $fileName): string
    {
        $dir = storage_path('app/public/exports');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filePath = "{$dir}/{$fileName}";
        (new Xlsx($spreadsheet))->save($filePath);

        return $filePath;
    }

    // ── Устаревшие методы (оставлены для совместимости) ──────────────────

    public function exportScheduleByDepartment(int $departmentId, Carbon $dateFrom, Carbon $dateTo, int $versionId): string
    {
        return $this->exportDepartmentGridByDate($versionId, $dateFrom->format('Y-m-d'));
    }

    public function exportFullSchedule(int $versionId, Carbon $weekStart): string
    {
        $version = ScheduleVersion::findOrFail($versionId);
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $weekEnd = $weekStart->copy()->addDays(5);
        $dateStr = $weekStart->format('d.m.Y').'-'.$weekEnd->format('d.m.Y');

        $sheet->mergeCells('A1:K1');
        $sheet->setCellValue('A1', "РАСПИСАНИЕ ЗАНЯТИЙ НА ПЕРИОД {$dateStr}");
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E2EFDA']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $row = 3;
        $headers = ['Кафедра', 'Группа', 'Дата', 'Пара 1', 'Пара 2', 'Пара 3', 'Пара 4', 'Пара 5', 'Пара 6', 'Пара 7', 'Пара 8'];
        foreach ($headers as $col => $text) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1).$row, $text);
        }
        $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D9D9D9']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $groups = Group::with('department')->where('is_active', true)->orderBy('department_id')->orderBy('name')->get();
        $row++;

        $days = [];
        for ($i = 0; $i < 6; $i++) {
            $days[] = $weekStart->copy()->addDays($i);
        }

        foreach ($groups as $group) {
            $groupStartRow = $row;
            foreach ($days as $date) {
                $sheet->setCellValue("A{$row}", $group->department?->short_name ?? $group->department?->name);
                $sheet->setCellValue("B{$row}", $group->name);
                $sheet->setCellValue("C{$row}", $date->translatedFormat('D (d.m.Y)'));

                if ($group->isOnPractice($date)) {
                    $sheet->mergeCells("D{$row}:K{$row}");
                    $sheet->setCellValue("D{$row}", 'ПРАКТИКА');
                    $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                } else {
                    $lessons = ScheduleLesson::with(['discipline', 'teacher', 'room'])
                        ->where('version_id', $versionId)
                        ->where('group_id', $group->id)
                        ->whereDate('date', $date->format('Y-m-d'))
                        ->where('status', '!=', 'cancelled')
                        ->get()
                        ->keyBy('lesson_number');

                    for ($ln = 1; $ln <= 8; $ln++) {
                        $colLetter = Coordinate::stringFromColumnIndex($ln + 3);
                        if (isset($lessons[$ln])) {
                            $l = $lessons[$ln];
                            $disc = $l->discipline?->short_name ?? $l->discipline?->name;
                            $teacher = $l->teacher?->short_name;
                            $room = $l->room?->number;
                            $sheet->setCellValue("{$colLetter}{$row}", "{$disc}\n{$teacher}\nкаб. {$room}");
                            $sheet->getStyle("{$colLetter}{$row}")->getAlignment()->setWrapText(true);
                        }
                    }
                }
                $row++;
            }

            $groupEndRow = $row - 1;
            $sheet->mergeCells("A{$groupStartRow}:A{$groupEndRow}");
            $sheet->mergeCells("B{$groupStartRow}:B{$groupEndRow}");
            $sheet->getStyle("A{$groupStartRow}:B{$groupEndRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }

        $sheet->getStyle('A3:K'.($row - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        foreach (range('D', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(false)->setWidth(25);
        }

        return $this->saveSpreadsheet($spreadsheet, "schedule_full_{$dateStr}.xlsx");
    }
}
