<div x-data="{
    toasts: [],
    init() {
        Livewire.on('toast', (event) => {
            const id = Date.now();
            this.toasts.push({ id, message: event.message || event[0]?.message || '', type: event.type || event[0]?.type || 'info' });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 5000);
        });
        // Convert flash messages to toasts
        if (document.querySelector('[data-flash]')) {
            document.querySelectorAll('[data-flash]').forEach(el => {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, message: el.dataset.flash, type: el.dataset.flashType || 'info' });
                setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 5000);
            });
        }
    },
    remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); }
}" class="fixed bottom-4 right-4 z-[100] flex flex-col gap-2 max-w-sm">
    <template x-for="t in toasts" :key="t.id">
        <div class="flex items-start gap-2 px-4 py-3 rounded-lg shadow-lg text-sm transition-all duration-300"
             :class="{
                 'bg-emerald-600 text-white': t.type === 'success' || t.type === 'message',
                 'bg-red-600 text-white': t.type === 'error',
                 'bg-amber-500 text-white': t.type === 'warning' || t.type === 'info',
                 'bg-blue-600 text-white': t.type === 'info'
             }"
             x-transition:enter="transform ease-out duration-200"
             x-transition:enter-start="translate-x-full opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             x-transition:leave="transform ease-in duration-200"
             x-transition:leave-start="translate-x-0 opacity-100"
             x-transition:leave-end="translate-x-full opacity-0">
            <span class="flex-1" x-text="t.message"></span>
            <button @click="remove(t.id)" class="shrink-0 opacity-70 hover:opacity-100">✕</button>
        </div>
    </template>
</div>
