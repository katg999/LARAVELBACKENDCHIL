<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class DoctorAvailability extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static string $view = 'filament.pages.doctor-availability';
    
    public $selectedSpecialization;
    public $availableDoctors = [];
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->action(fn () => $this->refreshData())
                ->icon('heroicon-o-arrow-path'),
        ];
    }
    
    public function mount()
    {
        $this->refreshData();
    }
    
    public function refreshData()
    {
        $this->availableDoctors = Doctor::with('specialization')
            ->where('is_available', true)
            ->get()
            ->groupBy('specialization.name');
    }
    
    public function getSpecializationsProperty()
    {
        return Specialization::all();
    }
}
