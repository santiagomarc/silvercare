<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Bring a fresh database back to a state you can sign into and use.
     *
     * Order matters: AccountsSeeder creates the accounts, DemoDataSeeder
     * attaches vitals, medications and checklists to them and errors out if
     * they do not exist yet.
     */
    public function run(): void
    {
        $this->call([
            AccountsSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
