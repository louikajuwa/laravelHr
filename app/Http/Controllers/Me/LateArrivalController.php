<?php

namespace App\Http\Controllers\Me;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LateArrivalReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LateArrivalController extends Controller
{
    public function create(): View
    {
        return view('me.late_arrivals.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
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
