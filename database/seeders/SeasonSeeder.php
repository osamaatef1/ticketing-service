<?php

namespace Database\Seeders;

use App\Models\Season;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    public function run(): void
    {
        $seasons = [
            ['name' => ['en' => 'Hajj 1447',     'ar' => 'حج 1447'],     'status' => true],
            ['name' => ['en' => 'Umrah Winter',  'ar' => 'عمرة الشتاء'], 'status' => true],
            ['name' => ['en' => 'Umrah Ramadan', 'ar' => 'عمرة رمضان'],  'status' => true],
            ['name' => ['en' => 'Hajj 1446',     'ar' => 'حج 1446'],     'status' => false],
        ];

        foreach ($seasons as $data) {
            Season::firstOrCreate(
                ['name->en' => $data['name']['en']],
                $data
            );
        }
    }
}
