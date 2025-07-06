<?php

namespace App\Http\Controllers;

use App\Repositories\AchievementRepositoryInterface;
use App\Http\Requests\StoreAchievementRequest;
use App\Models\Achievement;
use App\Models\AchievementAttachment;
use Illuminate\Support\Facades\Storage;

class AchievementController extends Controller
{

    public function store(StoreAchievementRequest $request)
    {
        $employee = auth()->user();

        $achievement = Achievement::create([
            'created_by'             => $employee->id,
            'achievement_description'=> $request->achievement_description,
            'issues_notes'           => $request->issues_notes,
            'attendance_id'          => $request->attendance_id,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('achievement_attachments', 'public');

                AchievementAttachment::create([
                    'achievement_id' => $achievement->id,
                    'file_path'      => $path,
                ]);
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Achievement created successfully.',
            'data'    => $achievement->load('attachments'),
        ]);
    }

}