<?php

namespace App\Http\Controllers;

use App\Repositories\AchievementRepositoryInterface;
use App\Http\Requests\StoreAchievementRequest;
use App\Models\Achievement;
use App\Models\AchievementAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $achievements = Achievement::with(['attachments', 'creator'])->get();
            
            return response()->json([
                'status' => true,
                'message' => 'Achievements retrieved successfully.',
                'data' => $achievements
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve achievements.',
                'data' => null
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $achievement = Achievement::with(['attachments', 'creator'])->findOrFail($id);
            
            return response()->json([
                'status' => true,
                'message' => 'Achievement retrieved successfully.',
                'data' => $achievement
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Achievement not found.',
                'data' => null
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $achievement = Achievement::findOrFail($id);
            
            $achievement->update([
                'achievement_description' => $request->achievement_description ?? $achievement->achievement_description,
                'issues_notes' => $request->issues_notes ?? $achievement->issues_notes,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Achievement updated successfully.',
                'data' => $achievement->load('attachments')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update achievement.',
                'data' => null
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $achievement = Achievement::findOrFail($id);
            
            // Delete associated attachments
            foreach ($achievement->attachments as $attachment) {
                Storage::disk('public')->delete($attachment->file_path);
                $attachment->delete();
            }
            
            $achievement->delete();
            
            return response()->json([
                'status' => true,
                'message' => 'Achievement deleted successfully.',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete achievement.',
                'data' => null
            ], 500);
        }
    }

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