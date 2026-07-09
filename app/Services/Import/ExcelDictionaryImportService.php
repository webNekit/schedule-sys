<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\EducationLevel;
use App\Models\Group;
use App\Models\Specialty;
use App\Models\Teacher;
use App\Models\TeacherPosition;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelDictionaryImportService
{
    public function importTeachers(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $imported = 0;
        $errors = [];

        DB::transaction(function () use ($rows, &$imported, &$errors) {
            $departments = Department::all()->keyBy('short_name');
            $departmentsByName = Department::all()->keyBy('name');
            $positions = TeacherPosition::all()->keyBy('name');

            foreach ($rows as $index => $row) {
                if ($index === 0) {
                    continue;
                } // Пропускаем заголовки

                $lastName = trim((string) ($row[0] ?? ''));
                $firstName = trim((string) ($row[1] ?? ''));

                if ($lastName === '' || $firstName === '') {
                    continue;
                }

                $middleName = trim((string) ($row[2] ?? ''));
                $deptStr = trim((string) ($row[3] ?? ''));
                $posStr = trim((string) ($row[4] ?? ''));
                $rateStr = trim((string) ($row[5] ?? '1.0'));
                $email = trim((string) ($row[6] ?? ''));
                $phone = trim((string) ($row[7] ?? ''));

                $dept = $departments->get($deptStr) ?? $departmentsByName->get($deptStr);
                $pos = $positions->get($posStr);

                if (! $dept) {
                    $errors[] = "Строка $index: Кафедра '$deptStr' не найдена.";

                    continue;
                }
                if (! $pos) {
                    $errors[] = "Строка $index: Должность '$posStr' не найдена.";

                    continue;
                }

                $shortName = $lastName.' '.mb_substr($firstName, 0, 1).'.'.($middleName ? mb_substr($middleName, 0, 1).'.' : '');
                $fullName = trim("$lastName $firstName $middleName");

                Teacher::updateOrCreate(
                    [
                        'last_name' => $lastName,
                        'first_name' => $firstName,
                        'middle_name' => $middleName ?: null,
                    ],
                    [
                        'full_name' => $fullName,
                        'short_name' => $shortName,
                        'department_id' => $dept->id,
                        'position_id' => $pos->id,
                        'rate' => (float) str_replace(',', '.', $rateStr),
                        'email' => $email ?: null,
                        'phone' => $phone ?: null,
                        'is_active' => true,
                    ]
                );

                $imported++;
            }
        });

        return ['imported' => $imported, 'errors' => $errors];
    }

    public function importGroups(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $imported = 0;
        $errors = [];

        DB::transaction(function () use ($rows, &$imported, &$errors) {
            $specialties = Specialty::all()->keyBy('code');
            $departments = Department::all()->keyBy('short_name');
            $academicYears = AcademicYear::all()->keyBy('name');
            $currentYear = AcademicYear::where('is_current', true)->first();

            foreach ($rows as $index => $row) {
                if ($index === 0) {
                    continue;
                }

                $name = trim((string) ($row[0] ?? ''));
                if ($name === '') {
                    continue;
                }

                $specCode = trim((string) ($row[1] ?? ''));
                $deptStr = trim((string) ($row[2] ?? ''));
                $yearStr = trim((string) ($row[3] ?? ''));
                $course = (int) trim((string) ($row[4] ?? '1'));
                $studentsCount = (int) trim((string) ($row[5] ?? '25'));
                $shift = (int) trim((string) ($row[6] ?? '1'));

                $spec = $specialties->get($specCode);
                $dept = $departments->get($deptStr);
                $year = $academicYears->get($yearStr) ?? $currentYear;

                if (! $spec) {
                    $errors[] = "Строка $index: Специальность с кодом '$specCode' не найдена.";

                    continue;
                }
                if (! $dept) {
                    $dept = $spec->department; // Фолбэк на кафедру специальности
                }
                if (! $year) {
                    $errors[] = "Строка $index: Учебный год не определен.";

                    continue;
                }

                Group::updateOrCreate(
                    ['name' => $name],
                    [
                        'short_name' => $name,
                        'specialty_id' => $spec->id,
                        'department_id' => $dept->id,
                        'academic_year_id' => $year->id,
                        'current_course' => $course,
                        'students_count' => $studentsCount,
                        'shift' => $shift,
                        'is_active' => true,
                    ]
                );

                $imported++;
            }
        });

        return ['imported' => $imported, 'errors' => $errors];
    }

    public function importSpecialties(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $imported = 0;
        $errors = [];

        DB::transaction(function () use ($rows, &$imported, &$errors) {
            $departments = Department::all()->keyBy('short_name');
            $levels = EducationLevel::all()->keyBy('name');

            foreach ($rows as $index => $row) {
                if ($index === 0) {
                    continue;
                }

                $code = trim((string) ($row[0] ?? ''));
                if ($code === '') {
                    continue;
                }

                $name = trim((string) ($row[1] ?? ''));
                $shortName = trim((string) ($row[2] ?? ''));
                $qual = trim((string) ($row[3] ?? ''));
                $deptStr = trim((string) ($row[4] ?? ''));
                $levelStr = trim((string) ($row[5] ?? ''));
                $years9 = str_replace('.', ',', trim((string) ($row[6] ?? '4')));
                $years11 = str_replace('.', ',', trim((string) ($row[7] ?? '3')));

                $dept = $departments->get($deptStr);
                $level = $levels->get($levelStr) ?? $levels->first();

                if (! $dept) {
                    $errors[] = "Строка $index: Кафедра '$deptStr' не найдена.";

                    continue;
                }

                Specialty::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $name,
                        'short_name' => $shortName ?: null,
                        'qualification' => $qual ?: null,
                        'department_id' => $dept->id,
                        'education_level_id' => $level?->id,
                        'study_years' => (int) $years9,
                        'study_years_9' => $years9,
                        'study_years_11' => $years11,
                        'is_active' => true,
                    ]
                );

                $imported++;
            }
        });

        return ['imported' => $imported, 'errors' => $errors];
    }
}
