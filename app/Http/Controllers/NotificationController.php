<?php

// namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use App\Repositories\NotificationRepository;
// use App\Services\FirebaseService;
// use App\Models\Admin;
// use App\Models\Client;

// class NotificationController extends Controller
// {
//     protected $notificationRepository;
//     protected $firebaseService;

//     public function __construct(NotificationRepository $notificationRepository, FirebaseService $firebaseService)
//     {
//         $this->notificationRepository = $notificationRepository;
//         $this->firebaseService = $firebaseService;
//     }

//     public function sendNotification(Request $request)
//     {
//         $request->validate([
//             'title' => 'required|string',
//             'message' => 'required|string',
//             'notifiable_id' => 'nullable|integer', 
//             'notifiable_type' => 'nullable|in:admin,client',
//         ]);

//         if ($request->has('notifiable_id') && $request->has('notifiable_type')) {
//             $model = $request->notifiable_type === 'admin' ? Admin::class : Client::class;
//             $notifiable = $model::find($request->notifiable_id);

//             if (!$notifiable || !$notifiable->fcm_token) {
//                 return response()->json(['message' => 'User not found or missing FCM token'], 400);
//             }

//             // Send Notification via Firebase
//             $this->firebaseService->sendNotification($notifiable->fcm_token, $request->title, $request->message);

//             // Store notification in the database
//             $this->notificationRepository->createNotification($notifiable, $request->title, $request->message, $notifiable->fcm_token);

//             return response()->json(['message' => 'Notification sent to user and stored successfully!']);
//         }

//         $clients = Client::whereNotNull('fcm_token')->get();

//         if ($clients->isEmpty()) {
//             return response()->json(['message' => 'No clients with valid FCM tokens.'], 400);
//         }

//         foreach ($clients as $client) {
//             $this->firebaseService->sendNotification($client->fcm_token, $request->title, $request->message);

//             // Store notification in the database for each client
//             $this->notificationRepository->createNotification($client, $request->title, $request->message, $client->fcm_token);
//         }

//         return response()->json(['message' => 'Notifications sent to all clients and stored successfully!']);
//     }

// }


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\NotificationRepository;
use App\Services\FirebaseService;
use App\Models\Admin;
use App\Models\Client;
use App\Models\NotificationTemplate;

class NotificationController extends Controller
{
    protected $notificationRepository;
    protected $firebaseService;

    public function __construct(NotificationRepository $notificationRepository, FirebaseService $firebaseService)
    {
        $this->notificationRepository = $notificationRepository;
        $this->firebaseService = $firebaseService;
    }

    public function sendNotification(Request $request)
    {
        $request->validate([
            'type' => 'required|string|exists:notification_templates,type', // Fetch from DB
            'notifiable_id' => 'nullable|integer',
            'notifiable_type' => 'nullable|in:admin,client',
            'data' => 'nullable|array' // Dynamic placeholders
        ]);

        $template = NotificationTemplate::where('type', $request->type)->first();

        if (!$template) {
            return response()->json(['message' => 'Invalid notification type'], 400);
        }

        $title = $template->title;
        $message = $template->message;

        if ($request->has('data')) {
            foreach ($request->data as $key => $value) {
                $message = str_replace("{" . $key . "}", $value, $message);
            }
        }

        if ($request->has('notifiable_id') && $request->has('notifiable_type')) {
            $model = $request->notifiable_type === 'admin' ? Admin::class : Client::class;
            $notifiable = $model::find($request->notifiable_id);

            if (!$notifiable || !$notifiable->fcm_token) {
                return response()->json(['message' => 'User not found or missing FCM token'], 400);
            }

            $this->firebaseService->sendNotification($notifiable->fcm_token, $title, $message);
            $this->notificationRepository->createNotification($notifiable, $title, $message, $notifiable->fcm_token);

            return response()->json(['message' => 'Notification sent successfully!']);
        }

        $clients = Client::whereNotNull('fcm_token')->get();

        if ($clients->isEmpty()) {
            return response()->json(['message' => 'No clients with valid FCM tokens.'], 400);
        }

        foreach ($clients as $client) {
            $this->firebaseService->sendNotification($client->fcm_token, $title, $message);
            $this->notificationRepository->createNotification($client, $title, $message, $client->fcm_token);
        }

        return response()->json(['message' => 'Notifications sent to all clients successfully!']);
    }
}
