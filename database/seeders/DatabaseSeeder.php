<?php

namespace Database\Seeders;

use Domains\Publishing\Database\Seeders\HomeFeedDemoSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            HomeFeedDemoSeeder::class,
        ]);
    }
}
