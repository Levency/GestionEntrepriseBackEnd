<?php

namespace App\Http\Controllers\api\personnel;

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
        $date = $request->get('date');
        $status = $request->get('status');
        
        $query = Attendance::with(['employee.user']);
        
        if ($date) {
            $query->whereDate('date', $date);
        }
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $attendances = $query->latest('date')->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $attendances
        ]);
    }

    /**
     * Check-in
     */
    public function checkIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'check_in_time' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $today = now()->format('Y-m-d');
        $checkInTime = $request->check_in_time ?? now()->format('H:i');
        
        // Vérifier si déjà enregistré aujourd'hui
        $existing = Attendance::where('employee_id', $request->employee_id)
            ->whereDate('date', $today)
            ->first();
        
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Présence déjà enregistrée aujourd\'hui'
            ], 400);
        }
        
        // Déterminer si en retard (après 8h30)
        $status = $checkInTime > '08:30' ? 'late' : 'present';
        
        $attendance = Attendance::create([
            'employee_id' => $request->employee_id,
            'date' => $today,
            'check_in' => $checkInTime,
            'status' => $status,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Check-in enregistré avec succès',
            'data' => $attendance->load('employee.user')
        ], 201);
    }

    /**
     * Check-out
     */
    public function checkOut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'check_out_time' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $today = now()->format('Y-m-d');
        $checkOutTime = $request->check_out_time ?? now()->format('H:i');
        
        $attendance = Attendance::where('employee_id', $request->employee_id)
            ->whereDate('date', $today)
            ->first();
        
        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun check-in trouvé aujourd\'hui'
            ], 404);
        }
        
        if ($attendance->check_out) {
            return response()->json([
                'success' => false,
                'message' => 'Check-out déjà enregistré'
            ], 400);
        }
        
        $attendance->update(['check_out' => $checkOutTime]);
        
        return response()->json([
            'success' => true,
            'message' => 'Check-out enregistré avec succès',
            'data' => $attendance->load('employee.user')
        ]);
    }

    /**
     * Présences du jour
     */
    public function today()
    {
        $today = now()->format('Y-m-d');
        
        $attendances = Attendance::with(['employee.user'])
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
