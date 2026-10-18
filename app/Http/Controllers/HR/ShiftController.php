<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shift\StoreShiftRequest;
use App\Models\Department;
use App\Models\Shift;
use App\Services\AuditLogger;
use App\Services\ShiftService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(private AuditLogger $audit, private ShiftService $shifts) {}

    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $shifts = Shift::query()->withCount('users')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->query('q').'%'))
            ->orderBy('name')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $shifts->getCollection()->map(fn ($s) => [
                    'id'                   => $s->id,
                    'name'                 => $s->name,
                    'start_time'           => substr($s->start_time, 0, 5),
                    'end_time'             => substr($s->end_time, 0, 5),
                    'grace_period_minutes' => $s->grace_period_minutes,
                    'working_days'         => $s->working_days ?? [],
                    'users_count'          => $s->users_count,
                    'urls'                 => [
                        'edit'    => route('hr.shifts.edit', $s),
                        'assign'  => route('hr.shifts.assign', $s),
                        'destroy' => route('hr.shifts.destroy', $s),
                    ],
                ]),
                'pagination' => $this->paginationMeta($shifts),
            ]);
        }

        return view('hr.shifts.index', [
            'shifts'      => $shifts,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('hr.shifts.create');
    }

    public function store(StoreShiftRequest $request): RedirectResponse
    {
        $shift = Shift::create($request->validated());
        $this->audit->log('shift.created', $shift, [], $shift->only(['name', 'start_time', 'end_time']), "Created shift {$shift->name}");

        return redirect()->route('hr.shifts.index')->with('status', 'Shift created.');
    }

    public function edit(Shift $shift): View
    {
        return view('hr.shifts.edit', ['shift' => $shift]);
    }

    public function update(StoreShiftRequest $request, Shift $shift): RedirectResponse
    {
        $before = $shift->only(['name', 'start_time', 'end_time', 'grace_period_minutes']);
        $shift->update($request->validated());
        $this->audit->log('shift.updated', $shift, $before, $shift->only(array_keys($before)), "Updated shift {$shift->name}");

        return redirect()->route('hr.shifts.index')->with('status', 'Shift updated.');
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        $shift->delete();

        return redirect()->route('hr.shifts.index')->with('status', 'Shift deleted.');
    }

    public function assign(Request $request, Shift $shift): RedirectResponse
    {
        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'overwrite_users' => ['nullable', 'boolean'],
        ]);

        $this->shifts->assignToDepartment($shift, Department::findOrFail($data['department_id']), $request->boolean('overwrite_users'));

        return back()->with('status', 'Shift assigned to department.');
    }
}
