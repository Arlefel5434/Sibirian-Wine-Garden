<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(6);

        return [
            // Автоматически создает нового пользователя-автора для каждого поста
            'user_id' => User::factory(),

            'title' => $title,
            'slug' => Str::slug($title),
            'preview_text' => $this->faker->paragraph(2),
            'preview_img' => $this->faker->imageUrl(800, 600, 'wine', true, 'Sibirian Garden'),

            // Генерируем структуру блочного JSON для тела поста
            'body' => [
                [
                    'type' => 'heading',
                    'data' => ['text' => $this->faker->sentence(4), 'level' => 2],
                ],
                [
                    'type' => 'paragraph',
                    'data' => ['text' => $this->faker->paragraph(4)],
                ],
                [
                    'type' => 'image',
                    'data' => ['url' => $this->faker->imageUrl(640, 480, 'nature'), 'caption' => 'Виноградники Сибири'],
                ],
                [
                    'type' => 'paragraph',
                    'data' => ['text' => $this->faker->paragraph(5)],
                ],
            ],

            // Случайный статус публикации
            'status' => $this->faker->randomElement(['draft', 'published']),
        ];
    }
}
