<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Repositories\InvoiceRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\QueryBuilder\QueryBuilder;


class InvoiceController extends BaseController
{
    private $repository;

    public function __construct(InvoiceRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }


    public function getInvoicesForProject($projectId, Request $request)
    {
        $project = Project::find($projectId);
    
        if (!$project) {
            return response()->json([
                'status' => false,
                'message' => 'Project not found.',
                'data' => null
            ], 404);
        }
    
        // Apply the filters and only include allowed ones
        $invoicesQuery = QueryBuilder::for(Invoice::class)
            ->where('project_id', $projectId)
            ->with('milestone');
    
        // Apply status filter if it's provided in the query string
        $invoicesQuery->allowedFilters([
            'status', // status can be 'paid', 'unpaid', etc.
            'payment_method',
            'due_date',
        ]);
    
        // Get the invoices
        $invoices = $invoicesQuery->get();
    
        if ($invoices->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No invoices found for this project.',
                'data' => []
            ], 200);
        }
    
        $creatorName = 'Unknown';
        if ($project->created_by_type == 'App\Models\Client') {
            $creator = Client::find($project->created_by_id);
            $creatorName = $creator ? $creator->name : 'Unknown';
        } elseif ($project->created_by_type == 'App\Models\Admin') {
            $creator = Admin::find($project->created_by_id);
            $creatorName = $creator ? $creator->username : 'Unknown';
        }
    
        // Transform invoice data for response
        $invoiceData = $invoices->map(function ($invoice) use ($project, $creatorName) {
            return [
                'status' => $invoice->status,
                'payment_method' => $invoice->payment_method,
                'created_at' => $invoice->created_at->toDateTimeString(),
                'due_date' => $invoice->due_date,
                'amount' => $invoice->amount,
                'project_name' => $project->name,
                'client_name' => $creatorName,
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

        if (!$user || $user instanceof \App\Models\Admin) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $projects = Project::where('created_by_id', $user->id)
            ->where('created_by_type', 'Client')
            ->with('invoices')
            ->get();

        $invoiceCounts = [
            'paid' => 0,
            'unpaid' => 0,
            'overdue' => 0,
            'total' => 0,
        ];

        foreach ($projects as $project) {
            foreach ($project->invoices as $invoice) {
                $invoiceCounts['total']++;

                if ($invoice->status === 'paid') {
                    $invoiceCounts['paid']++;
                } elseif ($invoice->status === 'unpaid') {
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

  
    public function getInvoicesForClient(Request $request)
{
    $client = auth()->user();
    
    // Fetch invoices for the client
    $invoices = Invoice::whereHas('project', function ($query) use ($client) {
        $query->where('created_by_id', $client->id);
    })->with('project.creator')->get();

    if ($invoices->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No invoices found.',
            'data' => []
        ], 404);
    }

    $formattedInvoices = $invoices->map(function ($invoice) {
        // Determine overdue status for unpaid invoices
        $status = $invoice->status;
        if ($status === 'unpaid' && $invoice->due_date && Carbon::parse($invoice->due_date)->isPast()) {
            $status = 'overdue';
        }

        return [
            'id' => $invoice->id,
            'invoice_id' => 'INV-' . $invoice->id,
            'client_name' => auth()->user()->name,
            'created_at' => $invoice->created_at->format('d-m-Y'),
            'amount' => number_format($invoice->amount, 2),
            'status' => ucfirst($status), // Overdue or Paid/Unpaid
        ];
    });

    return response()->json([
        'status' => true,
        'message' => 'Invoices retrieved successfully.',
        'data' => $formattedInvoices
    ], 200);
}

    
    public function getInvoiceDetails($invoiceId)
    {
        $invoice = Invoice::with([
            'milestone.tasks', 
            'project' 
        ])->find($invoiceId);
    
        if (!$invoice) {
            return response()->json([
                'status' => false,
                'message' => 'Invoice not found.',
                'data' => []
            ], 404);
        }
    
        $formattedInvoice = [
            'id' => $invoice->id,
            'invoice_id' => 'INV-' . $invoice->id,
            'status' => ucfirst($invoice->status),
            'created_at' => $invoice->created_at->format('d-m-Y'),
            'due_date' => $invoice->due_date ? $invoice->due_date: null,
            'payment_type' => $invoice->payment_method ?? 'N/A',
            'amount' => number_format($invoice->amount, 2),
            'tasks' => $invoice->milestone
                ? $invoice->milestone->tasks->map(function ($task) {
                    return [
                        'task_id' => $task->id,
                        'task_name' => $task->label,
                        'status' => ucfirst($task->status),
                        'created_at' => $task->created_at,
                    ];
                })
                : []
        ];
    
        return response()->json([
            'status' => true,
            'message' => 'Invoice details retrieved successfully.',
            'data' => $formattedInvoice
        ], 200);
    }
    

}
