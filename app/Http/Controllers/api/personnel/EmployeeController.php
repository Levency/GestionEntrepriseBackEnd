<?php

namespace App\Http\Controllers\api\personnel;

use App\Models\User;
use App\Models\Employee;
use GrahamCampbell\ResultType\Success;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\EmployeeResouce;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    /**
     * Liste tous les employés
     */
    public function index(Request $request)
    {
        if (!auth()->user()->can('view employee')) {
            return errorResponse('Vous n\'avez pas la permission de voir les employés', 403);
        }
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
        
        return successResponse(
            'Employés récupérés avec succès',
            EmployeeResouce::collection($employees)
        );
    }

    /**
     * Créer un nouvel employé
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('create employee')) {
            return errorResponse('Vous n\'avez pas la permission de créer des employés', 403);
        }
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
            
            return successResponse(
                'Employé créé avec succès',
                new EmployeeResouce($employee->load(['department'])),
                201
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(
                'Erreur lors de la création: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Afficher un employé
     */
    public function show(Employee $employee)
    {
        if (!auth()->user()->can('view employee')) {
            return errorResponse('Vous n\'avez pas la permission de voir les employés', 403);
        }
        
        return SuccessResponse(
            'Employé récupéré avec succès',
            new EmployeeResouce($employee->load(['department']))
        );
    }

    /**
     * Mettre à jour un employé
     */
    public function update(Request $request, Employee $employee)
    {
        if (!auth()->user()->can('update employee')) {
            return errorResponse('Vous n\'avez pas la permission de modifier des employés', 403);
        }
        
        $validator = Validator::make($request->all(), [
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
            
            return successResponse(
                'Employé mis à jour avec succès',
                new EmployeeResouce($employee->load(['department']))
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(
                'Erreur: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Supprimer un employé
     */
    public function destroy(Employee $employee)
    {
        if (!auth()->user()->can('destroy employee')) {
            return errorResponse('Vous n\'avez pas la permission de supprimer des employés', 403);
        }
        DB::beginTransaction();
        try {
            // $employee->user->delete();
            $employee->delete();
            
            DB::commit();
            
            return successResponse(
                'Employé supprimé avec succès'
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(
                'Erreur: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Historique des présences
     */
    public function attendanceHistory(Employee $employee)
    {
        if (!auth()->user()->can('view employee')) {
            return errorResponse('Vous n\'avez pas la permission de voir les présences', 403);
        }
        return successResponse(
            'Historique des présences récupéré avec succès',
            $employee->attendances()->latest()->paginate(12)
        );
    }

    /**
     * Historique des paies
     */ 
    public function payrollHistory(Employee $employee)
    {
        if (!auth()->user()->can('view employee')) {
            return errorResponse('Vous n\'avez pas la permission de voir les paies', 403);
        }   
        return successResponse(
            'Historique des paies récupéré avec succès',
            $employee->payrolls()->latest()->paginate(12)
        );
    }

    /**
     * Activer un employé
     */
    public function activate(Employee $employee)
    {
        if (!auth()->user()->can('activate employee')) {
            return errorResponse('Vous n\'avez pas la permission d\'activer des employés', 403);
        }
        $employee->update(['status' => 'Activate']);
        
        return successResponse(
            'Employé activé avec succès',
            new EmployeeResouce($employee->load(['department'])),
            200
        );
    }

    /**
     * Désactiver un employé
     */
    public function deactivate(Employee $employee)
    {
        if (!auth()->user()->can('deactivate employee')) {
            return errorResponse('Vous n\'avez pas la permission de désactiver des employés', 403);
        }
        try {
            //code...
        
            $employee->update(['status' => 'Deactivate']);
            
            return successResponse(
                'Employé désactivé avec succès',
                new EmployeeResouce($employee->load(['department'])),
                200
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('Erreur lors de la désactivation de l\'employé', 500);
        }
    }
}
