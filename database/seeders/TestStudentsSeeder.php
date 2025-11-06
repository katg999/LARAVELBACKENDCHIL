<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\School;
use Illuminate\Database\Seeder;

class TestStudentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $school = School::where('name', 'Test School')->first();

        if (!$school) {
            $this->command->error('Test School not found!');
            return;
        }

        $students = [
            // Grade 1 Students
            [
                'name' => 'Alice Johnson',
                'gender' => 'female',
                'birth_date' => '2018-03-15',
                'parent_contact' => '+256700123456',
                'grade' => 'Grade 1',
                'contact_number' => '+256700123456',
            ],
            [
                'name' => 'Bob Smith',
                'gender' => 'male',
                'birth_date' => '2017-09-22',
                'parent_contact' => '+256700123457',
                'grade' => 'Grade 1',
                'contact_number' => '+256700123457',
            ],
            [
                'name' => 'Charlie Brown',
                'gender' => 'male',
                'birth_date' => '2018-01-10',
                'parent_contact' => '+256700123458',
                'grade' => 'Grade 1',
                'contact_number' => '+256700123458',
            ],

            // Grade 2 Students
            [
                'name' => 'Diana Wilson',
                'gender' => 'female',
                'birth_date' => '2017-05-08',
                'parent_contact' => '+256700123459',
                'grade' => 'Grade 2',
                'contact_number' => '+256700123459',
            ],
            [
                'name' => 'Edward Davis',
                'gender' => 'male',
                'birth_date' => '2016-11-30',
                'parent_contact' => '+256700123460',
                'grade' => 'Grade 2',
                'contact_number' => '+256700123460',
            ],

            // Grade 3 Students
            [
                'name' => 'Fiona Garcia',
                'gender' => 'female',
                'birth_date' => '2016-07-14',
                'parent_contact' => '+256700123461',
                'grade' => 'Grade 3',
                'contact_number' => '+256700123461',
            ],
            [
                'name' => 'George Miller',
                'gender' => 'male',
                'birth_date' => '2015-12-05',
                'parent_contact' => '+256700123462',
                'grade' => 'Grade 3',
                'contact_number' => '+256700123462',
            ],
            [
                'name' => 'Helen Taylor',
                'gender' => 'female',
                'birth_date' => '2016-04-18',
                'parent_contact' => '+256700123463',
                'grade' => 'Grade 3',
                'contact_number' => '+256700123463',
            ],

            // Grade 4 Students
            [
                'name' => 'Ian Anderson',
                'gender' => 'male',
                'birth_date' => '2015-08-25',
                'parent_contact' => '+256700123464',
                'grade' => 'Grade 4',
                'contact_number' => '+256700123464',
            ],
            [
                'name' => 'Julia Martinez',
                'gender' => 'female',
                'birth_date' => '2014-10-12',
                'parent_contact' => '+256700123465',
                'grade' => 'Grade 4',
                'contact_number' => '+256700123465',
            ],

            // Grade 5 Students
            [
                'name' => 'Kevin Lee',
                'gender' => 'male',
                'birth_date' => '2014-06-03',
                'parent_contact' => '+256700123466',
                'grade' => 'Grade 5',
                'contact_number' => '+256700123466',
            ],
            [
                'name' => 'Laura White',
                'gender' => 'female',
                'birth_date' => '2013-09-28',
                'parent_contact' => '+256700123467',
                'grade' => 'Grade 5',
                'contact_number' => '+256700123467',
            ],
            [
                'name' => 'Michael Clark',
                'gender' => 'male',
                'birth_date' => '2014-02-14',
                'parent_contact' => '+256700123468',
                'grade' => 'Grade 5',
                'contact_number' => '+256700123468',
            ],

            // Grade 6 Students
            [
                'name' => 'Nancy Rodriguez',
                'gender' => 'female',
                'birth_date' => '2013-11-07',
                'parent_contact' => '+256700123469',
                'grade' => 'Grade 6',
                'contact_number' => '+256700123469',
            ],
            [
                'name' => 'Oliver Thompson',
                'gender' => 'male',
                'birth_date' => '2012-12-19',
                'parent_contact' => '+256700123470',
                'grade' => 'Grade 6',
                'contact_number' => '+256700123470',
            ],

            // Grade 7 Students
            [
                'name' => 'Patricia Lewis',
                'gender' => 'female',
                'birth_date' => '2012-03-22',
                'parent_contact' => '+256700123471',
                'grade' => 'Grade 7',
                'contact_number' => '+256700123471',
            ],
            [
                'name' => 'Quincy Hall',
                'gender' => 'male',
                'birth_date' => '2011-07-09',
                'parent_contact' => '+256700123472',
                'grade' => 'Grade 7',
                'contact_number' => '+256700123472',
            ],
            [
                'name' => 'Rachel Young',
                'gender' => 'female',
                'birth_date' => '2012-01-31',
                'parent_contact' => '+256700123473',
                'grade' => 'Grade 7',
                'contact_number' => '+256700123473',
            ],
        ];

        foreach ($students as $studentData) {
            Patient::create(array_merge($studentData, [
                'school_id' => $school->id,
                'medical_history' => rand(0, 2) === 0 ? 'No known medical conditions' : null, // 33% chance of having medical history
            ]));
        }

        $this->command->info('Created ' . count($students) . ' test students for ' . $school->name);
    }
}