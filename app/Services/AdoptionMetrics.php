<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Consent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * The adoption measures the pilot is judged on, worked out from appointment data:
 * how many bookings are paid online, how many new patients complete a first visit,
 * how long from booking to consultation, and how many come back.
 *
 * It reads whole appointment histories in memory, which is fine at pilot scale.
 * Move to SQL aggregates before running it over many thousands of patients.
 */
class AdoptionMetrics
{
    private const PAID = ['completed', 'insurance', 'employer'];
    private const DONE = ['completed', 'awaiting_approval'];

    /** @param Builder $scope appointments the viewer may see (all, or one clinic/doctor) */
    public function compute(Builder $scope, Carbon $from, Carbon $to): array
    {
        $all = (clone $scope)->where('status', '!=', 'cancelled')
            ->get(['id', 'patient_id', 'created_at', 'appointment_time', 'status', 'payment_status', 'payment_method', 'coverage_type']);

        $joined = Consent::whereIn('appointment_id', $all->pluck('id'))->pluck('appointment_id')->flip();
        $attended = fn ($a) => isset($joined[$a->id]) || in_array($a->status, self::DONE, true);

        $bookings = $all->filter(fn ($a) => $a->created_at->between($from, $to));
        $paid = $bookings->filter(fn ($a) => in_array($a->payment_status, self::PAID, true));

        // Patients whose first visit was booked in the period
        $byPatient = $all->groupBy('patient_id')->map(fn ($g) => $g->sortBy('created_at')->values());
        $newPatients = $byPatient->filter(fn ($g) => $g->first()->created_at->between($from, $to));
        $firstCompleted = $newPatients->filter(fn ($g) => $attended($g->first()));

        $minutes = $bookings->filter($attended)
            ->map(fn ($a) => max(0, $a->created_at->diffInMinutes($a->appointment_time, false)))
            ->sort()->values();

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'bookings' => $bookings->count(),
            'booked_and_paid' => ['count' => $paid->count(), 'share' => $this->share($paid->count(), $bookings->count())],
            'payment_methods' => $paid->groupBy(fn ($a) => $a->payment_method ?? 'unknown')->map->count()->sortDesc()->all(),
            'insured_share' => $this->share($bookings->where('coverage_type', 'insurance')->count(), $bookings->count()),
            'new_patients' => $newPatients->count(),
            'first_visit_completion' => ['count' => $firstCompleted->count(), 'share' => $this->share($firstCompleted->count(), $newPatients->count())],
            'minutes_booking_to_consult' => [
                'median' => $minutes->isEmpty() ? null : $this->median($minutes->all()),
                'sample' => $minutes->count(),
            ],
            'repeat_30_days' => $this->repeat($byPatient, 30, $to),
            'repeat_90_days' => $this->repeat($byPatient, 90, $to),
            // Call-centre workload is not stored in this system, so it cannot be measured here.
            'call_load' => null,
        ];
    }

    /** Of patients whose first visit is at least $days old, the share who booked again within $days of it. */
    private function repeat($byPatient, int $days, Carbon $asOf): array
    {
        $eligible = $byPatient->filter(fn ($g) => $g->first()->appointment_time->copy()->addDays($days)->lte($asOf));
        $returned = $eligible->filter(function ($g) use ($days) {
            $first = $g->first();
            $limit = $first->appointment_time->copy()->addDays($days);

            return $g->skip(1)->contains(fn ($a) => $a->appointment_time->gt($first->appointment_time) && $a->appointment_time->lte($limit));
        });

        return ['eligible' => $eligible->count(), 'returned' => $returned->count(), 'share' => $this->share($returned->count(), $eligible->count())];
    }

    private function share(int $part, int $whole): ?float
    {
        return $whole === 0 ? null : round($part / $whole, 3);
    }

    private function median(array $sorted): float
    {
        $n = count($sorted);
        $mid = intdiv($n, 2);

        return (float) ($n % 2 ? $sorted[$mid] : ($sorted[$mid - 1] + $sorted[$mid]) / 2);
    }
}
