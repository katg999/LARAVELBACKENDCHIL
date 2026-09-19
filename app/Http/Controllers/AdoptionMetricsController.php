<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksStaffAccess;
use App\Models\Appointment;
use App\Services\AdoptionMetrics;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Adoption numbers for the pilot: per clinic or doctor for staff, everything for admins. */
class AdoptionMetricsController extends Controller
{
    use ChecksStaffAccess;

    public function __construct(private AdoptionMetrics $metrics)
    {
    }

    public function mine(Request $request)
    {
        $user = $this->staff($request);

        return $this->respond($request, $this->scopeTo($user, Appointment::query()), 'Your adoption numbers');
    }

    public function all(Request $request)
    {
        return $this->respond($request, Appointment::query(), 'Adoption numbers, all clinics');
    }

    private function respond(Request $request, $scope, string $title)
    {
        $data = $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date']);
        $to = isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : now();
        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : $to->copy()->subDays(29)->startOfDay();

        $result = $this->metrics->compute($scope, $from, $to);

        return $request->wantsJson() ? response()->json($result) : view('metrics.adoption', ['m' => $result, 'title' => $title]);
    }
}
