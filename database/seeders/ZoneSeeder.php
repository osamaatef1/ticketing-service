<?php

namespace Database\Seeders;

use App\Models\Season;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        $perSeason = [
            ['en' => 'Makkah',  'ar' => 'مكة'],
            ['en' => 'Madinah', 'ar' => 'المدينة'],
            ['en' => 'Mina',    'ar' => 'منى'],
        ];

        Season::query()->each(function (Season $season) use ($perSeason) {
            foreach ($perSeason as $name) {
                Zone::firstOrCreate(
                    [
                        'season_id' => $season->id,
                        'name->en' => $name['en'],
                    ],
                    [
                        'name' => $name,
                        'status' => true,
                    ]
                );
            }
        });
    }
}
