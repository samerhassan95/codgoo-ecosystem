<?php

namespace App\Repositories;

use App\Models\Employee;
use App\Models\Skill;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Common\CommonRepository;
use Illuminate\Http\Request;
use App\Repositories\SkillRepositoryInterface;
use App\Http\Requests\SkillRequest;
use App\Http\Resources\SkillResource;

class SkillRepository extends CommonRepository implements SkillRepositoryInterface
{
    protected const REQUEST = SkillRequest::class;
    protected const RESOURCE = SkillResource::class;

    public function model(): string
    {
        return Skill::class;
    }

    public function assignSkillsToEmployee($employeeId, $skillIds)
    {
        $employee = Employee::findOrFail($employeeId);
        $employee->skills()->syncWithoutDetaching($skillIds);
        return $employee->skills;
    }

    public function removeSkillFromEmployee($employeeId, $skillId)
    {
        $employee = Employee::findOrFail($employeeId);
        $employee->skills()->detach($skillId);
    }
}
