<?php

declare(strict_types=1);

namespace App\Services\Curriculum;

use App\Models\ControlForm;
use App\Models\CurriculumDiscipline;
use App\Models\CurriculumPlan;
use App\Models\CurriculumPractice;
use App\Models\CurriculumSemester;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelCurriculumParserService
{
    public function parseFile(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);

        $meta = $this->parseTitleSheet($spreadsheet);
        $disciplines = $this->parsePlanSheet($spreadsheet);

        $yearStart = $meta['year_start'] ?? (int) date('Y');
        $practices = $this->parseGraphSheet($spreadsheet, $yearStart);

        return [
            'meta' => $meta,
            'disciplines' => $disciplines,
            'practices' => $practices,
            'total' => count($disciplines),
        ];
    }

    private function parseTitleSheet($spreadsheet): array
    {
        $sheet = null;
        foreach (['Титул', 'титул', 'Title'] as $name) {
            try {
                $sheet = $spreadsheet->getSheetByName($name);
            } catch (\Exception $e) {
            }
            if ($sheet) {
                break;
            }
        }

        if (! $sheet) {
            return [];
        }

        $data = [];
        $rows = $sheet->toArray(null, true, true, false);

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $cell) {
                $val = trim((string) ($cell ?? ''));
                if (str_contains($val, 'Год начала подготовки')) {
                    for ($c = $colIndex + 1; $c < $colIndex + 10; $c++) {
                        $v = trim((string) ($row[$c] ?? ''));
                        if (preg_match('/^\d{4}$/', $v)) {
                            $data['year_start'] = (int) $v;
                            break;
                        }
                    }
                }
                if (preg_match('/^\d{2}\.\d{2}\.\d{2}$/', $val)) {
                    $data['specialty_code'] = $val;
                }
                if (str_contains($val, 'Квалификация:') || str_contains($val, 'Квалификация ')) {
                    $q = trim(str_ireplace(['Квалификация:', 'Квалификация'], '', $val));
                    if ($q) {
                        $data['qualification'] = $q;
                    }
                }
            }
        }

        return $data;
    }

    private function parseGraphSheet($spreadsheet, int $yearStart): array
    {
        $sheet = null;
        foreach (['График', 'график', 'Graph', 'Календарный учебный график'] as $name) {
            try {
                $sheet = $spreadsheet->getSheetByName($name);
            } catch (\Exception $e) {
            }
            if ($sheet) {
                break;
            }
        }
        if (! $sheet) {
            return [];
        }

        $practices = [];
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        $weekRowIndex = -1;
        $weekColMap = [];

        for ($row = 1; $row <= 10; $row++) {
            $found1 = false;
            $found2 = false;
            for ($col = 1; $col <= $highestCol; $col++) {
                $val = trim((string) $sheet->getCell([$col, $row])->getCalculatedValue());
                if ($val == '1') {
                    $found1 = true;
                    $weekColMap[1] = $col;
                }
                if ($val == '2' && $found1) {
                    $found2 = true;
                    $weekColMap[2] = $col;
                }
                if ($found2 && is_numeric($val) && (int) $val <= 53) {
                    $weekColMap[(int) $val] = $col;
                }
            }
            if ($found1 && $found2) {
                $weekRowIndex = $row;
                break;
            }
        }

        if ($weekRowIndex === -1) {
            return [];
        }

        $currentCourse = 0;
        for ($row = $weekRowIndex + 1; $row <= $weekRowIndex + 40; $row++) {
            $courseStr = '';
            for ($c = 1; $c <= 3; $c++) {
                $courseStr .= trim((string) $sheet->getCell([$c, $row])->getCalculatedValue());
            }

            if (str_contains($courseStr, 'IV') || str_contains($courseStr, '4')) {
                $currentCourse = 4;
            } elseif (str_contains($courseStr, 'III') || str_contains($courseStr, '3')) {
                $currentCourse = 3;
            } elseif (str_contains($courseStr, 'II') || str_contains($courseStr, '2')) {
                $currentCourse = 2;
            } elseif (str_contains($courseStr, 'I') || str_contains($courseStr, '1')) {
                $currentCourse = 1;
            }

            if ($currentCourse > 0) {
                $courseYear = $yearStart + $currentCourse - 1;
                $baseDate = Carbon::create($courseYear, 9, 1)->startOfWeek(Carbon::MONDAY);

                foreach ($weekColMap as $weekNum => $colIdx) {
                    $cell = $sheet->getCell([$colIdx, $row]);
                    $val = $cell->getValue();

                    if ($cell->isInMergeRange()) {
                        $range = $cell->getMergeRange();
                        $firstCell = explode(':', $range)[0];
                        $val = $sheet->getCell($firstCell)->getValue();
                    }

                    $val = mb_strtoupper(trim((string) $val));

                    if (str_contains($val, 'У') || str_contains($val, 'П')) {
                        $type = str_contains($val, 'У') ? 'edu_practice' : 'prod_practice';

                        $startDate = $baseDate->copy()->addWeeks($weekNum - 1);
                        $endDate = $startDate->copy()->addDays(6);

                        $practices[] = [
                            'course_number' => $currentCourse,
                            'type' => $type,
                            'symbol' => str_replace([' ', "\n", "\r"], '', $val),
                            'week_number' => $weekNum,
                            'start_date' => $startDate->format('Y-m-d'),
                            'end_date' => $endDate->format('Y-m-d'),
                        ];
                    }
                }
                $currentCourse = 0;
            }
        }

        return $this->groupConsecutivePractices($practices);
    }

    private function groupConsecutivePractices(array $practices): array
    {
        if (empty($practices)) {
            return [];
        }

        $grouped = [];
        $current = $practices[0];

        for ($i = 1; $i < count($practices); $i++) {
            $next = $practices[$i];

            if (
                $current['course_number'] === $next['course_number'] &&
                $current['symbol'] === $next['symbol'] &&
                $next['week_number'] === $current['week_number'] + 1
            ) {

                $current['end_date'] = $next['end_date'];
                $current['week_number'] = $next['week_number'];
            } else {
                $grouped[] = $current;
                $current = $next;
            }
        }
        $grouped[] = $current;

        return array_map(function ($p) {
            unset($p['week_number']);

            return $p;
        }, $grouped);
    }

    private function parsePlanSheet($spreadsheet): array
    {
        $sheet = null;
        foreach (['План', 'план', 'Plan', 'ПланСвод'] as $name) {
            try {
                $sheet = $spreadsheet->getSheetByName($name);
            } catch (\Exception $e) {
            }
            if ($sheet) {
                break;
            }
        }

        if (! $sheet) {
            throw new \RuntimeException('Лист «План» не найден в файле.');
        }

        $rows = $sheet->toArray(null, true, true, false);
        $columnMap = $this->buildDynamicColumnMap($rows);
        $controlFormsCols = $this->findControlFormsColumns($rows);

        $disciplines = [];

        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex < 3) {
                continue;
            }

            $marker = trim((string) ($row[0] ?? ''));
            $code = trim((string) ($row[1] ?? ''));
            $name = trim((string) ($row[2] ?? ''));

            if ($name === '') {
                continue;
            }

            if (str_contains(mb_strtolower($name), 'наименование') || str_contains(mb_strtolower($name), 'итого')) {
                continue;
            }

            $isDiscipline = ($marker === '+' || $marker === '*' || $marker === '1');
            if (! $isDiscipline && preg_match('/[А-Яа-яA-Za-z0-9]+\.\d+/', $code)) {
                $isDiscipline = true;
            }

            if (! $isDiscipline) {
                continue;
            }

            $examSems = $this->parseControlSemesters((string) ($row[$controlFormsCols['exam']] ?? ''));
            $testSems = $this->parseControlSemesters((string) ($row[$controlFormsCols['test']] ?? ''));
            $diffTestSems = $this->parseControlSemesters((string) ($row[$controlFormsCols['diff_test']] ?? ''));
            $courseWorkSems = $this->parseControlSemesters((string) ($row[$controlFormsCols['cw']] ?? ''));
            $courseProjSems = $this->parseControlSemesters((string) ($row[$controlFormsCols['cp']] ?? ''));

            $cycle = $this->determineCycle($code);

            $discipline = [
                'code' => $code,
                'name' => $name,
                'cycle' => $cycle,
                'requires_lab' => false,
                'semesters' => [],
            ];

            $totalLabHours = 0;

            foreach ($columnMap as $semNum => $cols) {
                $lecH = $cols['lec'] !== null ? $this->safeInt($row[$cols['lec']] ?? null) : 0;
                $labH = $cols['lab'] !== null ? $this->safeInt($row[$cols['lab']] ?? null) : 0;
                $prH = $cols['prac'] !== null ? $this->safeInt($row[$cols['prac']] ?? null) : 0;
                $consH = $cols['cons'] !== null ? $this->safeInt($row[$cols['cons']] ?? null) : 0;
                $srH = $cols['sr'] !== null ? $this->safeInt($row[$cols['sr']] ?? null) : 0;
                $pattH = $cols['patt'] !== null ? $this->safeInt($row[$cols['patt']] ?? null) : 0;
                $krpH = $cols['krp'] !== null ? $this->safeInt($row[$cols['krp']] ?? null) : 0;

                $semTotal = $lecH + $labH + $prH + $consH + $srH + $pattH + $krpH;

                if ($semTotal === 0) {
                    continue;
                }

                $totalLabHours += $labH;
                $courseNum = (int) ceil($semNum / 2);
                $semInCourse = $semNum % 2 === 1 ? 1 : 2;

                $controlForm = null;
                if (in_array($semNum, $examSems)) {
                    $controlForm = 'exam';
                } elseif (in_array($semNum, $diffTestSems)) {
                    $controlForm = 'diff_test';
                } elseif (in_array($semNum, $testSems)) {
                    $controlForm = 'test';
                } elseif (in_array($semNum, $courseWorkSems)) {
                    $controlForm = 'course_work';
                } elseif (in_array($semNum, $courseProjSems)) {
                    $controlForm = 'course_project';
                }

                $discipline['semesters'][] = [
                    'semester_number' => $semNum,
                    'course_number' => $courseNum,
                    'semester_in_course' => $semInCourse,
                    'hours_lecture' => $lecH,
                    'hours_lab' => $labH,
                    'hours_practice' => $prH,
                    'hours_consultation' => $consH,
                    'hours_self_study' => $srH,
                    'exam_hours' => $pattH,
                    'course_work_hours' => $krpH,
                    'hours_total' => $semTotal,
                    'control_form_code' => $controlForm,
                ];
            }

            $discipline['requires_lab'] = $totalLabHours > 0;

            if (! empty($discipline['semesters'])) {
                $disciplines[] = $discipline;
            }
        }

        return $disciplines;
    }

    private function buildDynamicColumnMap(array $rows): array
    {
        $map = [];
        $semesterRowIndex = -1;
        $hoursRowIndex = -1;

        for ($i = 0; $i <= 4; $i++) {
            if (! isset($rows[$i])) {
                continue;
            }
            $rowStr = implode(' ', array_map('mb_strtolower', array_map('strval', $rows[$i])));
            if (str_contains($rowStr, 'семестр')) {
                $semesterRowIndex = $i;
            }
            if (str_contains($rowStr, 'лек') && str_contains($rowStr, 'пр')) {
                $hoursRowIndex = $i;
            }
        }

        if ($semesterRowIndex === -1 || $hoursRowIndex === -1) {
            throw new \RuntimeException('Не удалось найти заголовки семестров или типов часов в файле.');
        }

        $currentSemester = 0;
        foreach ($rows[$semesterRowIndex] as $colIndex => $cellValue) {
            $val = mb_strtolower(trim((string) $cellValue));

            if (str_contains($val, 'семестр')) {
                preg_match('/семестр\s*(\d+)/i', $val, $matches);
                if (! empty($matches[1])) {
                    $currentSemester = (int) $matches[1];
                    $map[$currentSemester] = ['lec' => null, 'lab' => null, 'prac' => null, 'cor' => null, 'krp' => null, 'cons' => null, 'sr' => null, 'patt' => null];
                }
            }

            if ($currentSemester > 0) {
                $hourType = mb_strtolower(trim((string) ($rows[$hoursRowIndex][$colIndex] ?? '')));

                if ($hourType === 'лек' || $hourType === 'л') {
                    $map[$currentSemester]['lec'] = $colIndex;
                } elseif (str_contains($hourType, 'лаб')) {
                    $map[$currentSemester]['lab'] = $colIndex;
                } elseif ($hourType === 'пр' || $hourType === 'п' || str_contains($hourType, 'прак')) {
                    $map[$currentSemester]['prac'] = $colIndex;
                } elseif (str_contains($hourType, 'ср') || str_contains($hourType, 'срс')) {
                    $map[$currentSemester]['sr'] = $colIndex;
                } elseif (str_contains($hourType, 'конс')) {
                    $map[$currentSemester]['cons'] = $colIndex;
                } elseif (str_contains($hourType, 'патт') || str_contains($hourType, 'атт') || str_contains($hourType, 'экз')) {
                    $map[$currentSemester]['patt'] = $colIndex;
                } elseif (str_contains($hourType, 'крп') || str_contains($hourType, 'кур')) {
                    $map[$currentSemester]['krp'] = $colIndex;
                } elseif (str_contains($hourType, 'кор') || $hourType === 'кр') {
                    $map[$currentSemester]['cor'] = $colIndex;
                }
            }
        }

        return array_filter($map, fn ($s) => $s['lec'] !== null || $s['prac'] !== null || $s['sr'] !== null);
    }

    private function findControlFormsColumns(array $rows): array
    {
        $cols = ['exam' => 3, 'test' => 4, 'diff_test' => 5, 'cw' => 6, 'cp' => 7];
        for ($i = 0; $i <= 3; $i++) {
            if (! isset($rows[$i])) {
                continue;
            }
            foreach ($rows[$i] as $idx => $val) {
                $v = mb_strtolower(trim((string) $val));
                if (str_contains($v, 'экзамен') && ! str_contains($v, 'квалиф')) {
                    $cols['exam'] = $idx;
                } elseif ($v === 'зачет' || $v === 'зачёт') {
                    $cols['test'] = $idx;
                } elseif (str_contains($v, 'зачет с оц') || str_contains($v, 'диф') || str_contains($v, 'зачёт с оц')) {
                    $cols['diff_test'] = $idx;
                } elseif ($v === 'кр' || str_contains($v, 'курсовая раб')) {
                    $cols['cw'] = $idx;
                } elseif ($v === 'кп' || $v === 'др' || str_contains($v, 'курсовой про')) {
                    $cols['cp'] = $idx;
                }
            }
        }

        return $cols;
    }

    private function parseControlSemesters(string $value): array
    {
        $value = trim($value);
        if ($value === '' || $value === '-' || $value === '0') {
            return [];
        }
        $clean = preg_replace('/[^0-9,\s]/', '', $value);
        if (str_contains($clean, ',') || str_contains($clean, ' ')) {
            return array_values(array_filter(array_map('intval', preg_split('/[,\s]+/', $clean)), fn ($n) => $n > 0 && $n <= 8));
        }
        if (strlen($clean) > 1) {
            return array_values(array_filter(array_map('intval', str_split($clean)), fn ($n) => $n > 0 && $n <= 8));
        }
        $n = (int) $clean;

        return $n > 0 && $n <= 8 ? [$n] : [];
    }

    private function determineCycle(string $code): string
    {
        $code = mb_strtoupper($code);
        if (str_starts_with($code, 'ОД')) {
            return 'ОД';
        }
        if (str_starts_with($code, 'ОГСЭ')) {
            return 'ОГСЭ';
        }
        if (str_starts_with($code, 'ЕН')) {
            return 'ЕН';
        }
        if (str_starts_with($code, 'ОП')) {
            return 'ОП';
        }
        if (str_starts_with($code, 'МДК')) {
            return 'МДК';
        }
        if (str_starts_with($code, 'ПМ')) {
            return 'ПМ';
        }
        if (str_starts_with($code, 'ФК') || str_starts_with($code, 'ФЦД')) {
            return 'ФК';
        }

        return 'ОП';
    }

    private function safeInt($value): int
    {
        if (is_null($value) || $value === '' || $value === '-') {
            return 0;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        $v = preg_replace('/[^0-9]/', '', (string) $value);

        return $v !== '' ? (int) $v : 0;
    }

    public function importToDatabase(array $parsedData, int $specialtyId, int $academicYearId, int $userId, string $filePath): array
    {
        $imported = 0;
        $failed = 0;
        $errors = [];

        DB::transaction(function () use ($parsedData, $specialtyId, $academicYearId, $userId, $filePath, &$imported, &$failed, &$errors) {
            $plan = CurriculumPlan::updateOrCreate(
                ['specialty_id' => $specialtyId, 'academic_year_id' => $academicYearId],
                [
                    'name' => 'Учебный план '.($parsedData['meta']['year_start'] ?? date('Y')),
                    'version' => date('Y').'-v1',
                    'excel_file_path' => $filePath,
                    'parsed_at' => now(),
                    'is_active' => true,
                    'created_by' => $userId,
                ]
            );

            CurriculumPractice::where('curriculum_plan_id', $plan->id)->delete();
            if (! empty($parsedData['practices'])) {
                foreach ($parsedData['practices'] as $practice) {
                    CurriculumPractice::create([
                        'curriculum_plan_id' => $plan->id,
                        'course_number' => $practice['course_number'],
                        'type' => $practice['type'],
                        'symbol' => $practice['symbol'],
                        'start_date' => $practice['start_date'],
                        'end_date' => $practice['end_date'],
                    ]);
                }
            }

            $controlForms = ControlForm::pluck('id', 'code')->toArray();

            foreach ($parsedData['disciplines'] as $disciplineData) {
                try {
                    $discipline = CurriculumDiscipline::updateOrCreate(
                        ['curriculum_plan_id' => $plan->id, 'code' => $disciplineData['code']],
                        [
                            'name' => $disciplineData['name'],
                            'short_name' => mb_substr($disciplineData['name'], 0, 50),
                            'cycle' => $disciplineData['cycle'],
                            'discipline_type' => $this->mapDisciplineType($disciplineData['code']),
                            'requires_lab' => $disciplineData['requires_lab'],
                            'sort_order' => $imported + 1,
                        ]
                    );

                    foreach ($disciplineData['semesters'] as $semData) {
                        $controlFormId = $semData['control_form_code'] ? ($controlForms[$semData['control_form_code']] ?? null) : null;
                        CurriculumSemester::updateOrCreate(
                            ['discipline_id' => $discipline->id, 'semester_number' => $semData['semester_number']],
                            [
                                'course_number' => $semData['course_number'],
                                'semester_in_course' => $semData['semester_in_course'],
                                'hours_total' => $semData['hours_total'],
                                'hours_lecture' => $semData['hours_lecture'],
                                'hours_practice' => $semData['hours_practice'],
                                'hours_lab' => $semData['hours_lab'],
                                'hours_self_study' => $semData['hours_self_study'],
                                'hours_consultation' => $semData['hours_consultation'],
                                'control_form_id' => $controlFormId,
                                'exam_hours' => $semData['exam_hours'],
                                'course_project_hours' => $semData['course_work_hours'],
                            ]
                        );
                    }
                    $imported++;
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = "Ошибка в дисциплине {$disciplineData['code']}: ".$e->getMessage();
                }
            }
        });

        return ['imported' => $imported, 'failed' => $failed, 'errors' => $errors];
    }

    private function mapDisciplineType(string $code): string
    {
        $code = mb_strtoupper($code);
        if (str_starts_with($code, 'ПМ') || str_starts_with($code, 'МДК')) {
            return 'professional_module';
        }
        if (str_starts_with($code, 'ФК') || str_starts_with($code, 'ФЦД')) {
            return 'optional';
        }

        return 'theoretical';
    }
}
