<?php

namespace Database\Seeders;

use App\Models\SchedulingRule;
use App\Services\Schedule\SchedulingRuleResolver;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SchedulingRulesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Создаёт глобальные строки правил со встроенными дефолтами.
     * Out-of-the-box поведение совпадает с прежним «вшитым».
     */
    public function run(): void
    {
        foreach (SchedulingRuleResolver::DEFAULTS as $key => $default) {
            SchedulingRule::firstOrCreate(
                ['key' => $key, 'scope' => 'global', 'scope_id' => null],
                [
                    'is_enabled' => $default['is_enabled'],
                    'severity' => $default['severity'],
                    'params' => $default['params'],
                ],
            );
        }
    }
}
