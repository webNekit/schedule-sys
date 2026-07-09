<?php

declare(strict_types=1);

use App\Http\Livewire\Admin\CustomRules;
use App\Http\Livewire\Admin\DeleteDataChecklist;
use App\Http\Livewire\Admin\DepartmentManager;
use App\Http\Livewire\Admin\RoleManager;
use App\Http\Livewire\Admin\RoomTypeManager;
use App\Http\Livewire\Admin\SchedulingRules;
use App\Http\Livewire\Admin\SpecialtyManager;
use App\Http\Livewire\Admin\SystemSettings;
use App\Http\Livewire\Admin\TeacherPositionManager;
use App\Http\Livewire\Admin\UserManager;
use App\Http\Livewire\Auth\Login;
use App\Http\Livewire\Curriculum\AcademicPeriodsManager;
use App\Http\Livewire\Curriculum\CurriculumImportForm;
use App\Http\Livewire\Curriculum\Index as CurriculumIndex;
use App\Http\Livewire\Curriculum\ShowPlan;
use App\Http\Livewire\Dashboard;
use App\Http\Livewire\Groups\Index as GroupsIndex;
use App\Http\Livewire\Groups\Show as GroupsShow;
use App\Http\Livewire\Rooms\Index as RoomsIndex;
use App\Http\Livewire\Rooms\RoomManagement;
use App\Http\Livewire\Schedule\DayShare as ScheduleDayShare;
use App\Http\Livewire\Schedule\Index as ScheduleIndex;
use App\Http\Livewire\Schedule\MonitoringHeatmap;
use App\Http\Livewire\Schedule\PublicView as SchedulePublicView;
use App\Http\Livewire\Schedule\ReplacementFinder;
use App\Http\Livewire\Schedule\ScheduleGeneratorForm;
use App\Http\Livewire\Schedule\ScheduleGrid;
use App\Http\Livewire\Teachers\Index as TeachersIndex;
use App\Http\Livewire\Teachers\Show as TeachersShow;
use App\Http\Livewire\Teachers\TeacherAssignment;
use App\Http\Livewire\Teachers\TeacherWorkloadDashboard;
use App\Models\ScheduleVersion;
use App\Services\Export\ExcelExportService;
use Illuminate\Support\Facades\Route;

Route::get('/schedule/shared', SchedulePublicView::class)->name('schedule.shared');
Route::get('/schedule/day', ScheduleDayShare::class)->name('schedule.day');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/logout', function () {
        auth()->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/groups', GroupsIndex::class)->name('groups.index');
    Route::get('/groups/{group}', GroupsShow::class)->name('groups.show');
    Route::get('/teachers', TeachersIndex::class)->name('teachers.index');
    Route::get('/teachers/workload', TeacherWorkloadDashboard::class)->name('teachers.workload');
    Route::get('/teachers/assignments', TeacherAssignment::class)->name('teachers.assignments');
    Route::get('/teachers/{teacher}', TeachersShow::class)->name('teachers.show');
    Route::get('/schedule', ScheduleIndex::class)->name('schedule.index');
    Route::get('/schedule/export/{version}/{date}', function (ScheduleVersion $version, string $date) {
        $filePath = app(ExcelExportService::class)->exportDepartmentGridByDate($version->id, $date);
        $fileName = 'Расписание_'.basename($filePath);

        return response()->download($filePath, $fileName)->deleteFileAfterSend();
    })->name('schedule.export.download');
    Route::get('/curriculum', CurriculumIndex::class)->name('curriculum.index');
    Route::get('/curriculum/academic-periods', AcademicPeriodsManager::class)->name('curriculum.periods');
    Route::get('/curriculum/import', CurriculumImportForm::class)->name('curriculum.import');
    Route::get('/curriculum/{plan}', ShowPlan::class)->name('curriculum.show');
    Route::get('/rooms', RoomsIndex::class)->name('rooms.index');
    Route::get('/schedule/generate', ScheduleGeneratorForm::class)->name('schedule.generate');
    Route::get('/schedule/view/{version?}', ScheduleGrid::class)->name('schedule.view');
    Route::get('/schedule/monitoring', MonitoringHeatmap::class)->name('schedule.monitoring');
    Route::get('/schedule/replacements', ReplacementFinder::class)->name('schedule.replacements');
    Route::get('/rooms/manage', RoomManagement::class)->name('rooms.manage');

    Route::middleware('permission:admin.access')->group(function () {
        Route::get('/admin/roles', RoleManager::class)->name('admin.roles');
        Route::get('/admin/users', UserManager::class)->name('admin.users');
        Route::get('/admin/delete-data', DeleteDataChecklist::class)->name('admin.delete-data');
        Route::get('/admin/settings', SystemSettings::class)->name('admin.settings');
        Route::get('/admin/scheduling-rules', SchedulingRules::class)->name('admin.scheduling-rules');
        Route::get('/admin/custom-rules', CustomRules::class)->name('admin.custom-rules');
        Route::get('/admin/departments', DepartmentManager::class)->name('admin.departments');
        Route::get('/admin/specialties', SpecialtyManager::class)->name('admin.specialties');
        Route::get('/admin/positions', TeacherPositionManager::class)->name('admin.positions');
        Route::get('/admin/room-types', RoomTypeManager::class)->name('admin.room-types');
    });
});
