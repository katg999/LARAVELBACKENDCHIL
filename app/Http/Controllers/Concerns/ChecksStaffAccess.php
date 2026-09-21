<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Shared rules for clinic and doctor staff who reach patient data through the session login. */
trait ChecksStaffAccess
{
    /** @return array{type: string, id: int} */
    protected function staff(Request $request): array
    {
        $user = $request->current_user ?? null;
        if (!$user || !in_array($user['type'], ['doctor', 'health_facility'], true)) {
            abort(403);
        }

        return $user;
    }

    /** Limit an appointment query to the staff member's own doctor or clinic. */
    protected function scopeTo(array $user, Builder $query): Builder
    {
        return $user['type'] === 'doctor'
            ? $query->where('doctor_id', $user['id'])
            : $query->where('health_facility_id', $user['id']);
    }

    protected function canSeePatient(array $user, Patient $patient): bool
    {
        if ($user['type'] === 'health_facility') {
            return (int) $patient->health_facility_id === (int) $user['id']
                || $patient->healthFacilities()->whereKey($user['id'])->exists();
        }

        return $patient->appointments()->where('doctor_id', $user['id'])->exists();
    }

    /** Limit a patient query to the patients this staff member may see. */
    protected function restrictToVisiblePatients($q, array $user)
    {
        if ($user['type'] === 'health_facility') {
            return $q->where(fn ($q) => $q->where('health_facility_id', $user['id'])->orWhereHas('healthFacilities', fn ($h) => $h->whereKey($user['id'])));
        }

        return $q->whereHas('appointments', fn ($a) => $a->where('doctor_id', $user['id']));
    }
}
