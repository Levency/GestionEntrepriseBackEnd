<?php

namespace App\Http\Controllers\api\personnel;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    /**
     * Liste tous les employés
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $department = $request->get('department_id');
        $status = $request->get('status');
        $search = $request->get('search');
        
        $query = Employee::with(['user', 'department']);
        
        if ($department) {
            $query->where('department_id', $department);
        }
        
        if ($status === 'active') {
            $query->whereHas('user', fn($q) => $q->where('is_active', true));
        } elseif ($status === 'inactive') {
            $query->whereHas('user', fn($q) => $q->where('is_active', false));
        }
        
        if ($search) {
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('employee_id', 'like', "%{$search}%");
        }
        
        $employees = $query->latest()->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $employees
        ]);
    }

    /**
     * Créer un nouvel employé
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,manager,cashier,employee',
            'employee_id' => 'required|unique:employees,employee_id',
            'position' => 'required|string',
            'department_id' => 'nullable|exists:departments,id',
            'hire_date' => 'required|date',
            'salary' => 'required|numeric|min:0',
            'employment_type' => 'required|in:full_time,part_time,contract',
            'national_id' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Créer l'utilisateur
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'is_active' => true,
            ]);
            
            // Créer l'employé
            $employee = Employee::create([
                'user_id' => $user->id,
                'employee_id' => $request->employee_id,
                'national_id' => $request->national_id,
                'position' => $request->position,
                'department_id' => $request->department_id,
                'hire_date' => $request->hire_date,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'marital_status' => $request->marital_status,
                'children_count' => $request->children_count ?? 0,
                'salary' => $request->salary,
                'employment_type' => $request->employment_type,
                'transport_allowance' => $request->transport_allowance ?? 0,
                'housing_allowance' => $request->housing_allowance ?? 0,
                'meal_allowance' => $request->meal_allowance ?? 0,
                'emergency_contact' => $request->emergency_contact,
                'emergency_phone' => $request->emergency_phone,
                'bank_name' => $request->bank_name,
                'bank_account' => $request->bank_account,
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Employé créé avec succès',
                'data' => $employee->load(['user', 'department'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un employé
     */
    public function show($id)
    {
        $employee = Employee::with(['user', 'department'])->find($id);
        
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employé non trouvé'
            ], 404);
        }
        
        // Calculer les statistiques
        $stats = [
            'total_attendance' => $employee->attendances()->count(),
            'present_days' => $employee->attendances()->where('status', 'present')->count(),
            'absent_days' => $employee->attendances()->where('status', 'absent')->count(),
            'late_days' => $employee->attendances()->where('status', 'late')->count(),
            'total_leaves' => $employee->leaves()->count(),
            'pending_leaves' => $employee->leaves()->where('status', 'pending')->count(),
            'approved_leaves' => $employee->leaves()->where('status', 'approved')->count(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => [
                'employee' => $employee,
                'stats' => $stats
            ]
        ]);
    }

    /**
     * Mettre à jour un employé
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::find($id);
        
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employé non trouvé'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $employee->user_id,
            'phone' => 'nullable|string',
            'position' => 'required|string',
            'department_id' => 'nullable|exists:departments,id',
            'salary' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Mettre à jour l'utilisateur
            $employee->user->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'role' => $request->role ?? $employee->user->role,
            ]);
            
            // Mettre à jour l'employé
            $employee->update($request->except(['name', 'email', 'phone', 'password', 'role']));
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Employé mis à jour avec succès',
                'data' => $employee->load(['user', 'department'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un employé
     */
    public function destroy($id)
    {
        $employee = Employee::find($id);
        
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employé non trouvé'
            ], 404);
        }
        
        DB::beginTransaction();
        try {
            $employee->user->delete();
            $employee->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Employé supprimé avec succès'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Historique des présences
     */
    public function attendanceHistory($id)
    {
        $employee = Employee::find($id);
        
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employé non trouvé'
            ], 404);
        }
        
        $attendances = $employee->attendances()
            ->latest('date')
            ->paginate(30);
        
        return response()->json([
            'success' => true,
            'data' => $attendances
        ]);
    }

    /**
     * Historique des paies
     */
    public function payrollHistory($id)
    {
        $employee = Employee::find($id);
        
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employé non trouvé'
            ], 404);
        }
        
        $payrolls = $employee->payrolls()
            ->latest('payment_date')
            ->paginate(12);
        
        return response()->json([
            'success' => true,
            'data' => $payrolls
        ]);
    }

    /**
     * Activer un employé
     */
    public function activate($id)
    {
        $employee = Employee::find($id);
        
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employé non trouvé'
            ], 404);
        }
        
        $employee->user->update(['is_active' => true]);
        
        return response()->json([
            'success' => true,
            'message' => 'Employé activé avec succès'
        ]);
    }

    /**
     * Désactiver un employé
     */
    public function deactivate($id)
    {
        $employee = Employee::find($id);
        
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employé non trouvé'
            ], 404);
        }
        
        $employee->user->update(['is_active' => false]);
        
        return response()->json([
            'success' => true,
            'message' => 'Employé désactivé avec succès'
        ]);
    }
}
