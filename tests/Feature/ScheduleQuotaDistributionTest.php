<?php

declare(strict_types=1);

use App\Services\Schedule\PairSlotPlanner;

/**
 * Характеризационные тесты чистой логики распределения пар по дням недели.
 * Дефолтные правила проекта: pairs_per_day base=3, max=5.
 *
 * @param  int  $totalPairs  Сколько пар нужно разложить за неделю
 * @param  int  $daysCount  На сколько учебных дней
 * @return array<int, int> Квоты по дням, отсортированные по убыванию
 */
function distributeQuota(int $totalPairs, int $daysCount): array
{
    return PairSlotPlanner::distributeQuota($totalPairs, $daysCount, base: 3, max: 5);
}

it('returns an empty array when there are no days', function () {
    expect(distributeQuota(10, 0))->toBe([]);
    expect(distributeQuota(10, -1))->toBe([]);
});

it('gives every day the base quota when total equals base * days', function () {
    // 5 дней × base(3) = 15, лишних пар нет.
    expect(distributeQuota(15, 5))->toBe([3, 3, 3, 3, 3]);
});

it('spreads extra pairs one at a time across days', function () {
    // 15 базовых + 3 лишних → три дня получают +1, отсортировано по убыванию.
    expect(distributeQuota(18, 5))->toBe([4, 4, 4, 3, 3]);
});

it('never exceeds the per-day maximum', function () {
    // Запросили больше, чем влезает: max(5) × 5 дней = 25 — потолок.
    expect(distributeQuota(30, 5))->toBe([5, 5, 5, 5, 5]);
});

it('caps exactly at the maximum when total equals max * days', function () {
    expect(distributeQuota(25, 5))->toBe([5, 5, 5, 5, 5]);
});

it('handles fewer pairs than the base without going negative', function () {
    // Меньше базы: остаток отрицательный, добивать нечего — все дни по base.
    expect(distributeQuota(2, 5))->toBe([3, 3, 3, 3, 3]);
});
