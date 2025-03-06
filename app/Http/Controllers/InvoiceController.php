<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Milestone;
use App\Models\Project;
use App\Repositories\InvoiceRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\QueryBuilder\QueryBuilder;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

class InvoiceController extends BaseController
{
    private $repository;
    private $firebaseService;

    public function __construct(InvoiceRepositoryInterface $repository, FirebaseService $firebaseService)
    {
        parent::__construct($repository);
        $this->repository = $repository;
        $this->firebaseService = $firebaseService;
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'milestone_id' => 'required|exists:milestones,id',
            'project_id' => 'required|exists:projects,id',
            'amount' => 'nullable|numeric|min:0',
            'due_date' => 'required|date|after:today',
        ]);

        $milestone = Milestone::findOrFail($validated['milestone_id']);

        $invoice = Invoice::create([
            'milestone_id' => $validated['milestone_id'],
            'project_id' => $validated['project_id'],
            'amount' => $validated['amount'] ?? $milestone->amount,
            'due_date' => $validated['due_date'],
            'status' => 'unpaid',
        ]);

        $this->sendInvoiceNotification($invoice);

        return response()->json([
            'status' => true,
            'message' => 'Invoice created successfully!',
            'data' => new InvoiceResource($invoice),
        ], 201);
    }


    private function sendInvoiceNotification(Invoice $invoice)
    {
        $client = $invoice->project->client;

        if (!$client || !$client->device_token) {
            Log::warning('Client not found or has no device token for invoice notification.', [
                'invoice_id' => $invoice->id,
                'client_id' => $client ? $client->id : null
            ]);
            return;
        }

        $template = \App\Models\NotificationTemplate::where('type', 'invoice_created')->first();
        if (!$template) {
            Log::error('Notification template "invoice_created" not found.');
            return;
        }

        $title = $template->title;
        $message = str_replace(
            ['{invoice_id}', '{amount}', '{due_date}'],
            ['INV-' . $invoice->id, number_format($invoice->amount, 2), $invoice->due_date->format('d-m-Y')],
            $template->message
        );

        try {
            $dataPayload = [
                'invoice_id' => $invoice->id,
                'notification_type' => 'invoice_created',
            ];
            $this->firebaseService->sendNotification($client->device_token, $title, $message,$dataPayload );

            app(\App\Repositories\NotificationRepository::class)->createNotification($client, $title, $message, $client->device_token, 'invoice_created');

            Log::info('Invoice notification sent successfully.', ['client_id' => $client->id, 'invoice_id' => $invoice->id]);
        } catch (\Exception $e) {
            Log::error('Error sending invoice notification: ' . $e->getMessage());
        }
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

        $client = Client::find($project->client_id);
        $creatorName = $client ? $client->name : 'Unknown';

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

        $projects = Project::where('client_id', $user->id)
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
            $query->where('client_id', $client->id);
        })->with('project.client')->get();

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

        // Determine overdue status for unpaid invoices
        $status = $invoice->status;
        if ($status === 'unpaid' && $invoice->due_date && Carbon::parse($invoice->due_date)->isPast()) {
            $status = 'overdue';
        }

        $formattedInvoice = [
            'id' => $invoice->id,
            'invoice_id' => 'INV-' . $invoice->id,
            'status' => ucfirst($status), // Overdue or Paid/Unpaid
            'created_at' => $invoice->created_at->format('d-m-Y'),
            'due_date' => $invoice->due_date ? $invoice->due_date : null,
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
