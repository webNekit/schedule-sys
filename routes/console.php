<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('schedule:promote-groups --force')
    ->yearlyOn(8, 30, '00:01')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('reports:hours-deficit')
    ->weeklyOn(7, '06:00')
    ->withoutOverlapping();

Schedule::command('schedule:import-holidays '.now()->year)
    ->dailyAt('23:00');

Schedule::command('schedule:notify-practices')
    ->dailyAt('08:00')
    ->withoutOverlapping();
