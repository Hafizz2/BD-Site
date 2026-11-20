<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Everywhere (incl. the live server): roles + Super Admin, site settings/pages, sample website content.
     * Local only: fake students, registrations and finance data.
     * Every seeder is safe to run again — it only adds what is missing.
     */
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            WebsiteContentSeeder::class,
            SampleContentSeeder::class,
        ]);

        // Fake data needs the dev tools (Faker), which a server install (`composer install --no-dev`) doesn't have.
        if (app()->environment('local') && class_exists(\Faker\Factory::class)) {
            $this->call(DemoSeeder::class);
        }
    }
}
