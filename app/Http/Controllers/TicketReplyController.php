<?php
namespace App\Http\Controllers;

use App\Http\Requests\TicketReplyRequest;
use App\Http\Resources\TicketReplyResource;
use App\Models\Admin;
use App\Models\NotificationTemplate;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Repositories\NotificationRepository;
use App\Repositories\TicketReplyRepositoryInterface;
use App\Services\FirebaseService;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketReplyController extends BaseController
{
    private $repository;
    private $firebaseService;
    private $notificationRepository;

    public function __construct(TicketReplyRepositoryInterface $repository, FirebaseService $firebaseService, NotificationRepository $notificationRepository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
        $this->firebaseService = $firebaseService;
        $this->notificationRepository = $notificationRepository;
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

        $ticket = Ticket::find($validated['ticket_id']);

        if ($creator instanceof Admin && $ticket) {
            $ticket->status = 'answered';
            $ticket->save();

            $this->sendTicketAnsweredNotification($ticket);
        }

        return new TicketReplyResource($ticketReply);
    }



    private function sendTicketAnsweredNotification(Ticket $ticket)
    {
        $client = $ticket->client;

        if (!$client || !$client->device_token) {
            Log::warning('Client not found or has no device token for ticket notification.', [
                'ticket_id' => $ticket->id,
                'client_id' => $client ? $client->id : null
            ]);
            return;
        }

        // **إحضار قالب الإشعار من قاعدة البيانات**
        $template = NotificationTemplate::where('type', 'ticket_answered')->first();
        if (!$template) {
            Log::error('Notification template "ticket_answered" not found.');
            return;
        }

        $title = $template->title;
        $message = str_replace(
            ['{ticket_id}'],
            [$ticket->name],
            $template->message
        );

        try {
            $this->firebaseService->sendNotification($client->device_token, $title, $message);

            $this->notificationRepository->createNotification($client, $title, $message, $client->device_token);

            Log::info('Ticket answered notification sent successfully.', ['client_id' => $client->id]);
        } catch (\Exception $e) {
            Log::error('Error sending ticket answered notification: ' . $e->getMessage());
        }
    }

}
