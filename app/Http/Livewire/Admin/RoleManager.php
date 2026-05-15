<?php

declare(strict_types=1);

namespace App\Http\Livewire\Admin;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class RoleManager extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public bool $showForm = false;

    public ?int $editId = null;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    /** @var array<int> */
    public array $selectedPermissions = [];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'slug')->ignore($this->editId),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    protected $messages = [
        'name.required' => 'Название роли обязательно.',
        'slug.required' => 'Слаг обязателен.',
        'slug.unique' => 'Роль с таким слагом уже существует.',
    ];

    public function render(): View
    {
        return view('livewire.admin.role-manager', [
            'title' => 'Управление ролями',
        ]);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->editId = null;
    }

    public function edit(int $id): void
    {
        $role = Role::with('permissions')->findOrFail($id);

        $this->editId = $role->id;
        $this->name = $role->name;
        $this->slug = $role->slug;
        $this->description = $role->description ?? '';
        $this->selectedPermissions = $role->permissions->pluck('id')->toArray();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editId) {
            $role = Role::findOrFail($this->editId);
            $role->update([
                'name' => $this->name,
                'slug' => $this->slug,
                'description' => $this->description,
            ]);
            $role->permissions()->sync($this->selectedPermissions);
        } else {
            $role = Role::create([
                'name' => $this->name,
                'slug' => $this->slug,
                'description' => $this->description,
            ]);
            $role->permissions()->sync($this->selectedPermissions);
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $id): void
    {
        $role = Role::findOrFail($id);

        if ($role->users()->exists()) {
            session()->flash('error', 'Нельзя удалить роль, к которой привязаны пользователи.');

            return;
        }

        $role->permissions()->detach();
        $role->delete();
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->editId = null;
        $this->name = '';
        $this->slug = '';
        $this->description = '';
        $this->selectedPermissions = [];
    }

    #[Computed]
    public function permissions(): Collection
    {
        return Permission::query()->orderBy('module')->orderBy('name')->get();
    }

    #[Computed]
    public function groupedPermissions(): array
    {
        $groups = [];

        foreach ($this->permissions as $permission) {
            $module = $permission->module;
            if (! isset($groups[$module])) {
                $groups[$module] = [
                    'label' => match ($module) {
                        'admin' => 'Администрирование',
                        'curriculum' => 'Учебные планы',
                        'groups' => 'Группы',
                        'reports' => 'Отчёты',
                        'rooms' => 'Аудитории',
                        'schedule' => 'Расписание',
                        'teachers' => 'Преподаватели',
                        default => $module,
                    },
                    'permissions' => [],
                ];
            }
            $groups[$module]['permissions'][] = $permission;
        }

        return $groups;
    }

    #[Computed]
    public function roles(): LengthAwarePaginator
    {
        return Role::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('slug', 'like', "%{$this->search}%"))
            ->withCount('users')
            ->orderBy('name')
            ->paginate(10);
    }
}
