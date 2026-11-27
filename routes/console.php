<?php

use App\Enums\PropertyType;
use App\Models\Branch;
use App\Models\Listing;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Reviewer helper: ensure the demo user has a saved search, create a matching
 * live listing (fires ListingObserver), and report the new alert.
 */
Artisan::command('alerts:demo', function () {
    $user = User::query()->orderBy('id')->first();

    if ($user === null) {
        $this->error('No users found. Run: php artisan migrate --seed');

        return 1;
    }

    $branch = Branch::query()->first();

    if ($branch === null) {
        $this->error('No branches found. Run: php artisan migrate --seed');

        return 1;
    }

    // Always create a search tied to this branch so the listing we publish is
    // guaranteed to match (reviewers may have odd leftover criteria).
    $search = SavedSearch::query()->create([
        'user_id' => $user->id,
        'property_type' => PropertyType::Flat,
        'max_price' => 400_000,
        'min_bedrooms' => 1,
        'region' => $branch->region,
    ]);

    $listing = Listing::factory()->live()->for($branch)->create([
        'property_type' => PropertyType::Flat,
        'price' => 275_000,
        'bedrooms' => 2,
        'address_line_1' => '1 Alert Demo Street',
        'city' => $branch->region,
    ]);

    $alert = $user->alerts()->where('listing_id', $listing->id)->first();

    if ($alert === null) {
        $this->warn('Listing created but no alert matched. Check saved-search criteria vs listing.');
        $this->line("Listing #{$listing->id} · search #{$search->id}");

        return 1;
    }

    $this->info("Alert #{$alert->id} created for {$user->email}");
    $this->line("Listing: {$listing->address_line_1}, {$listing->city}");
    $this->line('Open /alerts in the app to see it.');

    return 0;
})->purpose('Create a matching live listing so a saved-search alert fires');
