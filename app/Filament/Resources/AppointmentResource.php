<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Filament\Resources\AppointmentResource\RelationManagers;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

  public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Select::make('student_id')
                ->relationship('student', 'name')
                ->searchable()
                ->required(),
            Forms\Components\Select::make('doctor_id')
                ->label('Doctor')
                ->options(
                    Doctor::with('specialization')
                        ->where('is_available', true)
                        ->get()
                        ->mapWithKeys(fn ($doctor) => [
                            $doctor->id => "{$doctor->name} ({$doctor->specialization->name})"
                        ])
                )
                ->searchable()
                ->required(),
            Forms\Components\DateTimePicker::make('appointment_time')
                ->required()
                ->minDate(now())
                ->minutesStep(15),
            Forms\Components\Textarea::make('reason')
                ->required()
                ->columnSpanFull(),
            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),
        ]);
}

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('student.name')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('doctor.name')
                ->searchable(),
            Tables\Columns\TextColumn::make('doctor.specialization.name')
                ->label('Specialization'),
            Tables\Columns\TextColumn::make('appointment_time')
                ->dateTime()
                ->sortable(),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'warning',
                    'approved' => 'success',
                    'completed' => 'primary',
                    'cancelled' => 'danger',
                }),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ]),
            Tables\Filters\Filter::make('upcoming')
                ->query(fn (Builder $query): Builder => $query->where('appointment_time', '>=', now())),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('join')
                ->label('Join Meeting')
                ->url(fn (Appointment $record) => $record->meeting_url)
                ->visible(fn (Appointment $record) => $record->status === 'approved' && $record->meeting_url)
                ->openUrlInNewTab(),
        ]);
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
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
