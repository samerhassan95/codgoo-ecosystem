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
                $chatSummaries[$chatId] = [
                    'chatId' => $chatId,
                    'unreadMessages' => 0,
                    'lastMessage' => null,
                    'lastMessageTime' => null,
                ];
            }

            // حساب الرسائل غير المقروءة
            if (isset($messageData['seen']) && !$messageData['seen']) {
                $chatSummaries[$chatId]['unreadMessages']++;
            }

            // تعيين آخر رسالة
            if (!isset($chatSummaries[$chatId]['lastMessageTime']) || $messageData['createdAt'] > $chatSummaries[$chatId]['lastMessageTime']) {
                $chatSummaries[$chatId]['lastMessage'] = $messageData['message'] ?? null;
                $chatSummaries[$chatId]['lastMessageTime'] = $messageData['createdAt'] ?? null;
            }
        }

        return array_values($chatSummaries);
    }

}
