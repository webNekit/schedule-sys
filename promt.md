````markdown
# ПРОМТ ДЛЯ CLAUDE CODE: Система автоматической генерации расписания (СПО / Колледж)

> **Стек:** Laravel, Livewire, Alpine.js, Tailwind CSS, MySQL 8+  
> **Контекст:** Laravel и Livewire уже установлены. Нужно создать полную систему с нуля: миграции, сидеры, модели, сервисы, Livewire-компоненты и консольные команды.

---

## ОБЩЕЕ ОПИСАНИЕ СИСТЕМЫ

Разработай полноценную веб-систему для **автоматической генерации расписания занятий в колледже (СПО)**. Система должна:

- Импортировать учебные планы из XML-файлов программы «Шахтинская» (российская система для СПО)
- Управлять группами, специальностями, кафедрами, преподавателями, аудиториями и корпусами
- Автоматически генерировать расписание на день / неделю / месяц
- Учитывать десятки ограничений и правил (включая динамические каникулы и праздники)
- Экспортировать расписание в красиво оформленный Excel-файл (1 кафедра = 1 файл)
- Поддерживать ручную корректировку расписания после генерации
- Иметь систему ролей и прав доступа

---

## ЧАСТЬ 1: МИГРАЦИИ БАЗЫ ДАННЫХ

Создай все миграции в правильном порядке с учётом внешних ключей. Каждая миграция — отдельный файл.

### 1.1 СПРАВОЧНИКИ (создавать первыми, без зависимостей)

#### Таблица `education_levels` — уровни образования

```text
id, name (СПО базовой подготовки / СПО углублённой подготовки / ВПО),
study_years (int — кол-во лет обучения, например 3 или 4),
created_at, updated_at
```
````

#### Таблица `qualification_types` — квалификации

```text
id, name (Техник / Программист / Бухгалтер / Юрист и т.д.),
code (короткий код),
created_at, updated_at
```

#### Таблица `faculties` — отделения / факультеты

```text
id, name, short_name, head_name (ФИО заведующего),
phone, email, description,
sort_order (int, default 0),
is_active (bool, default true),
created_at, updated_at, deleted_at
```

#### Таблица `departments` — кафедры / предметно-цикловые комиссии (ПЦК)

```text
id, faculty_id (FK → faculties),
name, short_name,
head_name (ФИО председателя ПЦК),
phone, email, room_number (номер кабинета ПЦК),
description,
sort_order (int, default 0),
is_active (bool, default true),
created_at, updated_at, deleted_at
```

#### Таблица `specialties` — специальности

```text
id, department_id (FK → departments),
code (например 09.02.07),
name (Информационные системы и программирование),
short_name,
qualification (Техник-программист),
education_level_id (FK → education_levels),
study_years (int — срок обучения: 2, 3, 4),
study_months (int — дополнительные месяцы, например 10),
base_education (9 классов / 11 классов),
form_of_study (очная / заочная / очно-заочная),
max_courses (int — максимальный курс, обычно 3 или 4),
is_active (bool),
created_at, updated_at, deleted_at
```

#### Таблица `buildings` — корпуса

```text
id, name (Корпус №1 / Главный корпус и т.д.),
short_name (К1, К2...),
address,
floors_count (int),
description,
is_active (bool, default true),
sort_order (int),
created_at, updated_at, deleted_at
```

#### Таблица `room_types` — типы аудиторий (справочник)

```text
id, name (Учебная аудитория / Лекционный зал / Лаборатория / Компьютерный класс /
          Спортивный зал / Библиотека / Актовый зал / Мастерская),
short_name,
color (hex для UI),
icon,
can_be_shared (bool — можно ли делить на подгруппы),
created_at, updated_at
```

#### Таблица `equipment_types` — типы оборудования в аудитории (справочник)

```text
id, name (Проектор / Интерактивная доска / Компьютеры / Маркерная доска /
          Телевизор / Видеокамера / 3D-принтер и т.д.),
icon,
created_at, updated_at
```

#### Таблица `rooms` — аудитории

```text
id, building_id (FK → buildings),
room_type_id (FK → room_types),
number (номер аудитории — строка: 101, 2а, лаб-3),
name (полное название — Аудитория 101 / Лаборатория программирования),
capacity (int — кол-во мест),
area (decimal — площадь кв.м),
floor (int),
description,
is_active (bool, default true),
is_available_for_booking (bool — доступна для автогенерации),
notes (text — примечания, например "ремонт до марта"),
sort_order (int),
created_at, updated_at, deleted_at
```

#### Таблица `room_equipment` — оборудование в аудиториях (pivot)

```text
id, room_id (FK → rooms), equipment_type_id (FK → equipment_types),
quantity (int, default 1), notes,
created_at, updated_at
```

#### Таблица `bell_schedules` — расписание звонков

```text
id, name (Первая смена / Вторая смена / Сессия),
shift_number (1 или 2),
lesson_number (int — номер пары: 1..7),
time_start (time),
time_end (time),
break_after_minutes (int — перерыв после этой пары),
is_active (bool),
sort_order (int),
created_at, updated_at
```

#### Таблица `holidays` — праздничные и нерабочие дни (точечные дни)

```text
id, date (date),
name (Новый год / День защитника Отечества и т.д.),
type (holiday / transfer_day — перенесённый рабочий день / short_day — сокращённый),
description,
year (int — для быстрой выборки),
created_at, updated_at
```

_Уникальный индекс по полю `date`_

#### Таблица `academic_years` — учебные годы

```text
id, name (2025-2026),
year_start (int — год начала, например 2025),
year_end (int — год окончания, например 2026),
date_start (date — обычно 1 сентября),
date_end (date — обычно 31 августа),
first_semester_start (date), first_semester_end (date),
second_semester_start (date), second_semester_end (date),
is_current (bool, default false),
created_at, updated_at
```

#### Таблица `vacations` — каникулы (динамические периоды)

```text
id, academic_year_id (FK → academic_years),
name (Зимние каникулы / Майские каникулы / Летние каникулы и т.д.),
start_date (date),
end_date (date),
duration_days (int — количество дней, вычисляется автоматически),
description (text, nullable),
created_at, updated_at
```

_Индекс по `(academic_year_id, start_date)`_
_Индекс по `(start_date, end_date)`_

#### Таблица `lesson_types` — типы занятий (справочник)

```text
id, name (Лекция / Практическое занятие / Лабораторная работа /
          Самостоятельная работа / Учебная практика / Производственная практика /
          Консультация / Зачёт / Экзамен / Дифференцированный зачёт /
          Курсовой проект / Курсовая работа / Контрольная работа),
short_name (Лек / Пр / Лаб / СР / УП / ПП / Конс / Зач / Экз / ДЗ / КП / КР / КонтР),
code (lecture / practice / lab / self_study / edu_practice / prod_practice /
      consultation / test / exam / diff_test / course_project / course_work / control_work),
color (hex — для цветового кодирования в расписании),
icon,
requires_lab (bool — требует ли лаборатории/спецкласса),
is_control_form (bool — является ли формой контроля),
hours_coefficient (decimal default 1.0 — коэффициент для расчёта нагрузки),
created_at, updated_at
```

#### Таблица `control_forms` — формы итогового контроля (для учебного плана)

```text
id, name (Зачёт / Экзамен / Дифференцированный зачёт / Курсовой проект /
          Курсовая работа / Контрольная работа),
short_name, code, is_exam_session (bool — входит ли в сессию),
created_at, updated_at
```

#### Таблица `week_types` — типы недель (для расписания через неделю)

```text
id, name (Числитель / Знаменатель / Каждую неделю),
code (numerator / denominator / every),
created_at, updated_at
```

#### Таблица `teacher_positions` — должности преподавателей

```text
id, name (Преподаватель / Старший преподаватель / Доцент / Профессор /
          Мастер производственного обучения / Заведующий кафедрой /
          Методист / Педагог дополнительного образования),
short_name, category (basic / management / support),
max_hours_per_week (int — максимальная нагрузка по должности),
created_at, updated_at
```

#### Таблица `foreign_language_groups` — группы иностранного языка (потоки)

```text
id, name (Английский язык / Немецкий язык / Французский язык),
short_name (EN / DE / FR),
created_at, updated_at
```

---

### 1.2 ГРУППЫ И СТУДЕНТЫ

#### Таблица `groups` — учебные группы

```text
id, specialty_id (FK → specialties),
department_id (FK → departments),
academic_year_id (FK → academic_years — год поступления),
name (ИС-21 / БУХ-22-1),
short_name,
current_course (int 1..4 — текущий курс, обновляется автоматически),
students_count (int — количество студентов),
shift (1 или 2 — смена),
status (active / graduated / expelled / suspended),
enrollment_date (date — дата зачисления, обычно 1 сентября),
graduation_date (date — плановая дата выпуска),
notes (text),
is_active (bool),
created_at, updated_at, deleted_at
```

#### Таблица `group_buildings` — допустимые корпуса для группы

```text
id, group_id (FK → groups), building_id (FK → buildings),
is_primary (bool — основной корпус),
notes,
created_at, updated_at
```

_Уникальный составной индекс: group_id + building_id_

#### Таблица `subgroups` — подгруппы

```text
id, group_id (FK → groups),
name (Подгруппа 1 / Подгруппа 2),
number (int — номер подгруппы: 1, 2),
type (main — основная / foreign_language — иностранный язык),
foreign_language_group_id (FK → foreign_language_groups, nullable),
students_count (int),
notes,
is_active (bool),
created_at, updated_at
```

---

### 1.3 УЧЕБНЫЕ ПЛАНЫ

#### Таблица `curriculum_plans` — учебные планы

```text
id, specialty_id (FK → specialties),
academic_year_id (FK → academic_years),
name (Учебный план ИС 2024),
version (varchar — версия плана, например "2024-v2"),
xml_file_path (varchar — путь к исходному XML файлу),
xml_original (longtext — оригинальный XML),
parsed_at (timestamp — когда был распарсен),
total_hours (int — общий объём часов),
contact_hours (int — аудиторные часы),
self_study_hours (int — часы СР),
practice_hours (int — часы практики),
is_active (bool, default true),
notes (text),
created_by (FK → users),
created_at, updated_at, deleted_at
```

#### Таблица `curriculum_disciplines` — дисциплины из учебного плана

```text
id, curriculum_plan_id (FK → curriculum_plans),
name (полное название дисциплины),
short_name,
code (например ОП.01 / ЕН.02 / ПМ.01 / МДК.01.01),
cycle (ОГД — общий гуманитарный / ЕН — математический / ОП — общепрофессиональный /
       ПМ — профессиональный модуль / ФК — факультативы),
discipline_type (theoretical — теоретическое / professional_module — проф.модуль /
                 practice — практика / optional — факультатив),
is_federal (bool — федеральный компонент),
requires_subgroup (bool — требует деления на подгруппы),
subgroup_type (main / foreign_language / null),
requires_lab (bool — нужна лаборатория/спецкласс),
required_room_type_id (FK → room_types, nullable — тип аудитории),
sort_order (int),
notes (text),
created_at, updated_at
```

#### Таблица `curriculum_semesters` — распределение часов по семестрам

```text
id, discipline_id (FK → curriculum_disciplines),
course_number (int 1..4),
semester_number (int 1..8 — сквозная нумерация семестров),
semester_in_course (int 1 или 2 — семестр внутри курса),
hours_total (int),
hours_lecture (int),
hours_practice (int),
hours_lab (int),
hours_self_study (int),
hours_consultation (int),
control_form_id (FK → control_forms, nullable — форма итогового контроля),
exam_hours (int — часы на экзамен/зачёт),
course_project_hours (int — часы на КП/КР),
weeks_count (int — кол-во недель в семестре),
hours_per_week (decimal — расчётное кол-во часов в неделю),
created_at, updated_at
```

#### Таблица `group_curriculum_assignments` — привязка плана к группам

```text
id, group_id (FK → groups), curriculum_plan_id (FK → curriculum_plans),
assigned_at (timestamp), assigned_by (FK → users),
is_active (bool),
notes,
created_at, updated_at
```

---

### 1.4 ПРЕПОДАВАТЕЛИ

#### Таблица `teachers` — преподаватели

```text
id, user_id (FK → users, nullable — если есть аккаунт),
department_id (FK → departments),
position_id (FK → teacher_positions),
last_name, first_name, middle_name,
full_name (generated/computed — для быстрого поиска),
short_name (Иванов И.И.),
phone, email, internal_phone,
employment_type (full_time — штатный / part_time — совместитель / hourly — почасовик),
rate (decimal — ставка, например 1.0 / 0.5 / 1.5),
max_hours_per_week (int, default 36),
min_lessons_per_day (int, default 3),
max_lessons_per_day (int, default 5),
has_methodical_day (bool — есть ли методический день),
methodical_day_of_week (int nullable — день недели: 1-пн...6-сб),
qualification_category (первая / высшая / без категории),
academic_degree (к.т.н. / к.п.н. / д.т.н. и т.д., nullable),
hire_date (date),
is_active (bool, default true),
notes (text),
created_at, updated_at, deleted_at
```

#### Таблица `teacher_disciplines` — привязка преподавателей к дисциплинам

```text
id, teacher_id (FK → teachers),
discipline_id (FK → curriculum_disciplines),
group_id (FK → groups, nullable — если привязан к конкретной группе),
subgroup_id (FK → subgroups, nullable),
academic_year_id (FK → academic_years),
is_primary (bool — основной преподаватель по дисциплине),
planned_hours (int — плановая нагрузка часов),
actual_hours (int, default 0 — отработанные часы),
notes,
created_at, updated_at
```

#### Таблица `teacher_rooms` — приоритетные аудитории преподавателя

```text
id, teacher_id (FK → teachers), room_id (FK → rooms),
priority (int 1..5 — приоритет: 1 = наивысший),
is_personal (bool — личный кабинет преподавателя),
notes,
created_at, updated_at
```

#### Таблица `teacher_buildings` — допустимые корпуса преподавателя

```text
id, teacher_id (FK → teachers), building_id (FK → buildings),
is_primary (bool — основной корпус),
created_at, updated_at
```

#### Таблица `teacher_unavailability` — недоступность преподавателей

```text
id, teacher_id (FK → teachers),
type (sick_leave / vacation / business_trip / personal / methodical / other),
date_from (date), date_to (date),
reason (text),
all_day (bool, default true),
time_from (time nullable), time_to (time nullable),
is_approved (bool, default false),
approved_by (FK → users, nullable),
created_by (FK → users),
created_at, updated_at
```

#### Таблица `teacher_day_buildings` — в каком корпусе преподаватель в конкретный день (для контроля)

```text
id, teacher_id (FK → teachers), date (date), building_id (FK → buildings),
created_at, updated_at
```

_Уникальный составной индекс: teacher_id + date_

---

### 1.5 РАСПИСАНИЕ И УЧЕТ

#### Таблица `schedule_versions` — версии расписания

```text
id, name (Расписание на неделю 10-15 февраля 2025),
academic_year_id (FK → academic_years),
department_id (FK → departments, nullable — если для конкретной кафедры),
date_from (date), date_to (date),
period_type (day / week / month / semester / custom),
status (draft / review / published / archived),
generation_type (auto / manual / mixed),
generated_at (timestamp nullable),
published_at (timestamp nullable),
published_by (FK → users, nullable),
notes (text),
created_by (FK → users),
created_at, updated_at
```

#### Таблица `schedule_lessons` — пары в расписании

```text
id, version_id (FK → schedule_versions),
date (date),
lesson_number (int 1..7 — номер пары),
shift (1 или 2),
group_id (FK → groups),
subgroup_id (FK → subgroups, nullable),
discipline_id (FK → curriculum_disciplines),
lesson_type_id (FK → lesson_types),
teacher_id (FK → teachers),
room_id (FK → rooms),
building_id (FK → buildings),
week_type_id (FK → week_types, nullable — числитель/знаменатель/каждую неделю),
is_auto_generated (bool — сгенерировано автоматически или вручную),
is_replacement (bool — является ли заменой),
original_lesson_id (FK → schedule_lessons self-ref, nullable — оригинальная пара при замене),
status (scheduled / confirmed / cancelled / replaced / moved),
notes (text),
created_by (FK → users),
created_at, updated_at, deleted_at
```

#### Таблица `schedule_conflicts` — конфликты при генерации

```text
id, version_id (FK → schedule_versions),
conflict_type (teacher_busy / room_busy / group_busy /
               teacher_building_conflict / group_building_conflict /
               teacher_overload / no_room_available / no_teacher_available /
               subgroup_conflict / min_lessons_not_reached / max_lessons_exceeded),
severity (error / warning / info),
date (date),
lesson_number (int nullable),
group_id (FK → groups, nullable),
teacher_id (FK → teachers, nullable),
room_id (FK → rooms, nullable),
discipline_id (FK → curriculum_disciplines, nullable),
description (text — подробное описание конфликта),
is_resolved (bool, default false),
resolved_by (FK → users, nullable),
resolved_at (timestamp nullable),
resolution_notes (text nullable),
created_at, updated_at
```

#### Таблица `hours_tracking` — учёт отработанных часов

```text
id, group_id (FK → groups),
discipline_id (FK → curriculum_disciplines),
teacher_id (FK → teachers),
semester_id (FK → curriculum_semesters),
academic_year_id (FK → academic_years),
lesson_type_id (FK → lesson_types),
date (date),
hours_conducted (decimal — проведённые часы, обычно 2),
schedule_lesson_id (FK → schedule_lessons, nullable),
is_cancelled (bool, default false),
notes,
created_at, updated_at
```

#### Таблица `room_unavailability` — недоступность аудиторий

```text
id, room_id (FK → rooms),
type (repair / sanitary_day / event / reserved / other),
date_from (date), date_to (date),
all_day (bool, default true),
time_from (time nullable), time_to (time nullable),
reason (text),
created_by (FK → users),
created_at, updated_at
```

#### Таблица `group_day_buildings` — корпус группы на конкретный день (для контроля)

```text
id, group_id (FK → groups), date (date), building_id (FK → buildings),
auto_determined (bool — определено автоматически при генерации),
created_at, updated_at
```

_Уникальный составной индекс: group_id + date_

---

### 1.6 ПОЛЬЗОВАТЕЛИ И РОЛИ

#### Таблица `roles` — роли

```text
id, name, slug (superadmin / admin / dispatcher / department_head / teacher / viewer),
description,
created_at, updated_at
```

#### Таблица `permissions` — разрешения

```text
id, name, slug (например: curriculum.import / schedule.generate / schedule.publish /
                teachers.manage / rooms.manage / reports.export),
module (curriculum / schedule / teachers / rooms / reports / admin),
description,
created_at, updated_at
```

#### Таблица `role_permissions` (pivot)

```text
role_id, permission_id
```

#### Таблица `user_roles` (pivot)

```text
user_id, role_id, department_id (nullable — для ограничения по кафедре)
```

**Дополнить стандартную таблицу `users`:**
Добавить колонки: `teacher_id` (FK → teachers, nullable), `department_id` (FK → departments, nullable), `is_active` (bool), `last_login_at` (timestamp), `avatar` (varchar nullable)

---

### 1.7 СИСТЕМНЫЕ ТАБЛИЦЫ

#### Таблица `activity_logs` — журнал действий

```text
id, user_id (FK → users, nullable),
action (create / update / delete / login / logout / generate / publish / export / import),
module (curriculum / schedule / teachers / rooms / groups / reports / auth / admin),
model_type (nullable — класс модели), model_id (nullable),
description (text),
old_values (json nullable — старые значения при изменении),
new_values (json nullable — новые значения),
ip_address, user_agent,
created_at
```

#### Таблица `system_settings` — настройки системы

```text
id, key (varchar unique),
value (text),
type (string / integer / boolean / json / date),
group (general / schedule / generation / notifications / export),
label (человекочитаемое название),
description,
is_editable (bool),
created_at, updated_at
```

#### Таблица `import_logs` — логи импорта XML

```text
id, user_id (FK → users),
file_name, file_path, file_size,
type (curriculum_xml),
status (pending / processing / success / failed / partial),
records_total (int), records_imported (int), records_failed (int),
errors (json — массив ошибок),
warnings (json),
started_at, finished_at,
created_at, updated_at
```

#### Таблица `export_logs` — логи экспорта

```text
id, user_id (FK → users),
type (schedule_excel / workload_excel / schedule_pdf),
file_name, file_path, file_size,
parameters (json — параметры экспорта: период, кафедра и т.д.),
status (pending / processing / success / failed),
created_at, updated_at
```

#### Таблица `notifications` — уведомления внутри системы

```text
id, user_id (FK → users),
type (schedule_published / conflict_found / import_complete / hours_deficit / replacement_needed),
title, message (text),
data (json — дополнительные данные),
is_read (bool, default false),
read_at (timestamp nullable),
created_at
```

---

## ЧАСТЬ 2: СИДЕРЫ

Создай следующие сидеры в правильном порядке вызова через `DatabaseSeeder`:

### 2.1 `RolesAndPermissionsSeeder`

Создай роли: `superadmin`, `admin`, `dispatcher`, `department_head`, `teacher`, `viewer`  
Создай разрешения для каждого модуля и назначь их ролям.

### 2.2 `AdminUserSeeder`

Создай суперадмина: email `admin@college.ru`, пароль `Admin@12345`, назначь роль `superadmin`.  
Создай тестового диспетчера: email `dispatcher@college.ru`, пароль `Dispatcher@12345`.

### 2.3 `EducationLevelsSeeder`

```text
- СПО базовой подготовки (3 года 10 месяцев)
- СПО углублённой подготовки (4 года)
- ВПО бакалавриат (4 года)
```

### 2.4 `QualificationTypesSeeder`

Техник, Техник-программист, Программист, Бухгалтер, Юрист, Экономист, Менеджер, Мастер, Технолог, Электрик, Механик, Строитель и т.д. (20+ записей)

### 2.5 `TeacherPositionsSeeder`

```text
- Преподаватель (max 720 часов/год)
- Старший преподаватель (max 720 часов/год)
- Мастер производственного обучения (max 1120 часов/год)
- Заведующий кафедрой (max 360 часов/год)
- Методист
- Педагог дополнительного образования
```

### 2.6 `LessonTypesSeeder`

Создай все типы занятий с правильными цветами (hex), кодами и флагами:

```text
- Лекция (#3B82F6, lecture, requires_lab: false)
- Практическое занятие (#10B981, practice, requires_lab: false)
- Лабораторная работа (#F59E0B, lab, requires_lab: true)
- Самостоятельная работа (#6B7280, self_study)
- Учебная практика (#8B5CF6, edu_practice)
- Производственная практика (#EC4899, prod_practice)
- Консультация (#14B8A6, consultation)
- Зачёт (#EF4444, test, is_control_form: true)
- Экзамен (#DC2626, exam, is_control_form: true)
- Дифференцированный зачёт (#B91C1C, diff_test, is_control_form: true)
- Курсовой проект (#7C3AED, course_project)
- Курсовая работа (#6D28D9, course_work)
- Контрольная работа (#D97706, control_work)
```

### 2.7 `ControlFormsSeeder`

Зачёт, Экзамен, Дифференцированный зачёт, Курсовой проект, Курсовая работа, Контрольная работа

### 2.8 `RoomTypesSeeder`

```text
- Учебная аудитория (#94A3B8, can_be_shared: true)
- Лекционный зал (#3B82F6, can_be_shared: false)
- Компьютерный класс (#8B5CF6, can_be_shared: true, requires_lab: true)
- Лаборатория (#F59E0B, can_be_shared: true, requires_lab: true)
- Спортивный зал (#10B981, can_be_shared: false)
- Мастерская (#D97706, can_be_shared: true)
- Актовый зал (#EC4899, can_be_shared: false)
- Библиотека/Читальный зал (#6B7280)
```

### 2.9 `EquipmentTypesSeeder`

Проектор, Интерактивная доска, Компьютеры, Маркерная доска, Меловая доска, Телевизор, МФУ/Принтер, Лабораторное оборудование, 3D-принтер, Видеокамера, Микроскопы, Швейные машины, Токарные станки и т.д.

### 2.10 `WeekTypesSeeder`

```text
- Каждую неделю (every)
- По числителю (numerator)
- По знаменателю (denominator)
```

### 2.11 `ForeignLanguageGroupsSeeder`

```text
- Английский язык (EN)
- Немецкий язык (DE)
- Французский язык (FR)
```

### 2.12 `BellScheduleSeeder`

**Первая смена (1-2 курсы, Пн-Пт):**

```text
1 пара: 08:00 – 09:30 (перерыв 10 мин)
2 пара: 09:40 – 11:10 (перерыв 20 мин)
3 пара: 11:30 – 13:00 (перерыв 40 мин)
4 пара: 13:40 – 15:10 (перерыв 10 мин)
5 пара: 15:20 – 16:50
```

**Вторая смена (3-4 курсы, Вт-Сб):**

```text
3 пара: 13:40 – 15:10 (перерыв 10 мин) — если 5 пар в день
4 пара: 15:20 – 16:50 (перерыв 10 мин)
5 пара: 17:00 – 18:30 (перерыв 10 мин)
6 пара: 18:40 – 20:10 (перерыв 10 мин)
7 пара: 20:20 – 21:50
```

### 2.13 `AcademicYearsSeeder`

```text
2024-2025: 01.09.2024 – 31.08.2025 (не текущий)
2025-2026: 01.09.2025 – 31.08.2026 (is_current: true)
2026-2027: 01.09.2026 – 31.08.2027 (не текущий)
```

Заполни даты семестров для каждого.

### 2.14 `VacationsSeeder`

Заполни динамические каникулы с привязкой к созданным `AcademicYear`. Например для 2025-2026:

- Зимние каникулы: 29.12.2025 – 11.01.2026 (длительность авто-расчет)
- Весенние каникулы: 23.03.2026 – 29.03.2026
- Майские каникулы: 01.05.2026 – 10.05.2026
- Летние каникулы: 01.07.2026 – 31.08.2026
  Сгенерируй аналогичные периоды для 2026-2027 года.

### 2.15 `HolidaysSeeder`

Заполни праздники и перенесённые рабочие дни РФ на 2024-2027 (точечные нерабочие дни, которые не вошли в каникулы, например 23 февраля, 8 марта, День Победы 9 мая, День России 12 июня, День народного единства 4 ноября и переносы).

### 2.16 `SystemSettingsSeeder`

```json
{
    "college_name": "Государственное бюджетное профессиональное образовательное учреждение",
    "college_short_name": "ГБПОУ Колледж",
    "college_address": "г. Москва, ул. Примерная, д. 1",
    "schedule_generation_max_retries": 100,
    "schedule_min_lessons_per_day": 3,
    "schedule_max_lessons_per_day": 5,
    "schedule_max_hours_per_week": 36,
    "schedule_hours_per_lesson": 2,
    "schedule_auto_promote_date": "08-30",
    "schedule_allow_sport_hall_cross_building": true,
    "notification_email_enabled": false,
    "export_excel_template_path": "templates/schedule_template.xlsx",
    "curriculum_xml_encoding": "windows-1251",
    "working_days_course_1_2": "[1,2,3,4,5]",
    "working_days_course_3_4": "[2,3,4,5,6]"
}
```

### 2.17 `DemoDataSeeder` (только для разработки, вызывать через `--seed` с флагом)

Создай демонстрационные данные:

- 2 корпуса с 20 аудиториями в каждом (разные типы)
- 3 факультета, 6 кафедр
- 5 специальностей
- 15 групп (1-4 курсы, разные специальности)
- 25 преподавателей с привязками к кафедрам, аудиториям, корпусам
- Учебный план для одной из специальностей с дисциплинами и семестрами

---

## ЧАСТЬ 3: МОДЕЛИ И ОТНОШЕНИЯ

Создай Eloquent-модели для всех таблиц со следующими требованиями:

### Для каждой модели:

- Заполни `$fillable` или используй `$guarded =[]`
- Добавь `$casts` для дат, JSON-полей, булевых значений
- Реализуй все `HasMany`, `BelongsTo`, `BelongsToMany`, `HasManyThrough` отношения
- Для моделей с `deleted_at` — используй `SoftDeletes`
- Добавь скоупы (`scopeActive`, `scopeForAcademicYear`, `scopeForDepartment` и т.д.)
- Добавь аксессоры (`getFullNameAttribute` для учителей и т.д.)

### Ключевые модели с дополнительной логикой:

**Модель `AcademicYear`:**

```php
public function vacations(): HasMany // связь с каникулами
public function scopeCurrent($query)
```

**Модель `Vacation`:**

```php
protected $fillable = ['academic_year_id', 'name', 'start_date', 'end_date', 'duration_days', 'description'];
protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

// Аксессор: автоматический расчет дней при сохранении
protected static function booted()
{
    static::saving(function ($vacation) {
        if ($vacation->start_date && $vacation->end_date) {
            $vacation->duration_days = $vacation->start_date->diffInDays($vacation->end_date) + 1;
        }
    });
}

// Проверка: попадает ли переданная дата в эти каникулы
public function includesDate(Carbon $date): bool
```

**Модель `Group`:**

```php
// Аксессор: полное название с курсом
public function getFullNameAttribute(): string

// Скоупы
public function scopeActive($query)
public function scopeByCourse($query, int $course)
public function scopeByShift($query, int $shift)
public function scopeFirstShift($query)    // 1-2 курсы
public function scopeSecondShift($query)   // 3-4 курсы

// Методы
public function getCurrentSemester(): int
public function getWorkingDays(): array    // [1,2,3,4,5] или [2,3,4,5,6]
public function getAllowedLessonNumbers(): array
public function promote(): void            // перевод на следующий курс
public function graduate(): void           // статус "выпуск"
public function getRemainingHours(): Collection  // остаток часов по дисциплинам
```

**Модель `Teacher`:**

```php
public function getShortNameAttribute(): string   // Иванов И.И.
public function getWeeklyHoursUsed(Carbon $weekStart): int
public function isAvailableOn(Carbon $date, int $lessonNumber): bool
public function getBuildingForDate(Carbon $date): ?Building
public function getAvailableRooms(): Collection
```

**Модель `ScheduleLesson`:**

```php
public function getTimeStartAttribute(): string
public function getTimeEndAttribute(): string
public function scopeForDate($query, Carbon $date)
public function scopeForGroup($query, int $groupId)
public function scopeForTeacher($query, int $teacherId)
public function scopeForRoom($query, int $roomId)
public function scopePublished($query)
```

**Модель `CurriculumSemester`:**

```php
public function getCompletedHours(): int
public function getRemainingHours(): int
public function getCompletionPercent(): float
```

---

## ЧАСТЬ 4: СЕРВИСЫ

### `ScheduleGeneratorService` — главный сервис генерации

Реализуй следующие методы:

```php
public function generateForDay(Carbon $date, array $groupIds =[]): GenerationResult
public function generateForWeek(Carbon $weekStart, array $groupIds =[]): GenerationResult
public function generateForMonth(int $year, int $month, array $groupIds =[]): GenerationResult

private function generateForGroupOnDate(Group $group, Carbon $date, ScheduleVersion $version): array
private function findAvailableTeacher(CurriculumDiscipline $discipline, Carbon $date, int $lessonNumber, Building $building): ?Teacher
private function findAvailableRoom(CurriculumDiscipline $discipline, Carbon $date, int $lessonNumber, Building $building, Teacher $teacher): ?Room
private function determineBuildingForDay(Group $group, Carbon $date): ?Building
private function getWorkingSlots(Group $group, Carbon $date): array
private function sortDisciplinesByPriority(Group $group, Carbon $date): Collection
private function checkTeacherConflict(Teacher $teacher, Carbon $date, int $lessonNumber): bool
private function checkRoomConflict(Room $room, Carbon $date, int $lessonNumber): bool
private function checkGroupConflict(Group $group, Carbon $date, int $lessonNumber): bool
private function checkTeacherBuildingConflict(Teacher $teacher, Carbon $date, Building $building): bool
private function checkWeeklyHoursLimit(Group $group, Carbon $weekStart): bool
private function recordHours(ScheduleLesson $lesson): void
```

**Правила генерации которые ОБЯЗАТЕЛЬНО учесть:**

1. **Смены и дни недели:**
    - Курс 1-2: Пн(1)-Пт(5), смена 1, пары 1-5
    - Курс 3-4: Вт(2)-Сб(6), смена 2, пары 4-7 (если 5 пар — то 3-7)

2. **Ограничения по часам:**
    - Минимум 3 пары в день (6 часов)
    - Максимум 5 пар в день (10 часов)
    - Максимум 36 часов в неделю на группу

3. **Корпус на день:**
    - Группа в один день только в ОДНОМ корпусе (кроме спортзала)
    - Преподаватель в один день только в ОДНОМ корпусе (кроме спортзала)
    - Определять корпус в начале дня, фиксировать в `group_day_buildings` и `teacher_day_buildings`

4. **Подгруппы:**
    - При делении на подгруппы — каждая подгруппа в своей аудитории
    - Для иностранного языка — раздельное расписание по потокам (EN/DE)

5. **Приоритет дисциплин:**
    - Дисциплины с большим долгом по часам — выше приоритет
    - Равномерное распределение по неделе
    - Не ставить одну дисциплину более 2 раз в день

6. **Аудитории:**
    - Сначала проверять приоритетную аудиторию преподавателя
    - Тип занятия → тип аудитории (лаб → лаборатория/компьютерный класс)
    - Вместимость аудитории >= количество студентов в группе/подгруппе

7. **Праздники и Каникулы (ВАЖНО!):**
    - Пропускать точечные дни из таблицы `holidays` с типом `holiday`.
    - Учитывать перенесённые рабочие дни (`transfer_day`).
    - **КАНИКУЛЫ:** Перед попыткой сгенерировать расписание на день, проверять, не попадает ли дата в любой из периодов таблицы `vacations` для текущего `academic_year_id`. Если попадает — полностью пропускать генерацию на этот день (для всех групп или для групп, у которых в этот период нет практики — по умолчанию пропускаем для всех).

### `CurriculumXmlParserService` — парсер XML Шахтинской программы

```php
public function parse(string $xmlPath): ParsedCurriculum
public function validateXml(string $xmlPath): ValidationResult
public function importToPlan(ParsedCurriculum $data, CurriculumPlan $plan): ImportResult

// Структура ParsedCurriculum:
// - specialty (код, название, срок обучения)
// - disciplines[] (название, код, цикл, часы по семестрам, формы контроля)
// - semesters[] (распределение часов)
```

XML из Шахтинской программы имеет следующую примерную структуру (адаптируй парсер):

```xml
<EducationalPlan>
  <Speciality Code="09.02.07" Name="Информационные системы..." StudyYears="3" StudyMonths="10"/>
  <SemesterTable>
    <Discipline Code="ОП.01" Name="Основы алгоритмизации...">
      <Semester Num="1">
        <TotalHours>72</TotalHours>
        <LectureHours>36</LectureHours>
        <PracticeHours>36</PracticeHours>
        <LabHours>0</LabHours>
        <SelfStudyHours>0</SelfStudyHours>
        <ControlForm>Зачёт</ControlForm>
      </Semester>
    </Discipline>
  </SemesterTable>
</EducationalPlan>
```

### `ExcelExportService` — экспорт в Excel

Используй библиотеку `maatwebsite/excel` или `PhpSpreadsheet` напрямую.

```php
public function exportScheduleByDepartment(int $departmentId, Carbon $dateFrom, Carbon $dateTo, int $versionId): string
// Возвращает путь к сгенерированному файлу

// Структура Excel файла (1 кафедра = 1 файл):
// Лист "Расписание" — сводная таблица всех групп кафедры
// Лист "ИС-21" — расписание группы ИС-21 (по одному листу на группу)
// Лист "Нагрузка" — таблица нагрузки преподавателей кафедры
```

**Форматирование:**

- Шапка с названием учебного заведения, кафедры, периода
- Таблица: строки = пары (1-7), колонки = дни недели
- В ячейке: название дисциплины (жирный), ФИО преподавателя, аудитория (мелкий шрифт)
- Цветовое кодирование типов занятий
- Фиксация заголовков (freeze panes)
- Объединение ячеек для одинаковых дней
- Автоширина колонок

### `GroupPromotionService` — перевод групп на следующий курс

```php
public function promoteAllGroups(): PromotionResult
public function promoteGroup(Group $group): void
public function graduateGroup(Group $group): void
public function getGroupsForPromotion(): Collection
public function getGroupsForGraduation(): Collection
```

### `HoursTrackingService` — учёт и отслеживание часов

```php
public function getRemainingHours(Group $group, CurriculumDiscipline $discipline): int
public function getWeeklyLoad(Group $group, Carbon $weekStart): int
public function getTeacherWeeklyLoad(Teacher $teacher, Carbon $weekStart): int
public function recalculateFromSchedule(ScheduleVersion $version): void
public function getDisciplinesWithDebt(Group $group): Collection
public function getHoursDeficitReport(int $departmentId): array
```

---

## ЧАСТЬ 5: КОНСОЛЬНЫЕ КОМАНДЫ

### `schedule:promote-groups`

```text
Описание: Переводит группы на следующий курс (запускается 30 августа автоматически)
Опции: --dry-run (показать без применения), --force (без подтверждения)
Логика:
  1. Получить все активные группы
  2. Если current_course < max_courses специальности → current_course++
  3. Если current_course == max_courses → статус "graduated"
  4. Записать в activity_logs
  5. Вывести отчёт: переведено X групп, выпущено Y групп
```

### `schedule:generate`

```text
Описание: Генерация расписания из командной строки
Аргументы: {period : day|week|month} {date : дата в формате Y-m-d}
Опции: --groups=* (ID групп, если пусто — все), --department= (ID кафедры), --publish (сразу публиковать)
```

### `schedule:check-conflicts`

```text
Описание: Проверка конфликтов в существующем расписании
Аргументы: {version_id}
Вывод: Таблица конфликтов с типами и описаниями
```

### `schedule:export-excel`

```text
Описание: Экспорт расписания в Excel
Аргументы: {version_id} {department_id}
Опции: --output= (путь для сохранения)
```

### `curriculum:import-xml`

```text
Описание: Импорт учебного плана из XML
Аргументы: {file : путь к XML файлу} {specialty_id} {academic_year_id}
Опции: --dry-run (проверить без импорта)
```

### `reports:hours-deficit`

```text
Описание: Отчёт о недовыполнении часов по дисциплинам
Опции: --department= (ID кафедры), --academic-year= (ID учебного года)
Вывод: Таблица групп и дисциплин с долгом по часам
```

### `schedule:import-holidays`

```text
Описание: Импорт праздников из JSON файла или автоматически из открытых источников
Аргументы: {year}
```

---

## ЧАСТЬ 6: LIVEWIRE-КОМПОНЕНТЫ

### `AcademicPeriodsManager` — управление учебными годами и каникулами

```text
- Интерфейс для создания и редактирования учебного года (2025-2026, 2026-2027 и т.д.).
- При выборе учебного года открывается блок "Каникулярные периоды".
- Возможность динамически добавлять/удалять каникулы:
  - Инпут названия (Январские каникулы, Майские и т.д.)
  - Datepicker (от и до)
  - Автоматическое вычисление и отображение кол-ва дней.
- Валидация на пересечение дат каникул внутри одного учебного года.
- Таблица сгенерированных каникул с возможностью редактирования inline.
```

### `CurriculumImportForm` — форма импорта XML

```text
- Drag & drop загрузка XML файла
- Предварительный просмотр содержимого (дисциплины, часы)
- Выбор специальности и учебного года
- Кнопка "Импортировать" с прогресс-баром
- Отображение результата: сколько дисциплин создано, предупреждения
```

### `GroupCourseView` — просмотр курсов группы

```text
- Список групп с фильтром по кафедре/специальности
- При клике на группу — аккордеон с курсами (1, 2, 3, 4)
- При клике на курс — таблица дисциплин семестра 1 и семестра 2
- Для каждой дисциплины: часы (лек/пр/лаб/СР), форма контроля, остаток часов (прогресс-бар)
- Индикатор: сколько часов отработано / всего
```

### `ScheduleGenerator` — форма генерации расписания

```text
- Выбор типа периода: день / неделя / месяц (radiobutton)
- Выбор даты/диапазона (datepicker)
- Выбор групп (мультиселект с поиском) или "все группы"
- Чекбокс "только группы кафедры" с выбором кафедры
- Кнопка "Сгенерировать" → показывает прогресс
- После генерации: сводка (поставлено пар, конфликты)
- Список конфликтов с возможностью ручного разрешения
- Кнопки: "Сохранить как черновик" / "Опубликовать"
```

### `ScheduleGrid` — сетка расписания (просмотр и редактирование)

```text
- Переключение: по группе / по преподавателю / по аудитории
- Отображение недели с пнд по сб
- Ячейки: название дисциплины, препод, аудитория, цвет по типу
- Клик на ячейку → модальное окно редактирования пары
- Drag & drop для перемещения пар (Alpine.js + Sortable.js)
- Индикатор конфликтов (красная рамка у конфликтной ячейки)
- Кнопка "Добавить пару вручную"
```

### `TeacherWorkloadDashboard` — нагрузка преподавателей

```text
- Таблица: преподаватель, дисциплины, группы, план.часы, факт.часы, %выполнения
- Прогресс-бары по нагрузке
- Фильтр по кафедре и учебному году
- Экспорт в Excel
```

### `DeleteDataChecklist` — чеклист удаления данных

```text
- Список модулей с чекбоксами: Группы / Специальности / Учебные планы /
  Преподаватели / Аудитории / Корпуса / Расписание / Все данные
- При выборе показывать количество записей которые будут удалены
- Поле подтверждения (ввод слова "УДАЛИТЬ")
- Мягкое удаление (soft delete)
- Отдельная вкладка "Корзина" с возможностью восстановления
```

### `RoomManagement` — управление аудиториями

```text
- Список корпусов → список аудиторий
- Карточки аудиторий с типом, вместимостью, оборудованием
- Форма создания/редактирования аудитории
- Привязка оборудования к аудитории
- Отметка о недоступности (ремонт и т.д.) с датами
- Фильтры по типу, корпусу, доступности
```

### `TeacherAssignment` — привязка преподавателей к дисциплинам

```text
- Список дисциплин учебного плана
- Для каждой дисциплины — выпадающий список преподавателей (фильтр по кафедре)
- Быстрый поиск преподавателя
- Указание плановой нагрузки (часов)
- Подсветка: у кого уже много нагрузки (желтый/красный)
- Массовое назначение (один преподаватель → несколько дисциплин)
```

---

## ЧАСТЬ 7: ПЛАНИРОВЩИК ЗАДАЧ (App\Console\Kernel)

```php
// 30 августа в 00:01 — перевод групп на следующий курс
$schedule->command('schedule:promote-groups --force')
         ->yearlyOn(8, 30, '00:01')
         ->withoutOverlapping()
         ->runInBackground();

// Каждое воскресенье в 06:00 — отчёт о долге по часам
$schedule->command('reports:hours-deficit')
         ->weekly()->sundays()->at('06:00');

// Ежедневно в 23:00 — проверка праздников на следующую неделю и уведомления
$schedule->command('schedule:check-holidays')
         ->daily()->at('23:00');

// Первый день каждого учебного года — создание нового учебного года
// (1 сентября в 01:00)
$schedule->command('academic-year:create-new')
         ->yearlyOn(9, 1, '01:00');
```

---

## ЧАСТЬ 8: ДОПОЛНИТЕЛЬНЫЕ ТРЕБОВАНИЯ

### Структура директорий проекта:

```text
app/
├── Console/Commands/
│   ├── Schedule/
│   └── Curriculum/
├── Http/Livewire/
│   ├── Curriculum/
│   ├── Groups/
│   ├── Schedule/
│   ├── Teachers/
│   ├── Rooms/
│   └── Admin/
├── Models/
├── Services/
│   ├── Schedule/
│   │   ├── ScheduleGeneratorService.php
│   │   ├── ConflictCheckerService.php
│   │   └── HoursTrackingService.php
│   ├── Curriculum/
│   │   ├── CurriculumXmlParserService.php
│   │   └── CurriculumImportService.php
│   └── Export/
│       └── ExcelExportService.php
├── DTOs/
│   ├── GenerationResult.php
│   ├── ParsedCurriculum.php
│   └── ImportResult.php
└── Observers/
    ├── GroupObserver.php       — логирование изменений групп
    └── ScheduleLessonObserver.php  — обновление hours_tracking при изменении пары
```

### Индексы для производительности:

- `schedule_lessons`: индексы по (date, version_id), (group_id, date), (teacher_id, date), (room_id, date)
- `hours_tracking`: индекс по (group_id, discipline_id, academic_year_id)
- `teacher_unavailability`: индекс по (teacher_id, date_from, date_to)
- `holidays`: индекс по (date, year)
- `vacations`: индекс по (academic_year_id, start_date) и (start_date, end_date)

### Тесты (создай базовые):

- `ScheduleGeneratorTest`: тест генерации на день с соблюдением ограничений (в т.ч. пропуск каникул)
- `CurriculumXmlParserTest`: тест парсинга тестового XML файла
- `GroupPromotionTest`: тест перевода групп 30 августа
- `HoursTrackingTest`: тест корректного учёта часов

---

**При создании используй:**

- PHP (readonly properties, enums, named arguments)
- Laravel conventions (без Http/Kernel, новая структура)
- Livewire (wire:model.live, lazy loading, #[Rule] атрибуты)
- Строгая типизация (`declare(strict_types=1)`)
- Комментарии на русском языке для сложной бизнес-логики

---

_Если что-то в структуре XML Шахтинской программы будет отличаться при реальном импорте — сервис-парсер должен быть гибким и настраиваемым через config файл `config/curriculum.php` с маппингом полей XML._

```

```

# ПРОМТ ДЛЯ CLAUDE CODE: Исправление критических проблем системы расписания

> **Контекст:** В проекте уже реализована система генерации расписания на Laravel/Livewire.  
> Необходимо исправить 4 критические проблемы, описанные ниже.

---

## ПРОБЛЕМА 1: Не импортируются данные из Excel учебного плана (Шахтинская программа)

### Что нужно сделать

Создать новый сервис `App\Services\Curriculum\ExcelCurriculumParserService` и обновить существующий Livewire-компонент импорта.

### Структура Excel файла из Шахтинской программы

Файл имеет множество листов. Нас интересует **лист «План»** (иногда «ПланСвод»).

**Структура листа «Титул»** (строки 0-40, нужно извлечь):

- Название учебного заведения: ячейка col=2, row≈20 (содержит длинный текст с названием)
- Код специальности: ячейка col=4, row≈26 (например `15.02.14`)
- Название специальности: ячейка col=4, row≈28 (длинный текст)
- Квалификация: ячейка col=4, row≈39 (текст после «Квалификация:»)
- Год начала подготовки: ищем ячейку содержащую «Год начала подготовки» — значение правее

**Структура листа «План»** (главный лист с дисциплинами):

Строки заголовков (первые 3 строки, индексы 0, 1, 2):

- Строка 0: группы колонок (Курс 1, Курс 2, ... Курс 4)
- Строка 1: подгруппы (Семестр 1, Семестр 2 ... Семестр 8)
- Строка 2: названия полей (Индекс, Наименование, Экзамен, Зачёт, Зачёт с оц., КР, Аудиторные, Лек, Лаб, Пр, СР, Итого и т.д.)

**Маппинг колонок листа «План»** (col — индекс колонки с 0):

```
col 0  = "Считать в плане" (+ означает дисциплину, иначе — группа/заголовок)
col 1  = "Индекс" (код дисциплины: ОД.01, ОГСЭ.03, ЕН.01, ОП.01, ПМ.01, МДК.01.01)
col 2  = "Наименование" (название дисциплины)
col 3  = "Экзамен" (семестры через запятую или число, например "3,4" или "34")
col 4  = "Зачёт"
col 5  = "Зачёт с оц." (Зачёт с оценкой / Дифференцированный зачёт)
col 6  = "КР" (Курсовая работа)
col 7  = "Др" (Курсовой проект)
col 8  = "Трудоёмкость" (общий объём)
col 9  = "По плану" (аудиторные + СР)
col 10 = "Конт. раб." (контрольные работы — для заочной)
col 11 = "Ауд." (аудиторные часы всего)
col 12 = "Лек" (лекции всего)
col 13 = "Лаб" (лабораторные всего)
col 14 = "Пр" (практические всего)
col 15 = "КРП" (часы на курс. работу/проект)
col 16 = "Конс" (консультации)
col 17 = "СР" (самостоятельная работа всего)
col 18 = "КПЭ"
col 19 = "СРПЭ"
col 20 = "ПАтт" (промежуточная аттестация)
col 21 = "Пр. подгот" (практическая подготовка)
col 22 = "Обяз. часть" (обязательная часть — часы)
col 23 = "Вар. часть" (вариативная часть — часы)

--- КУРС 1 ---
col 24 = Курс 1, Итого
col 25 = Курс 1, Конт. раб.
col 26 = Курс 1, Семестр 1, Лек
col 27 = Курс 1, Семестр 1, Лаб
col 28 = Курс 1, Семестр 1, Пр
col 29 = Курс 1, Семестр 1, КоР
col 30 = Курс 1, Семестр 1, КРП
col 31 = Курс 1, Семестр 1, Конс
col 32 = Курс 1, Семестр 1, СР
col 33 = Курс 1, Семестр 1, КПЭ
col 34 = Курс 1, Семестр 1, СРПЭ
col 35 = Курс 1, Семестр 1, ПАтт
col 36 = Курс 1, Семестр 2, Итого
col 37 = Курс 1, Семестр 2, Конт. раб.
col 38 = Курс 1, Семестр 2, Лек
col 39 = Курс 1, Семестр 2, Лаб
col 40 = Курс 1, Семестр 2, Пр
col 41 = Курс 1, Семестр 2, КоР
col 42 = Курс 1, Семестр 2, КРП
col 43 = Курс 1, Семестр 2, Конс
col 44 = Курс 1, Семестр 2, СР
col 45 = Курс 1, Семестр 2, КПЭ
col 46 = Курс 1, Семестр 2, СРПЭ
col 47 = Курс 1, Семестр 2, ПАтт

--- КУРС 2 ---
col 48 = Курс 2, Итого
col 49 = Курс 2, Конт. раб.
col 50 = Курс 2, Семестр 3, Лек
col 51 = Курс 2, Семестр 3, Лаб
col 52 = Курс 2, Семестр 3, Пр
col 53 = Курс 2, Семестр 3, КоР
col 54 = Курс 2, Семестр 3, КРП
col 55 = Курс 2, Семестр 3, Конс
col 56 = Курс 2, Семестр 3, СР
col 57 = Курс 2, Семестр 3, КПЭ
col 58 = Курс 2, Семестр 3, СРПЭ
col 59 = Курс 2, Семестр 3, ПАтт
col 60 = Курс 2, Семестр 4, Итого
col 61 = Курс 2, Семестр 4, Конт. раб.
col 62 = Курс 2, Семестр 4, Лек
col 63 = Курс 2, Семестр 4, Лаб
col 64 = Курс 2, Семестр 4, Пр
col 65 = Курс 2, Семестр 4, КоР
col 66 = Курс 2, Семестр 4, КРП
col 67 = Курс 2, Семестр 4, Конс
col 68 = Курс 2, Семестр 4, СР
col 69 = Курс 2, Семестр 4, КПЭ
col 70 = Курс 2, Семестр 4, СРПЭ
col 71 = Курс 2, Семестр 4, ПАтт

--- КУРС 3 ---
col 72 = Курс 3, Итого
col 73 = Курс 3, Конт. раб.
col 74 = Курс 3, Семестр 5, Лек
col 75 = Курс 3, Семестр 5, Лаб
col 76 = Курс 3, Семестр 5, Пр
col 77 = Курс 3, Семестр 5, КоР
col 78 = Курс 3, Семестр 5, КРП
col 79 = Курс 3, Семестр 5, Конс
col 80 = Курс 3, Семестр 5, СР
col 81 = Курс 3, Семестр 5, КПЭ
col 82 = Курс 3, Семестр 5, СРПЭ
col 83 = Курс 3, Семестр 5, ПАтт
col 84 = Курс 3, Семестр 6, Итого
col 85 = Курс 3, Семестр 6, Конт. раб.
col 86 = Курс 3, Семестр 6, Лек
col 87 = Курс 3, Семестр 6, Лаб
col 88 = Курс 3, Семестр 6, Пр
col 89 = Курс 3, Семестр 6, КоР
col 90 = Курс 3, Семестр 6, КРП
col 91 = Курс 3, Семестр 6, Конс
col 92 = Курс 3, Семестр 6, СР
col 93 = Курс 3, Семестр 6, КПЭ
col 94 = Курс 3, Семестр 6, СРПЭ
col 95 = Курс 3, Семестр 6, ПАтт

--- КУРС 4 ---
col 96  = Курс 4, Итого
col 97  = Курс 4, Конт. раб.
col 98  = Курс 4, Семестр 7, Лек
col 99  = Курс 4, Семестр 7, Лаб
col 100 = Курс 4, Семестр 7, Пр
col 101 = Курс 4, Семестр 7, КоР
col 102 = Курс 4, Семестр 7, КРП
col 103 = Курс 4, Семестр 7, Конс
col 104 = Курс 4, Семестр 7, СР
col 105 = Курс 4, Семестр 7, КПЭ
col 106 = Курс 4, Семестр 7, СРПЭ
col 107 = Курс 4, Семестр 7, ПАтт
col 108 = Курс 4, Семестр 8, Итого
col 109 = Курс 4, Семестр 8, Конт. раб.
col 110 = Курс 4, Семестр 8, Лек
col 111 = Курс 4, Семестр 8, Лаб
col 112 = Курс 4, Семестр 8, Пр
col 113 = Курс 4, Семестр 8, КоР
col 114 = Курс 4, Семестр 8, КРП
col 115 = Курс 4, Семестр 8, Конс
col 116 = Курс 4, Семестр 8, СР
col 117 = Курс 4, Семестр 8, КПЭ
col 118 = Курс 4, Семестр 8, СРПЭ
col 119 = Курс 4, Семестр 8, ПАтт

col 120 = Код закреплённой кафедры
col 121 = Название закреплённой кафедры
```

**ВАЖНО:** Колонок Семестра 1 «Итого» нет отдельно — итого семестра 1 = col 24 (Курс 1 Итого) минус col 36 (Семестр 2 Итого). Либо считай самостоятельно как сумму Лек+Лаб+Пр+Конс+СР. Итого семестра нужно вычислять как `sum(Лек, Лаб, Пр, КоР, КРП, Конс, СР, КПЭ, СРПЭ, ПАтт)` — беря ненулевые значения.

### Алгоритм парсинга (важные детали)

```php
// Строка является дисциплиной (не группой/заголовком), если:
// 1. В col[0] содержится символ "+" (или true/1)
// 2. col[1] не пустой (есть индекс)
// 3. col[2] не пустой (есть название)
// 4. col[1] НЕ содержит только цифры (не сквозной номер)

// Строка является ГРУППОЙ/ЦИКЛОМ (заголовком раздела), если:
// col[0] НЕ содержит "+" но col[2] содержит текст типа "ОГСЭ.", "ЕН.", "ОПЦ.", "ПМ."
// Эти строки нужно пропускать, они не дисциплины

// Определение формы контроля по семестру:
// col[3] = "Экзамен" — парсим числа/строки: "3,4" → семестры 3 и 4
// col[4] = "Зачёт"
// col[5] = "Зачёт с оц." (Дифференцированный зачёт)
// Число может быть: "3" → семестр 3, "34" → семестры 3 и 4, "3,4" → то же
// Значение может содержать цифры и буквы (например "2*" — считать как семестр 2)

// Функция безопасного приведения к int:
function safeInt($value): int {
    if (is_null($value) || $value === '' || $value === '-') return 0;
    $v = preg_replace('/[^0-9]/', '', (string)$value);
    return $v !== '' ? (int)$v : 0;
}
```

### Создать файл: `app/Services/Curriculum/ExcelCurriculumParserService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services\Curriculum;

use App\Models\AcademicYear;
use App\Models\ControlForm;
use App\Models\CurriculumDiscipline;
use App\Models\CurriculumPlan;
use App\Models\CurriculumSemester;
use App\Models\ImportLog;
use App\Models\Specialty;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelCurriculumParserService
{
    // Маппинг семестра → индексы колонок [лек, лаб, пр, конс, ср, паттест]
    // Семестр 1 = Курс 1, 1й семестр
    private const SEMESTER_COLUMNS = [
        1 => ['lec' => 26, 'lab' => 27, 'prac' => 28, 'cor' => 29, 'krp' => 30, 'cons' => 31, 'sr' => 32, 'patt' => 35],
        2 => ['lec' => 38, 'lab' => 39, 'prac' => 40, 'cor' => 41, 'krp' => 42, 'cons' => 43, 'sr' => 44, 'patt' => 47],
        3 => ['lec' => 50, 'lab' => 51, 'prac' => 52, 'cor' => 53, 'krp' => 54, 'cons' => 55, 'sr' => 56, 'patt' => 59],
        4 => ['lec' => 62, 'lab' => 63, 'prac' => 64, 'cor' => 65, 'krp' => 66, 'cons' => 67, 'sr' => 68, 'patt' => 71],
        5 => ['lec' => 74, 'lab' => 75, 'prac' => 76, 'cor' => 77, 'krp' => 78, 'cons' => 79, 'sr' => 80, 'patt' => 83],
        6 => ['lec' => 86, 'lab' => 87, 'prac' => 88, 'cor' => 89, 'krp' => 90, 'cons' => 91, 'sr' => 92, 'patt' => 95],
        7 => ['lec' => 98, 'lab' => 99, 'prac' => 100, 'cor' => 101, 'krp' => 102, 'cons' => 103, 'sr' => 104, 'patt' => 107],
        8 => ['lec' => 110, 'lab' => 111, 'prac' => 112, 'cor' => 113, 'krp' => 114, 'cons' => 115, 'sr' => 116, 'patt' => 119],
    ];

    public function parseFile(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);

        $meta = $this->parseTitleSheet($spreadsheet);
        $disciplines = $this->parsePlanSheet($spreadsheet);

        return [
            'meta' => $meta,
            'disciplines' => $disciplines,
            'total' => count($disciplines),
        ];
    }

    private function parseTitleSheet($spreadsheet): array
    {
        $sheet = null;
        foreach (['Титул', 'титул', 'Title'] as $name) {
            try { $sheet = $spreadsheet->getSheetByName($name); } catch (\Exception $e) {}
            if ($sheet) break;
        }

        if (!$sheet) return [];

        $data = [];
        $rows = $sheet->toArray(null, true, true, false);

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $cell) {
                $val = trim((string)($cell ?? ''));
                if (str_contains($val, 'Год начала подготовки')) {
                    // Ищем число правее
                    for ($c = $colIndex + 1; $c < $colIndex + 10; $c++) {
                        $v = trim((string)($row[$c] ?? ''));
                        if (preg_match('/^\d{4}$/', $v)) {
                            $data['year_start'] = (int)$v;
                            break;
                        }
                    }
                }
                // Специальность — код формата XX.XX.XX
                if (preg_match('/^\d{2}\.\d{2}\.\d{2}$/', $val)) {
                    $data['specialty_code'] = $val;
                }
                // Название специальности — длинная строка содержащая ключевые слова
                if (strlen($val) > 30 && (str_contains(mb_strtoupper($val), 'АВТОМАТИЗ')
                    || str_contains(mb_strtoupper($val), 'ПРОГРАММ')
                    || str_contains(mb_strtoupper($val), 'ИНФОРМ')
                    || str_contains(mb_strtoupper($val), 'БУХГАЛТ')
                    || str_contains(mb_strtoupper($val), 'СТРОИ')
                    || str_contains(mb_strtoupper($val), 'ТЕХНИК')
                    ) && !isset($data['specialty_name'])) {
                    $data['specialty_name'] = $val;
                }
                // Квалификация
                if (str_contains($val, 'Квалификация:') || str_contains($val, 'Квалификация ')) {
                    $q = trim(str_ireplace(['Квалификация:', 'Квалификация'], '', $val));
                    if ($q) $data['qualification'] = $q;
                }
                // Название организации — ищем "ГБПОУ" или "колледж" или "техникум"
                if ((str_contains(mb_strtolower($val), 'колледж')
                    || str_contains(mb_strtolower($val), 'техникум')
                    || str_contains($val, 'ГБПОУ')
                    || str_contains($val, 'ГАПОУ'))
                    && strlen($val) > 20 && !isset($data['org_name'])) {
                    // Убираем переносы строк
                    $data['org_name'] = preg_replace('/\s+/', ' ', str_replace(["\r\n", "\n", "\r", "_x000d_"], ' ', $val));
                }
            }
        }

        return $data;
    }

    private function parsePlanSheet($spreadsheet): array
    {
        // Попробовать найти лист «План»
        $sheet = null;
        foreach (['План', 'план', 'Plan', 'ПланСвод'] as $name) {
            try { $sheet = $spreadsheet->getSheetByName($name); } catch (\Exception $e) {}
            if ($sheet) break;
        }

        if (!$sheet) {
            throw new \RuntimeException('Лист «План» не найден в файле. Доступные листы: '
                . implode(', ', array_map(fn($s) => $s->getTitle(), $spreadsheet->getAllSheets())));
        }

        $rows = $sheet->toArray(null, true, true, false);
        $disciplines = [];

        // Строки 0,1,2 — заголовки, пропускаем
        // Строка 3+ — данные
        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex < 3) continue;

            $marker = trim((string)($row[0] ?? ''));
            $code   = trim((string)($row[1] ?? ''));
            $name   = trim((string)($row[2] ?? ''));

            // Пропускаем пустые строки
            if ($name === '' && $code === '') continue;

            // Дисциплина = строки где в col[0] есть "+"
            if ($marker !== '+') continue;

            // Пропускаем строки-заголовки циклов (ОД, ОГСЭ, ЕН, ОПЦ, ПМ без точки+цифры)
            // Настоящие дисциплины имеют код вида: ОД.01, ОГСЭ.01, ЕН.01, ОП.01, МДК.01.01
            if (!preg_match('/[А-Яа-яA-Za-z]+\.\d+/', $code)) continue;

            // Формы контроля (семестры через запятую или слитно)
            $examSemesters  = $this->parseControlSemesters((string)($row[3] ?? ''));
            $testSemesters  = $this->parseControlSemesters((string)($row[4] ?? ''));
            $diffTestSems   = $this->parseControlSemesters((string)($row[5] ?? ''));
            $courseWorkSems = $this->parseControlSemesters((string)($row[6] ?? ''));
            $courseProjSems = $this->parseControlSemesters((string)($row[7] ?? ''));

            // Общие часы
            $totalHours    = $this->safeInt($row[8] ?? null);
            $contactHours  = $this->safeInt($row[11] ?? null); // Ауд.
            $lectureHours  = $this->safeInt($row[12] ?? null);
            $labHours      = $this->safeInt($row[13] ?? null);
            $pracHours     = $this->safeInt($row[14] ?? null);
            $selfStudyHours = $this->safeInt($row[17] ?? null);
            $practiceHours = $this->safeInt($row[21] ?? null);

            // Цикл дисциплины
            $cycle = $this->determineCycle($code);

            // Кафедра (последние 2 колонки)
            $deptCode = trim((string)($row[120] ?? ''));
            $deptName = trim((string)($row[121] ?? ''));

            // Нужна ли лабораторная/специальный кабинет
            $requiresLab = $labHours > 0;

            $discipline = [
                'code'           => $code,
                'name'           => $name,
                'cycle'          => $cycle,
                'total_hours'    => $totalHours,
                'contact_hours'  => $contactHours,
                'lecture_hours'  => $lectureHours,
                'lab_hours'      => $labHours,
                'practice_hours' => $pracHours,
                'self_study_hours' => $selfStudyHours,
                'practice_total_hours' => $practiceHours,
                'requires_lab'   => $requiresLab,
                'dept_code'      => $deptCode,
                'dept_name'      => $deptName,
                'semesters'      => [],
            ];

            // Парсим данные по каждому семестру (1-8)
            foreach (self::SEMESTER_COLUMNS as $semNum => $cols) {
                $lecH  = $this->safeInt($row[$cols['lec']]  ?? null);
                $labH  = $this->safeInt($row[$cols['lab']]  ?? null);
                $prH   = $this->safeInt($row[$cols['prac']] ?? null);
                $corH  = $this->safeInt($row[$cols['cor']]  ?? null);
                $krpH  = $this->safeInt($row[$cols['krp']]  ?? null);
                $consH = $this->safeInt($row[$cols['cons']] ?? null);
                $srH   = $this->safeInt($row[$cols['sr']]   ?? null);
                $pattH = $this->safeInt($row[$cols['patt']] ?? null);

                $semTotal = $lecH + $labH + $prH + $corH + $krpH + $consH + $srH + $pattH;

                // Пропускаем пустые семестры
                if ($semTotal === 0) continue;

                $courseNum = (int)ceil($semNum / 2);
                $semInCourse = $semNum % 2 === 1 ? 1 : 2;

                // Определяем форму контроля для этого семестра
                $controlForm = null;
                if (in_array($semNum, $examSemesters)) $controlForm = 'exam';
                elseif (in_array($semNum, $diffTestSems)) $controlForm = 'diff_test';
                elseif (in_array($semNum, $testSemesters)) $controlForm = 'test';
                elseif (in_array($semNum, $courseWorkSems)) $controlForm = 'course_work';
                elseif (in_array($semNum, $courseProjSems)) $controlForm = 'course_project';

                $discipline['semesters'][] = [
                    'semester_number'    => $semNum,
                    'course_number'      => $courseNum,
                    'semester_in_course' => $semInCourse,
                    'hours_lecture'      => $lecH,
                    'hours_lab'          => $labH,
                    'hours_practice'     => $prH,
                    'hours_consultation' => $consH,
                    'hours_self_study'   => $srH,
                    'hours_practice_prep' => $pattH,
                    'course_work_hours'  => $krpH,
                    'exam_hours'         => $pattH, // часы на аттестацию
                    'hours_total'        => $semTotal,
                    'control_form_code'  => $controlForm,
                ];
            }

            // Добавляем только если есть хотя бы 1 семестр
            if (!empty($discipline['semesters'])) {
                $disciplines[] = $discipline;
            }
        }

        return $disciplines;
    }

    /**
     * Парсит строку вида "3,4", "34", "3", "2*", "2 4" в массив номеров семестров
     */
    private function parseControlSemesters(string $value): array
    {
        $value = trim($value);
        if ($value === '' || $value === '-' || $value === '0') return [];

        // Убираем звёздочки, буквы кроме запятой и пробелов
        $clean = preg_replace('/[^0-9,\s]/', '', $value);

        // Если есть запятые или пробелы — разбиваем
        if (str_contains($clean, ',') || str_contains($clean, ' ')) {
            $parts = preg_split('/[,\s]+/', $clean);
            return array_values(array_filter(array_map('intval', $parts), fn($n) => $n > 0 && $n <= 8));
        }

        // Если длинное число типа "34" — каждая цифра отдельный семестр
        if (strlen($clean) > 1) {
            return array_values(array_filter(
                array_map('intval', str_split($clean)),
                fn($n) => $n > 0 && $n <= 8
            ));
        }

        $n = (int)$clean;
        return $n > 0 && $n <= 8 ? [$n] : [];
    }

    private function determineCycle(string $code): string
    {
        if (str_starts_with($code, 'ОД')) return 'ОД';
        if (str_starts_with($code, 'ОГСЭ')) return 'ОГСЭ';
        if (str_starts_with($code, 'ЕН')) return 'ЕН';
        if (str_starts_with($code, 'ОП')) return 'ОП';
        if (str_starts_with($code, 'МДК')) return 'МДК';
        if (str_starts_with($code, 'ПМ')) return 'ПМ';
        if (str_starts_with($code, 'ФК') || str_starts_with($code, 'ФЦД')) return 'ФК';
        return 'ОП';
    }

    private function safeInt($value): int
    {
        if (is_null($value) || $value === '' || $value === '-') return 0;
        if (is_numeric($value)) return (int)$value;
        $v = preg_replace('/[^0-9]/', '', (string)$value);
        return $v !== '' ? (int)$v : 0;
    }

    /**
     * Сохранить распарсенные данные в БД
     */
    public function importToDatabase(
        array $parsedData,
        int $specialtyId,
        int $academicYearId,
        int $userId,
        string $filePath
    ): array {
        $imported = 0;
        $failed = 0;
        $errors = [];

        DB::transaction(function () use ($parsedData, $specialtyId, $academicYearId, $userId, $filePath, &$imported, &$failed, &$errors) {
            // Создаём или находим план
            $plan = CurriculumPlan::updateOrCreate(
                [
                    'specialty_id'    => $specialtyId,
                    'academic_year_id' => $academicYearId,
                ],
                [
                    'name'        => 'Учебный план ' . ($parsedData['meta']['year_start'] ?? date('Y')),
                    'version'     => date('Y') . '-v1',
                    'excel_file_path' => $filePath,
                    'parsed_at'   => now(),
                    'is_active'   => true,
                    'created_by'  => $userId,
                ]
            );

            // Получаем маппинг форм контроля
            $controlForms = ControlForm::pluck('id', 'code')->toArray();

            foreach ($parsedData['disciplines'] as $disciplineData) {
                try {
                    $discipline = CurriculumDiscipline::updateOrCreate(
                        [
                            'curriculum_plan_id' => $plan->id,
                            'code'               => $disciplineData['code'],
                        ],
                        [
                            'name'              => $disciplineData['name'],
                            'short_name'        => mb_substr($disciplineData['name'], 0, 50),
                            'cycle'             => $disciplineData['cycle'],
                            'discipline_type'   => $this->mapDisciplineType($disciplineData['code']),
                            'requires_lab'      => $disciplineData['requires_lab'],
                            'sort_order'        => $imported + 1,
                        ]
                    );

                    // Сохраняем данные по семестрам
                    foreach ($disciplineData['semesters'] as $semData) {
                        $controlFormId = null;
                        if ($semData['control_form_code']) {
                            $controlFormId = $controlForms[$semData['control_form_code']] ?? null;
                        }

                        CurriculumSemester::updateOrCreate(
                            [
                                'discipline_id'      => $discipline->id,
                                'semester_number'    => $semData['semester_number'],
                            ],
                            [
                                'course_number'      => $semData['course_number'],
                                'semester_in_course' => $semData['semester_in_course'],
                                'hours_total'        => $semData['hours_total'],
                                'hours_lecture'      => $semData['hours_lecture'],
                                'hours_practice'     => $semData['hours_practice'],
                                'hours_lab'          => $semData['hours_lab'],
                                'hours_self_study'   => $semData['hours_self_study'],
                                'hours_consultation' => $semData['hours_consultation'],
                                'control_form_id'    => $controlFormId,
                                'exam_hours'         => $semData['exam_hours'],
                                'course_project_hours' => $semData['course_work_hours'],
                            ]
                        );
                    }

                    $imported++;
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = "Дисциплина {$disciplineData['code']} ({$disciplineData['name']}): " . $e->getMessage();
                    Log::error('Ошибка импорта дисциплины', [
                        'discipline' => $disciplineData['code'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        return [
            'imported' => $imported,
            'failed'   => $failed,
            'errors'   => $errors,
        ];
    }

    private function mapDisciplineType(string $code): string
    {
        if (str_starts_with($code, 'ПМ')) return 'professional_module';
        if (str_starts_with($code, 'МДК')) return 'professional_module';
        if (str_starts_with($code, 'ФК') || str_starts_with($code, 'ФЦД')) return 'optional';
        return 'theoretical';
    }
}
```

### Обновить Livewire-компонент `App\Http\Livewire\Curriculum\CurriculumImportForm`

Добавить поддержку Excel (`.xlsx`, `.xls`). Метод `import()`:

```php
public function import(ExcelCurriculumParserService $excelParser, XmlCurriculumParserService $xmlParser): void
{
    $this->validate([
        'file'           => 'required|file|mimes:xlsx,xls,xml|max:10240',
        'specialty_id'   => 'required|integer|exists:specialties,id',
        'academic_year_id' => 'required|integer|exists:academic_years,id',
    ]);

    $path = $this->file->store('curriculum_uploads', 'local');
    $fullPath = storage_path('app/' . $path);
    $ext = strtolower($this->file->getClientOriginalExtension());

    try {
        if (in_array($ext, ['xlsx', 'xls'])) {
            $parsed = $excelParser->parseFile($fullPath);
            $result = $excelParser->importToDatabase(
                $parsed,
                (int)$this->specialty_id,
                (int)$this->academic_year_id,
                auth()->id(),
                $path
            );
        } else {
            // XML — существующая логика
            $parsed = $xmlParser->parse($fullPath);
            $result = $xmlParser->importToPlan($parsed, /* ... */);
        }

        $this->importResult = [
            'success'  => true,
            'imported' => $result['imported'],
            'failed'   => $result['failed'],
            'errors'   => $result['errors'],
            'preview'  => $parsed['disciplines'] ?? [],
        ];

        session()->flash('message', "Импорт завершён: {$result['imported']} дисциплин добавлено.");
    } catch (\Throwable $e) {
        session()->flash('error', 'Ошибка импорта: ' . $e->getMessage());
        Log::error('Ошибка импорта учебного плана', ['error' => $e->getMessage()]);
    }
}
```

### Добавить зависимость PhpSpreadsheet

```bash
composer require phpoffice/phpspreadsheet
```

---

## ПРОБЛЕМА 2: Не работают правила проверки расписания

### Что именно не работает

Метод `checkGroupConflict` в `ConflictCheckerService` сейчас проверяет только факт существования урока в том же слоте. Но НЕ проверяет:

1. **Корпус группы на день** — группа не должна быть в двух корпусах за день
2. **Корпус преподавателя на день** — преподаватель не должен быть в двух корпусах
3. **Недельная нагрузка** — не более 36 часов в неделю (18 пар × 2 часа)
4. **Смена группы** — логика определения смены (1-2 курс = смена 1, 3-4 курс = смена 2)

### Файл: `app/Services/Schedule/ConflictCheckerService.php`

**Добавить следующие методы в класс:**

```php
/**
 * Проверка: группа/преподаватель в двух корпусах за один день
 */
private function checkBuildingConflicts(Collection $dayLessons, string $date, int $versionId, array &$conflicts): void
{
    // Проверяем группы
    $byGroup = $dayLessons->groupBy('group_id');
    foreach ($byGroup as $groupId => $groupLessons) {
        $buildings = $groupLessons
            ->filter(fn($l) => !$this->isSportBuilding($l))
            ->pluck('building_id')
            ->filter()
            ->unique();

        if ($buildings->count() > 1) {
            $group = $groupLessons->first()->group;
            $buildingNames = $groupLessons->map(fn($l) => $l->room?->building?->short_name ?? '?')->unique()->implode(', ');
            $conflicts[] = [
                'version_id'    => $versionId,
                'conflict_type' => 'group_building_conflict',
                'severity'      => 'error',
                'date'          => $date,
                'lesson_number' => null,
                'group_id'      => $groupId,
                'description'   => "Группа {$group?->name} в {$date} находится в нескольких корпусах: {$buildingNames}",
                'is_resolved'   => false,
                'suggestion'    => "Перенести все пары группы {$group?->name} в один корпус на {$date}",
            ];
        }
    }

    // Проверяем преподавателей
    $byTeacher = $dayLessons->groupBy('teacher_id');
    foreach ($byTeacher as $teacherId => $teacherLessons) {
        if (!$teacherId) continue;

        $buildings = $teacherLessons
            ->filter(fn($l) => !$this->isSportBuilding($l))
            ->pluck('building_id')
            ->filter()
            ->unique();

        if ($buildings->count() > 1) {
            $teacher = $teacherLessons->first()->teacher;
            $buildingNames = $teacherLessons->map(fn($l) => $l->room?->building?->short_name ?? '?')->unique()->implode(', ');
            $conflicts[] = [
                'version_id'    => $versionId,
                'conflict_type' => 'teacher_building_conflict',
                'severity'      => 'error',
                'date'          => $date,
                'lesson_number' => null,
                'teacher_id'    => $teacherId,
                'description'   => "Преподаватель {$teacher?->last_name} {$teacher?->first_name} в {$date} в нескольких корпусах: {$buildingNames}",
                'is_resolved'   => false,
                'suggestion'    => "Перенести все пары {$teacher?->last_name} в один корпус на {$date}",
            ];
        }
    }
}

/**
 * Проверка недельной нагрузки групп (не более 36 часов = 18 пар)
 */
private function checkWeeklyHoursLimit(Collection $weekLessons, string $weekStartDate, int $versionId, array &$conflicts): void
{
    $byGroup = $weekLessons->groupBy('group_id');
    $maxLessons = 18; // 36 часов / 2 часа на пару

    foreach ($byGroup as $groupId => $groupLessons) {
        $lessonCount = $groupLessons->where('status', '!=', 'cancelled')->count();
        if ($lessonCount > $maxLessons) {
            $group = $groupLessons->first()->group;
            $hours = $lessonCount * 2;
            $conflicts[] = [
                'version_id'    => $versionId,
                'conflict_type' => 'group_overload',
                'severity'      => 'warning',
                'date'          => $weekStartDate,
                'lesson_number' => null,
                'group_id'      => $groupId,
                'description'   => "Группа {$group?->name}: превышена недельная нагрузка — {$hours} часов (макс. 36)",
                'is_resolved'   => false,
                'suggestion'    => "Убрать " . ($lessonCount - $maxLessons) . " пар у группы {$group?->name} на этой неделе",
            ];
        }
    }

    // Аналогично для преподавателей
    $byTeacher = $weekLessons->groupBy('teacher_id');
    foreach ($byTeacher as $teacherId => $teacherLessons) {
        if (!$teacherId) continue;
        $teacher = $teacherLessons->first()->teacher;
        $maxWeekHours = $teacher?->max_hours_per_week ?? 36;
        $maxLessonsForTeacher = (int)($maxWeekHours / 2);
        $lessonCount = $teacherLessons->where('status', '!=', 'cancelled')->count();

        if ($lessonCount > $maxLessonsForTeacher) {
            $hours = $lessonCount * 2;
            $conflicts[] = [
                'version_id'    => $versionId,
                'conflict_type' => 'teacher_overload',
                'severity'      => 'warning',
                'date'          => $weekStartDate,
                'lesson_number' => null,
                'teacher_id'    => $teacherId,
                'description'   => "Преподаватель {$teacher?->last_name}: превышена нагрузка за неделю — {$hours} часов (макс. {$maxWeekHours})",
                'is_resolved'   => false,
                'suggestion'    => "Убрать " . ($lessonCount - $maxLessonsForTeacher) . " пар у {$teacher?->last_name} на неделе с {$weekStartDate}",
            ];
        }
    }
}

private function isSportBuilding($lesson): bool
{
    // Спортзал — исключение для правила одного корпуса
    $roomTypeName = $lesson->room?->roomType?->name ?? '';
    return str_contains(mb_strtolower($roomTypeName), 'спорт');
}
```

**Обновить метод `checkVersionForRange`** — добавить вызовы новых проверок:

```php
// В цикле foreach ($groupedByDate as $date => $dayLessons):
// После существующих проверок добавить:
$this->checkBuildingConflicts(collect($dayLessons), $date, $versionId, $conflicts);

// После цикла по датам добавить недельные проверки:
$groupedByWeek = $lessons->groupBy(fn($l) => Carbon::parse($l->date)->startOfWeek()->format('Y-m-d'));
foreach ($groupedByWeek as $weekStart => $weekLessons) {
    $this->checkWeeklyHoursLimit(collect($weekLessons), $weekStart, $versionId, $conflicts);
}
```

---

## ПРОБЛЕМА 3: Не работает авторешение конфликтов (`autoFix`)

### Проблема

Текущий `autoFix` пытается найти свободную комнату через `Room::where(...)->inRandomOrder()` — это работает только для конфликтов типа «одна аудитория у двух групп». Все остальные типы конфликтов не обрабатываются.

### Улучшить метод `autoFix` в `ConflictCheckerService`

Заменить текущий `autoFix` на следующий:

```php
public function autoFix(int $versionId): array
{
    $fixed = 0;
    $skipped = 0;
    $messages = [];

    $conflicts = ScheduleConflict::where('version_id', $versionId)
        ->where('is_resolved', false)
        ->orderBy('severity') // сначала error
        ->get();

    foreach ($conflicts as $conflict) {
        $result = match($conflict->conflict_type) {
            'room_multi_group'   => $this->fixRoomConflict($conflict, $versionId),
            'teacher_parallel'   => $this->fixTeacherParallelConflict($conflict, $versionId),
            'teacher_window'     => $this->fixTeacherWindow($conflict, $versionId),
            'group_window'       => $this->fixGroupWindow($conflict, $versionId),
            'group_shift_mismatch' => $this->fixGroupShift($conflict, $versionId),
            default              => ['fixed' => false, 'reason' => 'требует ручного вмешательства'],
        };

        if ($result['fixed']) {
            $fixed++;
            $conflict->update([
                'is_resolved' => true,
                'resolved_at' => now(),
                'resolution_notes' => 'Автоматически исправлено: ' . ($result['reason'] ?? ''),
            ]);
            $messages[] = "✓ [{$conflict->conflict_type}] {$result['reason']}";
        } else {
            $skipped++;
        }
    }

    return [
        'fixed'    => $fixed,
        'skipped'  => $skipped,
        'message'  => $fixed > 0
            ? "Исправлено {$fixed} конфликтов, пропущено {$skipped}"
            : 'Автоматически исправляемых конфликтов не найдено',
        'details'  => $messages,
    ];
}

/**
 * Исправить: две группы в одной аудитории → перенести одну в другую свободную
 */
private function fixRoomConflict(ScheduleConflict $conflict, int $versionId): array
{
    $lessons = ScheduleLesson::where('version_id', $versionId)
        ->where('room_id', $conflict->room_id)
        ->where('date', $conflict->date)
        ->where('lesson_number', $conflict->lesson_number)
        ->where('status', '!=', 'cancelled')
        ->get();

    if ($lessons->count() < 2) {
        return ['fixed' => false, 'reason' => 'конфликт уже устранён'];
    }

    // Оставляем первый урок, переносим второй в другую аудиторию
    $lessonToMove = $lessons->skip(1)->first();
    $busyRoomIds = ScheduleLesson::where('version_id', $versionId)
        ->where('date', $conflict->date)
        ->where('lesson_number', $conflict->lesson_number)
        ->where('status', '!=', 'cancelled')
        ->pluck('room_id')
        ->toArray();

    $group = $lessonToMove->group;
    $freeRoom = Room::where('is_active', true)
        ->where('is_available_for_booking', true)
        ->whereNotIn('id', $busyRoomIds)
        ->when($lessonToMove->building_id, fn($q) => $q->where('building_id', $lessonToMove->building_id))
        ->where('capacity', '>=', $group?->students_count ?? 1)
        ->orderBy('capacity')
        ->first();

    if (!$freeRoom) {
        return ['fixed' => false, 'reason' => 'нет свободной аудитории'];
    }

    $lessonToMove->update(['room_id' => $freeRoom->id]);

    return [
        'fixed'  => true,
        'reason' => "Группа {$group?->name} перемещена в ауд. {$freeRoom->number}",
    ];
}

/**
 * Исправить: преподаватель в двух местах одновременно → найти замену для одного из уроков
 */
private function fixTeacherParallelConflict(ScheduleConflict $conflict, int $versionId): array
{
    // Это сложный конфликт — просто помечаем как требующий ручного решения
    return ['fixed' => false, 'reason' => 'нужна замена преподавателя — решите вручную'];
}

/**
 * Исправить окно у преподавателя: попробовать перетащить урок ближе
 */
private function fixTeacherWindow(ScheduleConflict $conflict, int $versionId): array
{
    if (!$conflict->teacher_id || !$conflict->lesson_number) {
        return ['fixed' => false, 'reason' => 'нет данных для исправления'];
    }

    $lessonToMove = ScheduleLesson::where('version_id', $versionId)
        ->where('date', $conflict->date)
        ->where('teacher_id', $conflict->teacher_id)
        ->where('lesson_number', $conflict->lesson_number)
        ->where('status', '!=', 'cancelled')
        ->first();

    if (!$lessonToMove) {
        return ['fixed' => false, 'reason' => 'урок не найден'];
    }

    // Найти ближайший свободный слот
    $prevLessonNumber = $conflict->lesson_number - 1;
    while ($prevLessonNumber > 0) {
        $teacherBusy = ScheduleLesson::where('version_id', $versionId)
            ->where('date', $conflict->date)
            ->where('teacher_id', $conflict->teacher_id)
            ->where('lesson_number', $prevLessonNumber)
            ->where('status', '!=', 'cancelled')
            ->exists();

        $groupBusy = ScheduleLesson::where('version_id', $versionId)
            ->where('date', $conflict->date)
            ->where('group_id', $lessonToMove->group_id)
            ->where('lesson_number', $prevLessonNumber)
            ->where('status', '!=', 'cancelled')
            ->exists();

        $roomBusy = ScheduleLesson::where('version_id', $versionId)
            ->where('date', $conflict->date)
            ->where('room_id', $lessonToMove->room_id)
            ->where('lesson_number', $prevLessonNumber)
            ->where('status', '!=', 'cancelled')
            ->exists();

        if (!$teacherBusy && !$groupBusy && !$roomBusy) {
            $lessonToMove->update(['lesson_number' => $prevLessonNumber]);
            return [
                'fixed'  => true,
                'reason' => "Пара перемещена на {$prevLessonNumber}-й слот (устранено окно)",
            ];
        }
        $prevLessonNumber--;
    }

    return ['fixed' => false, 'reason' => 'нет свободного слота для устранения окна'];
}

/**
 * Аналогично для группы
 */
private function fixGroupWindow(ScheduleConflict $conflict, int $versionId): array
{
    // Та же логика что fixTeacherWindow но для группы
    if (!$conflict->group_id || !$conflict->lesson_number) {
        return ['fixed' => false, 'reason' => 'нет данных'];
    }

    $lessonToMove = ScheduleLesson::where('version_id', $versionId)
        ->where('date', $conflict->date)
        ->where('group_id', $conflict->group_id)
        ->where('lesson_number', $conflict->lesson_number)
        ->where('status', '!=', 'cancelled')
        ->first();

    if (!$lessonToMove) {
        return ['fixed' => false, 'reason' => 'урок не найден'];
    }

    $prevLessonNumber = $conflict->lesson_number - 1;
    $group = $lessonToMove->group;
    $allowedSlots = $group?->getAllowedLessonNumbers() ?: range(1, 7);

    while ($prevLessonNumber > 0 && in_array($prevLessonNumber, $allowedSlots)) {
        $groupBusy   = ScheduleLesson::where('version_id', $versionId)->where('date', $conflict->date)->where('group_id', $conflict->group_id)->where('lesson_number', $prevLessonNumber)->where('status', '!=', 'cancelled')->exists();
        $teacherBusy = ScheduleLesson::where('version_id', $versionId)->where('date', $conflict->date)->where('teacher_id', $lessonToMove->teacher_id)->where('lesson_number', $prevLessonNumber)->where('status', '!=', 'cancelled')->exists();
        $roomBusy    = ScheduleLesson::where('version_id', $versionId)->where('date', $conflict->date)->where('room_id', $lessonToMove->room_id)->where('lesson_number', $prevLessonNumber)->where('status', '!=', 'cancelled')->exists();

        if (!$groupBusy && !$teacherBusy && !$roomBusy) {
            $lessonToMove->update(['lesson_number' => $prevLessonNumber]);
            return ['fixed' => true, 'reason' => "Пара группы {$group?->name} перемещена на {$prevLessonNumber}-й слот"];
        }
        $prevLessonNumber--;
    }

    return ['fixed' => false, 'reason' => 'нет свободного слота'];
}

/**
 * Исправить несоответствие смены
 */
private function fixGroupShift(ScheduleConflict $conflict, int $versionId): array
{
    if (!$conflict->group_id || !$conflict->lesson_number) {
        return ['fixed' => false, 'reason' => 'нет данных'];
    }

    $lesson = ScheduleLesson::where('version_id', $versionId)
        ->where('date', $conflict->date)
        ->where('group_id', $conflict->group_id)
        ->where('lesson_number', $conflict->lesson_number)
        ->where('status', '!=', 'cancelled')
        ->first();

    if (!$lesson) return ['fixed' => false, 'reason' => 'урок не найден'];

    $group = $lesson->group;
    $allowedSlots = $group?->getAllowedLessonNumbers() ?: [];

    if (empty($allowedSlots)) return ['fixed' => false, 'reason' => 'не определены допустимые слоты'];

    // Найти первый свободный допустимый слот
    foreach ($allowedSlots as $slot) {
        if ($slot === $lesson->lesson_number) continue;
        $groupBusy   = ScheduleLesson::where('version_id', $versionId)->where('date', $conflict->date)->where('group_id', $conflict->group_id)->where('lesson_number', $slot)->where('status', '!=', 'cancelled')->exists();
        $teacherBusy = ScheduleLesson::where('version_id', $versionId)->where('date', $conflict->date)->where('teacher_id', $lesson->teacher_id)->where('lesson_number', $slot)->where('status', '!=', 'cancelled')->exists();
        $roomBusy    = ScheduleLesson::where('version_id', $versionId)->where('date', $conflict->date)->where('room_id', $lesson->room_id)->where('lesson_number', $slot)->where('status', '!=', 'cancelled')->exists();

        if (!$groupBusy && !$teacherBusy && !$roomBusy) {
            $lesson->update(['lesson_number' => $slot, 'shift' => $group->shift]);
            return ['fixed' => true, 'reason' => "Группа {$group?->name}: пара перемещена на {$slot}-й слот соответствующей смены"];
        }
    }

    return ['fixed' => false, 'reason' => 'нет свободного допустимого слота для этой смены'];
}
```

---

## ПРОБЛЕМА 4: Не работает кнопка «Подробнее» для конфликтов в Livewire

### Проблема

В `ScheduleGrid.php` метод `highlightConflict` есть, но в шаблоне `livewire/schedule/schedule-grid.blade.php` кнопка «Подробнее» либо не вызывает его, либо модальное окно не показывается.

### Обновить шаблон `resources/views/livewire/schedule/schedule-grid.blade.php`

**В блоке отображения конфликтов добавить:**

```blade
{{-- Список конфликтов --}}
@if($showConflictModal && !empty($conflicts))
<div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
    <div class="flex items-start justify-center min-h-screen pt-4 px-4 pb-20">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="$set('showConflictModal', false)"></div>
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl mt-8 p-0 overflow-hidden">
            {{-- Заголовок --}}
            <div class="flex items-center justify-between px-6 py-4 bg-red-50 border-b border-red-200">
                <h3 class="text-lg font-semibold text-red-800">
                    Конфликты расписания ({{ count($conflicts) }})
                </h3>
                <button wire:click="$set('showConflictModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Список конфликтов --}}
            <div class="divide-y divide-gray-100 max-h-[60vh] overflow-y-auto">
                @foreach($conflicts as $conflict)
                <div class="px-6 py-4 hover:bg-gray-50 transition-colors
                    {{ ($conflict['severity'] ?? 'warning') === 'error' ? 'border-l-4 border-red-400' : 'border-l-4 border-yellow-400' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            {{-- Тип + дата --}}
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    {{ ($conflict['severity'] ?? 'warning') === 'error' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ($conflict['severity'] ?? 'warning') === 'error' ? '⛔ Ошибка' : '⚠ Предупреждение' }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ $conflict['date'] ?? '' }}
                                    @if(!empty($conflict['lesson_number']))
                                        · {{ $conflict['lesson_number'] }}-я пара
                                    @endif
                                </span>
                                <span class="text-xs text-gray-400">
                                    [{{ str_replace('_', ' ', $conflict['conflict_type'] ?? '') }}]
                                </span>
                            </div>

                            {{-- Описание --}}
                            <p class="text-sm text-gray-800 font-medium">
                                {{ $conflict['description'] ?? '' }}
                            </p>

                            {{-- Рекомендация --}}
                            @if(!empty($conflict['suggestion']))
                            <p class="text-xs text-blue-600 mt-1">
                                💡 {{ $conflict['suggestion'] }}
                            </p>
                            @endif
                        </div>

                        {{-- Кнопки действий --}}
                        <div class="flex flex-col gap-1 shrink-0">
                            {{-- Кнопка "Подробнее" — выделить пары в сетке --}}
                            <button
                                wire:click="highlightConflict(
                                    '{{ $conflict['date'] ?? '' }}',
                                    {{ $conflict['teacher_id'] ?? 'null' }},
                                    {{ $conflict['group_id'] ?? 'null' }},
                                    {{ $conflict['room_id'] ?? 'null' }},
                                    {{ $conflict['lesson_number'] ?? 'null' }}
                                )"
                                class="px-3 py-1 text-xs font-medium text-white bg-blue-500 rounded hover:bg-blue-600 transition-colors whitespace-nowrap"
                                title="Выделить конфликтующие пары в сетке"
                            >
                                🔍 Показать
                            </button>

                            {{-- Кнопка "Решить вручную" --}}
                            @if($resolvingConflictId === ($conflict['id'] ?? null))
                            <div class="flex flex-col gap-1">
                                <input
                                    type="text"
                                    wire:model="resolutionNote"
                                    placeholder="Заметка об исправлении..."
                                    class="text-xs px-2 py-1 border border-gray-300 rounded w-40"
                                />
                                <button
                                    wire:click="resolveConflict"
                                    class="px-3 py-1 text-xs font-medium text-white bg-green-500 rounded hover:bg-green-600"
                                >
                                    ✓ Подтвердить
                                </button>
                                <button
                                    wire:click="$set('resolvingConflictId', null)"
                                    class="px-3 py-1 text-xs text-gray-500 hover:text-gray-700"
                                >
                                    Отмена
                                </button>
                            </div>
                            @else
                            <button
                                wire:click="startResolve({{ $conflict['id'] ?? 0 }})"
                                class="px-3 py-1 text-xs font-medium text-gray-600 border border-gray-300 rounded hover:bg-gray-50 transition-colors"
                            >
                                ✏️ Решить
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Нижняя панель --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <span class="text-sm text-gray-500">
                    Ошибок: {{ collect($conflicts)->where('severity', 'error')->count() }} ·
                    Предупреждений: {{ collect($conflicts)->where('severity', 'warning')->count() }}
                </span>
                <div class="flex gap-2">
                    <button
                        wire:click="autoFixConflicts"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600 disabled:opacity-50"
                    >
                        <span wire:loading wire:target="autoFixConflicts">⏳ Исправляю...</span>
                        <span wire:loading.remove wire:target="autoFixConflicts">🔧 Авто-исправить</span>
                    </button>
                    <button
                        wire:click="$set('showConflictModal', false)"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                    >
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
```

**Добавить выделение конфликтных ячеек в сетке расписания:**

В шаблоне каждой ячейки пары добавить динамический класс на основе `$highlightedLessonIds`:

```blade
{{-- Пример ячейки пары --}}
<div class="lesson-cell p-2 rounded-lg text-sm cursor-pointer border transition-all
    {{ in_array($lesson['id'], $highlightedLessonIds) ? 'ring-4 ring-red-500 ring-offset-1 bg-red-50 animate-pulse' : '' }}
    {{ isset($lessonConflictMap[$lesson['id']]) ? ($lessonConflictMap[$lesson['id']] === 'error' ? 'border-red-400' : 'border-yellow-400') : 'border-gray-200' }}"
    wire:click="editLesson({{ $lesson['id'] }})"
>
    ...
</div>
```

**Добавить Alpine.js обработчик для `clear-highlights`:**

```blade
<div x-data="{}" @clear-highlights.window="$wire.clearHighlights()">
```

---

## ДОПОЛНИТЕЛЬНО: Добавить в `ScheduleLesson` модель метод `trackHours` и `untrackLesson`

В `app/Models/ScheduleVersion.php` добавить:

```php
public function trackHours(): void
{
    // Пересчитывает hours_tracking при публикации версии
    $this->lessons()->where('status', '!=', 'cancelled')->each(function ($lesson) {
        \App\Models\HoursTracking::updateOrCreate(
            ['schedule_lesson_id' => $lesson->id],
            [
                'group_id'       => $lesson->group_id,
                'discipline_id'  => $lesson->discipline_id,
                'teacher_id'     => $lesson->teacher_id,
                'academic_year_id' => $this->academic_year_id,
                'lesson_type_id' => $lesson->lesson_type_id,
                'date'           => $lesson->date,
                'hours_conducted' => 2, // 1 пара = 2 часа
                'is_cancelled'   => false,
            ]
        );
    });
}

public function untrackLesson(\App\Models\ScheduleLesson $lesson): void
{
    \App\Models\HoursTracking::where('schedule_lesson_id', $lesson->id)->delete();
}
```

---

## ПОРЯДОК ВЫПОЛНЕНИЯ

1. `composer require phpoffice/phpspreadsheet` — установить зависимость
2. Создать `app/Services/Curriculum/ExcelCurriculumParserService.php` (код выше)
3. Обновить Livewire-компонент импорта для поддержки `.xlsx`
4. Добавить новые методы в `ConflictCheckerService`:
    - `checkBuildingConflicts()`
    - `checkWeeklyHoursLimit()`
    - Вызовы в `checkVersionForRange()`
5. Заменить `autoFix()` и добавить вспомогательные `fix*` методы
6. Обновить шаблон `schedule-grid.blade.php`:
    - Модальное окно конфликтов с кнопкой «Показать»
    - Подсветка ячеек через `$highlightedLessonIds`
7. Добавить `trackHours()` и `untrackLesson()` в `ScheduleVersion`
8. Проверить что в `ScheduleConflict` модели есть поле `suggestion` (добавить в миграцию если нет):
    ```php
    $table->text('suggestion')->nullable(); // рекомендация по исправлению
    ```
