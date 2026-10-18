<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\StoreLeaveTypeRequest;
use App\Models\LeaveType;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $types = LeaveType::query()->orderBy('name')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $types->getCollection()->map(fn ($t) => [
                    'id'                    => $t->id,
                    'name'                  => $t->name,
                    'code'                  => $t->code,
                    'default_days_per_year' => $t->default_days_per_year,
                    'is_paid'               => $t->is_paid,
                    'is_active'             => $t->is_active,
                    'urls'                  => [
                        'edit'    => route('hr.leave-types.edit', $t),
                        'destroy' => route('hr.leave-types.destroy', $t),
                    ],
                ]),
                'pagination' => $this->paginationMeta($types),
            ]);
        }

        return view('hr.leave_types.index', ['types' => $types]);
    }

    public function create(): View
    {
        return view('hr.leave_types.create');
    }

    public function store(StoreLeaveTypeRequest $request): RedirectResponse
    {
        $type = LeaveType::create($this->normalize($request->validated()));
        $this->audit->log('leave_type.created', $type, [], $type->only(['name', 'code']), "Created leave type {$type->name}");

        return redirect()->route('hr.leave-types.index')->with('status', 'Leave type created.');
    }

    public function edit(LeaveType $leaveType): View
    {
        return view('hr.leave_types.edit', ['leaveType' => $leaveType]);
    }

    public function update(StoreLeaveTypeRequest $request, LeaveType $leaveType): RedirectResponse
    {
        $before = $leaveType->only(['name', 'default_days_per_year', 'is_paid']);
        $leaveType->update($this->normalize($request->validated()));
        $this->audit->log('leave_type.updated', $leaveType, $before, $leaveType->only(array_keys($before)), "Updated leave type {$leaveType->name}");

        return redirect()->route('hr.leave-types.index')->with('status', 'Leave type updated.');
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        $leaveType->delete();

        return redirect()->route('hr.leave-types.index')->with('status', 'Leave type deleted.');
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function normalize(array $data): array
    {
        foreach (['is_paid', 'requires_approval', 'carry_over', 'is_active'] as $flag) {
            $data[$flag] = (bool) ($data[$flag] ?? false);
        }

        return $data;
    }
}
