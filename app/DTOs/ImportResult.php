<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class ImportResult
{
    public function __construct(
        public bool $success,
        public int $disciplinesImported = 0,
        public int $semestersImported = 0,
        public array $warnings = [],
        public array $errors = [],
        public ?int $curriculumPlanId = null,
    ) {}

    public static function success(
        int $disciplinesImported = 0,
        int $semestersImported = 0,
        array $warnings = [],
        ?int $curriculumPlanId = null,
    ): self {
        return new self(
            success: true,
            disciplinesImported: $disciplinesImported,
            semestersImported: $semestersImported,
            warnings: $warnings,
            curriculumPlanId: $curriculumPlanId,
        );
    }

    public static function fail(
        string $error,
        array $warnings = [],
    ): self {
        return new self(
            success: false,
            errors: [$error],
            warnings: $warnings,
        );
    }
}
