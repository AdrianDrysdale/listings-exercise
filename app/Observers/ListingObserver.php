<?php

namespace App\Observers;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\SavedSearch;

class ListingObserver
{
    public function created(Listing $listing): void
    {
        if ($listing->status === ListingStatus::Live) {
            // Sync for now; at scale: MatchListingToSavedSearches::dispatch($listing).
            SavedSearch::createAlertsForLiveListing($listing);
        }
    }

    public function updated(Listing $listing): void
    {
        if ($listing->status !== ListingStatus::Live) {
            return;
        }

        // Only fire when the listing newly becomes live — not on every edit
        // while already live (price tweaks, etc.).
        if (! $listing->wasChanged('status')) {
            return;
        }

        // Sync for now; at scale: MatchListingToSavedSearches::dispatch($listing).
        SavedSearch::createAlertsForLiveListing($listing);
    }
}
