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

        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
