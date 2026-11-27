<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlertResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $alerts = $user->alerts()
            ->with(['listing.branch', 'savedSearch'])
            ->latest('id')
            ->get();

        return Inertia::render('Alerts/Index', [
            'alerts' => AlertResource::collection($alerts),
        ]);
    }
}
