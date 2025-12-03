<?php

// app/Http/Controllers/Marketplace/ServicesAppController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ServiceApp;
use App\Http\Resources\ServiceAppResource;
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

        // Check for the optional 'type' filter
        if ($request->filled('type')) {
            $type = $request->input('type');
            // Ensure the type is valid before applying the filter
            if (in_array($type, ['General', 'Master'])) {
                $query->where('type', $type);
            }
        }

        // Fetch the results
        $services = $query->get();

        // Return the collection using the defined Resource
        return ServiceAppResource::collection($services);
    }
}
