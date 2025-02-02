<?php
namespace App\Http\Controllers;

use App\Http\Requests\TicketRequest;
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
            'name' => 'required|string|max:255',
            'department_id' => 'required|integer', 
            'priority' => 'required|in:High,Medium,Low', 
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
        $client = $request->user();  // Get the logged-in client
    
        // Status mapping: Numeric values to actual status labels
        $statusMapping = [
            0 => 'all',              // All statuses
            1 => 'open',             // Open status
            2 => 'closed',           // Closed status
            3 => 'answered',         // Answered status
            4 => 'pending',          // Pending status
        ];
    
        // Get the status from the query parameters (optional)
        $status = $request->query('status');  
    
        // Start the query to get the tickets created by the logged-in client
        $ticketsQuery = Ticket::where('created_by', $client->id);
    
        // If status is provided, check if it's valid and filter tickets accordingly
        if ($status && isset($statusMapping[$status])) {
            $statusString = $statusMapping[$status];
    
            // If 'all', don't filter by status
            if ($statusString !== 'all') {
                $ticketsQuery->where('status', $statusString);
            }
        }
    
        // Execute the query and get the results
        $tickets = $ticketsQuery->get();
    
        // Return the tickets wrapped in TicketResource
        return TicketResource::collection($tickets);
    }
    

    public function getTicketsAndSummary(Request $request)
    {
        $client = $request->user(); 

        $openCount = Ticket::where('created_by', $client->id)->where('status', 'open')->count();
        $closedCount = Ticket::where('created_by', $client->id)->where('status', 'closed')->count();
        $answeredCount = Ticket::where('created_by', $client->id)->where('status', 'answered')->count();
        $inProgressCount = Ticket::where('created_by', $client->id)->where('status', 'pending')->count();  

        $tickets = Ticket::where('created_by', $client->id)->get();

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
                'tickets' => TicketResource::collection($tickets) 
            ]
        ]);
    }
    
    // public function reply(Request $request, int $ticketId)
    // {
    //     $ticket = Ticket::findOrFail($ticketId);

    //     $reply = TicketReply::create([
    //         'ticket_id' => $ticket->id,
    //         'reply' => $request->reply,
    //         'admin_id' => $request->admin_id,
    //     ]);

    //     $ticket->update(['status' => 'answered']);

    //     return $reply;
    // }

    // public function updateStatus(Request $request, int $ticketId)
    // {
    //     $ticket = Ticket::findOrFail($ticketId);
    //     $ticket->update(['status' => $request->status]);

    //     return $ticket;
    // }

    // public function getAllForAdmin()
    // {
    //     return Ticket::with('replies.admin')->get();
    // }

    // public function getAllForClient(int $clientId)
    // {
    //     return Ticket::where('created_by', $clientId)->with('replies.admin')->get();
    // }

}
