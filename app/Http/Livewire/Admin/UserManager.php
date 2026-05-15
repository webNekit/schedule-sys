<?php

declare(strict_types=1);

namespace App\Http\Livewire\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class UserManager extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public bool $showForm = false;

    public ?int $editId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    /** @var array<int> */
    public array $selectedRoles = [];

    public bool $isActive = true;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editId),
            ],
            'password' => ['required_without:editId', 'nullable', 'string', 'min:8', 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required_with:password', 'nullable', 'string'],
            'selectedRoles' => ['array'],
            'selectedRoles.*' => ['integer', 'exists:roles,id'],
            'isActive' => ['boolean'],
        ];
    }

    protected $messages = [
        'name.required' => 'Имя обязательно.',
        'email.required' => 'Email обязателен.',
        'email.unique' => 'Пользователь с таким email уже существует.',
        'password.required_without' => 'Пароль обязателен.',
        'password.min' => 'Пароль должен содержать минимум 8 символов.',
        'password.same' => 'Пароли не совпадают.',
    ];

    public function render(): View
    {
        return view('livewire.admin.user-manager', [
            'title' => 'Управление пользователями',
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
        $user = User::with('roles')->findOrFail($id);

        $this->editId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->passwordConfirmation = '';
        $this->selectedRoles = $user->roles->pluck('id')->toArray();
        $this->isActive = $user->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->isActive,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editId) {
            $user = User::findOrFail($this->editId);
            $user->update($data);
            $user->roles()->sync($this->selectedRoles);
        } else {
            $data['password'] ??= Hash::make('password');
            $user = User::create($data);
            $user->roles()->sync($this->selectedRoles);
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $id): void
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'Вы не можете удалить самого себя.');

            return;
        }

        $user->roles()->detach();
        $user->delete();
    }

    public function toggleActive(int $id): void
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'Вы не можете деактивировать самого себя.');

            return;
        }

        $user->update(['is_active' => ! $user->is_active]);
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
        $this->email = '';
        $this->password = '';
        $this->passwordConfirmation = '';
        $this->selectedRoles = [];
        $this->isActive = true;
    }

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->with('roles')
            ->orderBy('name')
            ->paginate(10);
    }

    #[Computed]
    public function roles(): Collection
    {
        return Role::query()->orderBy('name')->get();
    }
}
