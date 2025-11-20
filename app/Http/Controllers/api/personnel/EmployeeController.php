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
        $perPage = $request->get('per_page', 15);

        $employees = Employee::paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $employees,
            'message' => 'Employés récupérés avec succès',
        ], 200);
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
                'employee_code' => generateEmployeeCode($request->first_name, $request->last_name),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'position' => $request->position,
                'department_id' => $request->department_id,
                'salary' => $request->salary,
                'status' => 'Active',

            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => new EmployeeResouce($employee->load(['department'])),
                'message' => 'Employé créé avec succès',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création: ' . $e->getMessage(),
            ], 500);
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
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'avez pas la permission d\'activer des employés',
            ], 403);
        }
        $employee->update(['status' => 'Active']);

        return response()->json([
            'status' => 'success',
            'data' => new EmployeeResouce($employee->load(['department'])),
            'message' => 'Employé activé avec succès',
        ], 200);
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
            $employee->update(['status' => 'Inactive']);

            return response()->json([
                'status' => 'success',
                'data' => new EmployeeResouce($employee->load(['department'])),
                'message' => 'Employé désactivé avec succès',
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la désactivation de l\'employé',
            ], 500);
        }
    }
}
