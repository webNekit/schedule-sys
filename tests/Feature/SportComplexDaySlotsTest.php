<?php

declare(strict_types=1);

use App\Services\Schedule\PairSlotPlanner;

/**
 * @param  array<int, int>  $allowed
 * @param  array<int, int>  $sport
 * @return array<int, int>
 */
function daySlots(array $allowed, array $sport, int $total): array
{
    return PairSlotPlanner::sportComplexDaySlots($allowed, $sport, $total);
}

it('puts regular pairs AFTER sport when sport is at the start of the day', function () {
    // Физра 1-2, обычные 3-4 — непрерывно.
    expect(daySlots([1, 2, 3, 4, 5], [1, 2], 4))->toBe([1, 2, 3, 4]);
});

it('puts regular pairs BEFORE sport when sport is later in the day', function () {
    // Обычные 1-2, физра 3-4 — непрерывно (раньше этот вариант был недоступен).
    expect(daySlots([1, 2, 3, 4, 5], [3, 4], 4))->toBe([1, 2, 3, 4]);
});

it('keeps only the sport block when there are no extra regular pairs', function () {
    expect(daySlots([1, 2, 3, 4, 5], [1, 2], 2))->toBe([1, 2]);
    expect(daySlots([1, 2, 3, 4, 5], [3, 4], 2))->toBe([3, 4]);
});

it('produces a continuous range with no gaps for an afternoon sport block', function () {
    // Сдвиг во 2-ю смену: первая пара дня — 3-я. Физра 5-6, обычные 3-4 вплотную.
    expect(daySlots([3, 4, 5, 6, 7], [5, 6], 4))->toBe([3, 4, 5, 6]);
});

it('never leaves a window between sport and regular blocks', function () {
    foreach ([[1, 2], [2, 3], [3, 4], [4, 5]] as $sport) {
        $slots = daySlots([1, 2, 3, 4, 5], $sport, 4);
        // Слоты идут подряд, без разрывов.
        for ($i = 1; $i < count($slots); $i++) {
            expect($slots[$i])->toBe($slots[$i - 1] + 1);
        }
        // Блок выезда целиком внутри дня.
        expect($slots)->toContain($sport[0])->toContain($sport[1]);
    }
});
