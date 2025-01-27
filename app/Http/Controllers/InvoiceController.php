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

        $invoices = QueryBuilder::for(Invoice::class)
            ->where('project_id', $projectId)
            ->with('milestone')
            ->allowedFilters([
                'status',
                'payment_method',
                'due_date',
            ])
            ->get();

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



}
