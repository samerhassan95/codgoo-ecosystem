<?php

namespace App\Http\Controllers;

use App\Models\EmployeeDocument;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Services\ImageService;

class EmployeeDocumentController extends Controller
{
    
    public function index($employeeId)
    {
        $documents = EmployeeDocument::where('employee_id', $employeeId)->get();

        return response()->json([
            'status' => true,
            'data' => $documents,
        ]);
    }

   
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
             'document_type_id' => 'required|exists:document_types,id',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png',
        ]);

        $employeeId = $request->input('employee_id');
        $documentTypeId = $request->input('document_type_id');

        $document = EmployeeDocument::firstOrNew([
            'employee_id' => $employeeId,
            'document_type_id' => $documentTypeId,
        ]);

        $directory = 'uploads/employee_documents/' . $employeeId;
        $filePath = ImageService::update($request->file('file'), $document->file_path ?? null, $directory);

        $document->file_path = $filePath;
        $document->status = 'uploaded';
        $document->uploaded_at = now();
        $document->save();

        return response()->json([
            'status' => true,
            'message' => 'Document uploaded successfully.',
            'data' => $document,
        ]);
    }
    public function show($id)
    {
        $document = EmployeeDocument::find($id);

        if (!$document) {
            return response()->json([
                'status' => false,
                'message' => 'Document not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $document,
        ]);
    }

    public function destroy($id)
    {
        $document = EmployeeDocument::find($id);

        if (!$document) {
            return response()->json([
                'status' => false,
                'message' => 'Document not found.',
            ], 404);
        }

        ImageService::delete($document->file_path);
        $document->delete();

        return response()->json([
            'status' => true,
            'message' => 'Document deleted successfully.',
        ]);
    }

}
