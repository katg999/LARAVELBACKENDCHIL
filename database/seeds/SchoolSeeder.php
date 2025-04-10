<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    public function run()
    {
        School::create([
           
            'name' => 'Greenwood High School',
            'email' => 'admin@greenwood.edu',
            'contact' => '+254700123456',
            'file_url' => 'https://example.com/school-docs/greenwood.pdf'
        ]);

        School::create([
            'name' => 'Sunrise Academy',
            'email' => 'info@sunrise.edu',
            'contact' => '+254711234567',
            'file_url' => 'https://example.com/school-docs/sunrise.pdf'
        ]);
    }
}