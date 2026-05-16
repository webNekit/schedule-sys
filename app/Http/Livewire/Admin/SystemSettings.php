<?php

declare(strict_types=1);

namespace App\Http\Livewire\Admin;

use App\Models\AcademicYear;
use App\Models\Group;
use App\Models\SystemSetting;
use App\Services\GroupPromotionService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SystemSettings extends Component
{
    public array $settings = [];

    public ?int $currentAcademicYearId = null;

    public string $promotionResult = '';

    protected function queryString(): array
    {
        return [];
    }

    public bool $showYearForm = false;

    public string $newYearName = '';

    public int $newYearStart = 0;

    public int $newYearEnd = 0;

    public string $newYearDateStart = '';

    public string $newYearDateEnd = '';

    public function mount(): void
    {
        $year = AcademicYear::where('is_current', true)->first();
        $this->currentAcademicYearId = $year?->id;
        $this->loadSettings();
    }

    public function openYearForm(): void
    {
        $lastYear = AcademicYear::orderBy('year_start', 'desc')->first();
        $nextStart = $lastYear ? $lastYear->year_start + 1 : (int) date('Y');
        $this->newYearStart = $nextStart;
        $nextEnd = $nextStart + 1;
        $this->newYearEnd = $nextEnd;
        $this->newYearName = $nextStart.'/'.$nextEnd;
        $this->newYearDateStart = $nextStart.'-09-01';
        $this->newYearDateEnd = $nextEnd.'-08-31';
        $this->showYearForm = true;
    }

    public function saveAcademicYear(): void
    {
        $this->validate([
            'newYearName' => 'required|string|max:50',
            'newYearStart' => 'required|integer|min:2000|max:2100',
            'newYearEnd' => 'required|integer|min:2000|max:2100',
            'newYearDateStart' => 'required|date',
            'newYearDateEnd' => 'required|date',
        ]);

        $year = AcademicYear::create([
            'name' => $this->newYearName,
            'year_start' => $this->newYearStart,
            'year_end' => $this->newYearEnd,
            'date_start' => $this->newYearDateStart,
            'date_end' => $this->newYearDateEnd,
            'first_semester_start' => $this->newYearDateStart,
            'first_semester_end' => $this->newYearDateEnd,
            'second_semester_start' => $this->newYearDateStart,
            'second_semester_end' => $this->newYearDateEnd,
            'is_current' => false,
        ]);

        $this->currentAcademicYearId = $year->id;
        $this->showYearForm = false;
        $this->newYearName = '';

        session()->flash('message', 'Учебный год создан');
    }

    public function loadSettings(): void
    {
        $records = SystemSetting::where('is_editable', true)->orderBy('group')->orderBy('key')->get();

        $this->settings = $records->mapWithKeys(function ($setting) {
            $value = $setting->value;
            if ($setting->type === 'json') {
                $value = json_decode($setting->value, true);
            } elseif ($setting->type === 'integer') {
                $value = (int) $setting->value;
            } elseif ($setting->type === 'boolean') {
                $value = $value === 'true' || $value === '1' || $value === true;
            }

            return [
                $setting->key => [
                    'id' => $setting->id,
                    'value' => $value,
                    'type' => $setting->type,
                    'group' => $setting->group,
                    'label' => $setting->label,
                    'description' => $setting->description,
                ],
            ];
        })->toArray();
    }

    public function save(): void
    {
        foreach ($this->settings as $key => $data) {
            $value = $data['value'];
            $type = $data['type'] ?? 'json';

            if ($type === 'json' && is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif ($type === 'boolean') {
                $value = $value ? 'true' : 'false';
            } elseif ($type === 'integer') {
                $value = (string) (int) $value;
            }
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => (string) $value, 'type' => $type],
            );
        }

        $this->loadSettings();

        session()->flash('message', 'Настройки сохранены');
    }

    public function setCurrentAcademicYear(int $yearId): void
    {
        AcademicYear::where('is_current', true)->update(['is_current' => false]);
        AcademicYear::where('id', $yearId)->update(['is_current' => true]);

        $this->currentAcademicYearId = $yearId;

        session()->flash('message', 'Учебный год изменён');
    }

    public function promoteGroups(): void
    {
        $service = app(GroupPromotionService::class);
        $result = $service->promoteAllGroups();

        $this->promotionResult = "Переведено: {$result['promoted']} групп, выпущено: {$result['graduated']}";

        session()->flash('message', $this->promotionResult);
    }

    private function persistSetting(string $key): void
    {
        $data = $this->settings[$key] ?? null;
        if (! $data) {
            return;
        }

        $value = $data['value'];
        $type = $data['type'] ?? 'json';

        if ($type === 'json' && is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        } elseif ($type === 'boolean') {
            $value = $value ? 'true' : 'false';
        } elseif ($type === 'integer') {
            $value = (string) (int) $value;
        }

        SystemSetting::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value, 'type' => $type],
        );
    }

    public function toggleLessonNumber(string $key, int $num, bool $checked): void
    {
        $current = $this->settings[$key]['value'] ?? [];
        if (! is_array($current)) {
            $current = [];
        }

        if ($checked) {
            $current[] = $num;
            $current = array_unique($current);
        } else {
            $current = array_values(array_filter($current, fn ($v) => (int) $v !== $num));
        }

        sort($current);
        $this->settings[$key]['value'] = $current;
        $this->persistSetting($key);
    }

    public function toggleLessonNumberForDay(string $key, int $day, int $num, bool $checked): void
    {
        $current = $this->settings[$key]['value'] ?? [];
        if (! is_array($current)) {
            $current = [];
        }

        $daySlots = $current[$day] ?? [];

        if ($checked) {
            $daySlots[] = $num;
            $daySlots = array_unique($daySlots);
        } else {
            $daySlots = array_values(array_filter($daySlots, fn ($v) => (int) $v !== $num));
        }

        sort($daySlots);
        $current[$day] = $daySlots;
        $this->settings[$key]['value'] = $current;
        $this->persistSetting($key);
    }

    public function toggleWorkingDay(string $key, int $day, bool $checked): void
    {
        $current = $this->settings[$key]['value'] ?? [];
        if (! is_array($current)) {
            $current = [];
        }

        if ($checked) {
            $current[] = $day;
            $current = array_unique($current);
        } else {
            $current = array_values(array_filter($current, fn ($v) => (int) $v !== $day));
        }

        sort($current);
        $this->settings[$key]['value'] = $current;
        $this->persistSetting($key);
    }

    #[Computed]
    public function academicYears(): Collection
    {
        return AcademicYear::orderBy('year_start', 'desc')->get();
    }

    public function render(): mixed
    {
        $groups = collect($this->settings)
            ->groupBy('group')
            ->forget(['generation', 'schedule'])
            ->filter(fn ($items, $group) => $group !== null && $group !== '');

        return view('livewire.admin.system-settings', [
            'groups' => $groups,
            'academicYears' => $this->academicYears,
            'groupsCount' => Group::where('is_active', true)->count(),
        ]);
    }
}
