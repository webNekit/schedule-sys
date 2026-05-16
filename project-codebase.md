# College Schedule Management System - Complete Codebase

## Directory Structure

```
.
├── AGENTS.md
├── .editorconfig
├── .env
├── .env.example
├── .gitattributes
├── .gitignore
├── .mcp.json
├── .npmrc
├── CLAUDE.md
├── GEMINI.md
├── README.md
├── artisan
├── boost.json
├── composer.json
├── opencode.json
├── package.json
├── phpunit.xml
├── vite.config.js
├── progress.md
├── promt.md
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── BackfillTeacherDisciplineSemesters.php
│   │       ├── Curriculum/CurriculumImportXml.php
│   │       ├── Schedule/NotifyUpcomingPractices.php
│   │       ├── Schedule/ReportsHoursDeficit.php
│   │       ├── Schedule/ScheduleCheckConflicts.php
│   │       ├── Schedule/ScheduleExportExcel.php
│   │       ├── Schedule/ScheduleGenerate.php
│   │       ├── Schedule/ScheduleImportHolidays.php
│   │       └── Schedule/SchedulePromoteGroups.php
│   ├── DTOs/
│   │   ├── GenerationResult.php
│   │   ├── ImportResult.php
│   │   └── ParsedCurriculum.php
│   ├── Http/
│   │   ├── Controllers/Controller.php
│   │   ├── Livewire/
│   │   │   ├── Admin/
│   │   │   │   ├── ActionsWidget.php
│   │   │   │   ├── DeleteDataChecklist.php
│   │   │   │   ├── DepartmentManager.php
│   │   │   │   ├── RoleManager.php
│   │   │   │   ├── RoomTypeManager.php
│   │   │   │   ├── SpecialtyManager.php
│   │   │   │   ├── SystemSettings.php
│   │   │   │   ├── TeacherPositionManager.php
│   │   │   │   └── UserManager.php
│   │   │   ├── Auth/Login.php
│   │   │   ├── Curriculum/
│   │   │   │   ├── AcademicPeriodsManager.php
│   │   │   │   ├── CurriculumImportForm.php
│   │   │   │   ├── Index.php
│   │   │   │   └── ShowPlan.php
│   │   │   ├── Groups/
│   │   │   │   ├── GroupCourseView.php
│   │   │   │   ├── Index.php
│   │   │   │   └── Show.php
│   │   │   ├── Rooms/
│   │   │   │   ├── Index.php
│   │   │   │   └── RoomManagement.php
│   │   │   ├── Schedule/
│   │   │   │   ├── DayShare.php
│   │   │   │   ├── Index.php
│   │   │   │   ├── PublicView.php
│   │   │   │   ├── ScheduleGeneratorForm.php
│   │   │   │   └── ScheduleGrid.php
│   │   │   ├── Teachers/
│   │   │   │   ├── Index.php
│   │   │   │   ├── Show.php
│   │   │   │   ├── TeacherAssignment.php
│   │   │   │   └── TeacherWorkloadDashboard.php
│   │   │   ├── Dashboard.php
│   │   │   └── NotificationsWidget.php
│   │   └── Middleware/CheckPermission.php
│   ├── Models/
│   │   ├── AcademicYear.php
│   │   ├── ActivityLog.php
│   │   ├── BellSchedule.php
│   │   ├── Building.php
│   │   ├── ControlForm.php
│   │   ├── CurriculumDiscipline.php
│   │   ├── CurriculumPlan.php
│   │   ├── CurriculumPractice.php
│   │   ├── CurriculumSemester.php
│   │   ├── Department.php
│   │   ├── EducationLevel.php
│   │   ├── EquipmentType.php
│   │   ├── ExportLog.php
│   │   ├── Faculty.php
│   │   ├── ForeignLanguageGroup.php
│   │   ├── Group.php
│   │   ├── GroupBuilding.php
│   │   ├── GroupCurriculumAssignment.php
│   │   ├── GroupDayBuilding.php
│   │   ├── Holiday.php
│   │   ├── HoursTracking.php
│   │   ├── ImportLog.php
│   │   ├── LessonType.php
│   │   ├── Notification.php
│   │   ├── Permission.php
│   │   ├── QualificationType.php
│   │   ├── Role.php
│   │   ├── Room.php
│   │   ├── RoomEquipment.php
│   │   ├── RoomType.php
│   │   ├── RoomUnavailability.php
│   │   ├── ScheduleConflict.php
│   │   ├── ScheduleLesson.php
│   │   ├── ScheduleVersion.php
│   │   ├── Specialty.php
│   │   ├── Subgroup.php
│   │   ├── SystemSetting.php
│   │   ├── Teacher.php
│   │   ├── TeacherBuilding.php
│   │   ├── TeacherDayBuilding.php
│   │   ├── TeacherDiscipline.php
│   │   ├── TeacherDisciplineSemester.php
│   │   ├── TeacherPosition.php
│   │   ├── TeacherRoom.php
│   │   ├── TeacherUnavailability.php
│   │   ├── User.php
│   │   ├── Vacation.php
│   │   └── WeekType.php
│   ├── Observers/
│   │   ├── GroupObserver.php
│   │   └── ScheduleLessonObserver.php
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── Services/
│       ├── Curriculum/
│       │   ├── CurriculumImportService.php
│       │   ├── CurriculumXmlParserService.php
│       │   └── ExcelCurriculumParserService.php
│       ├── Export/ExcelExportService.php
│       ├── GroupPromotionService.php
│       ├── Import/ExcelDictionaryImportService.php
│       └── Schedule/
│           ├── ConflictCheckerService.php
│           ├── HoursTrackingService.php
│           └── ScheduleGeneratorService.php
├── bootstrap/
│   ├── app.php
│   └── providers.php
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── database.php
│   ├── filesystems.php
│   ├── livewire.php
│   ├── logging.php
│   ├── mail.php
│   ├── queue.php
│   ├── services.php
│   └── session.php
├── database/
│   ├── .gitignore
│   ├── factories/UserFactory.php
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2026_05_13_105439_create_education_levels_table.php
│   │   ├── 2026_05_13_105439_create_qualification_types_table.php
│   │   ├── 2026_05_13_105440_create_departments_table.php
│   │   ├── 2026_05_13_105440_create_faculties_table.php
│   │   ├── 2026_05_13_105441_create_buildings_table.php
│   │   ├── 2026_05_13_105441_create_specialties_table.php
│   │   ├── 2026_05_13_105444_create_room_types_table.php
│   │   ├── 2026_05_13_105445_create_equipment_types_table.php
│   │   ├── 2026_05_13_105445_create_rooms_table.php
│   │   ├── 2026_05_13_105446_create_bell_schedules_table.php
│   │   ├── 2026_05_13_105446_create_room_equipment_table.php
│   │   ├── 2026_05_13_105447_create_holidays_table.php
│   │   ├── 2026_05_13_105450_create_academic_years_table.php
│   │   ├── 2026_05_13_105450_create_vacations_table.php
│   │   ├── 2026_05_13_105451_create_control_forms_table.php
│   │   ├── 2026_05_13_105451_create_lesson_types_table.php
│   │   ├── 2026_05_13_105452_create_teacher_positions_table.php
│   │   ├── 2026_05_13_105452_create_week_types_table.php
│   │   ├── 2026_05_13_105453_create_foreign_language_groups_table.php
│   │   ├── 2026_05_13_105457_create_group_buildings_table.php
│   │   ├── 2026_05_13_105457_create_groups_table.php
│   │   ├── 2026_05_13_105458_create_curriculum_plans_table.php
│   │   ├── 2026_05_13_105458_create_subgroups_table.php
│   │   ├── 2026_05_13_105459_create_curriculum_disciplines_table.php
│   │   ├── 2026_05_13_105500_create_curriculum_semesters_table.php
│   │   ├── 2026_05_13_105503_create_group_curriculum_assignments_table.php
│   │   ├── 2026_05_13_105503_create_teachers_table.php
│   │   ├── 2026_05_13_105504_create_teacher_disciplines_table.php
│   │   ├── 2026_05_13_105504_create_teacher_rooms_table.php
│   │   ├── 2026_05_13_105505_create_teacher_buildings_table.php
│   │   ├── 2026_05_13_105505_create_teacher_unavailability_table.php
│   │   ├── 2026_05_13_105506_create_teacher_day_buildings_table.php
│   │   ├── 2026_05_13_105509_create_schedule_lessons_table.php
│   │   ├── 2026_05_13_105509_create_schedule_versions_table.php
│   │   ├── 2026_05_13_105510_create_hours_tracking_table.php
│   │   ├── 2026_05_13_105510_create_schedule_conflicts_table.php
│   │   ├── 2026_05_13_105511_create_group_day_buildings_table.php
│   │   ├── 2026_05_13_105511_create_room_unavailability_table.php
│   │   ├── 2026_05_13_105514_create_roles_table.php
│   │   ├── 2026_05_13_105515_create_permissions_table.php
│   │   ├── 2026_05_13_105515_create_role_permissions_table.php
│   │   ├── 2026_05_13_105516_create_user_roles_table.php
│   │   ├── 2026_05_13_105517_add_teacher_department_fields_to_users_table.php
│   │   ├── 2026_05_13_105517_create_activity_logs_table.php
│   │   ├── 2026_05_13_105518_create_import_logs_table.php
│   │   ├── 2026_05_13_105518_create_system_settings_table.php
│   │   ├── 2026_05_13_105519_create_export_logs_table.php
│   │   ├── 2026_05_13_105519_create_notifications_table.php
│   │   ├── 2026_05_13_191918_create_teacher_discipline_semesters_table.php
│   │   ├── 2026_05_13_203526_add_specialty_fields.php
│   │   ├── 2026_05_13_210849_add_working_schedule_to_teachers.php
│   │   ├── 2026_05_14_094909_add_excel_file_path_to_curriculum_plans_table.php
│   │   ├── 2026_05_14_192445_add_suggestion_to_schedule_conflicts_table.php
│   │   ├── 2026_05_15_133310_create_curriculum_practices_table.php
│   │   ├── 2026_05_15_153317_update_lesson_numbers_to_per_day_structure.php
│   │   ├── 2026_05_15_175448_make_faculty_id_nullable_in_departments.php
│   │   └── 2026_05_15_181613_add_is_active_to_teacher_positions.php
│   └── seeders/
│       ├── AcademicYearsSeeder.php
│       ├── AdminUserSeeder.php
│       ├── BellScheduleSeeder.php
│       ├── ControlFormsSeeder.php
│       ├── DatabaseSeeder.php
│       ├── DemoDataSeeder.php
│       ├── EducationLevelsSeeder.php
│       ├── EquipmentTypesSeeder.php
│       ├── ForeignLanguageGroupsSeeder.php
│       ├── HolidaysSeeder.php
│       ├── LessonTypesSeeder.php
│       ├── QualificationTypesSeeder.php
│       ├── RolesAndPermissionsSeeder.php
│       ├── RoomTypesSeeder.php
│       ├── SystemSettingsSeeder.php
│       ├── TeacherPositionsSeeder.php
│       ├── VacationsSeeder.php
│       └── WeekTypesSeeder.php
├── public/
│   ├── .htaccess
│   ├── favicon.ico
│   ├── index.php
│   └── robots.txt
├── resources/
│   ├── css/app.css
│   ├── js/app.js
│   └── views/
│       ├── components/layouts/
│       │   ├── app.blade.php
│       │   ├── guest.blade.php
│       │   └── public.blade.php
│       ├── components/
│       │   ├── nav-link.blade.php
│       │   ├── searchable-select.blade.php
│       │   └── toast.blade.php
│       ├── livewire/
│       │   ├── admin/
│       │   │   ├── actions-widget.blade.php
│       │   │   ├── delete-data-checklist.blade.php
│       │   │   ├── department-manager.blade.php
│       │   │   ├── role-manager.blade.php
│       │   │   ├── room-type-manager.blade.php
│       │   │   ├── specialty-manager.blade.php
│       │   │   ├── system-settings.blade.php
│       │   │   ├── teacher-position-manager.blade.php
│       │   │   └── user-manager.blade.php
│       │   ├── auth/login.blade.php
│       │   ├── curriculum/
│       │   │   ├── academic-periods-manager.blade.php
│       │   │   ├── curriculum-import-form.blade.php
│       │   │   ├── index.blade.php
│       │   │   └── show-plan.blade.php
│       │   ├── groups/
│       │   │   ├── group-course-view.blade.php
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   ├── rooms/
│       │   │   ├── index.blade.php
│       │   │   └── room-management.blade.php
│       │   ├── schedule/
│       │   │   ├── day-share.blade.php
│       │   │   ├── index.blade.php
│       │   │   ├── public-view.blade.php
│       │   │   ├── schedule-generator-form.blade.php
│       │   │   └── schedule-grid.blade.php
│       │   ├── teachers/
│       │   │   ├── index.blade.php
│       │   │   ├── show.blade.php
│       │   │   ├── teacher-assignment.blade.php
│       │   │   └── teacher-workload-dashboard.blade.php
│       │   ├── dashboard.blade.php
│       │   └── notifications-widget.blade.php
│       └── welcome.blade.php
├── routes/
│   ├── console.php
│   └── web.php
└── tests/
    ├── Feature/ExampleTest.php
    ├── Pest.php
    ├── TestCase.php
    └── Unit/ExampleTest.php
```

---

## Root Configuration Files


### `.editorconfig`
```ini
root = true

[*]
charset = utf-8
end_of_line = lf
indent_size = 4
indent_style = space
insert_final_newline = true
trim_trailing_whitespace = true

[*.md]
trim_trailing_whitespace = false

[*.{yml,yaml}]
indent_size = 2

[{compose,docker-compose}.{yml,yaml}]
indent_size = 4
```

### `.env`
```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:/y1HwPPnkLeQQcO5CSOXvmA5os0Jj360C31bNvybb6I=
APP_DEBUG=true
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=sqlite

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
```

### `.env.example`
```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=sqlite

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
```

### `.gitattributes`
```
* text=auto eol=lf

*.blade.php diff=html
*.css diff=css
*.html diff=html
*.md diff=markdown
*.php diff=php

/.github export-ignore
CHANGELOG.md export-ignore
.styleci.yml export-ignore
```

### `.gitignore`
```
*.log
.DS_Store
.env
.env.backup
.env.production
.phpactor.json
.phpunit.result.cache
/.codex
/.cursor/
/.idea
/.nova
/.phpunit.cache
/.vscode
/.zed
/auth.json
/node_modules
/public/build
/public/fonts-manifest.dev.json
/public/hot
/public/storage
/storage/*.key
/storage/pail
/vendor
_ide_helper.php
Homestead.json
Homestead.yaml
Thumbs.db
```

### `.mcp.json`
```json
{
    "mcpServers": {
        "laravel-boost": {
            "command": "php",
            "args": [
                "artisan",
                "boost:mcp"
            ]
        }
    }
}
```

### `.npmrc`
```
ignore-scripts=true
audit=true
```

### `artisan`
```php
#!/usr/bin/env php
<?php

use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\ArgvInput;

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

$status = $app->handleCommand(new ArgvInput);

exit($status);
```

### `boost.json`
```json
{
    "agents": [
        "junie",
        "cursor",
        "claude_code",
        "opencode",
        "gemini"
    ],
    "cloud": false,
    "guidelines": true,
    "mcp": true,
    "nightwatch": false,
    "sail": false,
    "skills": [
        "laravel-best-practices",
        "livewire-development",
        "pest-testing",
        "tailwindcss-development"
    ]
}
```

### `composer.json`
```json
{
    "$schema": "https://getcomposer.org/schema.json",
    "name": "laravel/laravel",
    "type": "project",
    "description": "The skeleton application for the Laravel framework.",
    "keywords": ["laravel", "framework"],
    "license": "MIT",
    "require": {
        "php": "^8.3",
        "laravel/framework": "^13.7",
        "laravel/tinker": "^3.0",
        "livewire/livewire": "^4.3",
        "phpoffice/phpspreadsheet": "^5.7"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/boost": "^2.0",
        "laravel/pail": "^1.2.5",
        "laravel/pao": "^1.0.6",
        "laravel/pint": "^1.27",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.6",
        "pestphp/pest": "^4.7",
        "pestphp/pest-plugin-laravel": "^4.1"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "setup": [
            "composer install",
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\"",
            "@php artisan key:generate",
            "@php artisan migrate --force",
            "npm install --ignore-scripts",
            "npm run build"
        ],
        "dev": [
            "Composer\\Config::disableProcessTimeout",
            "npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1 --timeout=0\" \"php artisan pail --timeout=0\" \"npm run dev\" --names=server,queue,logs,vite --kill-others"
        ],
        "test": [
            "@php artisan config:clear --ansi @no_additional_args",
            "@php artisan test"
        ],
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ],
        "post-update-cmd": [
            "@php artisan vendor:publish --tag=laravel-assets --ansi --force",
            "@php artisan boost:update --ansi"
        ],
        "post-root-package-install": [
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
        ],
        "post-create-project-cmd": [
            "@php artisan key:generate --ansi",
            "@php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\"",
            "@php artisan migrate --graceful --ansi"
        ],
        "pre-package-uninstall": [
            "Illuminate\\Foundation\\ComposerScripts::prePackageUninstall"
        ]
    },
    "extra": {
        "laravel": {
            "dont-discover": []
        }
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true,
            "php-http/discovery": true
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

### `opencode.json`
```json
{
    "$schema": "https://opencode.ai/config.json",
    "mcp": {
        "laravel-boost": {
            "type": "local",
            "enabled": true,
            "command": ["php", "artisan", "boost:mcp"]
        }
    }
}
```

### `package.json`
```json
{
    "$schema": "https://www.schemastore.org/package.json",
    "private": true,
    "type": "module",
    "scripts": {
        "build": "vite build",
        "dev": "vite"
    },
    "devDependencies": {
        "@tailwindcss/vite": "^4.0.0",
        "concurrently": "^9.0.1",
        "laravel-vite-plugin": "^3.1",
        "tailwindcss": "^4.0.0",
        "vite": "^8.0.0"
    }
}
```

### `phpunit.xml`
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="APP_MAINTENANCE_DRIVER" value="file"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="BROADCAST_CONNECTION" value="null"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="DB_URL" value=""/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="PULSE_ENABLED" value="false"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
        <env name="NIGHTWATCH_ENABLED" value="false"/>
    </php>
</phpunit>
```

### `vite.config.js`
```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
```

---

## `bootstrap/`

### `bootstrap/app.php`
```php
<?php

use App\Http\Middleware\CheckPermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
```

### `bootstrap/providers.php`
```php
<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
];
```

---

## `app/`

### `app/Http/Controllers/Controller.php`
```php
<?php

namespace App\Http\Controllers;

abstract class Controller
{
    //
}
```

### `app/Http/Middleware/CheckPermission.php`
```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! $request->user() || ! $request->user()->hasPermission($permission)) {
            abort(403, 'У вас нет прав для выполнения этого действия.');
        }

        return $next($request);
    }
}
```

### `app/Providers/AppServiceProvider.php`
```php
<?php

namespace App\Providers;

use App\Models\Group;
use App\Models\ScheduleLesson;
use App\Observers\GroupObserver;
use App\Observers\ScheduleLessonObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Group::observe(GroupObserver::class);
        ScheduleLesson::observe(ScheduleLessonObserver::class);
    }
}
```

### `app/Observers/GroupObserver.php`
```php
<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Group;

class GroupObserver
{
    public function created(Group $group): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'create',
            'module' => 'groups',
            'model_type' => Group::class,
            'model_id' => $group->id,
            'description' => "Создана группа: {$group->name}",
            'new_values' => $group->toArray(),
        ]);
    }

    public function updated(Group $group): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'update',
            'module' => 'groups',
            'model_type' => Group::class,
            'model_id' => $group->id,
            'description' => "Обновлена группа: {$group->name}",
            'old_values' => $group->getOriginal(),
            'new_values' => $group->toArray(),
        ]);
    }

    public function deleted(Group $group): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'delete',
            'module' => 'groups',
            'model_type' => Group::class,
            'model_id' => $group->id,
            'description' => "Удалена группа: {$group->name}",
            'old_values' => $group->toArray(),
        ]);
    }

    public function restored(Group $group): void {}
    public function forceDeleted(Group $group): void {}
}
```

### `app/Observers/ScheduleLessonObserver.php`
```php
<?php

namespace App\Observers;

use App\Models\CurriculumSemester;
use App\Models\HoursTracking;
use App\Models\ScheduleLesson;

class ScheduleLessonObserver
{
    public function updated(ScheduleLesson $lesson): void
    {
        $version = $lesson->version;
        if (! $version || $version->status !== 'published') {
            return;
        }

        $isCancelled = $lesson->status === 'cancelled';
        $tracking = HoursTracking::where('schedule_lesson_id', $lesson->id)->first();

        if ($tracking) {
            if ($tracking->teacher_id !== $lesson->teacher_id || $isCancelled) {
                $tracking->update(['is_cancelled' => true, 'notes' => 'Замена или отмена']);
            }
        }

        if (! $isCancelled && (! $tracking || $tracking->teacher_id !== $lesson->teacher_id)) {
            $semesterId = $this->resolveSemesterId($lesson->discipline_id, $lesson->date, $version->academic_year_id);
            if ($semesterId) {
                HoursTracking::create([
                    'group_id' => $lesson->group_id,
                    'discipline_id' => $lesson->discipline_id,
                    'teacher_id' => $lesson->teacher_id,
                    'semester_id' => $semesterId,
                    'academic_year_id' => $version->academic_year_id,
                    'lesson_type_id' => $lesson->lesson_type_id,
                    'date' => $lesson->date,
                    'hours_conducted' => 2,
                    'schedule_lesson_id' => $lesson->id,
                    'is_cancelled' => false,
                ]);
            }
        }
    }

    public function deleted(ScheduleLesson $lesson): void
    {
        HoursTracking::where('schedule_lesson_id', $lesson->id)->update(['is_cancelled' => true, 'notes' => 'Пара удалена']);
    }

    private function resolveSemesterId(?int $disciplineId, mixed $date, ?int $academicYearId): ?int
    {
        if (! $disciplineId) return null;
        return CurriculumSemester::where('discipline_id', $disciplineId)->first()?->id;
    }
}
```

### `app/DTOs/GenerationResult.php`
```php
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
```

### `app/DTOs/ImportResult.php`
```php
<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class ImportResult
{
    public function __construct(
        public bool $success,
        public int $disciplinesImported = 0,
        public int $semestersImported = 0,
        public array $warnings = [],
        public array $errors = [],
        public ?int $curriculumPlanId = null,
    ) {}

    public static function success(
        int $disciplinesImported = 0,
        int $semestersImported = 0,
        array $warnings = [],
        ?int $curriculumPlanId = null,
    ): self {
        return new self(
            success: true,
            disciplinesImported: $disciplinesImported,
            semestersImported: $semestersImported,
            warnings: $warnings,
            curriculumPlanId: $curriculumPlanId,
        );
    }

    public static function fail(string $error, array $warnings = []): self
    {
        return new self(
            success: false,
            errors: [$error],
            warnings: $warnings,
        );
    }
}
```

### `app/DTOs/ParsedCurriculum.php`
```php
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
```

---

## `app/Models/`

### `app/Models/AcademicYear.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    protected $fillable = [
        'name', 'year_start', 'year_end', 'date_start', 'date_end',
        'first_semester_start', 'first_semester_end',
        'second_semester_start', 'second_semester_end',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'year_start' => 'integer',
            'year_end' => 'integer',
            'date_start' => 'date',
            'date_end' => 'date',
            'first_semester_start' => 'date',
            'first_semester_end' => 'date',
            'second_semester_start' => 'date',
            'second_semester_end' => 'date',
        ];
    }

    public function vacations(): HasMany
    {
        return $this->hasMany(Vacation::class);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }
}
```

### `app/Models/ActivityLog.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'action', 'module', 'model_type', 'model_id',
        'description', 'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### `app/Models/BellSchedule.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BellSchedule extends Model
{
    protected $fillable = [
        'name', 'shift_number', 'lesson_number', 'time_start', 'time_end',
        'break_after_minutes', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'shift_number' => 'integer',
            'lesson_number' => 'integer',
            'break_after_minutes' => 'integer',
            'sort_order' => 'integer',
            'time_start' => 'datetime:H:i',
            'time_end' => 'datetime:H:i',
        ];
    }
}
```

### `app/Models/Building.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'short_name', 'address', 'floors_count', 'description',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'floors_count' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
```

### `app/Models/ControlForm.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlForm extends Model
{
    protected $fillable = ['name', 'short_name', 'code', 'is_exam_session'];

    protected function casts(): array
    {
        return ['is_exam_session' => 'boolean'];
    }
}
```

### `app/Models/CurriculumDiscipline.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumDiscipline extends Model
{
    protected $fillable = [
        'curriculum_plan_id', 'name', 'short_name', 'code', 'cycle',
        'discipline_type', 'is_federal', 'requires_subgroup', 'subgroup_type',
        'requires_lab', 'required_room_type_id', 'sort_order', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_federal' => 'boolean',
            'requires_subgroup' => 'boolean',
            'requires_lab' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function curriculumPlan(): BelongsTo { return $this->belongsTo(CurriculumPlan::class); }
    public function requiredRoomType(): BelongsTo { return $this->belongsTo(RoomType::class, 'required_room_type_id'); }
    public function semesters(): HasMany { return $this->hasMany(CurriculumSemester::class, 'discipline_id'); }
    public function teacherDisciplines(): HasMany { return $this->hasMany(TeacherDiscipline::class, 'discipline_id'); }
    public function scheduleLessons(): HasMany { return $this->hasMany(ScheduleLesson::class, 'discipline_id'); }
}
```

### `app/Models/CurriculumPlan.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurriculumPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'specialty_id', 'academic_year_id', 'name', 'version',
        'xml_file_path', 'excel_file_path', 'xml_original', 'parsed_at',
        'total_hours', 'contact_hours', 'self_study_hours', 'practice_hours',
        'is_active', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean', 'parsed_at' => 'datetime',
            'total_hours' => 'integer', 'contact_hours' => 'integer',
            'self_study_hours' => 'integer', 'practice_hours' => 'integer',
        ];
    }

    public function specialty(): BelongsTo { return $this->belongsTo(Specialty::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function disciplines(): HasMany { return $this->hasMany(CurriculumDiscipline::class); }
    public function groupAssignments(): HasMany { return $this->hasMany(GroupCurriculumAssignment::class); }
    public function practices(): HasMany { return $this->hasMany(CurriculumPractice::class); }
    public function scopeActive(Builder $query): Builder { return $query->where('is_active', true); }
}
```

### `app/Models/CurriculumPractice.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumPractice extends Model
{
    protected $fillable = [
        'curriculum_plan_id', 'course_number', 'type', 'symbol',
        'start_date', 'end_date',
    ];

    protected function casts(): array
    {
        return [
            'course_number' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function curriculumPlan(): BelongsTo
    {
        return $this->belongsTo(CurriculumPlan::class);
    }
}
```

### `app/Models/CurriculumSemester.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumSemester extends Model
{
    protected $fillable = [
        'discipline_id', 'course_number', 'semester_number', 'semester_in_course',
        'hours_total', 'hours_lecture', 'hours_practice', 'hours_lab',
        'hours_self_study', 'hours_consultation', 'control_form_id', 'exam_hours',
        'course_project_hours', 'weeks_count', 'hours_per_week',
    ];

    protected function casts(): array
    {
        return [
            'course_number' => 'integer', 'semester_number' => 'integer',
            'semester_in_course' => 'integer', 'hours_total' => 'integer',
            'hours_lecture' => 'integer', 'hours_practice' => 'integer',
            'hours_lab' => 'integer', 'hours_self_study' => 'integer',
            'hours_consultation' => 'integer', 'exam_hours' => 'integer',
            'course_project_hours' => 'integer', 'weeks_count' => 'integer',
            'hours_per_week' => 'decimal:2',
        ];
    }

    public function discipline(): BelongsTo { return $this->belongsTo(CurriculumDiscipline::class, 'discipline_id'); }
    public function controlForm(): BelongsTo { return $this->belongsTo(ControlForm::class); }
}
```

### `app/Models/Department.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'faculty_id', 'name', 'short_name', 'head_name', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function faculty(): BelongsTo { return $this->belongsTo(Faculty::class); }
    public function specialties(): HasMany { return $this->hasMany(Specialty::class); }
    public function teachers(): HasMany { return $this->hasMany(Teacher::class); }
    public function groups(): HasMany { return $this->hasMany(Group::class); }
    public function scopeActive(Builder $query): Builder { return $query->where('is_active', true); }
}
```

### `app/Models/EducationLevel.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EducationLevel extends Model
{
    protected $fillable = ['name', 'study_years'];

    protected function casts(): array
    {
        return ['study_years' => 'integer'];
    }

    public function specialties(): HasMany
    {
        return $this->hasMany(Specialty::class);
    }
}
```

### `app/Models/EquipmentType.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentType extends Model
{
    protected $fillable = ['name', 'icon'];
}
```

### `app/Models/ExportLog.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportLog extends Model
{
    protected $fillable = [
        'user_id', 'type', 'file_name', 'file_path', 'file_size',
        'parameters', 'status',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'parameters' => 'array',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
```

### `app/Models/Faculty.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faculty extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'short_name', 'head_name', 'phone', 'email',
        'description', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function departments(): HasMany { return $this->hasMany(Department::class); }
    public function scopeActive(Builder $query): Builder { return $query->where('is_active', true); }
}
```

### `app/Models/ForeignLanguageGroup.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForeignLanguageGroup extends Model
{
    protected $fillable = ['name', 'short_name'];

    public function subgroups(): HasMany { return $this->hasMany(Subgroup::class); }
}
```

### `app/Models/Group.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'specialty_id', 'department_id', 'academic_year_id', 'name', 'short_name',
        'current_course', 'students_count', 'shift', 'status',
        'enrollment_date', 'graduation_date', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean', 'current_course' => 'integer',
            'students_count' => 'integer', 'shift' => 'integer',
            'enrollment_date' => 'date', 'graduation_date' => 'date',
        ];
    }

    public function specialty(): BelongsTo { return $this->belongsTo(Specialty::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function subgroups(): HasMany { return $this->hasMany(Subgroup::class); }
    public function groupBuildings(): HasMany { return $this->hasMany(GroupBuilding::class); }
    public function buildings(): BelongsToMany
    {
        return $this->belongsToMany(Building::class, 'group_buildings')
            ->withPivot('is_primary', 'notes')->withTimestamps();
    }
    public function scheduleLessons(): HasMany { return $this->hasMany(ScheduleLesson::class); }
    public function curriculumAssignments(): HasMany { return $this->hasMany(GroupCurriculumAssignment::class); }
    public function curriculumPlans(): BelongsToMany
    {
        return $this->belongsToMany(CurriculumPlan::class, 'group_curriculum_assignments')
            ->withPivot('assigned_at', 'assigned_by', 'is_active', 'notes')->withTimestamps();
    }
    public function dayBuildings(): HasMany { return $this->hasMany(GroupDayBuilding::class); }
    public function hoursTrackings(): HasMany { return $this->hasMany(HoursTracking::class); }
    public function scopeActive(Builder $query): Builder { return $query->where('is_active', true); }
    public function scopeByCourse(Builder $query, int $course): Builder { return $query->where('current_course', $course); }
    public function scopeByShift(Builder $query, int $shift): Builder { return $query->where('shift', $shift); }
    public function scopeFirstShift(Builder $query): Builder { return $query->where('shift', 1); }
    public function scopeSecondShift(Builder $query): Builder { return $query->where('shift', 2); }

    public function getFullNameAttribute(): string
    {
        return "{$this->name} ({$this->current_course} курс)";
    }

    public function calculateCurrentCourse(): int
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        if (! $currentYear || ! $this->enrollment_date) return $this->current_course;
        $enrollmentYear = (int) Carbon::parse($this->enrollment_date)->format('Y');
        return max(1, $currentYear->year_start - $enrollmentYear + 1);
    }

    public function getCurrentSemester(): int
    {
        $now = Carbon::now();
        $yearStart = $this->academicYear?->date_start
            ? Carbon::parse($this->academicYear->date_start)
            : Carbon::createFromDate($now->year, 9, 1);
        $isFirstSemester = $now->lessThan($yearStart->copy()->addMonths(6));
        return $isFirstSemester ? $this->current_course * 2 - 1 : $this->current_course * 2;
    }

    public function getWorkingDays(): array
    {
        $key = $this->current_course <= 2 ? 'working_days_course_1_2' : 'working_days_course_3_4';
        $setting = SystemSetting::where('key', $key)->first();
        if ($setting && $setting->value) {
            $days = json_decode($setting->value, true);
            if (is_array($days) && $days !== []) return $days;
        }
        return $this->current_course <= 2 ? [1, 2, 3, 4, 5] : [2, 3, 4, 5, 6];
    }

    public function getAllowedLessonNumbers(): array
    {
        $key = 'lesson_numbers_course_'.$this->current_course;
        $setting = SystemSetting::where('key', $key)->first();
        if ($setting && $setting->value) {
            $numbers = json_decode($setting->value, true);
            if (is_array($numbers) && $numbers !== []) return $numbers;
        }
        return [];
    }

    public function getAllowedLessonNumbersForDay(int $dayOfWeek): array
    {
        $all = $this->getAllowedLessonNumbers();
        if (is_array($all) && isset($all[$dayOfWeek]) && is_array($all[$dayOfWeek])) {
            return array_values($all[$dayOfWeek]);
        }
        return [];
    }

    public function promote(): void { $this->increment('current_course'); }
    public function graduate(): void { $this->update(['status' => 'graduated']); }

    public function isOnPractice(Carbon $date): bool
    {
        $assignment = $this->curriculumAssignments()->where('is_active', true)->first();
        if (! $assignment) return false;
        return CurriculumPractice::where('curriculum_plan_id', $assignment->curriculum_plan_id)
            ->where('course_number', $this->current_course)
            ->where('start_date', '<=', $date->format('Y-m-d'))
            ->where('end_date', '>=', $date->format('Y-m-d'))
            ->exists();
    }
}
```

### `app/Models/GroupBuilding.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupBuilding extends Model
{
    protected $fillable = ['group_id', 'building_id', 'is_primary', 'notes'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
    public function building(): BelongsTo { return $this->belongsTo(Building::class); }
}
```

### `app/Models/GroupCurriculumAssignment.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupCurriculumAssignment extends Model
{
    protected $fillable = [
        'group_id', 'curriculum_plan_id', 'assigned_at', 'assigned_by',
        'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'assigned_at' => 'datetime'];
    }

    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
    public function curriculumPlan(): BelongsTo { return $this->belongsTo(CurriculumPlan::class); }
    public function assignedBy(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
}
```

### `app/Models/GroupDayBuilding.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupDayBuilding extends Model
{
    protected $fillable = ['group_id', 'date', 'building_id', 'auto_determined'];

    protected function casts(): array
    {
        return ['date' => 'date', 'auto_determined' => 'boolean'];
    }

    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
    public function building(): BelongsTo { return $this->belongsTo(Building::class); }
}
```

### `app/Models/Holiday.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['date', 'name', 'type', 'description', 'year'];

    protected function casts(): array
    {
        return ['date' => 'date', 'year' => 'integer'];
    }
}
```

### `app/Models/HoursTracking.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HoursTracking extends Model
{
    protected $table = 'hours_tracking';

    protected $fillable = [
        'group_id', 'discipline_id', 'teacher_id', 'semester_id',
        'academic_year_id', 'lesson_type_id', 'date', 'hours_conducted',
        'schedule_lesson_id', 'is_cancelled', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hours_conducted' => 'decimal:2',
            'is_cancelled' => 'boolean',
        ];
    }

    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
    public function discipline(): BelongsTo { return $this->belongsTo(CurriculumDiscipline::class, 'discipline_id'); }
    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function semester(): BelongsTo { return $this->belongsTo(CurriculumSemester::class, 'semester_id'); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function lessonType(): BelongsTo { return $this->belongsTo(LessonType::class); }
    public function scheduleLesson(): BelongsTo { return $this->belongsTo(ScheduleLesson::class); }
}
```

### `app/Models/ImportLog.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    protected $fillable = [
        'user_id', 'file_name', 'file_path', 'file_size', 'type',
        'status', 'records_total', 'records_imported', 'records_failed',
        'errors', 'warnings', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'records_total' => 'integer', 'records_imported' => 'integer',
            'records_failed' => 'integer', 'errors' => 'array',
            'warnings' => 'array', 'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
```

### `app/Models/LessonType.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonType extends Model
{
    protected $fillable = [
        'name', 'short_name', 'code', 'color', 'icon',
        'requires_lab', 'is_control_form', 'hours_coefficient',
    ];

    protected function casts(): array
    {
        return [
            'requires_lab' => 'boolean',
            'is_control_form' => 'boolean',
            'hours_coefficient' => 'decimal:2',
        ];
    }
}
```

### `app/Models/Notification.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'type', 'title', 'message', 'data',
        'is_read', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
```

### `app/Models/Permission.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = ['name', 'slug', 'module', 'description'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')->withTimestamps();
    }
}
```

### `app/Models/QualificationType.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QualificationType extends Model
{
    protected $fillable = ['name', 'code'];
}
```

### `app/Models/Role.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withPivot('department_id')->withTimestamps();
    }
}
```

### `app/Models/Room.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'building_id', 'room_type_id', 'number', 'name', 'capacity',
        'area', 'floor', 'description', 'is_active',
        'is_available_for_booking', 'notes', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean', 'is_available_for_booking' => 'boolean',
            'capacity' => 'integer', 'floor' => 'integer', 'area' => 'decimal:2',
        ];
    }

    public function building(): BelongsTo { return $this->belongsTo(Building::class); }
    public function roomType(): BelongsTo { return $this->belongsTo(RoomType::class); }
    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(EquipmentType::class, 'room_equipment')
            ->withPivot('quantity', 'notes')->withTimestamps();
    }
    public function roomEquipment(): HasMany { return $this->hasMany(RoomEquipment::class); }
    public function teacherRooms(): HasMany { return $this->hasMany(TeacherRoom::class); }
    public function scopeActive(Builder $query): Builder { return $query->where('is_active', true); }
}
```

### `app/Models/RoomEquipment.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomEquipment extends Model
{
    protected $fillable = ['room_id', 'equipment_type_id', 'quantity', 'notes'];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function room(): BelongsTo { return $this->belongsTo(Room::class); }
    public function equipmentType(): BelongsTo { return $this->belongsTo(EquipmentType::class); }
}
```

### `app/Models/RoomType.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    protected $fillable = ['name', 'short_name', 'color', 'icon', 'can_be_shared'];

    protected function casts(): array
    {
        return ['can_be_shared' => 'boolean'];
    }

    public function rooms(): HasMany { return $this->hasMany(Room::class); }
}
```

### `app/Models/RoomUnavailability.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomUnavailability extends Model
{
    protected $fillable = [
        'room_id', 'type', 'date_from', 'date_to', 'all_day',
        'time_from', 'time_to', 'reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'all_day' => 'boolean', 'date_from' => 'date', 'date_to' => 'date',
            'time_from' => 'datetime:H:i', 'time_to' => 'datetime:H:i',
        ];
    }

    public function room(): BelongsTo { return $this->belongsTo(Room::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
```

### `app/Models/ScheduleConflict.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleConflict extends Model
{
    protected $fillable = [
        'version_id', 'conflict_type', 'severity', 'date', 'lesson_number',
        'group_id', 'teacher_id', 'room_id', 'discipline_id', 'description',
        'suggestion', 'is_resolved', 'resolved_by', 'resolved_at', 'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'is_resolved' => 'boolean', 'date' => 'date',
            'lesson_number' => 'integer', 'resolved_at' => 'datetime',
        ];
    }

    public function version(): BelongsTo { return $this->belongsTo(ScheduleVersion::class, 'version_id'); }
    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function room(): BelongsTo { return $this->belongsTo(Room::class); }
    public function discipline(): BelongsTo { return $this->belongsTo(CurriculumDiscipline::class, 'discipline_id'); }
    public function resolvedBy(): BelongsTo { return $this->belongsTo(User::class, 'resolved_by'); }
}
```

### `app/Models/ScheduleLesson.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleLesson extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'version_id', 'date', 'lesson_number', 'shift', 'group_id',
        'subgroup_id', 'discipline_id', 'lesson_type_id', 'teacher_id',
        'room_id', 'building_id', 'week_type_id', 'is_auto_generated',
        'is_replacement', 'original_lesson_id', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_auto_generated' => 'boolean', 'is_replacement' => 'boolean',
            'date' => 'date', 'lesson_number' => 'integer', 'shift' => 'integer',
        ];
    }

    public function version(): BelongsTo { return $this->belongsTo(ScheduleVersion::class, 'version_id'); }
    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
    public function subgroup(): BelongsTo { return $this->belongsTo(Subgroup::class); }
    public function discipline(): BelongsTo { return $this->belongsTo(CurriculumDiscipline::class, 'discipline_id'); }
    public function lessonType(): BelongsTo { return $this->belongsTo(LessonType::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function room(): BelongsTo { return $this->belongsTo(Room::class); }
    public function building(): BelongsTo { return $this->belongsTo(Building::class); }
    public function weekType(): BelongsTo { return $this->belongsTo(WeekType::class); }
    public function originalLesson(): BelongsTo { return $this->belongsTo(__CLASS__, 'original_lesson_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeForDate(Builder $query, $date): Builder { return $query->where('date', $date); }
    public function scopeForGroup(Builder $query, $groupId): Builder { return $query->where('group_id', $groupId); }
    public function scopeForTeacher(Builder $query, $teacherId): Builder { return $query->where('teacher_id', $teacherId); }
    public function scopeForRoom(Builder $query, $roomId): Builder { return $query->where('room_id', $roomId); }
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereHas('version', fn (Builder $q) => $q->where('status', 'published'));
    }

    public function getTimeStartAttribute(): string
    {
        $bell = BellSchedule::where('shift_number', $this->shift)
            ->where('lesson_number', $this->lesson_number)->first();
        return $bell?->time_start ?? '';
    }

    public function getTimeEndAttribute(): string
    {
        $bell = BellSchedule::where('shift_number', $this->shift)
            ->where('lesson_number', $this->lesson_number)->first();
        return $bell?->time_end ?? '';
    }
}
```

### `app/Models/ScheduleVersion.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ScheduleVersion extends Model
{
    protected $fillable = [
        'name', 'academic_year_id', 'department_id', 'date_from', 'date_to',
        'period_type', 'status', 'generation_type', 'generated_at',
        'published_at', 'published_by', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_from' => 'date', 'date_to' => 'date',
            'generated_at' => 'datetime', 'published_at' => 'datetime',
        ];
    }

    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function publishedBy(): BelongsTo { return $this->belongsTo(User::class, 'published_by'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function lessons(): HasMany { return $this->hasMany(ScheduleLesson::class, 'version_id'); }
    public function conflicts(): HasMany { return $this->hasMany(ScheduleConflict::class, 'version_id'); }

    public function publish(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $this->update(['status' => 'published', 'published_at' => now(), 'published_by' => $userId]);
            $this->trackHours();
        });
    }

    public function trackHours(): void
    {
        $lessons = $this->lessons()->with('version')->where('status', '!=', 'cancelled')->get();
        foreach ($lessons as $lesson) {
            $academicYearId = $this->academic_year_id ?? AcademicYear::where('is_current', true)->first()?->id;
            if (! $academicYearId || ! $lesson->discipline_id) continue;
            $semesterId = $this->resolveSemesterId($lesson->discipline_id, $lesson->date, $academicYearId);
            if (! $semesterId) continue;
            if (HoursTracking::where('schedule_lesson_id', $lesson->id)->exists()) continue;
            HoursTracking::create([
                'group_id' => $lesson->group_id, 'discipline_id' => $lesson->discipline_id,
                'teacher_id' => $lesson->teacher_id, 'semester_id' => $semesterId,
                'academic_year_id' => $academicYearId, 'lesson_type_id' => $lesson->lesson_type_id,
                'date' => $lesson->date, 'hours_conducted' => 1,
                'schedule_lesson_id' => $lesson->id, 'is_cancelled' => false,
            ]);
        }
    }

    public function untrackLesson(ScheduleLesson $lesson): void
    {
        HoursTracking::where('schedule_lesson_id', $lesson->id)->delete();
    }

    private function resolveSemesterId(?int $disciplineId, mixed $date, ?int $academicYearId): ?int
    {
        if (! $disciplineId || ! $academicYearId) return null;
        $lessonDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        $academicYear = AcademicYear::find($academicYearId);
        if (! $academicYear) return null;
        $semesterInCourse = 2;
        if ($lessonDate->greaterThanOrEqualTo($academicYear->first_semester_start)
            && $lessonDate->lessThanOrEqualTo($academicYear->first_semester_end)) {
            $semesterInCourse = 1;
        } elseif ($lessonDate->greaterThanOrEqualTo($academicYear->second_semester_start)
            && $lessonDate->lessThanOrEqualTo($academicYear->second_semester_end)) {
            $semesterInCourse = 2;
        }
        return CurriculumSemester::where('discipline_id', $disciplineId)
            ->where('semester_in_course', $semesterInCourse)->first()?->id;
    }
}
```

### `app/Models/Specialty.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Specialty extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'department_id', 'code', 'name', 'short_name', 'qualification',
        'education_level_id', 'study_years', 'study_months', 'base_education',
        'form_of_study', 'max_courses', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean', 'study_years' => 'integer',
            'study_months' => 'integer', 'max_courses' => 'integer',
        ];
    }

    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function educationLevel(): BelongsTo { return $this->belongsTo(EducationLevel::class); }
    public function groups(): HasMany { return $this->hasMany(Group::class); }
    public function curriculumPlans(): HasMany { return $this->hasMany(CurriculumPlan::class); }
    public function scopeActive(Builder $query): Builder { return $query->where('is_active', true); }
}
```

### `app/Models/Subgroup.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subgroup extends Model
{
    protected $fillable = [
        'group_id', 'name', 'number', 'type', 'foreign_language_group_id',
        'students_count', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean', 'number' => 'integer', 'students_count' => 'integer',
        ];
    }

    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
    public function foreignLanguageGroup(): BelongsTo { return $this->belongsTo(ForeignLanguageGroup::class); }
}
```

### `app/Models/SystemSetting.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label', 'description', 'is_editable'];

    protected function casts(): array
    {
        return ['is_editable' => 'boolean'];
    }
}
```

### `app/Models/Teacher.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'department_id', 'position_id', 'last_name', 'first_name',
        'middle_name', 'full_name', 'short_name', 'phone', 'email',
        'internal_phone', 'employment_type', 'rate', 'max_hours_per_week',
        'min_lessons_per_day', 'max_lessons_per_day', 'has_methodical_day',
        'methodical_day_of_week', 'qualification_category', 'academic_degree',
        'hire_date', 'is_active', 'notes', 'working_days', 'working_lesson_numbers',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean', 'rate' => 'decimal:2',
            'working_days' => 'array', 'working_lesson_numbers' => 'array',
            'max_hours_per_week' => 'integer', 'min_lessons_per_day' => 'integer',
            'max_lessons_per_day' => 'integer', 'has_methodical_day' => 'boolean',
            'methodical_day_of_week' => 'integer', 'hire_date' => 'date',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function position(): BelongsTo { return $this->belongsTo(TeacherPosition::class, 'position_id'); }
    public function disciplines(): HasMany { return $this->hasMany(TeacherDiscipline::class); }
    public function rooms(): HasMany { return $this->hasMany(TeacherRoom::class); }
    public function buildings(): HasMany { return $this->hasMany(TeacherBuilding::class); }
    public function unavailabilities(): HasMany { return $this->hasMany(TeacherUnavailability::class); }
    public function dayBuildings(): HasMany { return $this->hasMany(TeacherDayBuilding::class); }
    public function scheduleLessons(): HasMany { return $this->hasMany(ScheduleLesson::class); }
    public function scopeActive(Builder $query): Builder { return $query->where('is_active', true); }

    public function getShortNameAttribute(): string
    {
        if ($this->attributes['short_name'] ?? null) return $this->attributes['short_name'];
        $fi = $this->first_name ? mb_substr($this->first_name, 0, 1).'.' : '';
        $mi = $this->middle_name ? mb_substr($this->middle_name, 0, 1).'.' : '';
        return "{$this->last_name} {$fi}{$mi}";
    }

    public function isAvailableOn($date, $lessonNumber): bool { return true; }

    public function getBuildingForDate($date): ?Building
    {
        $db = $this->dayBuildings()->where('date', $date)->first();
        return $db?->building;
    }
}
```

### `app/Models/TeacherBuilding.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherBuilding extends Model
{
    protected $fillable = ['teacher_id', 'building_id', 'is_primary'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function building(): BelongsTo { return $this->belongsTo(Building::class); }
}
```

### `app/Models/TeacherDayBuilding.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherDayBuilding extends Model
{
    protected $fillable = ['teacher_id', 'date', 'building_id'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function building(): BelongsTo { return $this->belongsTo(Building::class); }
}
```

### `app/Models/TeacherDiscipline.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherDiscipline extends Model
{
    protected $fillable = [
        'teacher_id', 'discipline_id', 'group_id', 'subgroup_id',
        'academic_year_id', 'is_primary', 'planned_hours', 'actual_hours', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean', 'planned_hours' => 'integer', 'actual_hours' => 'integer',
        ];
    }

    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function discipline(): BelongsTo { return $this->belongsTo(CurriculumDiscipline::class, 'discipline_id'); }
    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
    public function subgroup(): BelongsTo { return $this->belongsTo(Subgroup::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function semesters(): HasMany { return $this->hasMany(TeacherDisciplineSemester::class); }
}
```

### `app/Models/TeacherDisciplineSemester.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherDisciplineSemester extends Model
{
    protected $fillable = [
        'teacher_discipline_id', 'curriculum_semester_id',
        'planned_hours', 'actual_hours', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'planned_hours' => 'integer', 'actual_hours' => 'integer', 'is_active' => 'boolean',
        ];
    }

    public function teacherDiscipline(): BelongsTo { return $this->belongsTo(TeacherDiscipline::class); }
    public function curriculumSemester(): BelongsTo { return $this->belongsTo(CurriculumSemester::class); }
}
```

### `app/Models/TeacherPosition.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherPosition extends Model
{
    protected $fillable = ['name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function teachers(): HasMany { return $this->hasMany(Teacher::class, 'position_id'); }
    public function scopeActive(Builder $query): Builder { return $query->where('is_active', true); }
}
```

### `app/Models/TeacherRoom.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherRoom extends Model
{
    protected $fillable = ['teacher_id', 'room_id', 'priority', 'is_personal', 'notes'];

    protected function casts(): array
    {
        return ['priority' => 'integer', 'is_personal' => 'boolean'];
    }

    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function room(): BelongsTo { return $this->belongsTo(Room::class); }
}
```

### `app/Models/TeacherUnavailability.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherUnavailability extends Model
{
    protected $fillable = [
        'teacher_id', 'type', 'date_from', 'date_to', 'reason',
        'all_day', 'time_from', 'time_to', 'is_approved', 'approved_by', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'all_day' => 'boolean', 'is_approved' => 'boolean',
            'date_from' => 'date', 'date_to' => 'date',
            'time_from' => 'datetime:H:i', 'time_to' => 'datetime:H:i',
        ];
    }

    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
```

### `app/Models/User.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'teacher_id', 'department_id', 'is_active', 'last_login_at', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('department_id')->withTimestamps();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return $this->roles()->whereHas('permissions', fn ($q) => $q->where('slug', $permissionSlug))->exists();
    }

    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    public function activityLogs(): HasMany { return $this->hasMany(ActivityLog::class); }
    public function notifications(): HasMany { return $this->hasMany(Notification::class); }
    public function importLogs(): HasMany { return $this->hasMany(ImportLog::class); }
    public function exportLogs(): HasMany { return $this->hasMany(ExportLog::class); }
    public function createdCurriculumPlans(): HasMany { return $this->hasMany(CurriculumPlan::class, 'created_by'); }
    public function createdSchedules(): HasMany { return $this->hasMany(ScheduleVersion::class, 'created_by'); }
    public function publishedSchedules(): HasMany { return $this->hasMany(ScheduleVersion::class, 'published_by'); }
    public function createdLessons(): HasMany { return $this->hasMany(ScheduleLesson::class, 'created_by'); }
    public function createdRoomUnavailabilities(): HasMany { return $this->hasMany(RoomUnavailability::class, 'created_by'); }
    public function assignedGroupCurriculums(): HasMany { return $this->hasMany(GroupCurriculumAssignment::class, 'assigned_by'); }
    public function approvedUnavailabilities(): HasMany { return $this->hasMany(TeacherUnavailability::class, 'approved_by'); }
    public function createdUnavailabilities(): HasMany { return $this->hasMany(TeacherUnavailability::class, 'created_by'); }
    public function resolvedConflicts(): HasMany { return $this->hasMany(ScheduleConflict::class, 'resolved_by'); }
}
```

### `app/Models/Vacation.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vacation extends Model
{
    protected $fillable = [
        'academic_year_id', 'name', 'start_date', 'end_date',
        'duration_days', 'description',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date', 'end_date' => 'date', 'duration_days' => 'integer',
        ];
    }

    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }

    protected static function booted(): void
    {
        static::saving(function (Vacation $vacation) {
            if ($vacation->start_date && $vacation->end_date) {
                $vacation->duration_days = Carbon::parse($vacation->start_date)
                    ->diffInDays(Carbon::parse($vacation->end_date));
            }
        });
    }

    public function includesDate(Carbon $date): bool
    {
        return $date->between(Carbon::parse($this->start_date), Carbon::parse($this->end_date));
    }
}
```

### `app/Models/WeekType.php`
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeekType extends Model
{
    protected $fillable = ['name', 'code'];
}
```

---

## `app/Console/Commands/`

### `app/Console/Commands/BackfillTeacherDisciplineSemesters.php`
```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CurriculumSemester;
use App\Models\TeacherDiscipline;
use App\Models\TeacherDisciplineSemester;
use Illuminate\Console\Command;

class BackfillTeacherDisciplineSemesters extends Command
{
    protected $signature = 'teachers:backfill-semesters';
    protected $description = 'Create semester hour records for existing teacher_disciplines';

    public function handle(): int
    {
        $tds = TeacherDiscipline::all();
        foreach ($tds as $td) {
            $existing = TeacherDisciplineSemester::where('teacher_discipline_id', $td->id)->count();
            if ($existing > 0) continue;
            $semesters = CurriculumSemester::where('discipline_id', $td->discipline_id)->get();
            foreach ($semesters as $semester) {
                TeacherDisciplineSemester::create([
                    'teacher_discipline_id' => $td->id,
                    'curriculum_semester_id' => $semester->id,
                    'planned_hours' => $semester->hours_total,
                ]);
            }
            $this->info("Backfilled {$semesters->count()} semesters for teacher_discipline #{$td->id}");
        }
        return self::SUCCESS;
    }
}
```

### `app/Console/Commands/Curriculum/CurriculumImportXml.php`
```php
<?php

declare(strict_types=1);

namespace App\Console\Commands\Curriculum;

use App\Models\AcademicYear;
use App\Models\Specialty;
use App\Services\Curriculum\CurriculumImportService;
use Illuminate\Console\Command;

class CurriculumImportXml extends Command
{
    protected $signature = 'curriculum:import-xml
        {file : Путь к XML файлу}
        {specialty_id : ID специальности}
        {academic_year_id : ID учебного года}
        {--dry-run : Проверить без импорта}';
    protected $description = 'Импортирует учебный план из XML';

    public function handle(CurriculumImportService $importService): int
    {
        $file = $this->argument('file');
        $specialtyId = (int) $this->argument('specialty_id');
        $academicYearId = (int) $this->argument('academic_year_id');

        if (! file_exists($file) || ! is_readable($file)) {
            $this->components->error("Файл не найден или недоступен: {$file}");
            return Command::FAILURE;
        }

        $specialty = Specialty::find($specialtyId);
        if ($specialty === null) {
            $this->components->error("Специальность #{$specialtyId} не найдена.");
            return Command::FAILURE;
        }

        $academicYear = AcademicYear::find($academicYearId);
        if ($academicYear === null) {
            $this->components->error("Учебный год #{$academicYearId} не найден.");
            return Command::FAILURE;
        }

        if ($this->option('dry-run')) {
            $preview = $importService->preview($file);
            if ($preview->hasErrors()) {
                $this->components->error('Ошибки валидации:');
                foreach ($preview->errors as $error) {
                    $this->components->twoColumnDetail('Ошибка', $error);
                }
                return Command::FAILURE;
            }
            $this->components->info('Предварительный просмотр:');
            $this->components->twoColumnDetail('Специальность', $preview->specialty['name'] ?? '—');
            $this->components->twoColumnDetail('Дисциплин', (string) $preview->getDisciplineCount());
            $this->components->twoColumnDetail('Семестров', (string) $preview->getSemesterCount());
            // ... table display omitted for brevity
            $this->components->info('Режим просмотра. Никаких изменений не применено.');
            return Command::SUCCESS;
        }

        $result = $importService->import($file, $specialtyId, $academicYearId);
        if (! $result->success) {
            $this->components->error('Ошибка импорта:');
            foreach ($result->errors as $error) {
                $this->components->twoColumnDetail('Ошибка', $error);
            }
            return Command::FAILURE;
        }

        $this->components->info('Импорт завершён:');
        $this->table(
            ['Параметр', 'Значение'],
            [
                ['Импортировано дисциплин', (string) $result->disciplinesImported],
                ['Импортировано семестров', (string) $result->semestersImported],
                ['ID учебного плана', $result->curriculumPlanId !== null ? "#{$result->curriculumPlanId}" : '—'],
            ],
        );
        return Command::SUCCESS;
    }
}
```

### `app/Console/Commands/Schedule/NotifyUpcomingPractices.php`
```php
<?php

declare(strict_types=1);

namespace App\Console\Commands\Schedule;

use App\Models\CurriculumPractice;
use App\Models\Group;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyUpcomingPractices extends Command
{
    protected $signature = 'schedule:notify-practices';
    protected $description = 'Отправляет уведомления за 7 дней до начала практик у групп';

    public function handle(): int
    {
        $targetDate = Carbon::today()->addDays(7)->format('Y-m-d');
        $practices = CurriculumPractice::where('start_date', $targetDate)->with('curriculumPlan.specialty')->get();

        if ($practices->isEmpty()) {
            $this->info("На {$targetDate} начало новых практик не запланировано.");
            return self::SUCCESS;
        }

        $usersToNotify = User::whereHas('roles', fn ($q) => $q->whereIn('slug', ['admin', 'superadmin', 'dispatcher']))->get();
        if ($usersToNotify->isEmpty()) {
            $this->warn('Нет пользователей с ролями admin/dispatcher для получения уведомлений.');
            return self::SUCCESS;
        }

        $notificationsSent = 0;
        foreach ($practices as $practice) {
            $groups = Group::active()->where('current_course', $practice->course_number)
                ->whereHas('curriculumAssignments', fn ($q) => $q->where('curriculum_plan_id', $practice->curriculum_plan_id)->where('is_active', true))
                ->get();
            if ($groups->isEmpty()) continue;

            $groupNames = $groups->pluck('name')->implode(', ');
            $typeLabel = $practice->type === 'edu_practice' ? 'Учебная практика' : 'Производственная практика';
            $title = '⚠️ Внимание: Скоро практика!';
            $message = "Через 7 дней ({$targetDate}) начинается {$typeLabel} ({$practice->symbol}) у групп: {$groupNames}. Не забудьте скорректировать расписание.";

            foreach ($usersToNotify as $user) {
                Notification::create([
                    'user_id' => $user->id, 'type' => 'practice_reminder',
                    'title' => $title, 'message' => $message,
                    'is_read' => false, 'created_at' => now(),
                ]);
                $notificationsSent++;
            }
        }

        $this->info("Сгенерировано {$notificationsSent} уведомлений о практиках на {$targetDate}.");
        return self::SUCCESS;
    }
}
```

### `app/Console/Commands/Schedule/ReportsHoursDeficit.php`
```php
<?php

declare(strict_types=1);

namespace App\Console\Commands\Schedule;

use App\Models\Department;
use App\Services\Schedule\HoursTrackingService;
use Illuminate\Console\Command;

class ReportsHoursDeficit extends Command
{
    protected $signature = 'reports:hours-deficit {--department= : ID кафедры} {--academic-year= : ID учебного года}';
    protected $description = 'Отчёт по дефициту часов по кафедре';

    public function handle(HoursTrackingService $hoursTracking): int
    {
        $departmentId = $this->option('department');
        $academicYearId = $this->option('academic-year');

        if ($departmentId === null) {
            $this->components->error('Укажите ID кафедры через --department.');
            return Command::FAILURE;
        }

        $department = Department::find((int) $departmentId);
        if ($department === null) {
            $this->components->error("Кафедра #{$departmentId} не найдена.");
            return Command::FAILURE;
        }

        $report = $hoursTracking->getHoursDeficitReport((int) $departmentId);
        if ($report === []) {
            $this->components->info("Дефицит часов по кафедре {$department->name} не найден.");
            return Command::SUCCESS;
        }

        $this->components->info("Отчёт по дефициту часов: {$department->name}");
        $this->table(
            ['Группа', 'Дисциплина', 'Осталось часов'],
            array_map(fn (array $row) => [$row['group_name'], $row['discipline_name'], (string) $row['remaining_hours']], $report),
        );

        $totalDeficit = array_sum(array_column($report, 'remaining_hours'));
        $this->components->twoColumnDetail('Всего групп', (string) count(array_unique(array_column($report, 'group_id'))));
        $this->components->twoColumnDetail('Всего дисциплин с дефицитом', (string) count($report));
        $this->components->twoColumnDetail('Общий дефицит часов', (string) $totalDeficit);
        return Command::SUCCESS;
    }
}
```

### `app/Console/Commands/Schedule/ScheduleCheckConflicts.php`
```php
<?php

declare(strict_types=1);

namespace App\Console\Commands\Schedule;

use App\Models\ScheduleVersion;
use App\Services\Schedule\ConflictCheckerService;
use Illuminate\Console\Command;

class ScheduleCheckConflicts extends Command
{
    protected $signature = 'schedule:check-conflicts {version_id}';
    protected $description = 'Проверяет конфликты в версии расписания';

    public function handle(ConflictCheckerService $conflictChecker): int
    {
        $versionId = (int) $this->argument('version_id');
        $version = ScheduleVersion::find($versionId);

        if ($version === null) {
            $this->components->error("Версия #{$versionId} не найдена.");
            return Command::FAILURE;
        }

        $conflicts = $conflictChecker->checkVersion($versionId);
        if ($conflicts === []) {
            $this->components->info('Конфликтов не найдено.');
            return Command::SUCCESS;
        }

        $this->components->info('Найдено конфликтов: '.count($conflicts));
        $this->table(
            ['ID', 'Тип', 'Серьёзность', 'Дата', 'Пара', 'Описание'],
            array_map(fn (array $c) => [$c['id'], $c['conflict_type'], $c['severity'], $c['date'], $c['lesson_number'], $c['description']], $conflicts),
        );

        $resolved = count(array_filter($conflicts, fn (array $c) => $c['is_resolved'] ?? false));
        $unresolved = count($conflicts) - $resolved;
        $this->components->twoColumnDetail('Решено', (string) $resolved);
        $this->components->twoColumnDetail('Не решено', (string) $unresolved);
        return Command::SUCCESS;
    }
}
```

### `app/Console/Commands/Schedule/ScheduleExportExcel.php`
```php
<?php

declare(strict_types=1);

namespace App\Console\Commands\Schedule;

use App\Models\Department;
use App\Models\ScheduleVersion;
use App\Services\Export\ExcelExportService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ScheduleExportExcel extends Command
{
    protected $signature = 'schedule:export-excel
        {version_id : ID версии расписания}
        {department_id : ID кафедры}
        {--output= : Путь для сохранения файла}';
    protected $description = 'Экспортирует расписание в Excel';

    public function handle(ExcelExportService $exportService): int
    {
        $versionId = (int) $this->argument('version_id');
        $departmentId = (int) $this->argument('department_id');

        $version = ScheduleVersion::find($versionId);
        if ($version === null) {
            $this->components->error("Версия #{$versionId} не найдена.");
            return Command::FAILURE;
        }

        $department = Department::find($departmentId);
        if ($department === null) {
            $this->components->error("Кафедра #{$departmentId} не найдена.");
            return Command::FAILURE;
        }

        try {
            $filePath = $exportService->exportScheduleByDepartment(
                departmentId: $departmentId,
                dateFrom: $version->date_from instanceof Carbon ? $version->date_from : Carbon::parse($version->date_from),
                dateTo: $version->date_to instanceof Carbon ? $version->date_to : Carbon::parse($version->date_to),
                versionId: $versionId,
            );
        } catch (\Exception $e) {
            $this->components->error("Ошибка экспорта: {$e->getMessage()}");
            return Command::FAILURE;
        }

        $this->components->info('Экспорт выполнен успешно.');
        $this->components->twoColumnDetail('Файл', $filePath);
        return Command::SUCCESS;
    }
}
```

### `app/Console/Commands/Schedule/ScheduleGenerate.php`
```php
<?php

declare(strict_types=1);

namespace App\Console\Commands\Schedule;

use App\Services\Schedule\ScheduleGeneratorService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ScheduleGenerate extends Command
{
    protected $signature = 'schedule:generate
        {period : day|week|month}
        {date : Дата в формате Y-m-d}
        {--groups=* : ID групп}
        {--department= : ID кафедры}
        {--publish : Сразу опубликовать}';
    protected $description = 'Генерирует расписание на указанный период';

    public function handle(ScheduleGeneratorService $generator): int
    {
        $period = $this->argument('period');
        $date = $this->argument('date');
        $groupIds = $this->option('groups');

        if (! in_array($period, ['day', 'week', 'month'], true)) {
            $this->components->error('Период должен быть: day, week или month.');
            return Command::FAILURE;
        }

        try {
            $carbonDate = Carbon::parse($date);
        } catch (\Exception $e) {
            $this->components->error("Некорректная дата: {$date}. Используйте формат Y-m-d.");
            return Command::FAILURE;
        }

        $result = match ($period) {
            'day' => $generator->generateForDay($carbonDate, $groupIds),
            'week' => $generator->generateForWeek($carbonDate, $groupIds),
            'month' => $generator->generateForMonth((int) $carbonDate->year, (int) $carbonDate->month, $groupIds),
        };

        if (! $result->success) {
            $this->components->error($result->message);
            return Command::FAILURE;
        }

        $this->components->info('Генерация завершена:');
        $this->table(
            ['Параметр', 'Значение'],
            [
                ['Всего занятий', (string) $result->totalLessons],
                ['Конфликтов', (string) $result->conflicts],
                ['Версия', $result->version !== null ? "#{$result->version->id}" : '—'],
                ['Статус', $result->version?->status ?? '—'],
            ],
        );

        if ($this->option('publish') && $result->version !== null) {
            $result->version->update(['status' => 'published', 'published_at' => now()]);
            $this->components->info("Версия #{$result->version->id} опубликована.");
        }

        return Command::SUCCESS;
    }
}
```

### `app/Console/Commands/Schedule/ScheduleImportHolidays.php`
```php
<?php

declare(strict_types=1);

namespace App\Console\Commands\Schedule;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ScheduleImportHolidays extends Command
{
    protected $signature = 'schedule:import-holidays {year : Год для импорта праздников}';
    protected $description = 'Импортирует государственные праздники России на указанный год';

    public function handle(): int
    {
        $year = (int) $this->argument('year');
        if ($year < 2000 || $year > 2100) {
            $this->components->error('Год должен быть в диапазоне 2000–2100.');
            return Command::FAILURE;
        }

        $holidays = $this->getRussianHolidays($year);
        $imported = 0;
        $skipped = 0;

        foreach ($holidays as $holiday) {
            Holiday::firstOrCreate(
                ['date' => $holiday['date'], 'year' => $year],
                ['date' => $holiday['date'], 'name' => $holiday['name'], 'type' => 'public', 'year' => $year, 'description' => $holiday['description'] ?? null],
            )->wasRecentlyCreated ? $imported++ : $skipped++;
        }

        $this->components->info("Импорт праздников на {$year} год завершён:");
        $this->table(
            ['Параметр', 'Значение'],
            [['Всего праздников', (string) count($holidays)], ['Импортировано', (string) $imported], ['Пропущено (уже есть)', (string) $skipped]],
        );
        return Command::SUCCESS;
    }

    private function getRussianHolidays(int $year): array
    {
        return [
            ['date' => "{$year}-01-01", 'name' => 'Новый год', 'description' => 'Новогодние каникулы'],
            ['date' => "{$year}-01-02", 'name' => 'Новый год'],
            ['date' => "{$year}-01-03", 'name' => 'Новый год'],
            ['date' => "{$year}-01-04", 'name' => 'Новый год'],
            ['date' => "{$year}-01-05", 'name' => 'Новый год'],
            ['date' => "{$year}-01-06", 'name' => 'Новый год'],
            ['date' => "{$year}-01-07", 'name' => 'Рождество Христово'],
            ['date' => "{$year}-01-08", 'name' => 'Новый год'],
            ['date' => "{$year}-02-23", 'name' => 'День защитника Отечества'],
            ['date' => "{$year}-03-08", 'name' => 'Международный женский день'],
            ['date' => "{$year}-05-01", 'name' => 'Праздник Весны и Труда'],
            ['date' => "{$year}-05-09", 'name' => 'День Победы'],
            ['date' => "{$year}-06-12", 'name' => 'День России'],
            ['date' => "{$year}-11-04", 'name' => 'День народного единства'],
        ];
    }
}
```

### `app/Console/Commands/Schedule/SchedulePromoteGroups.php`
```php
<?php

declare(strict_types=1);

namespace App\Console\Commands\Schedule;

use App\Models\Group;
use App\Services\GroupPromotionService;
use Illuminate\Console\Command;

class SchedulePromoteGroups extends Command
{
    protected $signature = 'schedule:promote-groups {--dry-run : Показать без применения} {--force : Без подтверждения}';
    protected $description = 'Переводит группы на следующий курс';

    public function handle(GroupPromotionService $promotionService): int
    {
        $toPromote = $promotionService->getGroupsForPromotion();
        $toGraduate = $promotionService->getGroupsForGraduation();

        if ($toPromote->isEmpty() && $toGraduate->isEmpty()) {
            $this->components->info('Нет групп для перевода или выпуска.');
            return Command::SUCCESS;
        }

        $this->components->twoColumnDetail('Групп к переводу', (string) $toPromote->count());
        $this->components->twoColumnDetail('Групп к выпуску', (string) $toGraduate->count());

        if ($this->option('dry-run')) {
            $this->components->info('Режим просмотра. Никаких изменений не применено.');
            return Command::SUCCESS;
        }

        if (! $this->option('force') && ! $this->components->confirm('Применить перевод и выпуск групп?')) {
            $this->components->info('Операция отменена.');
            return Command::SUCCESS;
        }

        $results = $promotionService->promoteAllGroups();
        $this->components->info('Результаты:');
        $this->table(
            ['Операция', 'Количество'],
            [['Переведено', $results['promoted']], ['Выпущено', $results['graduated']]],
        );
        return Command::SUCCESS;
    }
}
```

---

## `app/Services/`

### `app/Services/Curriculum/CurriculumImportService.php`
```php
<?php

declare(strict_types=1);

namespace App\Services\Curriculum;

use App\DTOs\ImportResult;
use App\DTOs\ParsedCurriculum;
use App\Models\AcademicYear;
use App\Models\CurriculumPlan;
use App\Models\Specialty;

class CurriculumImportService
{
    public function __construct(
        private readonly CurriculumXmlParserService $parser,
    ) {}

    public function import(string $xmlPath, int $specialtyId, int $academicYearId, ?int $userId = null): ImportResult
    {
        $validation = $this->parser->validateXml($xmlPath);
        if (! $validation['valid']) {
            return ImportResult::fail(error: 'XML validation failed: '.implode('; ', $validation['errors']));
        }

        $parsed = $this->parser->parse($xmlPath);
        if ($parsed->hasErrors()) {
            return ImportResult::fail(error: 'Parsing failed: '.implode('; ', $parsed->errors));
        }

        $specialty = Specialty::find($specialtyId);
        if ($specialty === null) return ImportResult::fail(error: "Specialty with ID {$specialtyId} not found.");
        $academicYear = AcademicYear::find($academicYearId);
        if ($academicYear === null) return ImportResult::fail(error: "Academic year with ID {$academicYearId} not found.");

        $plan = CurriculumPlan::create([
            'specialty_id' => $specialtyId, 'academic_year_id' => $academicYearId,
            'name' => $parsed->specialty['name'] ?? "{$specialty->name} ({$academicYear->name})",
            'xml_file_path' => $xmlPath, 'created_by' => $userId,
        ]);

        return $this->parser->importToPlan($parsed, $plan);
    }

    public function preview(string $xmlPath): ParsedCurriculum
    {
        $validation = $this->parser->validateXml($xmlPath);
        if (! $validation['valid']) {
            return new ParsedCurriculum(specialty: [], disciplines: [], semesters: [], errors: $validation['errors']);
        }
        return $this->parser->parse($xmlPath);
    }
}
```

### `app/Services/Curriculum/CurriculumXmlParserService.php`
```php
<?php

declare(strict_types=1);

namespace App\Services\Curriculum;

use App\DTOs\ImportResult;
use App\DTOs\ParsedCurriculum;
use App\Models\CurriculumDiscipline;
use App\Models\CurriculumPlan;
use App\Models\CurriculumSemester;
use SimpleXMLElement;

class CurriculumXmlParserService
{
    public function parse(string $xmlPath): ParsedCurriculum
    {
        $encoding = config('curriculum.xml_encoding', 'windows-1251');
        $xmlContent = file_get_contents($xmlPath);
        if ($xmlContent === false) {
            return new ParsedCurriculum(specialty: [], disciplines: [], semesters: [], errors: ["Unable to read XML file: {$xmlPath}"]);
        }
        $converted = mb_convert_encoding($xmlContent, 'UTF-8', $encoding);
        $xml = simplexml_load_string($converted);
        if ($xml === false) {
            return new ParsedCurriculum(specialty: [], disciplines: [], semesters: [], errors: ['Invalid XML structure.']);
        }

        $specialty = $this->extractSpecialty($xml);
        $disciplines = $this->extractDisciplines($xml);
        $semesters = $this->extractSemesters($xml);
        $warnings = $specialty === [] ? ['No <Speciality> tag found in XML.'] : [];

        return new ParsedCurriculum(specialty: $specialty, disciplines: $disciplines, semesters: $semesters, warnings: $warnings);
    }

    public function validateXml(string $xmlPath): array
    {
        if (! file_exists($xmlPath)) return ['valid' => false, 'errors' => ["File not found: {$xmlPath}"]];
        if (! is_readable($xmlPath)) return ['valid' => false, 'errors' => ["File is not readable: {$xmlPath}"]];

        $encoding = config('curriculum.xml_encoding', 'windows-1251');
        $xmlContent = file_get_contents($xmlPath);
        if ($xmlContent === false) return ['valid' => false, 'errors' => ['Unable to read file content.']];

        $converted = mb_convert_encoding($xmlContent, 'UTF-8', $encoding);
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($converted);

        if ($xml === false) {
            $errors = array_map(fn ($e) => trim($e->message), libxml_get_errors());
            libxml_clear_errors();
            return ['valid' => false, 'errors' => $errors];
        }

        $errors = [];
        if (! isset($xml->Speciality)) $errors[] = 'Missing required <Speciality> tag.';
        if (! isset($xml->SemesterTable)) $errors[] = 'Missing required <SemesterTable> tag.';

        return ['valid' => $errors === [], 'errors' => $errors];
    }

    public function importToPlan(ParsedCurriculum $data, CurriculumPlan $plan): ImportResult
    {
        if ($data->hasErrors()) return ImportResult::fail(implode('; ', $data->errors));

        $disciplinesImported = 0;
        $semestersImported = 0;

        foreach ($data->disciplines as $disciplineData) {
            CurriculumDiscipline::create([
                'curriculum_plan_id' => $plan->id, 'name' => $disciplineData['name'] ?? '',
                'short_name' => $disciplineData['short_name'] ?? '', 'code' => $disciplineData['code'] ?? '',
                'cycle' => $disciplineData['cycle'] ?? null, 'discipline_type' => $disciplineData['discipline_type'] ?? null,
                'is_federal' => $disciplineData['is_federal'] ?? false, 'sort_order' => $disciplineData['sort_order'] ?? 0,
            ]);
            $disciplinesImported++;
        }

        foreach ($data->semesters as $semesterData) {
            $discipline = CurriculumDiscipline::where('curriculum_plan_id', $plan->id)
                ->where('code', $semesterData['discipline_code'] ?? '')->first();
            if ($discipline === null) continue;

            CurriculumSemester::create([
                'discipline_id' => $discipline->id, 'course_number' => $semesterData['course_number'] ?? 1,
                'semester_number' => $semesterData['semester_number'] ?? 1, 'semester_in_course' => $semesterData['semester_in_course'] ?? 1,
                'hours_total' => $semesterData['hours_total'] ?? 0, 'hours_lecture' => $semesterData['hours_lecture'] ?? 0,
                'hours_practice' => $semesterData['hours_practice'] ?? 0, 'hours_lab' => $semesterData['hours_lab'] ?? 0,
                'hours_self_study' => $semesterData['hours_self_study'] ?? 0, 'hours_consultation' => $semesterData['hours_consultation'] ?? 0,
                'hours_per_week' => $semesterData['hours_per_week'] ?? 0, 'weeks_count' => $semesterData['weeks_count'] ?? 0,
            ]);
            $semestersImported++;
        }

        $totalHours = array_reduce($data->semesters, fn (int $c, array $s) => $c + ($s['hours_total'] ?? 0), 0);
        $contactHours = array_reduce($data->semesters, fn (int $c, array $s) => $c + ($s['hours_lecture'] ?? 0) + ($s['hours_practice'] ?? 0) + ($s['hours_lab'] ?? 0), 0);

        $plan->update(['total_hours' => $totalHours, 'contact_hours' => $contactHours, 'parsed_at' => now()]);

        return ImportResult::success(disciplinesImported: $disciplinesImported, semestersImported: $semestersImported, curriculumPlanId: $plan->id);
    }

    private function extractSpecialty(SimpleXMLElement $xml): array
    {
        $specialty = $xml->Speciality ?? null;
        if ($specialty === null) return [];
        return [
            'code' => (string) ($specialty->Code ?? $specialty->SpecialityCode ?? ''),
            'name' => (string) ($specialty->Name ?? $specialty->SpecialityName ?? ''),
            'studyYears' => (int) ($specialty->StudyYears ?? $specialty->Duration ?? 0),
            'studyMonths' => (int) ($specialty->StudyMonths ?? 0),
        ];
    }

    private function extractDisciplines(SimpleXMLElement $xml): array
    {
        $disciplines = [];
        $semesterTable = $xml->SemesterTable ?? null;
        if ($semesterTable === null) return [];

        foreach ($semesterTable->Discipline ?? [] as $disciplineNode) {
            $disciplines[] = [
                'code' => (string) ($disciplineNode->Code ?? $disciplineNode->DisciplineCode ?? ''),
                'name' => (string) ($disciplineNode->Name ?? $disciplineNode->DisciplineName ?? ''),
                'short_name' => (string) ($disciplineNode->ShortName ?? ''),
                'cycle' => (string) ($disciplineNode->Cycle ?? ''),
                'discipline_type' => (string) ($disciplineNode->Type ?? $disciplineNode->DisciplineType ?? ''),
                'is_federal' => strtolower((string) ($disciplineNode->Federal ?? '')) === 'yes',
                'sort_order' => (int) ($disciplineNode->Order ?? $disciplineNode->SortOrder ?? 0),
            ];
        }
        return $disciplines;
    }

    private function extractSemesters(SimpleXMLElement $xml): array
    {
        $semesters = [];
        $semesterTable = $xml->SemesterTable ?? null;
        if ($semesterTable === null) return [];

        foreach ($semesterTable->Discipline ?? [] as $disciplineNode) {
            $disciplineCode = (string) ($disciplineNode->Code ?? $disciplineNode->DisciplineCode ?? '');
            foreach ($disciplineNode->Semester ?? $disciplineNode->Semesters->Semester ?? [] as $semesterNode) {
                $semesters[] = [
                    'discipline_code' => $disciplineCode,
                    'course_number' => (int) ($semesterNode->Course ?? $semesterNode->CourseNumber ?? 1),
                    'semester_number' => (int) ($semesterNode->Number ?? $semesterNode->SemesterNumber ?? 1),
                    'semester_in_course' => (int) ($semesterNode->SemesterInCourse ?? 1),
                    'hours_total' => (int) ($semesterNode->TotalHours ?? $semesterNode->HoursTotal ?? 0),
                    'hours_lecture' => (int) ($semesterNode->LectureHours ?? $semesterNode->HoursLecture ?? 0),
                    'hours_practice' => (int) ($semesterNode->PracticeHours ?? $semesterNode->HoursPractice ?? 0),
                    'hours_lab' => (int) ($semesterNode->LabHours ?? $semesterNode->HoursLab ?? 0),
                    'hours_self_study' => (int) ($semesterNode->SelfStudyHours ?? $semesterNode->HoursSelfStudy ?? 0),
                    'hours_consultation' => (int) ($semesterNode->ConsultationHours ?? $semesterNode->HoursConsultation ?? 0),
                    'hours_per_week' => (float) ($semesterNode->HoursPerWeek ?? 0),
                    'weeks_count' => (int) ($semesterNode->Weeks ?? $semesterNode->WeeksCount ?? 0),
                ];
            }
        }
        return $semesters;
    }
}
```

### `app/Services/Export/ExcelExportService.php`
*(Full source at app/Services/Export/ExcelExportService.php - 120 lines)*

Exports schedule to Excel format (.xlsx). Creates a spreadsheet with:
- Yellow header showing department name and date
- Green column headers (Group, Discipline, Teacher, Room)
- Group rows with lesson data merged by group name
- Practice detection (replaces lessons with "Практика" when group is on practice)

### `app/Services/GroupPromotionService.php`
*(Full source at app/Services/GroupPromotionService.php - 66 lines)*

Handles academic year-end group promotion:
- `promoteAllGroups()`: Iterates groups calling promote/graduate
- `getGroupsForPromotion()`: Groups where `current_course < max_courses`
- `getGroupsForGraduation()`: Groups where `current_course >= max_courses`

### `app/Services/Import/ExcelDictionaryImportService.php`
*(Full source at app/Services/Import/ExcelDictionaryImportService.php - 223 lines)*

Bulk imports reference data from Excel spreadsheets:
- **importTeachers()**: Reads teacher rows with department/position name matching, auto-generates short names
- **importGroups()**: Reads group rows with specialty code/department/academic year lookup
- **importSpecialties()**: Reads specialty rows with department/education level lookup

All operations run inside DB transactions with upsert logic.

### `app/Services/Schedule/ConflictCheckerService.php`
*(Full source at app/Services/Schedule/ConflictCheckerService.php - 754 lines)*

Comprehensive conflict checking engine:
- **checkVersionForRange()**: Main entry - groups lessons by date and runs all checks
- **Teacher checks**: windows (gaps >1 pair), minimum pairs (1 pair = warning), parallel lessons (same time slot), Saturday limits (max 5th pair), discipline match verification
- **Group checks**: minimum pairs (less than 3), windows, shift mismatch (allowed slots per shift), physical education grouping (must be doubled)
- **Room checks**: multi-group conflicts in same room+slot
- **Building checks**: group and teacher must not cross buildings in one day (except sport rooms)
- **Weekly load**: 36h (18 pair) max for groups and teachers
- **autoFix()**: Resolves room, teacher window, group window, and shift conflicts automatically
- **Public slot checkers**: `checkTeacherConflict()`, `checkRoomConflict()`, `checkGroupConflict()` used by generator

### `app/Services/Schedule/HoursTrackingService.php`
*(Full source at app/Services/Schedule/HoursTrackingService.php - 138 lines)*

Tracks conducted vs. planned hours:
- `getRemainingHours()`: Total planned - conducted for a group/discipline
- `getWeeklyLoad()`: Hours conducted in a given week for group/teacher
- `recalculateFromSchedule()`: Clears and rebuilds tracking from a schedule version
- `getDisciplinesWithDebt()`: Disciplines with remaining hours for a group
- `getHoursDeficitReport()`: Full deficit report for a department

### `app/Services/Schedule/ScheduleGeneratorService.php`
*(Full source at app/Services/Schedule/ScheduleGeneratorService.php - 417 lines)*

Auto-generates schedule for day/week/month periods:
- **generateForWeek()**: Core generator - iterates working days, assigns lessons per group
- Uses template [4,4,4,3,3] pairs per day, respects shift slots and working days
- **Teacher selection**: Scores candidates by conflict-freeness, adjacency, building match
- **Room selection**: Finds available room by capacity, building, lab requirement, teacher preference
- **PE generation**: Random 20% chance to generate paired physical education lessons in sport room
- **Non-working day detection**: Sundays, holidays, vacations, practices
- Delegates to ConflictCheckerService for real-time conflict prevention

---

## `app/Http/Livewire/`

### `app/Http/Controllers/Controller.php`
```php
<?php

namespace App\Http\Controllers;

abstract class Controller
{
    //
}
```

### `app/Http/Middleware/CheckPermission.php`
```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! $request->user() || ! $request->user()->hasPermission($permission)) {
            abort(403, 'У вас нет прав для выполнения этого действия.');
        }
        return $next($request);
    }
}
```
