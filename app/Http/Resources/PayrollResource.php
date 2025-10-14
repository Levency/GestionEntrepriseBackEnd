<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
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
            'employee' => new EmployeeResouce($this->whenLoaded('employee')),
            'period' => $this->period,
            'gross_salary' => $this->gross_salary,
            'net_salary' => $this->net_salary,
            'discount' => $this->discount,
            'status' => $this->status,
            'statistics' => [
                'total_payrolls_count' => $this->employee->payrolls()->count(),
                'substtotal_gross_salary' => $this->employee->payrolls()->sum('gross_salary'),
                'subtotal_net_salary' => $this->employee->payrolls()->sum('net_salary'),
                'paid_payrolls_count' => $this->employee->payrolls()->where('status', 'paid')->count(),
                'unpaid_payrolls_count' => $this->employee->payrolls()->where('status', 'unpaid')->count(),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
