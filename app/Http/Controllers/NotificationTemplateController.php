<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotificationTemplate;

class NotificationTemplateController extends Controller
{
    public function index()
    {
        return response()->json(NotificationTemplate::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|unique:notification_templates,type',
            'title' => 'required|string',
            'message' => 'required|string',
        ]);

        $template = NotificationTemplate::create($request->all());
        return response()->json(['message' => 'Template created successfully', 'template' => $template]);
    }

    public function show($id)
    {
        return response()->json(NotificationTemplate::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $template = NotificationTemplate::findOrFail($id);

        $request->validate([
            'title' => 'required|string',
            'message' => 'required|string',
        ]);

        $template->update($request->all());
        return response()->json(['message' => 'Template updated successfully', 'template' => $template]);
    }

    public function destroy($id)
    {
        NotificationTemplate::destroy($id);
        return response()->json(['message' => 'Template deleted successfully']);
    }
}