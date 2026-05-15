<?php

declare(strict_types=1);

namespace App\Http\Livewire;

use App\Models\Notification;
use Livewire\Attributes\Computed;
use Livewire\Component;

class NotificationsWidget extends Component
{
    // Отметить конкретное уведомление прочитанным
    public function markAsRead(int $id): void
    {
        Notification::where('id', $id)
            ->where('user_id', auth()->id())
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    // Отметить все уведомления как прочитанные
    public function markAllAsRead(): void
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    // Подсчет количества непрочитанных
    #[Computed]
    public function unreadCount(): int
    {
        return Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();
    }

    // Получаем последние 10 уведомлений
    #[Computed]
    public function notifications()
    {
        return Notification::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.notifications-widget');
    }
}
