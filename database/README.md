# Marketplace Schema

The marketplace uses PostgreSQL. Migrations use Laravel's `Schema` facade and `Blueprint` methods only.

## Money and Quantities

Prices and order totals are integer minor units: `1299` means EUR 12.99. EUR is the initial default currency. Currency fields accept uppercase three-letter codes; application validation must additionally restrict currencies to the supported list.

Every SKU requires an explicit price. `orders.total_amount` is the final payable total, including any delivery, discounts, and taxes.

Quantities use whole pieces, grams, or millilitres. A SKU's price is per one quantity unit. For example, price grapes per gram, or create a packaged SKU priced per piece. Order items snapshot the unit, currency, product name, SKU code, variant name, variant attributes, and unit price. Checkout must supply these snapshots and must calculate totals from authoritative data inside a transaction. Database constraints do not implement stock reservation or order-state transitions.

Product attributes store definitions in `attributes` and product-specific values in `attribute_values`. There is one value per product and attribute. Variant names identify SKUs; `order_items.variant_attributes` preserves any additional variant details supplied by checkout.

## Profiles

The application creates one profile when it creates a user. A unique foreign key prevents duplicate profiles, and deleting a user cascades to the profile. User creation must go through the `User` model so its creation event runs. Direct database inserts do not create profiles.

Profiles contain `role`, `phone_number`, `avatar_path`, and optional `bio`. The initial roles are `customer` and `admin`; customers are the default. Role is excluded from mass assignment, so trusted administrative code must assign it explicitly. Policies and gates must still enforce permissions; this migration does not implement authorization.

Names and email remain in `users`. Store an avatar's storage path rather than image bytes. Keep shipping addresses in a separate address model when checkout needs saved addresses, and preserve the delivery snapshot on each order.

## Tests

The PHPUnit configuration uses PostgreSQL database `wine_garden_test`. Create that dedicated database and supply its host, port, username, and password through the test environment. Never point tests at the application database: `RefreshDatabase` rebuilds the test schema. Run the suite with `docker compose run --rm --no-deps app php artisan test` after configuring test database connectivity.
