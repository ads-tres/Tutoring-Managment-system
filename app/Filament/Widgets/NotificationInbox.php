<?php

// namespace App\Filament\Widgets;

// use Filament\Widgets\TableWidget;
// use Filament\Tables\Table;
// use Filament\Tables\Columns\TextColumn;
// use Filament\Tables\Actions\Action;
// use Filament\Tables\Actions\BulkAction;
// use Illuminate\Support\Facades\Auth;
// use App\Models\Student;
// use App\Notifications\ApproachingMaxSessions;

// class NotificationInbox extends TableWidget
// {
//     protected static ?string $heading = 'Inbox';
//     protected static ?int $sort = 1;

//     protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
//     {
//         // This query fetches the current user's notifications.
//         // It orders them by the most recent first.
//         return Auth::user()->notifications()->latest();
//     }

//     protected function getTableColumns(): array
//     {
//         return [
//             TextColumn::make('data.student_id')
//                 ->label('Student')
//                 ->getStateUsing(fn ($record) => optional(Student::find($record->data['student_id']))->full_name ?? '—'),
//             TextColumn::make('data.completed')
//                 ->label('Completed'),
//             TextColumn::make('data.max')
//                 ->label('Max'),
//             TextColumn::make('created_at')
//                 ->dateTime()
//                 ->label('Received At'),
//         ];
//     }

//     protected function getTableActions(): array
//     {
//         return [
//             Action::make('markRead')
//                 ->label('Mark as Read')
//                 ->action(fn ($record) => $record->markAsRead())
//                 ->requiresConfirmation()
//                 ->icon('heroicon-o-check'),
//         ];
//     }

//     protected function getTableBulkActions(): array
//     {
//         return [
//             BulkAction::make('markAllRead')
//                 ->label('Mark All Read')
//                 ->action(fn ($records) => $records->each->markAsRead())
//                 ->requiresConfirmation()
//                 ->icon('heroicon-o-check'),
//         ];
//     }
// }
