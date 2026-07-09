<?php

declare(strict_types=1);

namespace App\Services\Schedule;

/**
 * Чистая логика раскладки учебных пар по дням недели и слотам дня.
 *
 * Не обращается к БД и не хранит состояние — вынесена из
 * ScheduleGeneratorService, чтобы тестироваться в изоляции.
 */
final class PairSlotPlanner
{
    /**
     * Распределяет пары по учебным дням недели: каждому дню — base пар,
     * излишек добивается по одной паре в день по кругу, не превышая max.
     *
     * @return array<int, int> Квоты по дням, отсортированные по убыванию
     */
    public static function distributeQuota(int $totalPairs, int $daysCount, int $base, int $max): array
    {
        if ($daysCount <= 0) {
            return [];
        }

        $quotas = array_fill(0, $daysCount, $base);
        $remaining = $totalPairs - ($daysCount * $base);

        $i = 0;
        $guard = 0;
        while ($remaining > 0 && $i < $daysCount && $guard < $daysCount * $max) {
            if ($quotas[$i] < $max) {
                $quotas[$i]++;
                $remaining--;
            }
            $i = ($i + 1) % $daysCount;
            $guard++;
        }
        rsort($quotas);

        return $quotas;
    }

    /**
     * Слоты дня, когда часть пар — выезд в спорткомплекс: обычные пары
     * ставятся вплотную к блоку выезда (без окон), в пределах 1..7.
     *
     * @param  array<int, int>  $allowedSlots  Разрешённые номера пар в дне
     * @param  array<int, int>  $sportPairs  Номера пар, занятые выездом
     * @param  int  $totalPairs  Всего пар в дне (выезд + обычные)
     * @return array<int, int>
     */
    public static function sportComplexDaySlots(array $allowedSlots, array $sportPairs, int $totalPairs): array
    {
        $minSport = min($sportPairs);
        $maxSport = max($sportPairs);
        $firstSlot = empty($allowedSlots) ? $minSport : min($allowedSlots);
        $regularCount = max(0, $totalPairs - count($sportPairs));

        if ($minSport <= $firstSlot) {
            // Выезд в начале дня — обычные пары следом.
            $from = $minSport;
            $to = $maxSport + $regularCount;
        } else {
            // Выезд позже — обычные пары вплотную перед ним.
            $from = $minSport - $regularCount;
            $to = $maxSport;
        }

        return range(max(1, $from), min(7, $to));
    }
}
