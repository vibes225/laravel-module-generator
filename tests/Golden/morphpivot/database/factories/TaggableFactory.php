<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Tag;
use App\Models\Taggable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Taggable>
 */
class TaggableFactory extends Factory
{
    protected $model = Taggable::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tag_id' => Tag::factory(),
            'taggable_type' => Post::class,
            'taggable_id' => Post::factory(),
        ];
    }
}
