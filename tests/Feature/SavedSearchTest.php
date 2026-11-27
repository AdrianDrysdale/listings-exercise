<?php

namespace Tests\Feature;

use App\Enums\PropertyType;
use App\Models\Branch;
use App\Models\Listing;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SavedSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_the_current_users_searches(): void
    {
        $user = User::factory()->create(['email' => 'demo@street.example']);
        $other = User::factory()->create();

        SavedSearch::factory()->for($user)->create([
            'property_type' => PropertyType::Flat,
            'max_price' => null,
            'min_bedrooms' => null,
            'region' => null,
        ]);
        SavedSearch::factory()->for($other)->create([
            'property_type' => PropertyType::Detached,
            'max_price' => null,
            'min_bedrooms' => null,
            'region' => null,
        ]);

        $this->get('/saved-searches')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('SavedSearches/Index')
                ->has('savedSearches', 1)
                ->where('savedSearches.0.property_type', 'flat')
            );
    }

    public function test_store_creates_a_saved_search_from_criteria(): void
    {
        User::factory()->create(['email' => 'demo@street.example']);

        $this->post('/saved-searches', [
            'property_type' => 'flat',
            'max_price' => 350_000,
            'min_bedrooms' => 2,
            'region' => 'Bristol',
        ])
            ->assertRedirect(route('saved-searches.index'));

        $this->assertDatabaseHas('saved_searches', [
            'property_type' => 'flat',
            'max_price' => 350_000,
            'min_bedrooms' => 2,
            'region' => 'Bristol',
        ]);
    }

    public function test_store_rejects_empty_criteria(): void
    {
        User::factory()->create(['email' => 'demo@street.example']);

        $this->from('/')
            ->post('/saved-searches', [])
            ->assertSessionHasErrors('criteria');

        $this->assertDatabaseCount('saved_searches', 0);
    }

    public function test_store_rejects_invalid_property_type(): void
    {
        User::factory()->create(['email' => 'demo@street.example']);

        $this->from('/')
            ->post('/saved-searches', ['property_type' => 'castle'])
            ->assertSessionHasErrors('property_type');
    }

    public function test_destroy_deletes_own_search(): void
    {
        $user = User::factory()->create(['email' => 'demo@street.example']);
        $search = SavedSearch::factory()->for($user)->create([
            'property_type' => PropertyType::Flat,
            'max_price' => null,
            'min_bedrooms' => null,
            'region' => null,
        ]);

        $this->delete("/saved-searches/{$search->id}")
            ->assertRedirect(route('saved-searches.index'));

        $this->assertDatabaseMissing('saved_searches', ['id' => $search->id]);
    }

    public function test_destroy_returns_not_found_for_another_users_search(): void
    {
        User::factory()->create(['email' => 'demo@street.example']);
        $other = User::factory()->create();
        $search = SavedSearch::factory()->for($other)->create([
            'property_type' => PropertyType::Flat,
            'max_price' => null,
            'min_bedrooms' => null,
            'region' => null,
        ]);

        $this->delete("/saved-searches/{$search->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('saved_searches', ['id' => $search->id]);
    }

    public function test_saving_a_search_does_not_backfill_alerts_for_existing_live_listings(): void
    {
        $user = User::factory()->create(['email' => 'demo@street.example']);
        $branch = Branch::factory()->create(['region' => 'Bristol']);

        Listing::factory()->live()->for($branch)->create([
            'property_type' => PropertyType::Flat,
            'price' => 250_000,
            'bedrooms' => 2,
        ]);

        $this->post('/saved-searches', [
            'property_type' => 'flat',
            'max_price' => 300_000,
            'region' => 'Bristol',
        ])->assertRedirect();

        $this->assertDatabaseCount('alerts', 0);
        $this->assertDatabaseCount('saved_searches', 1);
        $this->assertTrue($user->savedSearches()->exists());
    }
}
