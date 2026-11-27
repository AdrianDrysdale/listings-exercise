<?php

namespace App\Models;

use App\Enums\ListingStatus;
use App\Enums\PropertyType;
use Database\Factories\SavedSearchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $user_id
 * @property PropertyType|null $property_type
 * @property int|null $max_price
 * @property int|null $min_bedrooms
 * @property string|null $region
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Alert> $alerts
 */
class SavedSearch extends Model
{
    /** @use HasFactory<SavedSearchFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'property_type',
        'max_price',
        'min_bedrooms',
        'region',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'property_type' => PropertyType::class,
            'max_price' => 'integer',
            'min_bedrooms' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Alert, $this>
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Null criteria are wildcards. A listing must be live and satisfy every
     * criterion that is set.
     */
    public function matches(Listing $listing): bool
    {
        if ($listing->status !== ListingStatus::Live) {
            return false;
        }

        if ($this->property_type !== null && $listing->property_type !== $this->property_type) {
            return false;
        }

        if ($this->max_price !== null && $listing->price > $this->max_price) {
            return false;
        }

        if ($this->min_bedrooms !== null && $listing->bedrooms < $this->min_bedrooms) {
            return false;
        }

        if ($this->region !== null) {
            $listing->loadMissing('branch');

            if ($listing->branch->region !== $this->region) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create at most one alert per user for a listing that has just become live.
     * When multiple saved searches match, the earliest search wins the link.
     */
    public static function createAlertsForLiveListing(Listing $listing): void
    {
        if ($listing->status !== ListingStatus::Live) {
            return;
        }

        $listing->loadMissing('branch');

        /** @var Collection<int, SavedSearch> $searches */
        $searches = static::query()->orderBy('id')->get();

        $alertedUserIds = [];

        foreach ($searches as $search) {
            if (isset($alertedUserIds[$search->user_id])) {
                continue;
            }

            if (! $search->matches($listing)) {
                continue;
            }

            Alert::query()->firstOrCreate(
                [
                    'user_id' => $search->user_id,
                    'listing_id' => $listing->id,
                ],
                [
                    'saved_search_id' => $search->id,
                ],
            );

            $alertedUserIds[$search->user_id] = true;
        }
    }
}
