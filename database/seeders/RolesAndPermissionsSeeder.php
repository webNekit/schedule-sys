<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (DB::table('roles')->exists()) {
            return;
        }

        $roles = [
            ['name' => 'Супер администратор', 'slug' => 'superadmin', 'description' => 'Полный доступ ко всем функциям системы'],
            ['name' => 'Администратор', 'slug' => 'admin', 'description' => 'Администратор системы'],
            ['name' => 'Диспетчер', 'slug' => 'dispatcher', 'description' => 'Диспетчер расписания'],
            ['name' => 'Заведующий кафедрой', 'slug' => 'department_head', 'description' => 'Заведующий кафедрой'],
            ['name' => 'Преподаватель', 'slug' => 'teacher', 'description' => 'Преподаватель'],
            ['name' => 'Наблюдатель', 'slug' => 'viewer', 'description' => 'Только просмотр'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insert($role);
        }

        $permissions = [
            ['name' => 'Импорт учебных планов', 'slug' => 'curriculum.import', 'module' => 'curriculum'],
            ['name' => 'Просмотр учебных планов', 'slug' => 'curriculum.view', 'module' => 'curriculum'],
            ['name' => 'Редактирование учебных планов', 'slug' => 'curriculum.edit', 'module' => 'curriculum'],
            ['name' => 'Удаление учебных планов', 'slug' => 'curriculum.delete', 'module' => 'curriculum'],
            ['name' => 'Просмотр расписания', 'slug' => 'schedule.view', 'module' => 'schedule'],
            ['name' => 'Генерация расписания', 'slug' => 'schedule.generate', 'module' => 'schedule'],
            ['name' => 'Редактирование расписания', 'slug' => 'schedule.edit', 'module' => 'schedule'],
            ['name' => 'Публикация расписания', 'slug' => 'schedule.publish', 'module' => 'schedule'],
            ['name' => 'Экспорт расписания', 'slug' => 'schedule.export', 'module' => 'schedule'],
            ['name' => 'Просмотр преподавателей', 'slug' => 'teachers.view', 'module' => 'teachers'],
            ['name' => 'Управление преподавателями', 'slug' => 'teachers.manage', 'module' => 'teachers'],
            ['name' => 'Просмотр аудиторий', 'slug' => 'rooms.view', 'module' => 'rooms'],
            ['name' => 'Управление аудиториями', 'slug' => 'rooms.manage', 'module' => 'rooms'],
            ['name' => 'Просмотр групп', 'slug' => 'groups.view', 'module' => 'groups'],
            ['name' => 'Управление группами', 'slug' => 'groups.manage', 'module' => 'groups'],
            ['name' => 'Просмотр отчётов', 'slug' => 'reports.view', 'module' => 'reports'],
            ['name' => 'Экспорт отчётов', 'slug' => 'reports.export', 'module' => 'reports'],
            ['name' => 'Доступ к администрированию', 'slug' => 'admin.access', 'module' => 'admin'],
            ['name' => 'Управление пользователями', 'slug' => 'admin.manage_users', 'module' => 'admin'],
            ['name' => 'Управление настройками', 'slug' => 'admin.manage_settings', 'module' => 'admin'],
            ['name' => 'Просмотр логов', 'slug' => 'admin.view_logs', 'module' => 'admin'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert($permission);
        }

        $allPermissions = DB::table('permissions')->pluck('id')->toArray();

        $curriculumPermissions = DB::table('permissions')->where('module', 'curriculum')->pluck('id')->toArray();
        $schedulePermissions = DB::table('permissions')->where('module', 'schedule')->pluck('id')->toArray();
        $teachersPermissions = DB::table('permissions')->where('module', 'teachers')->pluck('id')->toArray();
        $roomsPermissions = DB::table('permissions')->where('module', 'rooms')->pluck('id')->toArray();
        $groupsPermissions = DB::table('permissions')->where('module', 'groups')->pluck('id')->toArray();
        $reportsPermissions = DB::table('permissions')->where('module', 'reports')->pluck('id')->toArray();
        $adminPermissions = DB::table('permissions')->where('module', 'admin')->pluck('id')->toArray();

        $superadmin = DB::table('roles')->where('slug', 'superadmin')->first();
        $admin = DB::table('roles')->where('slug', 'admin')->first();
        $dispatcher = DB::table('roles')->where('slug', 'dispatcher')->first();
        $departmentHead = DB::table('roles')->where('slug', 'department_head')->first();
        $teacher = DB::table('roles')->where('slug', 'teacher')->first();
        $viewer = DB::table('roles')->where('slug', 'viewer')->first();

        if ($superadmin) {
            foreach ($allPermissions as $permId) {
                DB::table('role_permissions')->insert(['role_id' => $superadmin->id, 'permission_id' => $permId]);
            }
        }

        if ($admin) {
            foreach ($allPermissions as $permId) {
                DB::table('role_permissions')->insert(['role_id' => $admin->id, 'permission_id' => $permId]);
            }
        }

        if ($dispatcher) {
            $dispatcherPerms = array_merge($schedulePermissions, $roomsPermissions, $groupsPermissions, $reportsPermissions);
            foreach ($dispatcherPerms as $permId) {
                DB::table('role_permissions')->insert(['role_id' => $dispatcher->id, 'permission_id' => $permId]);
            }
        }

        if ($departmentHead) {
            $deptHeadPerms = array_merge(
                DB::table('permissions')->whereIn('slug', ['curriculum.view', 'curriculum.edit'])->pluck('id')->toArray(),
                $schedulePermissions,
                $teachersPermissions,
                DB::table('permissions')->whereIn('slug', ['rooms.view'])->pluck('id')->toArray(),
                DB::table('permissions')->whereIn('slug', ['groups.view'])->pluck('id')->toArray(),
                DB::table('permissions')->whereIn('slug', ['reports.view'])->pluck('id')->toArray(),
            );
            foreach ($deptHeadPerms as $permId) {
                DB::table('role_permissions')->insert(['role_id' => $departmentHead->id, 'permission_id' => $permId]);
            }
        }

        if ($teacher) {
            $teacherPerms = DB::table('permissions')->whereIn('slug', [
                'curriculum.view', 'schedule.view', 'teachers.view', 'rooms.view', 'groups.view',
            ])->pluck('id')->toArray();
            foreach ($teacherPerms as $permId) {
                DB::table('role_permissions')->insert(['role_id' => $teacher->id, 'permission_id' => $permId]);
            }
        }

        if ($viewer) {
            $viewerPerms = DB::table('permissions')->whereIn('slug', [
                'curriculum.view', 'schedule.view', 'teachers.view', 'rooms.view', 'groups.view', 'reports.view',
            ])->pluck('id')->toArray();
            foreach ($viewerPerms as $permId) {
                DB::table('role_permissions')->insert(['role_id' => $viewer->id, 'permission_id' => $permId]);
            }
        }
    }
}
