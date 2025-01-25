<?php
namespace App\Http\Controllers;

use App\Http\Requests\TicketReplyRequest;
use App\Http\Resources\TicketReplyResource;
use App\Models\TicketReply;
use App\Repositories\TicketReplyRepositoryInterface;
use App\Services\ImageService;
use Illuminate\Http\Request;

class TicketReplyController extends BaseController
{
    private $repository;

    public function __construct(TicketReplyRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }

    public function store(Request $request)
    {
        $creator = $request->user();
        $validated = $request->validate([
            'ticket_id' => 'required|exists:tickets,id', 
            'reply' => 'required|string', 
        ]);

        $ticketReply = new TicketReply();
        $ticketReply->ticket_id = $validated['ticket_id'];  
        $ticketReply->reply = $validated['reply'];  
        $ticketReply->creator_id = $creator->id;  
        $ticketReply->creator_type = get_class($creator);  
        $ticketReply->save(); 

        return new TicketReplyResource($ticketReply);
    }
}
