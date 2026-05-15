<div class="w-full max-w-md">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">Колледж</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Расписание занятий</p>
    </div>

    <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl rounded-2xl shadow-xl border border-gray-200/50 dark:border-gray-700/50 p-8">
        <h2 class="text-xl font-semibold mb-6">Вход в систему</h2>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form wire:submit="login" class="space-y-5">
            <div>
                <label for="email" class="block text-sm font-medium mb-1">Email</label>
                <input
                    wire:model="email"
                    id="email"
                    type="email"
                    autocomplete="email"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                    placeholder="admin@college.ru"
                >
                @error('email') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium mb-1">Пароль</label>
                <input
                    wire:model="password"
                    id="password"
                    type="password"
                    autocomplete="current-password"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                    placeholder="••••••••"
                >
            </div>

            <button type="submit" class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition cursor-pointer">
                Войти
            </button>
        </form>
    </div>
</div>
