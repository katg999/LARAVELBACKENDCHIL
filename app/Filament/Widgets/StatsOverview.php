<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Students', Student::where('school_id', auth()->user()->school_id)->count())
                ->icon('heroicon-o-user-group'),
            Stat::make('Upcoming Appointments', Appointment::where('school_id', auth()->user()->school_id)
                ->where('appointment_time', '>=', now())
                ->count())
                ->icon('heroicon-o-calendar'),
            Stat::make('Pending Lab Requests', LabRequest::where('school_id', auth()->user()->school_id)
                ->where('status', 'pending')
                ->count())
                ->icon('heroicon-o-beaker'),
            Stat::make('Available Doctors', Doctor::where('is_available', true)->count())
                ->icon('heroicon-o-user-plus'),
        ];
    }
}