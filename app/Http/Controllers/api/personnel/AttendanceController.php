<?php

namespace App\Http\Controllers\api\personnel;

use App\Http\Resources\AttendanceResouce;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    /**
     * Liste toutes les présences
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 50);
        $date = $request->get('date');
        $status = $request->get('status');

        $query = Attendance::with(['employee']);

        // Filtrer par date
        if ($date) {
            $query->whereDate('created_at', $date);
        }

        // Filtrer par statut
        if ($status) {
            $query->where('status', $status);
        }

        $attendances = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Présences récupérées avec succès',
            'data' => AttendanceResouce::collection($attendances),
        ]);
    }
    /**
     * Check-in
     */
    public function checkIn($employee)
    {
        try {
            //code...
            $today = now()->format('Y-m-d');
            $checkInTime = now()->format('H:i');

            $existingAttendance = Attendance::where('employee_id', $employee)
                ->whereDate('created_at', $today)
                ->first();

            if ($existingAttendance) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Check-in déjà enregistré pour aujourd\'hui',
                ], 400);
            }

            $attendance = Attendance::create([
                'employee_id' => $employee,
                'check_in' => $checkInTime,
                'status' => 'present',
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Check-in enregistré avec succès',
                'data' => new AttendanceResouce($attendance->load('employee')),
            ], 201);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'enregistrement du check-in',
            ], 500);
        }
    }

    /**
     * Check-out
     */
    public function checkOut($employee)
    {
        $today = now()->format('Y-m-d');
        $checkOutTime = now()->format('H:i');

        $attendance = Attendance::where('employee_id', $employee)
            ->whereDate('created_at', $today)
            ->first();

        if (!$attendance) {  // ✅ Si N'EXISTE PAS
            return response()->json([
                'status' => 'error',
                'message' => 'Aucun check-in enregistré pour aujourd\'hui',
            ], 400);
        }

        if ($attendance->check_out) {  // ✅ Si check_out déjà fait
            return response()->json([
                'status' => 'error',
                'message' => 'Check-out déjà enregistré pour aujourd\'hui',
            ], 400);
        }

        // ✅ UPDATE l'enregistrement existant
        $attendance->update(['check_out' => $checkOutTime]);

        return response()->json([
            'status' => 'success',
            'message' => 'Check-out enregistré avec succès',
            'data' => new AttendanceResouce($attendance->load('employee')),
        ], 200);
    }

    /**
     * Présences du jour
     */
    public function today()
    {
        $today = now()->format('Y-m-d');

        $attendances = Attendance::with(['employee'])
            ->whereDate('created_at', $today)
            ->get();

        $allEmployees = Employee::where('status', 'active')->get();

        $present = $attendances->whereIn('status', ['present', 'late'])->count();
        $late = $attendances->where('status', 'late')->count();
        $absent = $allEmployees->count() - $present;

        return response()->json([
            'status' => 'success',
            'data' => [
                'date' => $today,
                'summary' => [
                    'total_employees' => $allEmployees->count(),
                    'present' => $present,
                    'late' => $late,
                    'absent' => $absent,
                ],
                'attendances' => $attendances
            ]
        ]);
    }

    /**
     * Pointer tout
     */
    public function markAllPresent(Request $request)
    {
        $today = now()->format('Y-m-d');
        $allEmployees = Employee::where('status', 'active')->get();
        foreach ($allEmployees as $employee) {
            $existingAttendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('created_at', $today)
                ->first();

            if (!$existingAttendance) {
                Attendance::create([
                    'employee_id' => $employee->id,
                    'check_in' => '09:00',
                    'status' => 'present',
                ]);
            }
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Tous les employés ont été marqués comme présents pour aujourd\'hui',
        ]);
    }

    /**
     * Présences par employé
     */
    public function byEmployee($employeeId, Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereBetween('created_at', [$startDate, $endDate])  // ✅ CORRECT
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $month,
                'attendances' => $attendances
            ]
        ]);
    }


    /**
     * Résumé des présences
     */
    public function summary(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])->get();
        $totalEmployees = Employee::whereHas('user', fn($q) => $q->where('is_active', true))->count();
        $workingDays = $this->getWorkingDays($startDate, $endDate);

        $summary = [
            'period' => $month,
            'working_days' => $workingDays,
            'total_employees' => $totalEmployees,
            'total_present' => $attendances->whereIn('status', ['present', 'late'])->count(),
            'total_late' => $attendances->where('status', 'late')->count(),
            'total_absent' => $attendances->where('status', 'absent')->count(),
            'average_attendance' => $workingDays > 0 ? ($attendances->whereIn('status', ['present', 'late'])->count() / ($totalEmployees * $workingDays)) * 100 : 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Calculer les jours ouvrables
     */
    private function getWorkingDays($startDate, $endDate)
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $days = 0;

        while ($start <= $end) {
            $dayOfWeek = $start->format('N');
            if ($dayOfWeek < 6) { // Lundi à Vendredi
                $days++;
            }
            $start->modify('+1 day');
        }

        return $days;
    }
}
