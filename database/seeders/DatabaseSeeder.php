<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Everything `db:seed` builds. Running it a second time leaves a usable database
 * rather than a duplicated one, because each seeder below rebuilds its own account's
 * data instead of adding to it.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            DemoAccountSeeder::class,
            SecondaryAccountSeeder::class,
        ]);
    }
}
