<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AttendanceResource\RelationManagers\AttendancesRelationManager;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Select::make('student_id')
                ->relationship('student', 'full_name')
                ->required(),
            Forms\Components\Select::make('tutor_id')
                ->relationship('tutor', 'name')
                ->required(),
            Forms\Components\Select::make('type')
                ->options([
                    'on-schedule' => 'On Schedule',
                    'additional' => 'Additional',
                    'rescheduled' => 'Rescheduled',
                ])
                ->required(),
            Forms\Components\DatePicker::make('scheduled_date')->required(),
            Forms\Components\DatePicker::make('actual_date')
                ->visible(fn ($get) => $get('type') === 'rescheduled'),
            Forms\Components\TextInput::make('reason')
                ->visible(fn ($get) => $get('type') === 'rescheduled'),
            Forms\Components\TextInput::make('subject')->required(),
            Forms\Components\TextInput::make('topic')->required(),
            Forms\Components\TextInput::make('duration')->numeric()->required(),
            Forms\Components\Textarea::make('comment1'),
            Forms\Components\Textarea::make('comment2'),
            Forms\Components\Toggle::make('status')
                ->label('Approved')
                ->onIcon('heroicon-s-check')
                ->offIcon('heroicon-s-clock'),
            Forms\Components\Toggle::make('payment_status')
                ->label('Paid')
                ->onIcon('heroicon-o-currency-dollar')
                ->offIcon('heroicon-s-currency-dollar'),
        ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('student.full_name')->label('Student'),
            Tables\Columns\TextColumn::make('tutor.name')->label('Tutor'),
            // Tables\Columns\TextColumn::make('type')
            //     ->badge()
            //     ->color(fn (string $type): string => match ($type) {
            //         'additional' => 'gray',
            //         'rescheduled' => 'warning',
            //         'on-schedule' => 'success',
                    
            //     }),

                Forms\Components\Select::make('type')
                    ->options([
                        'additional' => 'Additional',
                        'rescheduled' => 'Rescheduled',
                        'on-schedule' => 'On-schedule',
                    ])
                    ->default('on-schedule')
                    ->required(),
                
                // ->enum([
                //     'on-schedule' => 'On Schedule',
                //     'additional' => 'Additional',
                //     'rescheduled' => 'Rescheduled',
                // ]),
            Tables\Columns\TextColumn::make('scheduled_date')->date(),
            Tables\Columns\TextColumn::make('actual_date')->date(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('payment_status')->badge(),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('status')->options([
                'filled'=>'Filled',
                'pending' => 'Pending',
                'approved' => 'Approved'
            ]),
            Tables\Filters\SelectFilter::make('payment_status')->options([
                'paid' => 'Paid',
                'unpaid' => 'Unpaid'
            ]),
        ])
        ->actions([Tables\Actions\EditAction::make()])
        ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
}


    public static function getRelations(): array
    {
        return [
        //    
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
