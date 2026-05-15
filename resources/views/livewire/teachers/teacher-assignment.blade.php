<div class="space-y-6">
    <h2 class="text-2xl font-bold">Назначение преподавателей</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Кафедра</label>
            <select wire:model.live="departmentId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                <option value="">Все кафедры</option>
                @foreach (\App\Models\Department::active()->orderBy('name')->get() as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Учебный план</label>
            <select wire:model.live="curriculumPlanId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                <option value="">Выберите план</option>
                @foreach ($this->plans as $plan)
                    <option value="{{ $plan->id }}">{{ $plan->name }} ({{ $plan->specialty?->name }})</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($curriculumPlanId && $assignments !== [])
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Назначение преподавателей на дисциплины</h3>
                <button wire:click="saveAssignments" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition">Сохранить</button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900/50">
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Дисциплина</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Преподаватель</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($this->disciplines as $discipline)
                            <tr wire:key="discipline-{{ $discipline->id }}">
                                <td class="px-4 py-3 font-medium">{{ $discipline->name }}</td>
                                <td class="px-4 py-3">
                                    <select wire:model="assignments.{{ $discipline->id }}" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                                        <option value="">Не назначен</option>
                                        @foreach ($this->teachers as $teacher)
                                            <option value="{{ $teacher->id }}">{{ $teacher->last_name }} {{ $teacher->first_name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
