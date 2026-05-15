<?php

declare(strict_types=1);

namespace App\Http\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Login extends Component
{
    #[Rule(['required', 'email'])]
    public string $email = '';

    #[Rule(['required'])]
    public string $password = '';

    public function login(): void
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], false)) {
            if (! auth()->user()->is_active) {
                auth()->logout();
                $this->addError('email', 'Ваша учётная запись деактивирована.');

                return;
            }

            request()->session()->regenerate();

            auth()->user()->update(['last_login_at' => now()]);

            $this->redirectIntended(route('dashboard'), navigate: true);
        } else {
            $this->addError('email', 'Неверный email или пароль.');
        }
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
