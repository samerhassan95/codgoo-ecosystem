<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketRequest;
use App\Http\Resources\TicketReplyResource;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\Department;
use App\Repositories\TicketRepositoryInterface;
use App\Services\ImageService;
use Illuminate\Http\Request;

class TicketController extends BaseController
{
    private $repository;

    public function __construct(TicketRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }

    /**
     * Get all departments for ticket creation
     */
    public function getDepartments()
    {
    $departments = Department::select('id', 'name')->get();
    
    // Transform department names based on language
    $departments->transform(function ($department) {
        $department->name = $this->localizedText($department->name);
        return $department;
    });        
        return response()->json([
            'status' => true,
            'message' => 'Departments retrieved successfully.',
            'data' => $departments
        ]);
    }

    /**
     * Store new ticket with attachment
     */
public function store(Request $request)
{
    $validatedData = $request->validate([
        'subject' => 'required|string|max:255',
        'department_id' => 'required|integer|exists:departments,id',
        'priority' => 'required|in:High,Medium,Low',
        'message' => 'required|string',
        'status' => 'nullable|in:pending,open,closed,answered',
        'attachments' => 'nullable|array',
        'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
    ]);

    $ticketData = [
        'subject' => $validatedData['subject'],
        'department_id' => $validatedData['department_id'],
        'priority' => $validatedData['priority'],
        'message' => $validatedData['message'],
        'status' => $validatedData['status'] ?? 'pending',
        'created_by' => auth()->id(),
        'attachments' => null,
    ];

    // Handle multiple attachments
    if ($request->hasFile('attachments')) {
        $uploadedFiles = [];
        foreach ($request->file('attachments') as $file) {
            $path = ImageService::upload($file, 'tickets');
            $uploadedFiles[] = $path;
        }
        $ticketData['attachments'] = json_encode($uploadedFiles);
    }

    $ticket = Ticket::create($ticketData);
    $ticket->load('department', 'client', 'replies');

    return response()->json([
        'status' => true,
        'message' => 'Ticket created successfully.',
        'data' => new TicketResource($ticket)
    ], 201);
}

    /**
     * Update ticket
     */
    public function update(Request $request, $ticketId)
    {
        $validatedData = $request->validate([
            'subject' => 'nullable|string|max:255',
            'department_id' => 'nullable|integer|exists:departments,id',
            'priority' => 'nullable|in:High,Medium,Low',
            'message' => 'nullable|string',
            'status' => 'nullable|in:pending,open,closed,answered',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        $ticket = Ticket::findOrFail($ticketId);

        // Check ownership
        if ($ticket->created_by !== auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized to update this ticket.'
            ], 403);
        }

        $ticketData = collect($validatedData)->except(['attachments'])->toArray();

        // Handle attachments
        if ($request->hasFile('attachments')) {
            // Delete old attachments if needed
            if ($ticket->attachments) {
                $oldAttachments = json_decode($ticket->attachments, true);
                foreach ($oldAttachments as $oldPath) {
                    $fullPath = public_path('storage/' . $oldPath);
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
            }

            // Upload new attachments
            $attachments = [];
            foreach ($request->file('attachments') as $file) {
                $attachmentPath = ImageService::upload($file, 'tickets');
                $attachments[] = $attachmentPath;
            }
            $ticketData['attachments'] = json_encode($attachments);
        }

        $ticket->update($ticketData);

        return response()->json([
            'status' => true,
            'message' => 'Ticket updated successfully.',
            'data' => new TicketResource($ticket)
        ], 200);
    }

    /**
     * Get tickets for authenticated client with filters
     */
    public function getTicketsForClient(Request $request)
    {
        $client = $request->user();

        $statusMapping = [
            0 => 'all',
            1 => 'open',
            2 => 'closed',
            3 => 'answered',
            4 => 'pending',
        ];

        $status = $request->query('status');
        $priority = $request->query('priority'); // High, Medium, Low
        $departmentId = $request->query('department_id');
        $search = $request->query('search');

        $ticketsQuery = Ticket::where('created_by', $client->id)
            ->with(['department', 'replies'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($status && isset($statusMapping[$status])) {
            $statusString = $statusMapping[$status];
            if ($statusString !== 'all') {
                $ticketsQuery->where('status', $statusString);
            }
        }

        // Filter by priority
        if ($priority) {
            $ticketsQuery->where('priority', $priority);
        }

        // Filter by department
        if ($departmentId) {
            $ticketsQuery->where('department_id', $departmentId);
        }

        // Search by subject or message
        if ($search) {
            $ticketsQuery->where(function($query) use ($search) {
                $query->where('subject', 'LIKE', "%{$search}%")
                      ->orWhere('message', 'LIKE', "%{$search}%");
            });
        }

        $tickets = $ticketsQuery->paginate(10);

        return response()->json([
            'status' => true,
            'message' => 'Tickets retrieved successfully.',
            'data' => TicketResource::collection($tickets),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
                'last_page' => $tickets->lastPage(),
            ]
        ]);
    }

    /**
     * Get tickets summary and list
     */
    public function getTicketsAndSummary(Request $request)
    {
        $client = $request->user();

        $openCount = Ticket::where('created_by', $client->id)->where('status', 'open')->count();
        $closedCount = Ticket::where('created_by', $client->id)->where('status', 'closed')->count();
        $answeredCount = Ticket::where('created_by', $client->id)->where('status', 'answered')->count();
        $pendingCount = Ticket::where('created_by', $client->id)->where('status', 'pending')->count();
        $totalCount = Ticket::where('created_by', $client->id)->count();

        $tickets = Ticket::where('created_by', $client->id)
            ->with(['department', 'replies'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'status' => true,
            'message' => 'Tickets and summary retrieved successfully.',
            'data' => [
                'summary' => [
                    'total' => $totalCount,
                    'open' => $openCount,
                    'closed' => $closedCount,
                    'answered' => $answeredCount,
                    'pending' => $pendingCount,
                ],
                'tickets' => TicketResource::collection($tickets),
                'pagination' => [
                    'current_page' => $tickets->currentPage(),
                    'from' => $tickets->firstItem(),
                    'to' => $tickets->lastItem(),
                    'per_page' => $tickets->perPage(),
                    'total' => $tickets->total(),
                    'last_page' => $tickets->lastPage(),
                ]
            ]
        ]);
    }

    /**
     * Get single ticket details with replies
     */
    public function show($ticketId)
    {
        $client = auth()->user();
        
        $ticket = Ticket::with(['department', 'replies.creator'])
            ->where('id', $ticketId)
            ->where('created_by', $client->id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'status' => false,
                'message' => 'Ticket not found or unauthorized.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Ticket retrieved successfully.',
            'data' => new TicketResource($ticket),
        ]);
    }

    /**
     * Get replies for a ticket
     */
    public function getRepliesForTicket($ticketId)
    {
        $client = auth()->user();
        
        $ticket = Ticket::where('id', $ticketId)
            ->where('created_by', $client->id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'status' => false,
                'message' => 'Ticket not found or unauthorized.',
            ], 404);
        }

        $replies = $ticket->replies()
            ->with('creator')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Replies retrieved successfully.',
            'data' => TicketReplyResource::collection($replies),
        ]);
    }

    /**
     * Close ticket
     */
    public function closeTicket($ticketId)
    {
        $client = auth()->user();
        
        $ticket = Ticket::where('id', $ticketId)
            ->where('created_by', $client->id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'status' => false,
                'message' => 'Ticket not found or unauthorized.',
            ], 404);
        }

        $ticket->update(['status' => 'closed']);

        return response()->json([
            'status' => true,
            'message' => 'Ticket closed successfully.',
            'data' => new TicketResource($ticket),
        ]);
    }

    /**
     * Delete ticket
     */
    public function destroy($ticketId)
    {
        $client = auth()->user();
        
        $ticket = Ticket::where('id', $ticketId)
            ->where('created_by', $client->id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'status' => false,
                'message' => 'Ticket not found or unauthorized.',
            ], 404);
        }

        // Delete attachments
        if ($ticket->attachments) {
            $attachments = json_decode($ticket->attachments, true);
            foreach ($attachments as $path) {
                $fullPath = public_path('storage/' . $path);
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
        }

        $ticket->delete();

        return response()->json([
            'status' => true,
            'message' => 'Ticket deleted successfully.',
        ]);
    }
    
    
    private function localizedText(?string $text): ?string
{
    if (!$text) {
        return null;
    }

    $lang = request()->query('lang')
        ?? request()->header('Accept-Language', 'en');

    if (!str_contains($text, '|')) {
        return $text;
    }

    [$ar, $en] = array_map('trim', explode('|', $text, 2));

    return strtolower($lang) === 'ar' ? $ar : $en;
}
}