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

    public function mount(): void
    {
        $year = AcademicYear::where('is_current', true)->first();
        $this->currentAcademicYearId = $year?->id;
        $this->loadSettings();
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

            return [$setting->key => [
                'id' => $setting->id,
                'value' => $value,
                'type' => $setting->type,
                'group' => $setting->group,
                'label' => $setting->label,
                'description' => $setting->description,
            ]];
        })->toArray();
    }

    public function save(): void
    {
        foreach ($this->settings as $key => $data) {
            $setting = SystemSetting::where('key', $key)->first();
            if (! $setting) {
                continue;
            }

            $value = $data['value'];

            if ($data['type'] === 'json' && is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif ($data['type'] === 'boolean') {
                $value = $value ? 'true' : 'false';
            } elseif ($data['type'] === 'integer') {
                $value = (string) (int) $value;
            }

            $setting->update(['value' => (string) $value]);
        }

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
    }

    #[Computed]
    public function academicYears(): Collection
    {
        return AcademicYear::orderBy('year_start', 'desc')->get();
    }

    public function render(): mixed
    {
        $groups = collect($this->settings)->groupBy('group');
        $groups->forget('generation');

        return view('livewire.admin.system-settings', [
            'groups' => $groups,
            'academicYears' => $this->academicYears,
            'groupsCount' => Group::where('is_active', true)->count(),
        ]);
    }
}
