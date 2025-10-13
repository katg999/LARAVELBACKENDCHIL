<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $durations = [
            [
                'minutes' => 15,
                'general_price' => 30000.00,
                'specialist_price' => 0, // Not used for general
                'type' => 'general',
            ],
            [
                'minutes' => 15,
                'general_price' => 0, // Not used for specialist
                'specialist_price' => 100000.00,
                'type' => 'specialist',
            ],
            [
                'minutes' => 20,
                'general_price' => 45000.00,
                'specialist_price' => 0, // Not used for general
                'type' => 'general',
            ],
            [
                'minutes' => 20,
                'general_price' => 0, // Not used for specialist
                'specialist_price' => 150000.00,
                'type' => 'specialist',
            ],
            [
                'minutes' => 30,
                'general_price' => 75000.00,
                'specialist_price' => 0, // Not used for general
                'type' => 'general',
            ],
            [
                'minutes' => 30,
                'general_price' => 0, // Not used for specialist
                'specialist_price' => 250000.00,
                'type' => 'specialist',
            ],
            [
                'minutes' => 45,
                'general_price' => 100000.00,
                'specialist_price' => 0, // Not used for general
                'type' => 'general',
            ],
            [
                'minutes' => 45,
                'general_price' => 0, // Not used for specialist
                'specialist_price' => 350000.00,
                'type' => 'specialist',
            ],
            [
                'minutes' => 60,
                'general_price' => 125000.00,
                'specialist_price' => 0, // Not used for general
                'type' => 'general',
            ],
            [
                'minutes' => 60,
                'general_price' => 0, // Not used for specialist
                'specialist_price' => 450000.00,
                'type' => 'specialist',
            ],
        ];

        foreach ($durations as $duration) {
            \App\Models\Duration::updateOrCreate(
                [
                    'minutes' => $duration['minutes'],
                    'type' => $duration['type']
                ],
                $duration
            );
        }
    }
}
