<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Project;
use App\Repositories\InvoiceRepositoryInterface;
use Illuminate\Http\Request;


class InvoiceController extends BaseController
{
    private $repository;

    public function __construct(InvoiceRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }


    public function getInvoicesForProject($projectId)
    {
        // Retrieve the project
        $project = Project::find($projectId);

        if (!$project) {
            return response()->json([
                'status' => false,
                'message' => 'Project not found.',
                'data' => null
            ], 404);
        }

        // Retrieve all Invoices related to the project
        $Invoices = Invoice::where('project_id', $projectId)->get();

        // Check if there are any Invoices
        if ($Invoices->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No Invoices found for this project.',
                'data' => []
            ], 200);
        }

        // Return Invoices with a success response
        return response()->json([
            'status' => true,
            'message' => 'Invoices retrieved successfully.',
            'data' => $Invoices
        ], 200);
    }
}
