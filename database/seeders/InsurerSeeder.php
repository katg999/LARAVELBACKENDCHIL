<?php

namespace Database\Seeders;

use App\Models\Insurer;
use Illuminate\Database\Seeder;

/** Insurers Rocket Health lists on its website. Handoff defaults to csv until an integration exists. */
class InsurerSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'ICEA', 'GA Insurance', 'AAR', 'Liberty', 'Prudential', 'Sanlam', 'Jubilee',
            'UAP Old Mutual', 'Aetna', 'Bupa', 'Allianz', 'Britam',
        ] as $name) {
            Insurer::firstOrCreate(['code' => strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $name))], [
                'name' => $name,
                'handoff_method' => 'csv',
            ]);
        }
    }
}
