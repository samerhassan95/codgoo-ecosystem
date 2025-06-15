<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $fillable = ['name', 'visible'];

    public function employeeDocuments()
    {
        return $this->hasMany(EmployeeDocument::class);
    }
}
