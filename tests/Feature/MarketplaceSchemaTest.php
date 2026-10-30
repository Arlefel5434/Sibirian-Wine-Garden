<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarketplaceSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_user_gets_one_customer_profile(): void
    {
        $user = User::factory()->create();

        $this->assertSame('customer', $user->profile->role);
        $this->assertTrue($user->profile->user->is($user));
        $this->assertSame(1, $user->profile()->count());
        $this->assertRejected(fn () => DB::table('profiles')->insert(['user_id' => $user->id]));
    }

    public function test_deleting_a_user_deletes_the_profile(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $user->delete();

        $this->assertDatabaseMissing('profiles', ['user_id' => $userId]);
    }

    public function test_profile_details_can_be_updated_without_mass_assigning_role(): void
    {
        $profile = User::factory()->create()->profile;

        $profile->update([
            'phone_number' => '+491234567890',
            'avatar_path' => 'avatars/example.jpg',
            'bio' => 'Wine grower',
            'role' => 'admin',
        ]);

        $profile->refresh();

        $this->assertSame('+491234567890', $profile->phone_number);
        $this->assertSame('customer', $profile->role);
    }

    public function test_products_can_have_different_values_for_the_same_attribute(): void
    {
        $firstProduct = $this->product('first');
        $secondProduct = $this->product('second');
        $attribute = DB::table('attributes')->insertGetId([
            'name' => 'Alcohol content',
            'type' => 'decimal',
            'unit' => '%',
        ]);

        DB::table('attribute_values')->insert([
            ['product_id' => $firstProduct, 'attribute_id' => $attribute, 'value' => '12'],
            ['product_id' => $secondProduct, 'attribute_id' => $attribute, 'value' => '14'],
        ]);

        $this->assertDatabaseHas('attribute_values', ['product_id' => $firstProduct, 'value' => '12']);
        $this->assertDatabaseHas('attribute_values', ['product_id' => $secondProduct, 'value' => '14']);
        $this->assertRejected(fn () => DB::table('attribute_values')->insert([
            'product_id' => $firstProduct,
            'attribute_id' => $attribute,
            'value' => '13',
        ]));
    }

    public function test_invalid_enum_values_are_rejected(): void
    {
        $product = $this->product('wine');
        $sku = DB::table('skus')->insertGetId([
            'product_id' => $product,
            'sku' => 'WINE-750',
            'name' => '750 ml',
            'price' => 1299,
        ]);
        $order = DB::table('orders')->insertGetId(['total_amount' => 1299]);

        $this->assertRejected(fn () => DB::table('products')->where('id', $product)->update(['status' => 'unknown']));
        $this->assertRejected(fn () => DB::table('skus')->where('id', $sku)->update(['quantity_unit' => 'unknown']));
        $this->assertRejected(fn () => DB::table('orders')->where('id', $order)->update(['status' => 'unknown']));
    }

    public function test_deleting_a_product_preserves_order_item_snapshots(): void
    {
        $product = $this->product('wine');
        $sku = DB::table('skus')->insertGetId([
            'product_id' => $product,
            'sku' => 'WINE-750',
            'name' => '750 ml',
            'price' => 1299,
        ]);
        $order = DB::table('orders')->insertGetId(['total_amount' => 1299]);
        $item = DB::table('order_items')->insertGetId([
            'order_id' => $order,
            'sku_id' => $sku,
            'quantity' => 1,
            'price_at_purchase' => 1299,
            'product_name' => 'Wine',
            'sku_code' => 'WINE-750',
            'variant_name' => '750 ml',
        ]);

        DB::table('products')->where('id', $product)->delete();

        $this->assertDatabaseHas('order_items', [
            'id' => $item,
            'sku_id' => null,
            'product_name' => 'Wine',
            'sku_code' => 'WINE-750',
            'variant_name' => '750 ml',
            'price_at_purchase' => 1299,
            'currency' => 'EUR',
        ]);
    }

    public function test_demo_seeding_creates_profiles_and_assigns_the_admin_role(): void
    {
        $this->seed();

        $admin = User::where('email', 'alex@example.com')->firstOrFail();

        $this->assertSame('admin', $admin->profile->role);
        $this->assertSame(User::count(), DB::table('profiles')->count());
    }

    private function product(string $slug): int
    {
        return DB::table('products')->insertGetId([
            'name' => 'Wine',
            'slug' => $slug,
        ]);
    }

    private function assertRejected(callable $operation): void
    {
        try {
            DB::transaction($operation);
        } catch (QueryException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail('The database accepted invalid data.');
    }
}
