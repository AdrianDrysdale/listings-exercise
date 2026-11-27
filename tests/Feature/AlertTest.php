<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\PropertyType;
use App\Models\Alert;
use App\Models\Branch;
use App\Models\Listing;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_alerts_for_the_current_user(): void
    {
        $user = User::factory()->create(['email' => 'demo@street.example']);
        $other = User::factory()->create();
        $branch = Branch::factory()->create();

        // Avoid the observer so this test only covers the alerts page props.
        $listing = Listing::withoutEvents(
            fn () => Listing::factory()->live()->for($branch)->create(),
        );
        $otherListing = Listing::withoutEvents(
            fn () => Listing::factory()->live()->for($branch)->create(),
        );

        $search = SavedSearch::factory()->for($user)->create([
            'property_type' => PropertyType::Flat,
            'max_price' => null,
            'min_bedrooms' => null,
            'region' => null,
        ]);
        $otherSearch = SavedSearch::factory()->for($other)->create([
            'property_type' => PropertyType::Detached,
            'max_price' => null,
            'min_bedrooms' => null,
            'region' => null,
        ]);

        Alert::query()->create([
            'user_id' => $user->id,
            'saved_search_id' => $search->id,
            'listing_id' => $listing->id,
        ]);
        Alert::query()->create([
            'user_id' => $other->id,
            'saved_search_id' => $otherSearch->id,
            'listing_id' => $otherListing->id,
        ]);

        $this->get('/alerts')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Alerts/Index')
                ->has('alerts', 1)
                ->has('alerts.0.listing', fn (AssertableInertia $listingProp) => $listingProp
                    ->hasAll('id', 'address_line_1', 'city', 'price', 'property_type_label', 'branch')
                    ->etc()
                )
                ->has('alerts.0.saved_search', fn (AssertableInertia $search) => $search
                    ->where('property_type', 'flat')
                    ->hasAll('id', 'property_type', 'property_type_label', 'max_price', 'min_bedrooms', 'region')
                    ->etc()
                )
            );
    }

    public function test_creating_a_matching_live_listing_creates_an_alert(): void
    {
        $user = User::factory()->create(['email' => 'demo@street.example']);
        $branch = Branch::factory()->create(['region' => 'Bristol']);

        SavedSearch::factory()->for($user)->create([
            'property_type' => PropertyType::Flat,
            'max_price' => 300_000,
            'min_bedrooms' => 2,
            'region' => 'Bristol',
        ]);

        Listing::factory()->live()->for($branch)->create([
            'property_type' => PropertyType::Flat,
            'price' => 250_000,
            'bedrooms' => 2,
        ]);

        $this->assertDatabaseCount('alerts', 1);
        $this->assertDatabaseHas('alerts', ['user_id' => $user->id]);
    }

    public function test_non_matching_live_listing_does_not_create_an_alert(): void
    {
        $user = User::factory()->create(['email' => 'demo@street.example']);
        $branch = Branch::factory()->create(['region' => 'Bristol']);

        SavedSearch::factory()->for($user)->create([
            'property_type' => PropertyType::Flat,
            'max_price' => 200_000,
            'min_bedrooms' => null,
            'region' => 'Bristol',
        ]);

        Listing::factory()->live()->for($branch)->create([
            'property_type' => PropertyType::Flat,
            'price' => 450_000,
            'bedrooms' => 2,
        ]);

        $this->assertDatabaseCount('alerts', 0);
    }

    public function test_draft_becoming_live_creates_an_alert(): void
    {
        $user = User::factory()->create(['email' => 'demo@street.example']);
        $branch = Branch::factory()->create(['region' => 'Leeds']);

        SavedSearch::factory()->for($user)->create([
            'property_type' => null,
            'max_price' => null,
            'min_bedrooms' => 3,
            'region' => 'Leeds',
        ]);

        $listing = Listing::factory()->draft()->for($branch)->create([
            'property_type' => PropertyType::Detached,
            'price' => 400_000,
            'bedrooms' => 4,
        ]);

        $this->assertDatabaseCount('alerts', 0);

        $listing->update([
            'status' => ListingStatus::Live,
            'listed_at' => now(),
        ]);

        $this->assertDatabaseCount('alerts', 1);
    }

    public function test_updating_an_already_live_listing_does_not_duplicate_alerts(): void
    {
        $user = User::factory()->create(['email' => 'demo@street.example']);
        $branch = Branch::factory()->create(['region' => 'York']);

        SavedSearch::factory()->for($user)->create([
            'property_type' => null,
            'max_price' => null,
            'min_bedrooms' => null,
            'region' => 'York',
        ]);

        $listing = Listing::factory()->live()->for($branch)->create([
            'price' => 300_000,
        ]);

        $this->assertDatabaseCount('alerts', 1);

        $listing->update(['price' => 290_000]);

        $this->assertDatabaseCount('alerts', 1);
    }

    public function test_two_matching_searches_create_only_one_alert_per_user(): void
    {
        $user = User::factory()->create(['email' => 'demo@street.example']);
        $branch = Branch::factory()->create(['region' => 'Bath']);

        SavedSearch::factory()->for($user)->create([
            'property_type' => PropertyType::Flat,
            'max_price' => null,
            'min_bedrooms' => null,
            'region' => null,
        ]);
        SavedSearch::factory()->for($user)->create([
            'property_type' => null,
            'max_price' => 500_000,
            'min_bedrooms' => null,
            'region' => 'Bath',
        ]);

        Listing::factory()->live()->for($branch)->create([
            'property_type' => PropertyType::Flat,
            'price' => 275_000,
            'bedrooms' => 2,
        ]);

        $this->assertDatabaseCount('alerts', 1);
        $this->assertSame(1, Alert::query()->where('user_id', $user->id)->count());
    }
}
