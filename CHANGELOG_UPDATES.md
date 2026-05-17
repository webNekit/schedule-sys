# Changelog Updates

## 2026-05-17

### Task 1: Умный парсинг "непланируемых" дисциплин
**Файлы:**
- `app/Services/Curriculum/ExcelCurriculumParserService.php`

**Изменения:**
- Добавлен метод `isSchedulable()`, который жёстко устанавливает `is_schedulable = false` для:
  - Кодов, начинающихся с: УП, ПП, ПДП, ГИА, ПМ. (с точкой)
  - Названий, содержащих: "экзамен", "практика", "консультация", "государственн", "квалификационн"
  - Кодов, строго равных заголовкам циклов: ОП, ОГСЭ, ЕН, ОД, ФК, ФЦД
  - Дисциплин с нулевым количеством контактных часов (лекции + практики + лабы = 0)
- При импорте в `importToDatabase()` теперь передаётся `is_schedulable` при создании/обновлении дисциплины

### Task 2: Строгий генератор расписания без "окон"
**Файлы:**
- `app/Services/Schedule/ScheduleGeneratorService.php`

**Изменения:**
- Исправлен алгоритм `findBestStrictWindow()` — теперь при неудачной попытке заполнить окна текущей длины алгоритм автоматически пробует окна меньшей длины (от `$targetPairs` до 1)
- Устранена ошибка, когда при неудаче с окнами максимальной длины возвращался пустой результат вместо попытки найти окно меньшей длины
- Исправлен баг с мутацией `$building` при использовании fallback-комнат/преподавателей — теперь используется локальная переменная `$localBuilding`

### Task 3: Компактное отображение МДК в UI
**Файлы:**
- `resources/views/livewire/schedule/schedule-grid.blade.php`
- `resources/views/livewire/schedule/public-view.blade.php`
- `resources/views/livewire/schedule/day-share.blade.php`

**Изменения:**
- Проверено: логика компактного отображения МДК уже реализована во всех трёх представлениях
- Если код дисциплины начинается на "МДК" — выводится только код (например, "МДК.01.02") с полным названием в `title` тултипе
- Если код не МДК — выводится название дисциплины как обычно

### Task 4: Отображение кода практик в расписании (УП, ПП, ПДП, ГИА)
**Файлы:**
- `resources/views/livewire/schedule/schedule-grid.blade.php`
- `resources/views/livewire/schedule/public-view.blade.php`
- `resources/views/livewire/schedule/day-share.blade.php`

**Изменения:**
- Во всех трёх представлениях расписания расширена логика отображения: теперь не только МДК, но и дисциплины с кодами УП, ПП, ПДП, ГИА отображаются компактно (показывается код, полное название в `title`)

### Task 5: Цветовая маркировка занятий в расписании
**Файлы:**
- `resources/views/livewire/schedule/schedule-grid.blade.php`
- `resources/views/livewire/schedule/public-view.blade.php`
- `resources/views/livewire/schedule/day-share.blade.php`

**Изменения:**
- Добавлена цветовая маркировка по типу дисциплины (левая граница + фон):
  - Обычные пары — нейтральный серый
  - Учебная практика (УП) — индиго/синий
  - Производственная практика (ПП) — розовый
  - Преддипломная практика (ПДП) — янтарный
  - ГИА (ГИА) — красный
  - Экзамены (код на Э или название содержит "экзамен") — фиолетовый
  - МДК — бирюзовый

### Task 6: График экзаменационных сессий в учебном плане
**Файлы:**
- `resources/views/livewire/curriculum/show-plan.blade.php`

**Изменения:**
- Добавлен блок "Экзаменационные сессии" после календаря практик
- Данные выводятся на основе семестров с `exam_hours > 0`
- Блоки сгруппированы по курсам и семестрам
- Цветовая индикация: фиолетовый (экзамен), розовый (диф. зачёт), жёлтый (зачёт)
- Для каждого семестра перечислены дисциплины с формами контроля
- Исправлена маркировка ГИА в календаре практик: ГП (подготовка) и ДП (сдача) теперь отображаются с правильными подписями

### Task 8: Исправление is_schedulable для дисциплин практик (УП, ПП, ПДП, ГИА)
**Файлы:**
- `app/Services/Curriculum/ExcelCurriculumParserService.php`
- `resources/views/livewire/schedule/schedule-grid.blade.php`
- `resources/views/livewire/schedule/public-view.blade.php`
- `resources/views/livewire/schedule/day-share.blade.php`

**Изменения:**
- Исправлен баг: дисциплины с кодами УП.*, ПП.*, ПДП.*, ГИА.* имели `is_schedulable = 1`, из-за чего генератор ставил их как обычные пары
- Выполнен SQL UPDATE для 48 существующих записей — `is_schedulable = 0` для всех практик
- Удалено 7 автосгенерированных занятий практик из существующих версий расписания
- Исправлен CSS-баг в `schedule-grid.blade.php`: класс `$conflictClasses` (default: `bg-gray-50`) переопределял `$lessonTypeColor`, т.к. шёл позже в атрибуте class
  - Решение: `$lessonTypeColor` теперь включает `bg-gray-50` для обычных пар; `$conflictClasses` default больше не ставит bg/border
  - Для ошибок/предупреждений используются `!important`-классы для гарантии переопределения
- В `day-share.blade.php` добавлен `default => 'border-l-4 border-l-transparent bg-gray-50'` для фона обычных пар
- В `public-view.blade.php` и `day-share.blade.php` удалены дублирующиеся определения `$discCode/$discName/$isMdk/$isPractice` (использовались префиксные варианты)

### Task 9: Цветовая маркировка (Tailwind v4 safelist), генератор 36 часов, отображение корпуса
**Файлы:**
- `resources/views/livewire/schedule/schedule-grid.blade.php`
- `resources/views/livewire/schedule/public-view.blade.php`
- `resources/views/livewire/schedule/day-share.blade.php`
- `app/Services/Schedule/ScheduleGeneratorService.php`

**Изменения:**

**Цветовая маркировка (Tailwind v4):**
- Добавлены `<span class="hidden ...">` со всеми динамическими CSS-классами для Tailwind v4 safelist (v4 сканирует исходники статически и не видит классы в PHP-переменных)
- Исправлено переопределение: `$lessonTypeColor` теперь сам даёт `bg-gray-50` для обычных пар; `$conflictClasses` default — только `hover:*`

**Генератор 36 часов в неделю (18 пар):**
- `findBestStrictWindow()` теперь принимает `$usedDisciplines` и `$usedTeachers` по ссылке, чтобы можно было вызывать её многократно за один день
- В `generateForWeek()` добавлен цикл: после заполнения одного окна, оставшиеся слоты и целевое количество пар передаются в следующий вызов, пока не наберётся нужное количество пар (или не кончатся слоты)
- Исправлен баг с `min($targetPairs, $n)` в верхней границе цикла, чтобы избежать пустых итераций

**Фильтр дисциплин по рабочим дням преподавателя:**
- В `pickDisciplineForGroup()` добавлен параметр `$dayOfWeek` и фильтр `whereHas('teachers', ...)` — отбираются только дисциплины, у которых есть преподаватели, работающие в этот день недели
- `findBestStrictWindow()` передаёт `(int) $date->format('N')` при вызове

**Отображение корпуса:**
- Исправлен доступ к зданию: в `loadWeek()` связь `room.building` загружается, но после `toArray()` корпус оказывается в `$lesson['room']['building']`, а не в `$lesson['building']`
- Исправлено во всех трёх blade-файлах

### Task 7: Удаление учебных планов из списка
**Файлы:**
- `app/Http/Livewire/Curriculum/Index.php`
- `resources/views/livewire/curriculum/index.blade.php`

**Изменения:**
- В `Index.php` добавлен метод `deletePlan(int $id)` — находит план, проверяет отсутствие привязок к группам, выполняет soft-delete
- В `index.blade.php` для каждого плана добавлена красная кнопка удаления (иконка корзины, появляется при наведении) с `wire:click.stop` и `wire:confirm`
- Добавлены блоки отображения flash-сообщений (`session('message')` и `session('error')`) в шапку списка учебных планов
