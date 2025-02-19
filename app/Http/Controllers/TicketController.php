<?php
namespace App\Http\Controllers;

use App\Http\Requests\TicketRequest;
use App\Http\Resources\TicketReplyResource;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
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

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|integer|exists:departments,id',
            'priority' => 'required|in:High,Medium,Low',
            'description' => 'nullable|string',
            'status' => 'nullable|in:pending,open,closed,answered',
            'attachment' => 'nullable|file|mimes:jpg,png,pdf|max:2048',
        ]);

        $ticketData = collect($validatedData)->except(['attachment'])->toArray();

        $userId = auth()->id();
        $ticketData['created_by'] = $userId;

        if ($request->hasFile('attachment')) {
            $attachmentPath = ImageService::upload($request->file('attachment'), 'tickets');
            $ticketData['attachment'] = $attachmentPath;
        }

        $ticket = Ticket::create($ticketData);

        return response()->json(new TicketResource($ticket), 201);
    }


    public function update(Request $request, $ticketId)
    {
        $validatedData = $request->validate([
            'name' => 'nullable|string|max:255',
            'department_id' => 'nullable|integer',
            'priority' => 'nullable|in:High,Medium,Low',
            'description' => 'nullable|string',
            'status' => 'nullable|in:pending,open,closed,answered',
            'attachment' => 'nullable|file|mimes:jpg,png,pdf|max:2048',
        ]);

        $ticket = Ticket::findOrFail($ticketId);

        $ticketData = collect($validatedData)->except(['attachment'])->toArray();

        if ($request->hasFile('attachment')) {
            if ($ticket->attachment) {
                $oldAttachmentPath = public_path('storage/' . $ticket->attachment);
                if (file_exists($oldAttachmentPath)) {
                    unlink($oldAttachmentPath);
                }
            }

            $attachmentPath = ImageService::upload($request->file('attachment'), 'tickets');
            $ticketData['attachment'] = $attachmentPath;
        }

        $ticket->update($ticketData);

        return response()->json(new TicketResource($ticket), 200);
    }
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

        $ticketsQuery = Ticket::where('created_by', $client->id);

        if ($status && isset($statusMapping[$status])) {
            $statusString = $statusMapping[$status];

            if ($statusString !== 'all') {
                $ticketsQuery->where('status', $statusString);
            }
        }

        $tickets = $ticketsQuery->get();

        return TicketResource::collection($tickets);
    }


    public function getTicketsAndSummary(Request $request)
    {
        $client = $request->user();

        $openCount = Ticket::where('created_by', $client->id)->where('status', 'open')->count();
        $closedCount = Ticket::where('created_by', $client->id)->where('status', 'closed')->count();
        $answeredCount = Ticket::where('created_by', $client->id)->where('status', 'answered')->count();
        $inProgressCount = Ticket::where('created_by', $client->id)->where('status', 'pending')->count();

        $tickets = Ticket::where('created_by', $client->id)->paginate(10);

        return response()->json([
            'status' => true,
            'message' => 'Tickets and summary retrieved successfully.',
            'data' => [
                'summary' => [
                    'open' => $openCount,
                    'closed' => $closedCount,
                    'answered' => $answeredCount,
                    'in_progress' => $inProgressCount,
                ],
                'tickets' => TicketResource::collection($tickets),

                'from' => $tickets->firstItem(),
                'per_page' => $tickets->perPage(),
                'to' => $tickets->lastItem(),
                'total' => $tickets->total(),
                'count' => $tickets->count(),

            ]
        ]);
    }

    public function getRepliesForTicket($ticket_id)
    {
        // Find the ticket by its ID
        $ticket = Ticket::find($ticket_id);

        // Check if the ticket exists
        if (!$ticket) {
            return response()->json([
                'status' => false,
                'message' => 'Ticket not found.',
            ], 404);
        }

        // Get all replies for the specific ticket
        $replies = $ticket->replies; // Assuming `replies()` relationship is defined in the Ticket model

        // Return the replies wrapped in a resource collection
        return response()->json([
            'status' => true,
            'message' => 'Replies retrieved successfully.',
            'data' => TicketReplyResource::collection($replies), // Transform the replies
        ]);
    }
}
