<?php

namespace App\Http\Controllers\Me;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LateArrivalReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LateArrivalController extends Controller
{
    public function create(): View
    {
        return view('me.late_arrivals.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today',
                Rule::unique('late_arrival_reports', 'date')
                    ->where('user_id', $request->user()->id)
                    ->whereIn('status', ['pending', 'accepted', 'excused'])],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $attendance = Attendance::forUser($request->user()->id)->where('date', $data['date'])->first();

        LateArrivalReport::create([
            'user_id' => $request->user()->id,
            'attendance_id' => $attendance?->id,
            'date' => $data['date'],
            'late_minutes' => $attendance?->late_minutes ?? 0,
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        return redirect()->route('me.attendance.index')->with('status', 'Late arrival report submitted.');
    }
}
