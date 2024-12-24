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
