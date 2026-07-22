<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContentType>
 */
class ContentTypeFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name'          => ucfirst($name),
            'slug'          => strtolower($name),
            'context'       => fake()->randomElement(['post', 'page']),
            'description'   => fake()->sentence(),
            'fields_schema' => [],
            'is_system'     => false,
            'is_active'     => true,
        ];
    }
}
