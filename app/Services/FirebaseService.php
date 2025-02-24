<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use App\Models\Client; 
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Pagination\LengthAwarePaginator;
class FirebaseService
{
    protected $messaging;
    protected $firestore;

    public function __construct()
    {
        $credentialsPath = config('firebase.credentials');

        if (!file_exists(base_path($credentialsPath))) {
            throw new \Exception("Firebase credentials file not found at: " . base_path($credentialsPath));
        }

        $factory = (new Factory)->withServiceAccount(base_path($credentialsPath));
        $this->messaging = $factory->createMessaging();
        $this->firestore = new FirestoreClient([
            'keyFilePath' => base_path($credentialsPath),
        ]);
    }




    public function getAllChats()
    {
        $messagesCollection = $this->firestore->collectionGroup('messages')->documents();
        $chatSummaries = [];

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
                $client = Client::find($chatId);

                $chatSummaries[$chatId] = [
                    'chatId' => $chatId,
                    'clientName' => $client->name ?? 'Unknown',
                    'clientImage' => asset($client->photo) ?? null,
                    'unreadMessages' => 0,
                    'lastMessage' => null,
                    'lastMessageType' => null,
                    'lastMessageTime' => null,
                ];
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

            if (!isset($chatSummaries[$chatId]['lastMessageTime']) || $messageData['createdAt'] > $chatSummaries[$chatId]['lastMessageTime']) {
                $chatSummaries[$chatId]['lastMessage'] = $lastMessageContent;
                $chatSummaries[$chatId]['lastMessageType'] = $messageType;
                $chatSummaries[$chatId]['lastMessageTime'] = $messageData['createdAt'] ?? null;
            }

            if (isset($messageData['seen']) && !$messageData['seen']) {
                $chatSummaries[$chatId]['unreadMessages']++;
            }
        }

        return array_values($chatSummaries);
    }


    public function sendNotification($token, $title, $body)
    {
        $notification = Notification::create($title, $body);
        $message = CloudMessage::withTarget('token', $token)
            ->withNotification($notification)
            ->withData([
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ]);

        return $this->messaging->send($message);
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
    
    
}
