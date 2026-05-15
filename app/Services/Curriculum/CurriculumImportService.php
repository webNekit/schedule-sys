<?php

declare(strict_types=1);

namespace App\Services\Curriculum;

use App\DTOs\ImportResult;
use App\DTOs\ParsedCurriculum;
use App\Models\AcademicYear;
use App\Models\CurriculumPlan;
use App\Models\Specialty;

class CurriculumImportService
{
    public function __construct(
        private readonly CurriculumXmlParserService $parser,
    ) {}

    public function import(string $xmlPath, int $specialtyId, int $academicYearId, ?int $userId = null): ImportResult
    {
        $validation = $this->parser->validateXml($xmlPath);

        if (! $validation['valid']) {
            return ImportResult::fail(
                error: 'XML validation failed: '.implode('; ', $validation['errors']),
            );
        }

        $parsed = $this->parser->parse($xmlPath);

        if ($parsed->hasErrors()) {
            return ImportResult::fail(
                error: 'Parsing failed: '.implode('; ', $parsed->errors),
            );
        }

        $specialty = Specialty::find($specialtyId);

        if ($specialty === null) {
            return ImportResult::fail(error: "Specialty with ID {$specialtyId} not found.");
        }

        $academicYear = AcademicYear::find($academicYearId);

        if ($academicYear === null) {
            return ImportResult::fail(error: "Academic year with ID {$academicYearId} not found.");
        }

        $plan = CurriculumPlan::create([
            'specialty_id' => $specialtyId,
            'academic_year_id' => $academicYearId,
            'name' => $parsed->specialty['name'] ?? "{$specialty->name} ({$academicYear->name})",
            'xml_file_path' => $xmlPath,
            'created_by' => $userId,
        ]);

        return $this->parser->importToPlan($parsed, $plan);
    }

    public function preview(string $xmlPath): ParsedCurriculum
    {
        $validation = $this->parser->validateXml($xmlPath);

        if (! $validation['valid']) {
            return new ParsedCurriculum(
                specialty: [],
                disciplines: [],
                semesters: [],
                errors: $validation['errors'],
            );
        }

        return $this->parser->parse($xmlPath);
    }
}
