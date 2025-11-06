<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School;

class TestSchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting to seed test schools...');

        $schools = [
            [
                'name' => 'Nairobi Primary School',
                'address' => 'Westlands, Nairobi',
                'email' => 'info@nairobiprimary.ac.ke',
                'contact' => '+254712345601',
            ],
            [
                'name' => 'Mombasa Secondary School',
                'address' => 'Nyali, Mombasa',
                'email' => 'admin@mombasasecondary.ac.ke',
                'contact' => '+254712345602',
            ],
            [
                'name' => 'Kisumu Academy',
                'address' => 'Milimani, Kisumu',
                'email' => 'contact@kisumuacademy.ac.ke',
                'contact' => '+254712345603',
            ],
            [
                'name' => 'Eldoret International School',
                'address' => 'Pioneer Estate, Eldoret',
                'email' => 'info@eldoretinternational.ac.ke',
                'contact' => '+254712345604',
            ],
            [
                'name' => 'Nakuru High School',
                'address' => 'Section 58, Nakuru',
                'email' => 'admin@nakuruhigh.ac.ke',
                'contact' => '+254712345605',
            ],
        ];

        foreach ($schools as $schoolData) {
            $school = School::create($schoolData);
            $this->command->info("Created school: {$school->name}");
        }

        $this->command->info("✓ Successfully created " . count($schools) . " test schools!");
    }
}
