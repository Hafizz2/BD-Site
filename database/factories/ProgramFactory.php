<?php

namespace Database\Factories;

use App\Enums\ProgramType;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Program> */
class ProgramFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true).' Training';

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'type' => $this->faker->randomElement(ProgramType::cases()),
            'summary' => $this->faker->sentence(14),
            'description' => '<p>'.$this->faker->paragraph(4).'</p>',
            'icon' => 'graduation',
            'is_active' => true,
            'show_on_website' => true,
        ];
    }
}
