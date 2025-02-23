<?php

namespace App\Services;

use Google\Cloud\Firestore\FirestoreClient;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

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
        $chatsCollection = $this->firestore->collection('chats')->documents();
        $chatSummaries = [];

        foreach ($chatsCollection as $chatDoc) {
            if (!$chatDoc->exists()) {
                continue;
            }

            $chatData = $chatDoc->data();
            $messagesCollection = $this->firestore->collection('chats')->document($chatDoc->id())->collection('messages')->documents();

            $messages = collect();
            foreach ($messagesCollection as $messageDoc) {
                $messages->push($messageDoc->data());
            }

            // Count unread messages
            $unreadCount = $messages->filter(fn($msg) => isset($msg['seen']) && !$msg['seen'])->count();

            // Get last message
            $lastMessage = $messages->sortByDesc('createdAt')->first();

            $chatSummaries[] = [
                'chatId' => $chatDoc->id(),
                'unreadMessages' => $unreadCount,
                'lastMessage' => $lastMessage['message'] ?? null,
                'lastMessageTime' => $lastMessage['createdAt'] ?? null,
            ];
        }

        return $chatSummaries;
    }
}
