<?php

declare(strict_types=1);

namespace App\Services\Curriculum;

use App\DTOs\ImportResult;
use App\DTOs\ParsedCurriculum;
use App\Models\CurriculumDiscipline;
use App\Models\CurriculumPlan;
use App\Models\CurriculumSemester;
use SimpleXMLElement;

class CurriculumXmlParserService
{
    public function parse(string $xmlPath): ParsedCurriculum
    {
        $encoding = config('curriculum.xml_encoding', 'windows-1251');

        $xmlContent = file_get_contents($xmlPath);

        if ($xmlContent === false) {
            return new ParsedCurriculum(
                specialty: [],
                disciplines: [],
                semesters: [],
                errors: ["Unable to read XML file: {$xmlPath}"],
            );
        }

        $converted = mb_convert_encoding($xmlContent, 'UTF-8', $encoding);

        $xml = simplexml_load_string($converted);

        if ($xml === false) {
            return new ParsedCurriculum(
                specialty: [],
                disciplines: [],
                semesters: [],
                errors: ['Invalid XML structure.'],
            );
        }

        $specialty = $this->extractSpecialty($xml);
        $disciplines = $this->extractDisciplines($xml);
        $semesters = $this->extractSemesters($xml);
        $warnings = [];

        if ($specialty === []) {
            $warnings[] = 'No <Speciality> tag found in XML.';
        }

        return new ParsedCurriculum(
            specialty: $specialty,
            disciplines: $disciplines,
            semesters: $semesters,
            warnings: $warnings,
        );
    }

    public function validateXml(string $xmlPath): array
    {
        $errors = [];

        if (! file_exists($xmlPath)) {
            return ['valid' => false, 'errors' => ["File not found: {$xmlPath}"]];
        }

        if (! is_readable($xmlPath)) {
            return ['valid' => false, 'errors' => ["File is not readable: {$xmlPath}"]];
        }

        $encoding = config('curriculum.xml_encoding', 'windows-1251');
        $xmlContent = file_get_contents($xmlPath);

        if ($xmlContent === false) {
            return ['valid' => false, 'errors' => ['Unable to read file content.']];
        }

        $converted = mb_convert_encoding($xmlContent, 'UTF-8', $encoding);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($converted);

        if ($xml === false) {
            foreach (libxml_get_errors() as $error) {
                $errors[] = trim($error->message);
            }
            libxml_clear_errors();

            return ['valid' => false, 'errors' => $errors];
        }

        if (! isset($xml->Speciality)) {
            $errors[] = 'Missing required <Speciality> tag.';
        }

        if (! isset($xml->SemesterTable)) {
            $errors[] = 'Missing required <SemesterTable> tag.';
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    public function importToPlan(ParsedCurriculum $data, CurriculumPlan $plan): ImportResult
    {
        if ($data->hasErrors()) {
            return ImportResult::fail(implode('; ', $data->errors));
        }

        $disciplinesImported = 0;
        $semestersImported = 0;

        foreach ($data->disciplines as $disciplineData) {
            $discipline = CurriculumDiscipline::create([
                'curriculum_plan_id' => $plan->id,
                'name' => $disciplineData['name'] ?? '',
                'short_name' => $disciplineData['short_name'] ?? '',
                'code' => $disciplineData['code'] ?? '',
                'cycle' => $disciplineData['cycle'] ?? null,
                'discipline_type' => $disciplineData['discipline_type'] ?? null,
                'is_federal' => $disciplineData['is_federal'] ?? false,
                'sort_order' => $disciplineData['sort_order'] ?? 0,
            ]);

            $disciplinesImported++;
        }

        foreach ($data->semesters as $semesterData) {
            $discipline = CurriculumDiscipline::where('curriculum_plan_id', $plan->id)
                ->where('code', $semesterData['discipline_code'] ?? '')
                ->first();

            if ($discipline === null) {
                continue;
            }

            CurriculumSemester::create([
                'discipline_id' => $discipline->id,
                'course_number' => $semesterData['course_number'] ?? 1,
                'semester_number' => $semesterData['semester_number'] ?? 1,
                'semester_in_course' => $semesterData['semester_in_course'] ?? 1,
                'hours_total' => $semesterData['hours_total'] ?? 0,
                'hours_lecture' => $semesterData['hours_lecture'] ?? 0,
                'hours_practice' => $semesterData['hours_practice'] ?? 0,
                'hours_lab' => $semesterData['hours_lab'] ?? 0,
                'hours_self_study' => $semesterData['hours_self_study'] ?? 0,
                'hours_consultation' => $semesterData['hours_consultation'] ?? 0,
                'hours_per_week' => $semesterData['hours_per_week'] ?? 0,
                'weeks_count' => $semesterData['weeks_count'] ?? 0,
            ]);

            $semestersImported++;
        }

        $totalHours = array_reduce($data->semesters, fn (int $carry, array $s) => $carry + ($s['hours_total'] ?? 0), 0);
        $contactHours = array_reduce(
            $data->semesters,
            fn (int $carry, array $s) => $carry + ($s['hours_lecture'] ?? 0) + ($s['hours_practice'] ?? 0) + ($s['hours_lab'] ?? 0),
            0,
        );

        $plan->update([
            'total_hours' => $totalHours,
            'contact_hours' => $contactHours,
            'parsed_at' => now(),
        ]);

        return ImportResult::success(
            disciplinesImported: $disciplinesImported,
            semestersImported: $semestersImported,
            curriculumPlanId: $plan->id,
        );
    }

    private function extractSpecialty(SimpleXMLElement $xml): array
    {
        $specialty = $xml->Speciality ?? null;

        if ($specialty === null) {
            return [];
        }

        return [
            'code' => (string) ($specialty->Code ?? $specialty->SpecialityCode ?? ''),
            'name' => (string) ($specialty->Name ?? $specialty->SpecialityName ?? ''),
            'studyYears' => (int) ($specialty->StudyYears ?? $specialty->Duration ?? 0),
            'studyMonths' => (int) ($specialty->StudyMonths ?? 0),
        ];
    }

    private function extractDisciplines(SimpleXMLElement $xml): array
    {
        $disciplines = [];

        $semesterTable = $xml->SemesterTable ?? null;

        if ($semesterTable === null) {
            return [];
        }

        foreach ($semesterTable->Discipline ?? [] as $disciplineNode) {
            $disciplines[] = [
                'code' => (string) ($disciplineNode->Code ?? $disciplineNode->DisciplineCode ?? ''),
                'name' => (string) ($disciplineNode->Name ?? $disciplineNode->DisciplineName ?? ''),
                'short_name' => (string) ($disciplineNode->ShortName ?? ''),
                'cycle' => (string) ($disciplineNode->Cycle ?? ''),
                'discipline_type' => (string) ($disciplineNode->Type ?? $disciplineNode->DisciplineType ?? ''),
                'is_federal' => strtolower((string) ($disciplineNode->Federal ?? '')) === 'yes',
                'sort_order' => (int) ($disciplineNode->Order ?? $disciplineNode->SortOrder ?? 0),
            ];
        }

        return $disciplines;
    }

    private function extractSemesters(SimpleXMLElement $xml): array
    {
        $semesters = [];

        $semesterTable = $xml->SemesterTable ?? null;

        if ($semesterTable === null) {
            return [];
        }

        $index = 0;

        foreach ($semesterTable->Discipline ?? [] as $disciplineNode) {
            $disciplineCode = (string) ($disciplineNode->Code ?? $disciplineNode->DisciplineCode ?? '');

            foreach ($disciplineNode->Semester ?? $disciplineNode->Semesters->Semester ?? [] as $semesterNode) {
                $semesters[] = [
                    'discipline_code' => $disciplineCode,
                    'course_number' => (int) ($semesterNode->Course ?? $semesterNode->CourseNumber ?? 1),
                    'semester_number' => (int) ($semesterNode->Number ?? $semesterNode->SemesterNumber ?? 1),
                    'semester_in_course' => (int) ($semesterNode->SemesterInCourse ?? 1),
                    'hours_total' => (int) ($semesterNode->TotalHours ?? $semesterNode->HoursTotal ?? 0),
                    'hours_lecture' => (int) ($semesterNode->LectureHours ?? $semesterNode->HoursLecture ?? 0),
                    'hours_practice' => (int) ($semesterNode->PracticeHours ?? $semesterNode->HoursPractice ?? 0),
                    'hours_lab' => (int) ($semesterNode->LabHours ?? $semesterNode->HoursLab ?? 0),
                    'hours_self_study' => (int) ($semesterNode->SelfStudyHours ?? $semesterNode->HoursSelfStudy ?? 0),
                    'hours_consultation' => (int) ($semesterNode->ConsultationHours ?? $semesterNode->HoursConsultation ?? 0),
                    'hours_per_week' => (float) ($semesterNode->HoursPerWeek ?? 0),
                    'weeks_count' => (int) ($semesterNode->Weeks ?? $semesterNode->WeeksCount ?? 0),
                ];
            }

            $index++;
        }

        return $semesters;
    }
}
