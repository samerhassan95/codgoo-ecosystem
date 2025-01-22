<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Project;
use App\Repositories\InvoiceRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;


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
    // Retrieve the project with its related creator
    $project = Project::find($projectId);

    if (!$project) {
        return response()->json([
            'status' => false,
            'message' => 'Project not found.',
            'data' => null
        ], 404);
    }

    // Retrieve all invoices related to the project
    $invoices = Invoice::where('project_id', $projectId)
        ->with('milestone') // Optionally load milestone if needed
        ->get();

    // Check if there are any invoices
    if ($invoices->isEmpty()) {
        return response()->json([
            'status' => true,
            'message' => 'No invoices found for this project.',
            'data' => []
        ], 200);
    }

    // Get the creator's name or username
    $creator = null;
    $creatorName = 'Unknown'; 
    if ($project->created_by_type == 'App\Models\Client') {
        $creator = Client::find($project->created_by_id);
        $creatorName = $creator ? $creator->name : 'Unknown';
    } elseif ($project->created_by_type == 'App\Models\Admin') {
        $creator = Admin::find($project->created_by_id);
        $creatorName = $creator ? $creator->username : 'Unknown'; // Use 'username' for Admin
    }

    // Map the invoices and include the creator's name or username
    $invoiceData = $invoices->map(function ($invoice) use ($project, $creatorName) {
        return [
            'status' => $invoice->status,
            'payment_method' => $invoice->payment_method,
            'created_at' => $invoice->created_at->toDateTimeString(),
            'due_date' => $invoice->due_date,
            'project_name' => $project->name,
            'client_name' => $creatorName, // Include the name/username
        ];
    });

    return response()->json([
        'status' => true,
        'message' => 'Invoices retrieved successfully.',
        'data' => $invoiceData
    ], 200);
}

    



    public function getInvoiceStatusCounts()
    {
        $user = auth()->user();

        // Ensure the user is authenticated and is a Client
        if (!$user || $user instanceof \App\Models\Admin) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        // Get all the projects for the logged-in client, with related invoices
        $projects = Project::where('created_by_id', $user->id)
            ->where('created_by_type', 'Client')
            ->with('invoices') // Ensure invoices are loaded
            ->get();

        $invoiceCounts = [
            'paid' => 0,
            'unpaid' => 0,
            'overdue' => 0,
            'total' => 0, // To track the total number of invoices
        ];

        // Calculate invoice status counts
        foreach ($projects as $project) {
            foreach ($project->invoices as $invoice) {
                // Increment total invoice count
                $invoiceCounts['total']++;

                // Count invoices by their status
                if ($invoice->status === 'paid') {
                    $invoiceCounts['paid']++;
                } elseif ($invoice->status === 'unpaid') {
                    // Check for overdue invoices based on the due date
                    if (Carbon::parse($invoice->due_date)->isPast()) {
                        $invoiceCounts['overdue']++;
                    } else {
                        $invoiceCounts['unpaid']++;
                    }
                }
            }
        }

        return response()->json([
            'status' => true,
            'data' => $invoiceCounts,
        ]);
    }



}
