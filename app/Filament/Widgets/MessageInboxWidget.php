<?php

namespace App\Filament\Widgets;

use App\Models\Message;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Filament\Support\Markdown;

class MessageInboxWidget extends Widget
{
    protected static string $view = 'filament.widgets.message-inbox-widget';
    protected int | string | array $columnSpan = 'full';

    public Collection $messages;

    public function mount(): void
    {
        $user = auth()->user();
        if ($user) {
            // Use the scope defined in the Message Model
            $this->messages = Message::query()
                ->forUser($user)
                ->orderBy('created_at', 'desc')
                ->limit(5) // Show only the 5 most recent
                ->get();
        } else {
            $this->messages = new Collection();
        }
    }
}
