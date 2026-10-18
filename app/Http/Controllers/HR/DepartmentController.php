<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Models\Department;
use App\Models\Shift;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $departments = Department::query()
            ->withCount('employees')
            ->with(['manager:id,name', 'shift:id,name'])
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->query('q').'%'))
            ->orderBy('name')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $departments->getCollection()->map(fn ($d) => [
                    'id'              => $d->id,
                    'name'            => $d->name,
                    'code'            => $d->code,
                    'is_active'       => $d->is_active,
                    'manager'         => $d->manager?->name,
                    'shift'           => $d->shift?->name,
                    'employees_count' => $d->employees_count,
                    'urls'            => [
                        'edit'    => route('hr.departments.edit', $d),
                        'destroy' => route('hr.departments.destroy', $d),
                    ],
                ]),
                'pagination' => $this->paginationMeta($departments),
            ]);
        }

        return view('hr.departments.index', ['departments' => $departments]);
    }

    public function create(): View
    {
        return view('hr.departments.create', $this->formData());
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $dept = Department::create($request->validated());
        $this->audit->log('department.created', $dept, [], $dept->only(['name', 'code']), "Created department {$dept->name}");

        return redirect()->route('hr.departments.index')->with('status', 'Department created.');
    }

    public function edit(Department $department): View
    {
        return view('hr.departments.edit', array_merge($this->formData(), ['department' => $department]));
    }

    public function update(StoreDepartmentRequest $request, Department $department): RedirectResponse
    {
        $before = $department->only(['name', 'code', 'manager_id', 'shift_id', 'is_active']);
        $department->update($request->validated());
        $this->audit->log('department.updated', $department, $before, $department->only(array_keys($before)), "Updated department {$department->name}");

        return redirect()->route('hr.departments.index')->with('status', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->audit->log('department.deleted', $department, $department->only(['name']), [], "Deleted department {$department->name}");
        $department->delete();

        return redirect()->route('hr.departments.index')->with('status', 'Department deleted.');
    }

    /**
     * @return array<string,mixed>
     */
    private function formData(): array
    {
        return [
            'managers' => User::active()->orderBy('name')->get(['id', 'name']),
            'shifts' => Shift::orderBy('name')->get(['id', 'name']),
        ];
    }
}
