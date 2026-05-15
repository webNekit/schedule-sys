<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\ScheduleVersion;

readonly class GenerationResult
{
    public function __construct(
        public bool $success,
        public int $totalLessons = 0,
        public int $conflicts = 0,
        public array $conflictDetails = [],
        public string $message = '',
        public ?ScheduleVersion $version = null,
        public array $warnings = [],
    ) {}

    public static function success(
        int $totalLessons = 0,
        int $conflicts = 0,
        array $conflictDetails = [],
        string $message = 'Schedule generated successfully.',
        ?ScheduleVersion $version = null,
        array $warnings = [],
    ): self {
        return new self(
            success: true,
            totalLessons: $totalLessons,
            conflicts: $conflicts,
            conflictDetails: $conflictDetails,
            message: $message,
            version: $version,
            warnings: $warnings,
        );
    }

    public static function fail(
        string $message = 'Schedule generation failed.',
        array $warnings = [],
        array $conflictDetails = [],
    ): self {
        return new self(
            success: false,
            message: $message,
            warnings: $warnings,
            conflictDetails: $conflictDetails,
        );
    }
}
