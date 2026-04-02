<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Slider;
use App\Models\Task;
use Illuminate\Http\Request;

class ClientDashboardController extends Controller
{
public function dashboard(Request $request)
{
    $client = auth()->user();

    // ✅ Eager load banners
    $projects = Project::with('banners')
        ->where('client_id', $client->id)
        ->orderByDesc('updated_at')
        ->get();

    $projectsCount = $projects->count();
    
    $projectStatusSummary = [
        'ongoing'   => $projects->where('status', 'ongoing')->count(),
        'pending'  => $projects->where('status', 'pending')->count(),
        'completed' => $projects->where('status', 'completed')->count(),
        'requested' => $projects->where('status', 'requested')->count(),
    ];
    
    $projectsChart = $projects
        ->groupBy(function($item){
            return $item->created_at->format('Y-m');
        })
        ->map(function($row){
            return $row->count();
        });

    $taskIds = Task::whereHas('milestone.project', function($q) use ($client) {
        $q->where('client_id', $client->id);
    })->get();

    $tasks = [
        'completed' => $taskIds->where('status', 'completed')->count(),
        'in_progress' => $taskIds->where('status', 'in_progress')->count(),
        'waiting_feedback' => $taskIds->where('status', 'waiting_feedback')->count(),
        'canceled' => $taskIds->where('status', 'canceled')->count(),
    ];

    $meetings = Meeting::where('client_id', $client->id)
        ->orderByDesc('updated_at')
        ->take(5)
        ->get()
        ->map(function($meeting){
            return [
                'id'=>$meeting->id,
                'meeting_name' => $meeting->meeting_name,
                'date' => $meeting->date,
                'start' => $meeting->start_time,
                'end' => $meeting->end_time,
                'project' => $meeting->project->name ?? null,
            ];
        });

    $invoices = Invoice::whereHas('project', function($q) use ($client) {
        $q->where('client_id', $client->id);
    })->get();

    $invoiceStatus = [
        'paid' => $invoices->where('status', 'paid')->count(),
        'unpaid' => $invoices->where('status', 'unpaid')->count(),
        'overdue' => $invoices->filter(fn($inv) =>
            $inv->status === 'unpaid' &&
            now()->gt($inv->due_date)
        )->count(),
    ];

    // ✅ Add banners to projects list
    $projectsList = $projects->map(function($project){
        return [
            'id' => $project->id,
            'name' => $project->name,
            'date' => $project->created_at->format('d M, Y'),
            'status' => $project->status,
            // 'banners' => $project->banners->map(fn($banner) => [
            //     'id' => $banner->id,
            //     'image' => asset('storage/' . $banner->image_path),
            //     'caption' => $banner->caption,
            // ]),
        ];
    });

    $sliders = Slider::with([
        'product' => function ($q) {
            $q->select('id', 'name', 'category_id', 'price', 'description')
            ->with(['category:id,name']);
        }
    ])
    ->get()
    ->map(function ($slider) {
        $product = $slider->product;
        
        // Handle image array properly
        $firstImage = null;
        if (is_array($slider->image) && !empty($slider->image)) {
            $firstImage = url($slider->image[0]);
        } elseif (is_string($slider->image) && !empty($slider->image)) {
            $firstImage = url($slider->image);
        }
        
        return [
            'id' => $slider->id,
            'image' => $firstImage,
            'product' => $product ? [
                'id' => $product->id,
                'name' => $product->name,
            ] : null
        ];
    });
    
    return response()->json([
        'status' => true,
        'data' => [
            'projects_summary' => [
                'count' => $projectsCount,
                'chart' => $projectsChart,
                'status' => $projectStatusSummary
            ],
            'tasks' => $tasks,
            'meetings' => $meetings,
            'invoice_status' => $invoiceStatus,
            'projects' => $projectsList,
            'sliders' => $sliders
        ],
    ]);
}
}
