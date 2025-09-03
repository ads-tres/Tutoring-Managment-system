<?php


namespace App\Filament\Resources\StudentResource\RelationManagers;

use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    protected static ?string $recordTitleAttribute = 'subject';

    // NOTE: In Filament v3, RelationManager::form() is an instance method (not static).
    // Docs: “Managing relationships” & v3 upgrade notes. :contentReference[oaicite:0]{index=0}
    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Select::make('type')
                ->label('Type')
                ->options([
                    'on-schedule' => 'On-schedule',
                    'additional'  => 'Additional',
                    'rescheduled' => 'Rescheduled',
                ])
                ->required()
                ->live() // trigger reactivity
                // When type switches to on-schedule, compute the next date from the student's weekly plan
                ->afterStateUpdated(function (Set $set, Get $get) {
                    if ($get('type') === 'on-schedule') {
                        $owner = $this->getOwnerRecord(); // the current Student
                        $next  = $this->nextOccurrenceDate($owner->scheduled_days ?? []);
                        if ($next) {
                            $set('scheduled_date', $next->toDateString());
                        }
                    }
                }),

            DatePicker::make('scheduled_date')
                ->label('Scheduled date')
                ->native(false) 
                ->required()
                // For on-schedule, we fill it automatically & lock it
                ->disabled(fn (Get $get) => $get('type') === 'on-schedule'),

            DatePicker::make('actual_date')
                ->label('New date (if rescheduled)')
                ->native(false)
                ->visible(fn (Get $get) => $get('type') === 'rescheduled'),

            Textarea::make('reason')
                ->label('Reason (for reschedule)')
                ->rows(2)
                ->maxLength(500)
                ->visible(fn (Get $get) => $get('type') === 'rescheduled'),

            TextInput::make('subject')->maxLength(100),
            TextInput::make('topic')->maxLength(150),

            TextInput::make('duration')
                ->label('Duration (hours)')
                ->numeric()
                ->minValue(1)
                ->maxValue(8)
                ->required(),

            Select::make('status')
                ->options([
                    'pending'  => 'Pending',
                    'approved' => 'Approved',
                ])
                ->default('pending')
                ->required(),

            Select::make('payment_status')
                ->options([
                    'unpaid' => 'Unpaid',
                    'paid'   => 'Paid',
                ])
                ->default('unpaid')
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_date')->date(),
                Tables\Columns\TextColumn::make('actual_date')->date()->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'on-schedule' => 'primary',
                        'additional' => 'warning',
                        'rescheduled' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('subject')->limit(20)->wrap(),
                Tables\Columns\TextColumn::make('duration')->label('Minutes'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Paid?')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'unpaid' => 'danger',
                        'paid' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since()->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Log attendance'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('approve')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(fn ($record) => $record->update(['status' => 'approved']))
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check-circle'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Compute the next date (>= today) matching any of the student's scheduled weekdays.
     * @param array<string> $days e.g. ["monday","wednesday"]
     */
    private function nextOccurrenceDate(array $days): ?Carbon
    {
        if (empty($days)) {
            return null;
        }

        $normalized = array_map(fn ($d) => strtolower(trim($d)), $days);
        $map = [
            'monday'    => 1,
            'tuesday'   => 2,
            'wednesday' => 3,
            'thursday'  => 4,
            'friday'    => 5,
            'saturday'  => 6,
            'sunday'    => 7,
        ];

        $targets = array_values(array_unique(array_intersect_key($map, array_flip($normalized))));
        if (empty($targets)) {
            return null;
        }

        $today = Carbon::today();
        $todayDow = (int) $today->isoWeekday(); // 1 (Mon) .. 7 (Sun)

        // Find the smallest forward delta
        $bestDelta = null;
        foreach ($targets as $dow) {
            $delta = ($dow - $todayDow + 7) % 7;
            $delta = $delta === 0 ? 0 : $delta; // today if matches
            $bestDelta = is_null($bestDelta) ? $delta : min($bestDelta, $delta);
        }

        return $today->copy()->addDays($bestDelta ?? 0);
    }
}
