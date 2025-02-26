<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContractResource;
use App\Services\ImageService;
use Illuminate\Http\Request;
use App\Models\Contract;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

class ContractController extends Controller
{
    public function uploadContract(Request $request, $projectId)
    {
        $request->validate([
            'file' => 'required|mimes:pdf,doc,docx|max:2048',
        ]);
    
        $project = Project::findOrFail($projectId);
        $admin = auth()->user();
    
        $filePath = ImageService::upload($request->file('file'), 'contracts');
    
        $contract = Contract::create([
            'project_id' => $project->id,
            'admin_id' => $admin->id,
            'file_path' => asset($filePath),
        ]);
    
        return response()->json([
            'message' => 'Contract uploaded successfully',
            'contract' => new ContractResource($contract),
        ]);
    }
    

    public function signContract($contractId)
    {
        $contract = Contract::findOrFail($contractId);
        $client = auth()->user();

        if ($contract->project->client_id !== $client->id) {
            return response()->json(['message' => 'You are not authorized to sign this contract'], 403);
        }

        $contract->update([
            'status' => 'signed',
            'signed_at' => now(),
        ]);

        return response()->json(['message' => 'Contract signed successfully', 'contract' => $contract]);
    }

    public function getContractDetails($contractId)
    {
        try {
            $contract = Contract::with('project')->findOrFail($contractId);
            return response()->json([
                'status' => true,
                'message' => 'Contract retrieved successfully',
                'data' => new ContractResource($contract)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Contract not found'
            ], 404);
        }
    }



    public function getClientContracts()
    {
        $client = Auth::user();
    
        $contracts = Contract::whereHas('project', function ($query) use ($client) {
        })->with(['project' => function ($query) {
            $query->select('id', 'name');
        }])->latest()->get();
    
        $data = $contracts->map(function ($contract) {
            return [
                'id' => $contract->id,
                'project_name' => $contract->project->name ?? 'No Project',
                'status' => $contract->status ?? 'Pending',
                'file_path' => asset($contract->file_path),
                'created_at' => $contract->created_at->format('Y-m-d H:i:s'),
            ];
        });
    
        return response()->json([
            'status' => true,
            'message' => 'Contracts retrieved successfully',
            'data' => $data,
        ]);
    }
    

}
