<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $seeders = [
            TicketCategorySeeder::class,
            TicketingUserSeeder::class,
            TicketSeeder::class,
        ];

        if ($this->app->environment(['local', 'testing'])) {
            $seeders[] = ActivityLogSeeder::class;
        }

        $this->call($seeders);
    }
}
