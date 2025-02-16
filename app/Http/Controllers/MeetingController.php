<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeetingRequest;
use App\Http\Resources\MeetingResource;
use App\Http\Resources\ProjectResource;
use App\Models\AvailableSlot;
use App\Models\Meeting;
use App\Repositories\MeetingRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    private $repository;

    public function __construct(MeetingRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function store(MeetingRequest $request)
    {
        $validated = $request->validated();
    
        $duration = $validated['duration'] ?? 60;
        
        $slot = AvailableSlot::findOrFail($validated['slot_id']);
        $requestedStart = Carbon::parse($validated['start_time']);
        $requestedEnd = $requestedStart->copy()->addMinutes($duration);
    
        $existingMeetings = $slot->meetings()
            ->where(function ($query) use ($requestedStart, $requestedEnd) {
                $query->where('start_time', '<', $requestedEnd->toTimeString())
                      ->where('end_time', '>', $requestedStart->toTimeString());
            })
            ->exists();
    
        if ($existingMeetings) {
            return response()->json([
                'status' => false,
                'message' => 'This time slot is already occupied.',
            ], 400);
        }
    
        $jitsiRoom = 'meeting-' . uniqid();
        $jitsiUrl = config('services.jitsi.base_url') . '/' . $jitsiRoom;
    
        $meeting = $this->repository->create([
            'slot_id' => $validated['slot_id'],
            'client_id' => $validated['client_id'],
            'meeting_name' => $validated['meeting_name'],
            'description' => $validated['description'] ?? null, 
            'start_time' => $requestedStart->toTimeString(),
            'end_time' => $requestedEnd->toTimeString(),
            'project_id' => $validated['project_id'] ?? null,
            'jitsi_url' => $jitsiUrl,
            'status' => 'Request Sent',
        ]);
    
        $slot->meetings()->update([
            'status' => true
        ]);
    
        return response()->json(new MeetingResource($meeting), 201);
    }
    

    public function getMeetingsForClient(Request $request)
    {
        $client = $request->user();

        $meetings = Meeting::where('client_id', $client->id)->get();

        return MeetingResource::collection($meetings);
    }

    public function filterMeetingsByStatus(Request $request)
    {
        $client = $request->user();

        $statusMapping = [
            0 => 'all',            
            1 => 'Request Sent',
            2 => 'Confirmed',
            3 => 'Completed',
            4 => 'Canceled',
        ];

        $status = $request->query('status');

        if ($status && isset($statusMapping[$status])) {
            if ($status == 0) {
                $meetings = Meeting::where('client_id', $client->id)->get();
            } else {
                $meetings = Meeting::where('client_id', $client->id)
                    ->where('status', $statusMapping[$status])
                    ->get();
            }
        } else {
            $meetings = Meeting::where('client_id', $client->id)->get();
        }

        return MeetingResource::collection($meetings);
    }


    public function getMeetingById($id, Request $request)
    {
        $user = $request->user();
        $meeting = Meeting::with('project')->find($id);

        if (!$meeting) {
            return response()->json([
                'status' => false,
                'message' => 'Meeting not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'meeting' => new MeetingResource($meeting),
                'project' => new ProjectResource($meeting->project),
            ],
        ]);
    }


    public function getMeetingsWithProject(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $meetings = $this->repository->getMeetingsWithProject($perPage);

        $filteredMeetings = $meetings->map(function ($meeting) {
            return [
                'meeting_id' => $meeting->id,
                'meeting_name' => $meeting->meeting_name,
                'meeting_date' => $meeting->slot ? $meeting->slot->date : null,
                'name' => $meeting->project ? $meeting->project->name : null, 
                'status' => $meeting->status, 
                'start_time' => $meeting->start_time,
                'end_time' => $meeting->end_time,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $filteredMeetings->values(),
            'total' => $meetings->total(),
            'per_page' => $meetings->perPage(),
            'from' => $meetings->firstItem(),
            'to' => $meetings->lastItem(),
            'count' => $meetings->count(),
            
        ]);
    }

    public function update(MeetingRequest $request, $id)
    {
        $meeting = Meeting::find($id);
    
        if (!$meeting) {
            return response()->json([
                'status' => false,
                'message' => 'Meeting not found.',
            ], 404);
        }
    
        if ($request->has('slot_id') || $request->has('start_time')) {
            $slot = AvailableSlot::findOrFail($request->slot_id ?? $meeting->slot_id);
            $requestedStart = Carbon::parse($request->start_time ?? $meeting->start_time);
            $requestedEnd = $requestedStart->copy()->addMinutes($request->duration ?? 60);
    
            $existingMeetings = $slot->meetings()
                ->where('id', '!=', $meeting->id)
                ->where(function ($query) use ($requestedStart, $requestedEnd) {
                    $query->where('start_time', '<', $requestedEnd->toTimeString())
                        ->where('end_time', '>', $requestedStart->toTimeString());
                })
                ->exists();
    
            if ($existingMeetings) {
                return response()->json([
                    'status' => false,
                    'message' => 'This time slot is already occupied.',
                ], 400);
            }
        }
    
        // Update the meeting
        $meeting->update([
            'slot_id' => $request->slot_id ?? $meeting->slot_id,
            'meeting_name' => $request->meeting_name ?? $meeting->meeting_name,
            'description' => $request->description ?? $meeting->description,
            'start_time' => $request->start_time ?? $meeting->start_time,
            'end_time' => isset($request->start_time) ? $requestedEnd->toTimeString() : $meeting->end_time,
            'project_id' => $request->project_id ?? $meeting->project_id,
            'jitsi_url' => $request->jitsi_url ?? $meeting->jitsi_url,
            'status' => $request->status ?? $meeting->status,
        ]);
    
        // Check if the status is updated to 'Canceled'
        if ($request->status === 'Canceled' && $meeting->project) {
            $meeting->project->update(['status' => 'reject']);
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Meeting updated successfully.',
            'data' => new MeetingResource($meeting),
        ]);
    }
    

    
}
