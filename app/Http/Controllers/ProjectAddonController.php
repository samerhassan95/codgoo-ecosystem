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

    

}
