<?php

namespace App\Http\Controllers\api\personnel;

use App\Http\Resources\PayrollResource;
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
        
        $query = Payroll::with(['employee']);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($period) {
            $query->where('period', $period);
        }
        
        $payrolls = $query->latest('created_at')->paginate($perPage);
        
        return successResponse(
            'Paies récupérées avec succès',
            PayrollResource::collection($payrolls->load('employee')),
            200
        );
    }

    /**
     * Afficher une paie
     */
    public function show(Payroll $payroll)
    {
        return successResponse(
            new PayrollResource($payroll->load('employee')),
            'Paie récupérée avec succès',
            200
        );
    }

    /**
     * Générer les paies pour une période
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'required|string', // Format: YYYY-MM
        ]);

        try {
            //code...
            $period = $request->period;
            
            // Vérifier si les paies existent déjà
            $existing = Payroll::where('period', $period)->exists();
            if ($existing) {
                return errorResponse('Les paies pour cette période ont déjà été générées', 400);
            }
            
            $employees = Employee::all();
            $generated = 0;
            foreach ($employees as $employee) {
                $basicSalary = $employee->salary;
                
                // Calculer les déductions (à personnaliser)
                $deductions = $this->calculateDeductions($employee, $period);
                
                $netSalary = $basicSalary - $deductions;
                
                $payroll = Payroll::create([
                    'employee_id' => $employee->id,
                    'period' => $period,
                    'gross_salary' => $basicSalary,
                    'discount' => $deductions,
                    'net_salary' => $netSalary,
                    'status' => 'paid',
                ]);
                
                $generated++;
            }
            
            return successResponse(
                'Paies générées avec succès pour la période ' . $period,
                ['generated_count' => $generated],
                new PayrollResource($payroll->load('employee')),
                201
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('Erreur lors de la génération des paies: ' . $th->getMessage(), 500);
        }

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
            ->whereBetween('created_at', [$startDate, $endDate])
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
