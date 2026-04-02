<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\NotificationTemplate;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use App\Models\Client;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

class FirebaseService
{
    protected $messaging;
    protected $firestore;

    public function __construct()
    {
        $credentialsPath = config('firebase.credentials');

        // Handle both absolute and relative paths
        if (!$credentialsPath) {
            throw new \Exception("Firebase credentials path not configured");
        }

        // If path is not absolute, make it relative to base path
        if (!file_exists($credentialsPath)) {
            $basePath = base_path($credentialsPath);
            if (file_exists($basePath)) {
                $credentialsPath = $basePath;
            } else {
                throw new \Exception(
                    "Firebase credentials file not found at: {$credentialsPath} or {$basePath}"
                );
            }
        }

        $factory = (new Factory)->withServiceAccount($credentialsPath);

        $this->messaging = $factory->createMessaging();

        $this->firestore = new FirestoreClient([
            'keyFilePath' => $credentialsPath,
             'transport' => 'rest',
            
        ]);
    }

    public function getAllChats()
    {
        $clients = Client::all();

        $messagesCollection = $this->firestore->collectionGroup('messages')->documents();

        $chatSummaries = [];

        foreach ($clients as $client) {
            $chatSummaries[$client->id] = [
                'chatId' => (string)$client->id,
                'clientName' => $client->name ?? 'Unknown',
                'clientImage' => $client->photo ? asset($client->photo) : null,
                'phone' => $client->phone ?? 'Unknown',
                'unreadMessages' => 0,
                'lastMessage' => null,
                'lastMessageType' => null,
                'lastMessageTime' => null,
            ];
        }


        foreach ($messagesCollection as $messageDoc) {
            if (!$messageDoc->exists()) {
                continue;
            }

            $messageData = $messageDoc->data();
            $parentRef = $messageDoc->reference()->parent()->parent();
            if (!$parentRef) {
                continue;
            }

            $chatId = $parentRef->id();

            if (!isset($chatSummaries[$chatId])) {

                continue;
            }

            $messageType = 'text';
            $lastMessageContent = $messageData['message'] ?? null;

            if (!empty($messageData['imageUrl'])) {
                $messageType = 'image';
                $lastMessageContent = '[Image]';
            } elseif (!empty($messageData['audio'])) {
                $messageType = 'audio';
                $lastMessageContent = '[Audio]';
            }


            if (isset($messageData['seen']) && !$messageData['seen'] && isset($messageData['userId']) && $messageData['userId'] != $chatId) {
                $chatSummaries[$chatId]['unreadMessages']++;
            }


            if (
                !isset($chatSummaries[$chatId]['lastMessageTime']) ||
                $messageData['createdAt'] > $chatSummaries[$chatId]['lastMessageTime']
            ) {
                $chatSummaries[$chatId]['lastMessage'] = $lastMessageContent;
                $chatSummaries[$chatId]['lastMessageType'] = $messageType;
                $chatSummaries[$chatId]['lastMessageTime'] = $messageData['createdAt'] ?? null;
            }
        }

        return array_values($chatSummaries);
    }

    public function sendNotification($deviceToken, $title, $message, $type = null, $extraData = [])
    {
        try {
            $notification = Notification::create($title, $message);

            $messageData = array_merge([
                'type' => (string)($type ?? 'default'),
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ], $this->stringifyData($extraData));

            $firebaseMessage = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification($notification)
                ->withData($messageData);

            \Log::info('Sending Firebase Notification', [
                'token' => $deviceToken,
                'title' => $title,
                'body' => $message,
                'data' => $messageData,
            ]);

            $this->messaging->send($firebaseMessage);

            \Log::info('Notification sent successfully', ['token' => $deviceToken]);

        } catch (\Throwable $e) {
            \Log::error('Firebase notification failed', ['error' => $e->getMessage()]);
        }
    }

    public function markMessagesAsSeen($chatId)
    {
        $chatRef = $this->firestore->collection('chats')->document($chatId);
        $messagesCollection = $chatRef->collection('messages')->documents();

        foreach ($messagesCollection as $messageDoc) {
            if (!$messageDoc->exists()) {
                continue;
            }

            $messageData = $messageDoc->data();

            if (isset($messageData['seen']) && !$messageData['seen']) {
                $messageDoc->reference()->update([
                    ['path' => 'seen', 'value' => true]
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'All messages marked as seen for chat: ' . $chatId
        ]);
    }

    public function sendChatNotification($token, $messageData)
    {
        if ($messageData['sender_type'] === 'client') {
            $sender = Client::find($messageData['sender_id']);
            $title = $sender ? $sender->name : 'Unknown Sender';

        } else {
            $sender = Admin::find($messageData['sender_id']);
            $title = $sender ? $sender->username : 'Unknown Sender';

        }

        $body = $messageData['message'] ?? '📩 You have a new message';

        if (!empty($messageData['imageUrl'])) {
            $body = "📷 New Image";
        } elseif (!empty($messageData['audio'])) {
            $body = "🎤 New Voice Message";
        }

        $notification = \Kreait\Firebase\Messaging\Notification::fromArray([
            'title' => $title,
            'body' => $body,
        ]);

        $firebaseMessage = CloudMessage::withTarget('token', $token)
            ->withNotification($notification)
            ->withData([
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'chat_id' => $messageData['chat_id'] ?? '',
                'user_id' => $messageData['userId'] ?? '',
                'message' => $messageData['message'] ?? '',
                'imageUrl' => $messageData['imageUrl'] ?? '',
                'audio' => $messageData['audio'] ?? '',
                'type' => 'chat_message',
            ]);

        return $this->messaging->send($firebaseMessage);
    }

    private function stringifyData(array $data): array
    {
        return array_map(function ($value) {
            if (is_array($value) || is_object($value)) {
                return json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            return (string)$value;
        }, $data);
    }

    
}
