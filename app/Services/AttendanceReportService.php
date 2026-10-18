<?php

namespace App\Services;

use App\Models\Attendance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AttendanceReportService
{
    /**
     * @return array{present:int,late:int,absent:int,on_leave:int,half_day:int,total_late_minutes:int,total_overtime_minutes:int}
     */
    public function summary(string $from, string $to, ?int $departmentId = null): array
    {
        $base = fn () => $this->scoped($from, $to, $departmentId);

        return [
            'present' => (clone $base())->where('status', 'present')->count(),
            'late' => (clone $base())->where('status', 'late')->count(),
            'absent' => (clone $base())->where('status', 'absent')->count(),
            'on_leave' => (clone $base())->where('status', 'on_leave')->count(),
            'half_day' => (clone $base())->where('status', 'half_day')->count(),
            'total_late_minutes' => (int) (clone $base())->sum('late_minutes'),
            'total_overtime_minutes' => (int) (clone $base())->sum('overtime_minutes'),
        ];
    }

    public function detail(string $from, string $to, ?int $departmentId = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->scoped($from, $to, $departmentId)->with(['user:id,name,employee_code,department_id', 'user.department:id,name']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('date')->orderBy('user_id')->paginate($perPage)->withQueryString();
    }

    private function scoped(string $from, string $to, ?int $departmentId): Builder
    {
        $query = Attendance::query()->between($from, $to);

        if ($departmentId) {
            $query->whereHas('user', fn ($q) => $q->where('department_id', $departmentId));
        }

        return $query;
    }
}
