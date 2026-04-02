<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Enum\SectionEnum;
use Illuminate\Http\Request;
use App\Http\Resources\TopicResource;
use App\Http\Resources\TopicSummaryResource;

// Note: I removed the BaseController dependency for this example 
// to ensure it works immediately without repository errors.
class TopicController extends Controller 
{
public function getSections()
{
    $sections = \App\Enum\SectionEnum::getList();
    $data = [];

    foreach ($sections as $id => $label) {
        // Fetch only the first 3 topics for the preview list on Screen 1
        $topicsPreview = \App\Models\Topic::where('section_id', $id)
            ->orderBy('id', 'asc')
            ->limit(3)
            ->get(['id', 'header']);

        $data[] = [
            'id'             => $id,
            'label'          => $label,
            'total_count'    => \App\Models\Topic::where('section_id', $id)->count(),
            'topics_preview' => $topicsPreview, // This matches the bullet points in Figma
        ];
    }

    return response()->json([
        'success' => true,
        'data'    => $data
    ]);
}

    public function getSectionById($id)
    {
        if (!SectionEnum::isValid($id)) {
            return response()->json(['success' => false, 'message' => 'Section not found'], 404);
        }

        $topics = Topic::where('section_id', $id)->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'section_id'    => (int)$id,
                'section_label' => SectionEnum::getLabel($id),
                'topics'        => TopicResource::collection($topics)
            ]
        ]);
    }

    public function getTopicById($id)
    {
        $topic = Topic::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new TopicResource($topic)
        ]);
    }
    
    public function handleFeedback(Request $request, $id)
{
    $topic = Topic::findOrFail($id);
    $type = $request->input('type');

    if ($type === 'yes') {
        $topic->increment('helpful_yes');
    } elseif ($type === 'no') {
        $topic->increment('helpful_no');
    }

    return response()->json([
        'success' => true,
        'message' => 'Thank you for your review!' // Design text from Screen 3
    ]);
}
}