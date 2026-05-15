# Progress Log — Система генерации расписания (СПО / Колледж)

> **Дата начала:** 2026-05-13
> **Стек:** Laravel 13.8, Livewire 3, Alpine.js, Tailwind CSS 4, SQLite (dev) / MySQL 8+ (prod)
> **Зеленый акцент:** #10B981 (emerald-500)

---

## Сделанные шаги

| # | Дата | Описание | Статус |
|---|------|----------|--------|
| 00 | 2026-05-13 | Инициализация проекта (Laravel 13 + Livewire уже установлены) | ✅ |
| 01 | 2026-05-13 | Создан `progress.md` — файл отслеживания прогресса | ✅ |
| 02 | 2026-05-13 | Получен полный промт (1354 строки, 8 частей) | ✅ |
| 03 | 2026-05-13 | **Часть 1:** Созданы 48 миграций (1.1-1.7) — 48 таблиц | ✅ |
| 04 | 2026-05-13 | Миграции выполнены, все 48 успешно | ✅ |
| 05 | 2026-05-13 | **Часть 3:** Созданы 46 Eloquent-моделей с отношениями, скоупами, аксессорами | ✅ |
| 06 | 2026-05-13 | **Часть 2:** Созданы 17 сидеров (Roles, Admin, EducationLevels, ... DemoData) | ✅ |
| 07 | 2026-05-13 | Все сидеры выполнены, данные загружены | ✅ |
| 08 | 2026-05-13 | **Часть 4:** Созданы 3 DTO + 5 сервисов + 2 Observer | ✅ |
| 09 | 2026-05-13 | **Часть 5:** Созданы 7 консольных команд | ✅ |
| 10 | 2026-05-13 | **Часть 6:** Созданы 9 Livewire-компонентов | ✅ |
| 11 | 2026-05-13 | **Часть 7:** Настроен планировщик (3 задачи) | ✅ |
| 12 | 2026-05-13 | **Часть 8:** Layout + Dashboard + Vite build + Pint + Темная тема | ✅ |
| 13 | 2026-05-13 | Тесты проходят (3/3), Pint пройден | ✅ |
| 14 | 2026-05-13 | **Auth:** Login, CheckPermission middleware, guard | ✅ |
| 15 | 2026-05-13 | **Роли:** RoleManager (CRUD + чекбоксы разрешений), UserManager (назначение ролей) | ✅ |
| 16 | 2026-05-13 | **Редизайн:** современный UI (тёмный сайдбар, glassmorphism карточки, эмодзи-иконки, hover-эффекты) | ✅ |
| 17 | 2026-05-13 | **Навигация:** 18 роутов, все ссылки меню рабочие (Livewire full-page компоненты) | ✅ |
| 18 | 2026-05-13 | **Команды → UI:** promoteGroups, importHolidays, checkConflicts, exportExcel — через кнопки в интерфейсе | ✅ |
| 19 | 2026-05-13 | **Fix:** schedule generation shift NOT NULL (добавлен `shift` в `generateForWeek` и `generateForMonth`) | ✅ |
| 20 | 2026-05-13 | **Schedule:** добавлены кнопки "Архив" и "Удал." для версий расписания | ✅ |
| 21 | 2026-05-13 | **Groups/Show:** добавлена привязка корпусов (кнопка "+ Привязать корпус"), фильтрация дисциплин по преподавателю + поиск по названию | ✅ |
| 22 | 2026-05-13 | **Groups/Show:** при привязке учебного плана — сначала выбор специальности, потом плана | ✅ |
| 23 | 2026-05-13 | **Teachers/Show:** дерево корпусов и аудиторий (иерархия: корпус → аудитории с приоритетами), привязка/отвязка корпусов и аудиторий | ✅ |
| 24 | 2026-05-13 | **Teachers/Workload:** детальный просмотр нагрузки (клик по преподавателю → дисциплины с разбивкой по семестрам) | ✅ |
| 25 | 2026-05-13 | **DB:** новая миграция `teacher_discipline_semesters`, модель `TeacherDisciplineSemester`, авто-заполнение семестров при назначении дисциплины | ✅ |
| 26 | 2026-05-13 | **Backfill:** `php artisan teachers:backfill-semesters` — создано 64 записи для 16 существующих назначений | ✅ |
| 27 | 2026-05-13 | **Pint:** все файлы отформатированы | ✅ |
| 28 | 2026-05-13 | **Fix:** генерация расписания — добавлено реальное назначение дисциплин, преподов, аудиторий (discipline_id NOT NULL) | ✅ |
| 29 | 2026-05-13 | **Rooms/Index:** добавлены кнопки "Ред."/"Удал." + модалка редактирования в списке аудиторий | ✅ |
| 30 | 2026-05-13 | **Workload:** `academicYearId` по умолчанию = текущий учебный год; проведённые часы считаются из `hours_tracking`; остаток = план - проведено в рамках года | ✅ |
| 31 | 2026-05-13 | **Workload:** остаток показывается только за выбранный учебный год, не суммируется через все годы | ✅ |
| 32 | 2026-05-13 | **Workload:** редизайн — две колонки "1 семестр" / "2 семестр" с карточками дисциплин, прогресс-баром, формой контроля (экзамен/зачет); проведенные часы считаются только из опубликованного расписания | ✅ |
| 33 | 2026-05-13 | **Fix:** `HoursTracking` → `$table = 'hours_tracking'` (было `hours_trackings`) | ✅ |
| 34 | 2026-05-13 | **Fix:** `ScheduleLessonObserver` — `semester_id` определялся как null, падала вставка в `hours_tracking`. Теперь определяется по дате урока + semestr_in_course | ✅ |
| 35 | 2026-05-13 | **ScheduleGrid:** редизайн отображения — группировка по группам/преподавателям/аудиториям, каждая сущность в отдельной таблице со своими парами | ✅ |
| 36 | 2026-05-13 | **ScheduleGrid:** фильтрация по группам/преподавателям/аудиториям работает через `viewMode` + `viewId` | ✅ |
| 37 | 2026-05-13 | **Schedule:** генерация временной ссылки (модалка → выбор режима → копирование URL с параметрами `viewMode`, `viewId`, `weekStart`) | ✅ |
| 38 | 2026-05-13 | **Fix:** `$lessons->toArray()` давал дату с временем → фильтр в шаблоне не совпадал | ✅ |
| 39 | 2026-05-13 | **ScheduleGrid:** полное название дисциплины, ФИО преподавателя, корпус + аудитория | ✅ |
| 40 | 2026-05-13 | **Generator:** макс 5 пар/день, распределение пар по дням для ~36 часов/нед (5-дневка: 4+4+4+3+3, 6-дневка: 3+3+3+3+3+3) | ✅ |
| 41 | 2026-05-13 | **Admin/Settings:** модуль настроек системы (admin/settings) — редактирование рабочих дней, номеров пар по курсам, общих параметров | ✅ |
| 42 | 2026-05-13 | **Group:** getWorkingDays/getAllowedLessonNumbers читают из system_settings, а не хардкода | ✅ |
| 43 | 2026-05-13 | **DB:** добавлены lesson_numbers_course_1/2/3/4 в system_settings | ✅ |
| 44 | 2026-05-13 | **Admin/Settings:** блок "Учебный год" — выбор текущего года, перевод групп на след. курс кнопкой | ✅ |
| 45 | 2026-05-13 | **Admin/Settings:** рабочие дни и номера пар — чекбоксы; генерационные настройки убраны | ✅ |
| 46 | 2026-05-13 | **Migration:** добавлены поля study_years_9, study_years_11, budget_places, contract_places в specialties | ✅ |
| 47 | 2026-05-13 | **Admin/SpecialtyManager:** CRUD специальностей (код, название, кафедра, срок 9/11 кл, форма, бюджет/внебюджет) | ✅ |
| 48 | 2026-05-13 | **Component:** `<x-searchable-select>` — select с поиском через Alpine.js (фильтрация по вводу текста) | ✅ |
| 49 | 2026-05-13 | **Sidebar:** реорганизация — блоки "Справочная", "Расписание", "Нагрузка", "Управление", "Администрирование" | ✅ |
| 50 | 2026-05-13 | **ScheduleGrid:** добавлен режим просмотра "По кафедре" (показывает расписание всех групп кафедры) | ✅ |
| 51 | 2026-05-13 | **ScheduleGrid:** ссылка поддерживает department + все options | ✅ |
| 52 | 2026-05-13 | **Hours tracking:** перенесено из ScheduleLessonObserver → в ScheduleVersion::publish(). Часы начисляются только при публикации | ✅ |
| 53 | 2026-05-13 | **Replacement:** при сохранении изменений в опубликованном расписании — часы пересчитываются (untrackLesson + trackHours) | ✅ |
| 54 | 2026-05-13 | **Teacher working schedule:** добавлены поля `working_days` и `working_lesson_numbers` (JSON), настройка в форме редактирования преподавателя | ✅ |
| 55 | 2026-05-13 | **Schedule grid edit:** фильтр преподавателей по дисциплине + группе; добавлен выбор дисциплины; кнопка проверки конфликтов | ✅ |
| 56 | 2026-05-13 | **ConflictCheckerService:** полная проверка 9 правил (окна, смены, параллели, физ-ра, минимум пар, суббота, аудитории) | ✅ |
| 57 | 2026-05-13 | **Lesson types:** добавлены physical_education, swimming, foreign_language | ✅ |
| 58 | 2026-05-13 | **Conflict descriptions:** подробное описание каждого конфликта (препод, группа, аудитория, дисциплина, дата, день недели) + совет по исправлению | ✅ |
| 59 | 2026-05-13 | **Auto-fix:** кнопка "Автоисправление" в модалке конфликтов — автоматическое перемещение групп по аудиториям | ✅ |
| 60 | 2026-05-13 | **Fix:** TypeError в detail() — Carbon в strtotime (конвертация в timestamp) | ✅ |
| 61 | 2026-05-13 | **Conflict detail:** кнопка "Подробнее" — закрывает модалку, подсвечивает связанные ячейки красным с тенью | ✅ |
| 62 | 2026-05-13 | **Conflict scope:** проверка только на текущую неделю (checkVersionForRange) | ✅ |
| 63 | 2026-05-13 | **Fix:** "Подробнее" не работал — versionId не сохранялся (добавлено сохранение ID версии) | ✅ |
| 64 | 2026-05-13 | **Conflict check:** teacher_discipline_mismatch — преподаватель не ведёт назначенную дисциплину | ✅ |
| 65 | 2026-05-13 | **Grid:** пустые ячейки кликабельны (+) — добавление занятия; hover-кнопка удаления ✕ на существующих; автоскролл к подсвеченным | ✅ |
| 66 | 2026-05-13 | **Fix:** highlightConflict теперь вызывает loadWeek() для перерендера | ✅ |
| 67 | 2026-05-13 | **Fix:** addLesson создаёт новую draft-версию, если нет существующей | ✅ |
| 68 | 2026-05-13 | **Toast:** уведомления в правом нижнем углу, автозакрытие 5 сек, крестик | ✅ |
| 69 | 2026-05-13 | **Conflict coloring:** ячейки с конфликтами errors → красный фон, warnings → янтарный; tooltip при наведении | ✅ |

---

## Детальный план (выполнено)

### Часть 1 — Миграции (48 файлов)
- **1.1 Справочники (19):** education_levels, qualification_types, faculties, departments, specialties, buildings, room_types, equipment_types, rooms, room_equipment, bell_schedules, holidays, academic_years, vacations, lesson_types, control_forms, week_types, teacher_positions, foreign_language_groups
- **1.2 Группы (3):** groups, group_buildings, subgroups
- **1.3 Планы (4):** curriculum_plans, curriculum_disciplines, curriculum_semesters, group_curriculum_assignments
- **1.4 Преподаватели (6):** teachers, teacher_disciplines, teacher_rooms, teacher_buildings, teacher_unavailability, teacher_day_buildings
- **1.5 Расписание (6):** schedule_versions, schedule_lessons, schedule_conflicts, hours_tracking, room_unavailability, group_day_buildings
- **1.6 Роли (4):** roles, permissions, role_permissions, user_roles + modify users
- **1.7 Системные (5):** activity_logs, system_settings, import_logs, export_logs, notifications

### Часть 2 — Сидеры (17 файлов)
RolesAndPermissions, AdminUser, EducationLevels, QualificationTypes, TeacherPositions, LessonTypes, ControlForms, RoomTypes, EquipmentTypes, WeekTypes, ForeignLanguageGroups, BellSchedule, AcademicYears, Vacations, Holidays, SystemSettings, DemoData

### Часть 3 — Модели (46 файлов)
Все Eloquent-модели с $fillable, $casts, отношениями (HasMany/BelongsTo/BelongsToMany), SoftDeletes, скоупами (active/forDepartment/forAcademicYear), аксессорами

### Часть 4 — Сервисы + DTO + Observers (10 файлов)
- **DTO:** GenerationResult, ParsedCurriculum, ImportResult
- **Сервисы:** CurriculumXmlParserService, CurriculumImportService, ScheduleGeneratorService, ConflictCheckerService, HoursTrackingService, GroupPromotionService, ExcelExportService (stub)
- **Observers:** GroupObserver (логирование), ScheduleLessonObserver (учет часов)

### Часть 5 — Консольные команды (перенесены в UI)
Все 7 команд теперь доступны через веб-интерфейс:
- promote-groups → кнопка на дашборде (ActionsWidget)
- generate → ScheduleGeneratorForm (страница /schedule/generate)
- check-conflicts → ScheduleGrid (кнопка проверки)
- export-excel → ScheduleGrid (кнопка экспорта)
- import-xml → CurriculumImportForm (страница /curriculum/import)
- hours-deficit → TeacherWorkloadDashboard (страница /teachers/workload)
- import-holidays → кнопка в AcademicPeriodsManager

### Часть 6 — Livewire-компоненты (18+ компонентов)
- **Auth:** Login
- **Admin:** RoleManager (CRUD ролей + привязка разрешений), UserManager (CRUD пользователей), ActionsWidget, DeleteDataChecklist
- **Dashboard:** Dashboard (статистика, активность, быстрые действия)
- **Groups:** Index, GroupCourseView
- **Teachers:** Index, TeacherAssignment, TeacherWorkloadDashboard
- **Schedule:** Index, ScheduleGeneratorForm, ScheduleGrid
- **Curriculum:** Index, AcademicPeriodsManager, CurriculumImportForm
- **Rooms:** Index, RoomManagement

### Часть 7 — Планировщик (фоновые задачи)
- 30 авг 00:01 → promote-groups (автоматический перевод)
- Вс 06:00 → deficit report
- Ежедн 23:00 → import holidays

### Часть 8 — Полный редизайн + Инфраструктура
- Современный UI (2025+): тёмный сайдбар, карточки с тенями, glassmorphism, плавные transitions
- Guest layout для страницы логина
- 18 рабочих роутов с Livewire full-page компонентами
- Темная тема (Alpine.js + localStorage persistence)
- User dropdown (профиль, выход)
- Mobile-friendly: sidebar overlay, hamburger menu
- Navigation: active state (зеленый бордер + фон)
- Tests: 3/3 pass (login page, auth dashboard, встроенный тест)

---

## Заметки

- Laravel 13.8.0, PHP 8.4
- DB: SQLite (dev) / MySQL 8+ (prod)
- Пакеты: laravel/boost, pest 4, pint 1.x, tailwindcss 4
- Акцентный цвет: emerald-500 (#10B981)
- Строгая типизация (declare(strict_types=1))
- Livewire: #[Rule], #[Computed], #[Layout], WithFileUploads
- Для Excel экспорта нужно установить `maatwebsite/excel`

## Последние исправления (2026-05-14)

| # | Дата | Описание | Статус |
|---|------|----------|--------|
| 83 | 2026-05-14 | **Fix 1:** Добавлен сервис `ExcelCurriculumParserService` для импорта учебных планов из Excel файлов Шахтинской программы | ✅ |
| 84 | 2026-05-14 | **Fix 1:** Обновлен Livewire-компонент `CurriculumImportForm` для поддержки Excel (.xlsx, .xls) форматов | ✅ |
| 85 | 2026-05-14 | **Fix 2:** Добавлены методы проверки конфликтов корпуса и недельной нагрузки в `ConflictCheckerService` | ✅ |
| 86 | 2026-05-14 | **Fix 2:** Обновлен метод `checkVersionForRange` для вызова новых проверок конфликтов | ✅ |
| 87 | 2026-05-14 | **Fix 3:** Полностью переписан метод `autoFix` в `ConflictCheckerService` для автоматического решения различных типов конфликтов | ✅ |
| 88 | 2026-05-14 | **Fix 4:** Исправлен TypeError Carbon → string в autoFix методах | ✅ |
| 89 | 2026-05-14 | **Fix 5:** Исправлен accept файлов в `CurriculumImportForm` (добавлены .xlsx, .xls) | ✅ |
| 90 | 2026-05-14 | **Fix 6:** Миграция `add_excel_file_path_to_curriculum_plans_table` | ✅ |
| 91 | 2026-05-14 | **Fix 7:** Упрощена форма импорта (прямой import без предпросмотра) | ✅ |
| 92 | 2026-05-14 | **Docs:** Создан `project-codebase.md` (15,335 строк) для отправки нейронке | ✅ |
