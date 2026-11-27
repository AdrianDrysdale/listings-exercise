<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSavedSearchRequest;
use App\Http\Resources\SavedSearchResource;
use App\Models\SavedSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SavedSearchController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $searches = $user->savedSearches()
            ->latest('id')
            ->get();

        return Inertia::render('SavedSearches/Index', [
            'savedSearches' => SavedSearchResource::collection($searches),
        ]);
    }

    public function store(StoreSavedSearchRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $user->savedSearches()->create([
            'property_type' => $request->input('property_type') ?: null,
            'max_price' => $request->input('max_price') ?: null,
            'min_bedrooms' => $request->input('min_bedrooms') ?: null,
            'region' => $request->input('region') ?: null,
        ]);

        return redirect()
            ->route('saved-searches.index')
            ->with('success', 'Search saved.');
    }

    public function destroy(Request $request, SavedSearch $savedSearch): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);
        abort_unless($savedSearch->user_id === $user->id, 404);

        $savedSearch->delete();

        return redirect()
            ->route('saved-searches.index')
            ->with('success', 'Search deleted.');
    }
}
