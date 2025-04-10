<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LabRequestResource\Pages;
use App\Filament\Resources\LabRequestResource\RelationManagers;
use App\Models\LabRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LabRequestResource extends Resource
{
    protected static ?string $model = LabRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

 public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Select::make('student_id')
                ->relationship('student', 'name')
                ->searchable()
                ->required(),
            Forms\Components\Select::make('lab_test_id')
                ->relationship('labTest', 'name')
                ->searchable()
                ->required(),
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
            Tables\Columns\TextColumn::make('labTest.name')
                ->searchable(),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'warning',
                    'processing' => 'info',
                    'completed' => 'success',
                }),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable(),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'pending' => 'Pending',
                    'processing' => 'Processing',
                    'completed' => 'Completed',
                ]),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListLabRequests::route('/'),
            'create' => Pages\CreateLabRequest::route('/create'),
            'edit' => Pages\EditLabRequest::route('/{record}/edit'),
        ];
    }
}
