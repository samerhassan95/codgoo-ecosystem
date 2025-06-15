<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
        protected $fillable = ['employee_id', 'document_type_id', 'file_path', 'status', 'uploaded_at'];


    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    protected $appends = ['file_url'];

    public function getFileUrlAttribute()
    {
        return $this->file_path ? asset($this->file_path) : null;
    }


    public function type()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }
}
