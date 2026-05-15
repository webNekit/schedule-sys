<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <h3 class="text-lg font-semibold mb-4">Быстрые действия</h3>
    <div class="space-y-3">
        <button wire:click="promoteGroups" wire:confirm="Перевести группы на следующий курс?" class="w-full text-left px-4 py-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-colors">
            <span class="font-medium">Перевести группы</span>
            <p class="text-sm text-emerald-600 dark:text-emerald-400 mt-1">Перевод на следующий курс / выпуск</p>
        </button>
        <button wire:click="importHolidays" wire:confirm="Импортировать праздники на текущий год?" class="w-full text-left px-4 py-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors">
            <span class="font-medium">Импорт праздников</span>
            <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">Загрузить праздники РФ на текущий год</p>
        </button>
    </div>
</div>
