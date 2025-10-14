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
        $perPage = $request->get('per_page', 15);
        $date = $request->get('created_at');
        $status = $request->get('status');

        $query = Attendance::with(['employee']);

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $attendances = $query->latest('created_at')->paginate($perPage);

        return successResponse(
            AttendanceResouce::collection($attendances->load('employee')),
            'Présences récupérées avec succès',
            200
        );
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
                return errorResponse('Check-in déjà enregistré pour aujourd\'hui', 400);
            }

            $attendance = Attendance::create([
                'employee_id' => $employee,
                'check_in' => $checkInTime,
                'status' => 'present',
            ]);
            return successResponse(
                'Check-in enregistré avec succès',
                new AttendanceResouce($attendance->load('employee')),
                201
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('Erreur lors de l\'enregistrement du check-in', 500);
        }
    }

    /**
     * Check-out
     */
    public function checkOut($employee)
    {
        try {
            //code...
            $today = now()->format('Y-m-d');
            $checkOutTime = $request->check_out_time ?? now()->format('H:i');

            $attendance = Attendance::where('employee_id', $employee)
                ->whereDate('created_at', $today)
                ->first();

            if (!$attendance) {
                $attendance->update(['check_out' => $checkOutTime]);
                return successResponse(
                    'Check-out modifié avec succès',
                    new AttendanceResouce($attendance->load('employee')),
                    200
                );
            }

            if ($attendance->check_out) {
                return errorResponse('Check-out déjà enregistré', 400);
            }

            $attendance = Attendance::create([
                'employee_id' => $employee,
                'check_in' => $checkOutTime,
                'status' => 'absent',
            ]);

            return successResponse(
                'Check-out enregistré avec succès',
                new AttendanceResouce($attendance->load('employee')),
                200
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('Erreur lors de l\'enregistrement du check-out', 500);
        }
    }

    /**
     * Présences du jour
     */
    public function today()
    {
        $today = now()->format('Y-m-d');

        $attendances = Attendance::with(['employee'])
            ->whereDate('date', $today)
            ->get();

        $allEmployees = Employee::with('user')->whereHas('user', fn($q) => $q->where('is_active', true))->get();

        $present = $attendances->whereIn('status', ['present', 'late'])->count();
        $late = $attendances->where('status', 'late')->count();
        $absent = $allEmployees->count() - $present;

        return response()->json([
            'success' => true,
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
     * Présences par employé
     */
    public function byEmployee($employeeId, Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        $summary = [
            'total_days' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'leave' => $attendances->where('status', 'leave')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $month,
                'summary' => $summary,
                'attendances' => $attendances
            ]
        ]);
    }

    /**
     * Marquer absent
     */
    public function markAbsent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $attendance = Attendance::create([
            'employee_id' => $request->employee_id,
            'date' => $request->date,
            'status' => 'absent',
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Absence enregistrée',
            'data' => $attendance
        ], 201);
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
