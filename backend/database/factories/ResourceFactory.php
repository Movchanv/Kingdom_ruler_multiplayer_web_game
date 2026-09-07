<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Resource>
 */
class ResourceFactory extends Factory
{
    protected $model = Resource::class;

    public function definition(): array
    {
        $key = fake()->unique()->word();

        return [
            'key' => $key,
            'name' => ucfirst($key),
            'description' => null,
            'is_military' => false,
            'is_active' => true,
        ];
    }
}
