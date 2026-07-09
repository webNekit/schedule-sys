<div class="w-full max-w-md relative">
    {{-- Логотип / шапка --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-600 to-emerald-400 shadow-lg shadow-emerald-900/40 mb-4">
            <span class="text-zinc-950 text-2xl">📅</span>
        </div>
        <div class="flex items-center justify-center gap-2">
            <h1 class="text-2xl font-bold tracking-tight text-white">Educational System</h1>
            <span class="text-[10px] bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded-full font-mono font-bold">Pro</span>
        </div>
        <p class="text-[13px] text-zinc-500 mt-1.5">Интеллектуальный планировщик колледжа</p>
    </div>

    {{-- Карточка входа --}}
    <div class="bg-zinc-900/60 backdrop-blur-xl rounded-2xl shadow-2xl border border-zinc-800 p-8">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-white">Вход в систему</h2>
            <p class="text-xs text-zinc-500 mt-0.5 font-mono">AUTHORIZATION REQUIRED</p>
        </div>

        @if ($errors->any())
            <div class="mb-5 px-4 py-3 bg-red-500/10 border border-red-500/30 rounded-lg text-sm text-red-400">
                @foreach ($errors->all() as $error)
                    <p class="flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $error }}
                    </p>
                @endforeach
            </div>
        @endif

        <form wire:submit="login" class="space-y-5">
            <div>
                <label for="email" class="block text-xs font-semibold text-zinc-400 mb-1.5 uppercase tracking-wide">Email</label>
                <input
                    wire:model="email"
                    id="email"
                    type="email"
                    autocomplete="email"
                    class="w-full px-4 py-2.5 rounded-lg border border-zinc-700 bg-zinc-950/80 text-zinc-100 placeholder-zinc-600 focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 outline-none transition"
                    placeholder="admin@college.ru"
                >
                @error('email') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-zinc-400 mb-1.5 uppercase tracking-wide">Пароль</label>
                <input
                    wire:model="password"
                    id="password"
                    type="password"
                    autocomplete="current-password"
                    class="w-full px-4 py-2.5 rounded-lg border border-zinc-700 bg-zinc-950/80 text-zinc-100 placeholder-zinc-600 focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 outline-none transition"
                    placeholder="••••••••"
                >
                @error('password') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full py-2.5 px-4 bg-gradient-to-tr from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-zinc-950 font-semibold rounded-lg transition shadow-lg shadow-emerald-900/30 disabled:opacity-60 cursor-pointer flex items-center justify-center gap-2"
            >
                <span wire:loading.remove wire:target="login">Войти</span>
                <span wire:loading wire:target="login">Вход…</span>
            </button>
        </form>
    </div>
</div>
