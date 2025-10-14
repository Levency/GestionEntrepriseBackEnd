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
        
        $query = Employee::with( 'department');
        
        if ($department) {
            $query->where('department_id', $department);
        }
        
        // if ($status === 'active') {
        //     $query->whereHas('user', fn($q) => $q->where('is_active', true));
        // } elseif ($status === 'inactive') {
        //     $query->whereHas('user', fn($q) => $q->where('is_active', false));
        // }
        
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
            'first_name' => 'required|string|max:255|min:3',
            'last_name' => 'required|string|max:255|min:3',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'position' => 'required|string',
            'department_id' => 'nullable|exists:departments,id',
            'salary' => 'required|numeric|min:0',
            'status' => 'required|string',
        ]);


        DB::beginTransaction();
        try {
            // Créer l'utilisateur
            // $user = User::create([
            //     'name' => $request->name,
            //     'email' => $request->email,
            //     'phone' => $request->phone,
            //     'password' => Hash::make($request->password),
            //     'role' => $request->role,
            //     'is_active' => true,
            // ]);
            
            // Créer l'employé
            $employee = Employee::create([
                'employee_code' => 'CODE-' . date('YmdHis') . rand(100, 999),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'position' => $request->position,
                'department_id' => $request->department_id,
                'salary' => $request->salary,
                'status' => $request->status,

            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Employé créé avec succès',
                'data' => $employee->load( 'department')
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
        $employee = Employee::with( 'department')->find($id);
        
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
            // 'total_leaves' => $employee->leaves()->count(),
            // 'pending_leaves' => $employee->leaves()->where('status', 'pending')->count(),
            // 'approved_leaves' => $employee->leaves()->where('status', 'approved')->count(),
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


        DB::beginTransaction();
        try {
            // Mettre à jour l'utilisateur
            // $employee->user->update([
            //     'name' => $request->name,
            //     'email' => $request->email,
            //     'phone' => $request->phone,
            //     'role' => $request->role ?? $employee->user->role,
            // ]);
            
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
            // $employee->user->delete();
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
            ->latest('created_at')
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
        
        $employee->update(['status' => 'Activate']);
        
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
        
        $employee->update(['status' => 'Deactivate']);
        
        return response()->json([
            'success' => true,
            'message' => 'Employé désactivé avec succès'
        ]);
    }
}
