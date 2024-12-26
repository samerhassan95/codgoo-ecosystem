<?php

namespace App\Http\Controllers;

use App\Http\Requests\SkillRequest;
use App\Http\Resources\SkillResource;
use App\Models\Skill;
use App\Repositories\SkillRepositoryInterface;
use App\Services\ImageService;
use Illuminate\Http\Request;

class SkillController extends BaseController
{
    private $repository;

    public function __construct(SkillRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:skills',
            'icon' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $skillData = collect($validatedData)->except(['icon'])->toArray();

        if ($request->hasFile('icon')) {
            $imagePath = ImageService::upload($request->file('icon'), 'skill_images');
            $skillData['icon'] = $imagePath;
        }

        $skill = Skill::create($skillData);

        return response()->json(new SkillResource($skill), 201); // تمرير النموذج للموارد
    }

    public function update(Request $request, $id)
    {
        $skill = Skill::find($id);
    
        if (!$skill) {
            return response()->json(['message' => 'Skill not found.'], 404);
        }
    
        $validatedData = $request->validate([
            'name' => 'nullable|string|max:255|unique:skills,name,' . $id,
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
    
        $skillData = collect($validatedData)->except(['icon'])->toArray();
    
        if ($request->hasFile('icon')) {
            if ($skill->icon) {
                ImageService::delete($skill->icon);
            }
    
            $imagePath = ImageService::upload($request->file('icon'), 'skill_images');
            $skillData['icon'] = $imagePath;
        }
    
        $skill->update($skillData);
    
        return response()->json(new SkillResource($skill), 200);
    }
    

    public function assignSkillsToEmployee(Request $request, $employeeId)
    {
        $request->validate([
            'skills' => 'required|array|min:1',
            'skills.*' => 'exists:skills,id',
        ]);

        $skills = $this->repository->assignSkillsToEmployee($employeeId, $request->skills);

        return response()->json([
            'message' => 'Skills assigned successfully.',
            'skills' => SkillResource::collection($skills),
        ], 200);
    }

    public function removeSkillFromEmployee(Request $request, $employeeId, $skillId)
    {
        $this->repository->removeSkillFromEmployee($employeeId, $skillId);

        return response()->json([
            'message' => 'Skill removed successfully.',
        ], 200);
    }

    
}
