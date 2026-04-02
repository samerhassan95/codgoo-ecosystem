<?php
namespace App\Http\Controllers;

use App\Http\Requests\AddonRequest;
use App\Http\Resources\AddonResource;
use App\Repositories\AddonRepositoryInterface;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Addon;


class AddonController extends BaseController
{
    private $repository;

    public function __construct(AddonRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }
    private function localizedText(?string $text): ?string
{
    if (!$text) {
        return null;
    }

    $lang = request()->query('lang')
        ?? request()->header('Accept-Language', 'en');

    if (!str_contains($text, '|')) {
        return $text;
    }

    [$ar, $en] = array_map('trim', explode('|', $text, 2));

    return strtolower($lang) === 'ar' ? $ar : $en;
}



public function index(Request $request)
{
    $perPage = $request->query('per_page', 15);

    $addons = Addon::paginate($perPage);

    $addons->getCollection()->transform(function ($addon) {
        $addon->name = $this->localizedText($addon->name);
        $addon->description = $this->localizedText($addon->description);
        return $addon;
    });

    return AddonResource::collection($addons);
}

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'icon' => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048', // Use 'image' validation for icons
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->toArray()], 422);
        }

        // Handle image upload
        $iconPath = null;
        if ($request->hasFile('icon')) {
            $iconPath = ImageService::upload($request->file('icon'), 'addons');
        }

        // Add the uploaded icon path to the validated data
        $validatedData = $validator->validated();
        $validatedData['icon'] = $iconPath;

        // Create the addon using the repository
        $addon = $this->repository->create($validatedData);

        return new AddonResource($addon);
    }

    public function update(Request $request, $id)
{
    $addon = $this->repository->find($id);

    if (!$addon) {
        return response()->json(['message' => 'Addon not found.'], 404);
    }

    $validator = Validator::make($request->all(), [
        'name' => 'nullable|string|max:255',
        'icon' => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048', // Use 'image' validation for icons
        'description' => 'nullable|string',
        'price' => 'nullable|numeric|min:0',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()->toArray()], 422);
    }

    $validatedData = $validator->validated();

    // Handle file uploads
    if ($request->hasFile('icon')) {
        // Delete the old icon if it exists
        if ($addon->icon) {
            ImageService::delete($addon->icon);
        }

        // Upload the new icon
        $validatedData['icon'] = ImageService::upload($request->file('icon'), 'addons');
    }

    // Update the addon using the repository
    $updatedAddon = $this->repository->update($id, $validatedData);

    return new AddonResource($updatedAddon);
}

    
    
    

}
