<?php

namespace Database\Seeders;

use App\Models\Doctor;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $doctors = [
            [
                'name' => 'Dr. Sarah Johnson',
                'email' => 'sarah.johnson@example.com',
                'contact' => '+1234567890',
                'specialization' => 'General Medicine',
                'file_url' => 'https://randomuser.me/api/portraits/women/1.jpg',
                'meeting_slug' => 'sarah-johnson-consultation',
                'health_facility_id' => null,
            ],
            [
                'name' => 'Dr. Michael Chen',
                'email' => 'michael.chen@example.com',
                'contact' => '+1234567891',
                'specialization' => 'Pediatrics',
                'file_url' => 'https://randomuser.me/api/portraits/men/2.jpg',
                'meeting_slug' => 'michael-chen-consultation',
                'health_facility_id' => null,
            ],
            [
                'name' => 'Dr. Emily Rodriguez',
                'email' => 'emily.rodriguez@example.com',
                'contact' => '+1234567892',
                'specialization' => 'Cardiology',
                'file_url' => 'https://randomuser.me/api/portraits/women/3.jpg',
                'meeting_slug' => 'emily-rodriguez-consultation',
                'health_facility_id' => null,
            ],
            [
                'name' => 'Dr. David Kim',
                'email' => 'david.kim@example.com',
                'contact' => '+1234567893',
                'specialization' => 'Dermatology',
                'file_url' => 'https://randomuser.me/api/portraits/men/4.jpg',
                'meeting_slug' => 'david-kim-consultation',
                'health_facility_id' => null,
            ],
            [
                'name' => 'Dr. Lisa Thompson',
                'email' => 'lisa.thompson@example.com',
                'contact' => '+1234567894',
                'specialization' => 'Orthopedics',
                'file_url' => 'https://randomuser.me/api/portraits/women/5.jpg',
                'meeting_slug' => 'lisa-thompson-consultation',
                'health_facility_id' => null,
            ],
            [
                'name' => 'Dr. James Wilson',
                'email' => 'james.wilson@example.com',
                'contact' => '+1234567895',
                'specialization' => 'Gynecology',
                'file_url' => 'https://randomuser.me/api/portraits/men/6.jpg',
                'meeting_slug' => 'james-wilson-consultation',
                'health_facility_id' => null,
            ],
            [
                'name' => 'Dr. Maria Garcia',
                'email' => 'maria.garcia@example.com',
                'contact' => '+1234567896',
                'specialization' => 'Ophthalmology',
                'file_url' => 'https://randomuser.me/api/portraits/women/7.jpg',
                'meeting_slug' => 'maria-garcia-consultation',
                'health_facility_id' => null,
            ],
            [
                'name' => 'Dr. Robert Lee',
                'email' => 'robert.lee@example.com',
                'contact' => '+1234567897',
                'specialization' => 'Dentistry',
                'file_url' => 'https://randomuser.me/api/portraits/men/8.jpg',
                'meeting_slug' => 'robert-lee-consultation',
                'health_facility_id' => null,
            ],
            [
                'name' => 'Dr. Jennifer Brown',
                'email' => 'jennifer.brown@example.com',
                'contact' => '+1234567898',
                'specialization' => 'Psychiatry',
                'file_url' => 'https://randomuser.me/api/portraits/women/9.jpg',
                'meeting_slug' => 'jennifer-brown-consultation',
                'health_facility_id' => null,
            ],
            [
                'name' => 'Dr. Thomas Anderson',
                'email' => 'thomas.anderson@example.com',
                'contact' => '+1234567899',
                'specialization' => 'Neurology',
                'file_url' => 'https://randomuser.me/api/portraits/men/10.jpg',
                'meeting_slug' => 'thomas-anderson-consultation',
                'health_facility_id' => null,
            ],
        ];

        foreach ($doctors as $doctor) {
            Doctor::create($doctor);
        }
    }
}