<?php

namespace App\Providers;

use App\Models\Group;
use App\Models\ScheduleLesson;
use App\Observers\GroupObserver;
use App\Observers\ScheduleLessonObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Group::observe(GroupObserver::class);
        ScheduleLesson::observe(ScheduleLessonObserver::class);
    }
}
