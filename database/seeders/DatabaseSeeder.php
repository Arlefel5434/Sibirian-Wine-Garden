<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use App\Services\WalletService;
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

        $contentManager = User::factory()->create([
            'name' => 'Demo Content Manager',
            'email' => 'content@example.com',
            'password' => bcrypt('password'),
        ]);

        $contentManager->profile->forceFill(['role' => 'content_manager'])->save();

        $demo = User::factory()->create([
            'name' => 'Demo Customer',
            'email' => 'demo@example.com',
            'password' => bcrypt('password'),
        ]);

        app(WalletService::class)->adjust($demo, 'credit', 250000, $admin, 'Initial demonstration balance');

        Post::factory()->count(10)->create([
            'user_id' => $contentManager->id,
        ]);

        User::factory()
            ->count(5)
            ->has(Post::factory()->count(3))
            ->create();

        $this->call(ProductCatalogSeeder::class);
    }
}
