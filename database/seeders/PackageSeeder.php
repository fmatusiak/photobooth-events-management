<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => '2 godzinny',
                'rental_time' => 120,
                'price' => 899
            ],
            [
                'name' => '3 godzinny',
                'rental_time' => 180,
                'price' => 1199
            ],
            [
                'name' => '4 godzinny',
                'rental_time' => 240,
                'price' => 1399
            ]
        ];

        foreach ($packages as $package) {
            Package::updateOrCreate($package);
        }

    }
}