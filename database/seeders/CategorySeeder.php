<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Pick the Makkah zone of the first season as the default zone for the
        // seeded categories. If zones haven't been seeded yet, fall back to
        // null (zone_id is nullable in this microservice).
        $defaultZone = Zone::query()
            ->where('name->en', 'Makkah')
            ->orderBy('id')
            ->first();
        $defaultZoneId = $defaultZone?->id;

        $tree = [
            [
                'name'     => ['en' => 'Accommodation', 'ar' => 'إقامة'],
                'children' => [
                    ['name' => ['en' => 'Hotel issues',     'ar' => 'مشاكل الفندق']],
                    ['name' => ['en' => 'Room cleanliness', 'ar' => 'نظافة الغرفة']],
                    ['name' => ['en' => 'Check-in delay',   'ar' => 'تأخر تسجيل الوصول']],
                ],
            ],
            [
                'name'     => ['en' => 'Transportation', 'ar' => 'النقل'],
                'children' => [
                    ['name' => ['en' => 'Bus delay',    'ar' => 'تأخر الحافلة']],
                    ['name' => ['en' => 'Lost luggage', 'ar' => 'فقدان الأمتعة']],
                ],
            ],
            [
                'name'     => ['en' => 'Catering', 'ar' => 'الإطعام'],
                'children' => [
                    ['name' => ['en' => 'Food quality', 'ar' => 'جودة الطعام']],
                    ['name' => ['en' => 'Meal timing',  'ar' => 'مواعيد الوجبات']],
                ],
            ],
        ];

        foreach ($tree as $root) {
            $children = $root['children'];
            unset($root['children']);

            $parent = Category::firstOrCreate(
                ['name->en' => $root['name']['en']],
                $root + [
                    'zone_id' => $defaultZoneId,
                    'status' => true,
                    'is_custom' => false,
                    'enable_email' => true,
                ],
            );

            foreach ($children as $child) {
                Category::firstOrCreate(
                    ['name->en' => $child['name']['en']],
                    $child + [
                        'parent_id' => $parent->id,
                        'zone_id' => $parent->zone_id,
                        'status' => true,
                        'is_custom' => false,
                        'enable_email' => true,
                    ],
                );
            }
        }
    }
}
