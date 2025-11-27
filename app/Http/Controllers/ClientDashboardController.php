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

      
        $projects = Project::where('client_id', $client->id)->get();

        $projectsCount = $projects->count();

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
            ->orderBy('start_time', 'asc')
            ->take(5)
            ->get()
            ->map(function($meeting){
                return [
                    'meeting_name' => $meeting->meeting_name,
                    'date' => $meeting->start_time,
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


        $projectsList = $projects->map(function($project){
            return [
                'id' => $project->id,
                'name' => $project->name,
                'date' => $project->created_at->format('d M, Y'),
                'status' => $project->status,
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

            return [
                'id' => $slider->id,
                'image' => $slider->image ? url($slider->image) : null,

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
                    'chart' => $projectsChart
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
