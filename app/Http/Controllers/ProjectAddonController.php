<?php

namespace App\Http\Controllers;

use App\Repositories\ProjectAddonRepositoryInterface;
use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ProjectAddons;

class ProjectAddonController extends BaseController
{
    public function __construct(ProjectAddonRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'addon_id' => 'required|exists:addons,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ProjectAddon = ProjectAddons::create($validator->validated());

        return response()->json($ProjectAddon, 201);
    }

    public function update(Request $request, $id)
    {
        $project = Project::find($id);

        if (!$project) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        $validatedData = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
            'status' => 'nullable|string|in:approved,not_approved,canceled',
            'attachments.*' => 'file|max:10240', // Max 10MB
        ]);

        $user = auth()->user();
        $type = $user instanceof \App\Models\Admin ? 'Admin' : 'Client';

        if ($type === 'Client' && isset($validatedData['price'])) {
            return response()->json([
                'status' => false,
                'message' => 'Only Admin can update the price.',
            ], 403); // Forbidden
        }

        $project->update(collect($validatedData)->except('attachments')->toArray());

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                // Optionally, delete old attachments (not required unless explicitly needed)
                $project->attachments()->delete();

                // Upload the new attachment
                $path = ImageService::upload($file, 'attachments');

                // Add the new attachment
                $project->attachments()->create([
                    'file_path' => $path,
                ]);
            }
        }

        // Return the updated project as a resource
        return response()->json(new ProjectResource($project->load('attachments')), 200);
    }

}
