<?php
namespace App\Http\Controllers;

use App\Http\Requests\AddonRequest;
use App\Http\Resources\AddonResource;
use App\Repositories\AddonRepositoryInterface;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AddonController extends BaseController
{
    private $repository;

    public function __construct(AddonRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
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

    // public function update(Request $request, $id)
    // {
    //     $addon = $this->repository->find($id);
    
    //     if (!$addon) {
    //         return response()->json(['message' => 'Addon not found.'], 404);
    //     }
    
    //     $validator = Validator::make($request->all(), (new \App\Http\Requests\AddonRequest())->rules());
    
    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()->toArray()], 422);
    //     }
    
    //     $validatedData = $validator->validated();
    
    //     // Prepare files for update (icon in this case)
    //     if ($request->hasFile('icon')) {
    //         $validatedData['files'] = [
    //             'icon' => [
    //                 'file' => $request->file('icon'),
    //                 'path' => $addon->icon, // Old icon path
    //                 'directory' => 'product_media',
    //             ],
    //         ];
    //     }
    
    //     // Call the common update method in the repository
    //     $updatedAddon = $this->repository->update($id, $validatedData);
    
    //     return new AddonResource($updatedAddon);
    // }
    
    
    

}
