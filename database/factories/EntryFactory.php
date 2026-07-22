<?php

namespace Database\Factories;

use App\Models\ContentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Entry>
 */
class EntryFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'content_type_id' => ContentType::factory(),
            'author_id'       => User::factory(),
            'title'           => $title,
            'slug'            => str($title)->slug()->toString() . '-' . fake()->unique()->numberBetween(100, 9999),
            'body'            => null,
            'custom_fields'   => [],
            'status'          => 'draft',
            'published_at'    => null,
        ];
    }

    public function published(): static
    {
        return $this->state([
            'status'       => 'published',
            'published_at' => now()->subHour(),
        ]);
    }
}
