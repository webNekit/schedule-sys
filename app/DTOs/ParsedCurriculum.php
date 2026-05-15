<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class ParsedCurriculum
{
    public function __construct(
        public array $specialty,
        public array $disciplines,
        public array $semesters,
        public array $warnings = [],
        public array $errors = [],
    ) {}

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function getDisciplineCount(): int
    {
        return count($this->disciplines);
    }

    public function getSemesterCount(): int
    {
        return count($this->semesters);
    }
}
