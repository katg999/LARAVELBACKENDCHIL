<?php

namespace App\Filament\Resources\LabRequestResource\Pages;

use App\Filament\Resources\LabRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLabRequest extends EditRecord
{
    protected static string $resource = LabRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
