<?php

// app/Http/Controllers/Marketplace/ServicesAppController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceAppResource;
use App\Http\Resources\BusinessAppResource;
use App\Models\ServiceApp;
use App\Models\BusinessApp;
use Illuminate\Http\Request;

class ServicesAppController extends Controller
{
    /**
     * Endpoint 1: GET /api/Marketplace/ServicesApps
     * Handles optional filtering by 'type' query parameter.
     */
 public function index(Request $request)
    {
        // Start building the query
        $query = ServiceApp::query();

        // Optional type filter
        if ($request->filled('type')) {
            $type = $request->input('type');
            if (in_array($type, ['General', 'Bussiness'])) {
                $query->where('type', $type);
            }
        }

        // Optional marketplace filters
        if ($request->filled('has_free_trial')) {
            $query->where('has_free_trial', (bool) $request->has_free_trial);
        }

        if ($request->filled('pricing_type')) {
            $query->where('pricing_type', $request->pricing_type);
        }

        // Sorting
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'popular':
                    $query->orderByDesc('installs');
                    break;

                case 'rating':
                    $query->orderByDesc('rating_average');
                    break;

                case 'latest':
                    $query->orderByDesc('last_update');
                    break;
            }
        }

        // Fetch services (no pagination)
        $services = $query->get();

        // Return JSON response
        return response()->json([
            'status' => true,
            'services' => ServiceAppResource::collection($services),
        ]);
    }

}
