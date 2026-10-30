<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $admin = User::factory()->create([
            'name' => 'Alex Developer',
            'email' => 'alex@example.com',
            'password' => bcrypt('password'),
        ]);

        $admin->profile->forceFill(['role' => 'admin'])->save();

        Post::factory()->count(10)->create([
            'user_id' => $admin->id,
        ]);

        User::factory()
            ->count(5)
            ->has(Post::factory()->count(3))
            ->create();

    }
}
