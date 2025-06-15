<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use Illuminate\Http\Request;

class DocumentTypeController extends Controller
{
    
    public function index()
    {
        $types = DocumentType::all();

        return response()->json([
            'status' => true,
            'data' => $types,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:document_types,name',
            'visible' => 'boolean',
        ]);

        $type = DocumentType::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Document type created successfully.',
            'data' => $type,
        ]);
    }

    public function show($id)
    {
        $type = DocumentType::find($id);

        if (!$type) {
            return response()->json([
                'status' => false,
                'message' => 'Document type not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $type,
        ]);
    }

    public function update(Request $request, $id)
    {
        $type = DocumentType::find($id);

        if (!$type) {
            return response()->json([
                'status' => false,
                'message' => 'Document type not found.',
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|unique:document_types,name,' . $id,
            'visible' => 'boolean',
        ]);

        $type->update([
            'name' => $request->name,
            'visible' => $request->visible,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Document type updated successfully.',
            'data' => $type,
        ]);
    }

  
    public function destroy($id)
    {
        $type = DocumentType::find($id);

        if (!$type) {
            return response()->json([
                'status' => false,
                'message' => 'Document type not found.',
            ], 404);
        }

        $type->delete();

        return response()->json([
            'status' => true,
            'message' => 'Document type deleted successfully.',
        ]);
    }
}
