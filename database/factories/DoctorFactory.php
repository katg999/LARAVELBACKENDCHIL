<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\HealthFacility;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $specializations = [
            'General Medicine',
            'Pediatrics',
            'Cardiology',
            'Dermatology',
            'Orthopedics',
            'Gynecology',
            'Ophthalmology',
            'Dentistry',
            'Psychiatry',
            'Neurology'
        ];

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'contact' => fake()->phoneNumber(),
            'specialization' => fake()->randomElement($specializations),
            'file_url' => fake()->imageUrl(400, 400, 'people', true, 'doctor'),
            'meeting_slug' => fake()->slug(),
            'health_facility_id' => null,
        ];
    }
}