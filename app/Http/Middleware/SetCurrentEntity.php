<?php

namespace App\Http\Middleware;

use App\Models\Doctor;
use App\Models\School;
use App\Models\HealthFacility;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentEntity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Set current entity context in session based on route parameters
        $this->setCurrentEntityFromRoute($request);

        return $next($request);
    }

    /**
     * Set the current entity in session based on route parameters
     */
    private function setCurrentEntityFromRoute(Request $request)
    {
        $route = $request->route();

        if (!$route) {
            return;
        }

        $parameters = $route->parameters();

        // Check for school routes
        if (isset($parameters['school'])) {
            $school = $parameters['school'];
            if ($school instanceof School) {
                session(['current_entity' => [
                    'type' => 'school',
                    'id' => $school->id,
                    'name' => $school->name
                ]]);
            }
        }
        // Check for health facility routes
        elseif (isset($parameters['id']) && str_contains($route->getName(), 'health-facility')) {
            try {
                $healthFacility = HealthFacility::find($parameters['id']);
                if ($healthFacility) {
                    session(['current_entity' => [
                        'type' => 'health_facility',
                        'id' => $healthFacility->id,
                        'name' => $healthFacility->name
                    ]]);
                }
            } catch (\Exception $e) {
                // Ignore if not found
            }
        }
        // Check for doctor routes
        elseif (isset($parameters['doctorId']) || isset($parameters['doctor'])) {
            $doctorId = $parameters['doctorId'] ?? $parameters['doctor'];
            try {
                $doctor = Doctor::find($doctorId);
                if ($doctor) {
                    session(['current_entity' => [
                        'type' => 'doctor',
                        'id' => $doctor->id,
                        'name' => $doctor->name
                    ]]);
                }
            } catch (\Exception $e) {
                // Ignore if not found
            }
        }
    }
}
