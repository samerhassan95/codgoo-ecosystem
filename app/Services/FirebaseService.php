<?php

namespace App\Services;

use App\Models\Client; // Import Client Model
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Pagination\LengthAwarePaginator;
use Kreait\Firebase\Factory;

class FirebaseService
{
    protected $firestore;

    public function __construct()
    {
        $credentialsPath = config('firebase.credentials');

        if (!file_exists(base_path($credentialsPath))) {
            throw new \Exception("Firebase credentials file not found at: " . base_path($credentialsPath));
        }

        $factory = (new Factory)->withServiceAccount(base_path($credentialsPath));

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
                $client = Client::find($chatId); // Fetch Client from Database

                $chatSummaries[$chatId] = [
                    'chatId' => $chatId,
                    'clientName' => $client->name ?? 'Unknown',
                    'clientImage' => asset($client->photo) ?? null,
                    'unreadMessages' => 0,
                    'lastMessage' => null,
                    'lastMessageTime' => null,
                ];
            }

            if (isset($messageData['seen']) && !$messageData['seen']) {
                $chatSummaries[$chatId]['unreadMessages']++;
            }

            if (!isset($chatSummaries[$chatId]['lastMessageTime']) || $messageData['createdAt'] > $chatSummaries[$chatId]['lastMessageTime']) {
                $chatSummaries[$chatId]['lastMessage'] = $messageData['message'] ?? null;
                $chatSummaries[$chatId]['lastMessageTime'] = $messageData['createdAt'] ?? null;
            }
        }

        return array_values($chatSummaries);
    }
}
