<?php

namespace App\Http\Controllers\api\personnel;

use App\Models\Payroll;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PayrollController extends Controller
{
     /**
     * Liste toutes les paies
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status');
        $period = $request->get('period');
        
        $query = Payroll::with(['employee.user']);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($period) {
            $query->where('period', $period);
        }
        
        $payrolls = $query->latest('payment_date')->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $payrolls
        ]);
    }

    /**
     * Afficher une paie
     */
    public function show($id)
    {
        $payroll = Payroll::with(['employee.user', 'employee.department'])->find($id);
        
        if (!$payroll) {
            return response()->json([
                'success' => false,
                'message' => 'Paie non trouvée'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $payroll
        ]);
    }

    /**
     * Générer les paies pour une période
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'required|string', // Format: YYYY-MM
            'payment_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $period = $request->period;
        $paymentDate = $request->payment_date;
        
        // Vérifier si les paies existent déjà
        $existing = Payroll::where('period', $period)->exists();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Les paies pour cette période existent déjà'
            ], 400);
        }
        
        $employees = Employee::with('user')->whereHas('user', fn($q) => $q->where('is_active', true))->get();
        $generated = 0;
        
        foreach ($employees as $employee) {
            $basicSalary = $employee->salary;
            
            // Calculer les déductions (à personnaliser)
            $deductions = $this->calculateDeductions($employee, $period);
            
            $netSalary = $basicSalary - $deductions;
            
            Payroll::create([
                'employee_id' => $employee->id,
                'period' => $period,
                'gross_salary' => $basicSalary,
                'deductions' => $deductions,
                'net_salary' => $netSalary,
                'payment_date' => $paymentDate,
                'status' => 'pending',
            ]);
            
            $generated++;
        }
        
        return response()->json([
            'success' => true,
            'message' => "{$generated} paies générées avec succès"
        ], 201);
    }

    /**
     * Marquer comme payé
     */
    public function markAsPaid($id)
    {
        $payroll = Payroll::find($id);
        
        if (!$payroll) {
            return response()->json([
                'success' => false,
                'message' => 'Paie non trouvée'
            ], 404);
        }
        
        $payroll->update(['status' => 'paid']);
        
        return response()->json([
            'success' => true,
            'message' => 'Paie marquée comme payée'
        ]);
    }

    /**
     * Paies par période
     */
    public function byPeriod($period)
    {
        $payrolls = Payroll::where('period', $period)
            ->with(['employee.user'])
            ->get();
        
        $summary = [
            'period' => $period,
            'total_employees' => $payrolls->count(),
            'total_basic_salary' => $payrolls->sum('basic_salary'),
            'total_allowances' => $payrolls->sum('allowances'),
            'total_deductions' => $payrolls->sum('deductions'),
            'total_net_salary' => $payrolls->sum('net_salary'),
            'paid_count' => $payrolls->where('status', 'paid')->count(),
            'pending_count' => $payrolls->where('status', 'pending')->count(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'payrolls' => $payrolls
            ]
        ]);
    }

    /**
     * Paies par employé
     */
    public function byEmployee($employeeId)
    {
        $payrolls = Payroll::where('employee_id', $employeeId)
            ->latest('payment_date')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $payrolls
        ]);
    }

    /**
     * Calculer les déductions
     */
    private function calculateDeductions($employee, $period)
    {
        $deductions = 0;
        
        // Calculer les absences non justifiées
        $startDate = $period . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $absences = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'absent')
            ->count();
        
        // Déduire pour les absences (salaire journalier * nombre d'absences)
        $dailySalary = $employee->salary / 30;
        $deductions += $dailySalary * $absences;
        
        // Ajouter d'autres déductions (taxes, assurances, etc.)
        
        return round($deductions, 2);
    }

    /**
     * Supprimer une paie
     */
    public function destroy($id)
    {
        $payroll = Payroll::find($id);
        
        if (!$payroll) {
            return response()->json([
                'success' => false,
                'message' => 'Paie non trouvée'
            ], 404);
        }
        
        if ($payroll->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une paie déjà payée'
            ], 400);
        }
        
        $payroll->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Paie supprimée avec succès'
        ]);
    }
}
