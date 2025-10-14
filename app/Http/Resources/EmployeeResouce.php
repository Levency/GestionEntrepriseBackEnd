<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResouce extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_code' => $this->employee_code,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'position' => $this->position,
            'department' => new DepartementResouce($this->whenLoaded('department')),
            'statistics' => [
                'attendances_count' => $this->attendances()->count(),
                'present_days_count' => $this->attendances()->where('status', 'present')->count(),
                'absent_days_count' => $this->attendances()->where('status', 'absent')->count(),
                'late_days_count' => $this->attendances()->where('status', 'late')->count(),
                'payrolls_count' => $this->payrolls()->count(),
            ],
            'salary' => $this->salary,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
