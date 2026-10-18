<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\JobPosition;
use App\Models\Shift;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(private EmployeeService $employees) {}

    public function index(Request $request): View|JsonResponse
    {
        $employees = User::query()
            ->with(['department:id,name', 'jobPosition:id,title', 'roles:id,name'])
            ->search($request->query('q'))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->input('status') === 'active', fn ($q) => $q->active())
            ->when($request->input('status') === 'inactive', fn ($q) => $q->inactive())
            ->orderBy('name')
            ->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $employees->getCollection()->map(fn ($e) => [
                    'id' => $e->id,
                    'name' => $e->name,
                    'email' => $e->email,
                    'employee_code' => $e->employee_code,
                    'initials' => $e->initials(),
                    'is_active' => $e->is_active,
                    'department' => $e->department?->name,
                    'job_position' => $e->jobPosition?->title,
                    'roles' => $e->roles->pluck('name')->all(),
                    'urls' => [
                        'show' => route('hr.employees.show', $e),
                        'edit' => route('hr.employees.edit', $e),
                        'toggle' => route('hr.employees.toggle', $e),
                    ],
                ]),
                'pagination' => $this->paginationMeta($employees),
            ]);
        }

        return view('hr.employees.index', [
            'employees' => $employees,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('hr.employees.create', $this->formData());
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $this->employees->create($request->validated());

        return redirect()->route('hr.employees.index')->with('status', 'Employee created.');
    }

    public function show(User $employee): View
    {
        $employee->load(['department', 'jobPosition', 'shift', 'roles', 'activeSalaryStructure']);

        return view('hr.employees.show', ['employee' => $employee]);
    }

    public function edit(User $employee): View
    {
        return view('hr.employees.edit', array_merge($this->formData(), [
            'employee' => $employee->load('roles'),
        ]));
    }

    public function update(UpdateEmployeeRequest $request, User $employee): RedirectResponse
    {
        $this->employees->update($employee, $request->validated());

        return redirect()->route('hr.employees.index')->with('status', 'Employee updated.');
    }

    public function destroy(User $employee): RedirectResponse
    {
        if ($error = $this->protectionError($employee, 'delete')) {
            return back()->withErrors(['employee' => $error]);
        }

        $employee->delete();

        return redirect()->route('hr.employees.index')->with('status', 'Employee removed.');
    }

    public function toggleActive(User $employee): RedirectResponse
    {
        if ($employee->is_active && ($error = $this->protectionError($employee, 'deactivate'))) {
            return back()->withErrors(['employee' => $error]);
        }

        $this->employees->setActive($employee, ! $employee->is_active);

        return back()->with('status', 'Employee status updated.');
    }

    private function protectionError(User $employee, string $action): ?string
    {
        if ($employee->id === auth()->id()) {
            return "You cannot {$action} your own account.";
        }

        if ($employee->isAdmin() && $employee->is_active
            && ! User::role('admin')->where('is_active', true)->where('id', '!=', $employee->id)->exists()) {
            return "You cannot {$action} the last active admin.";
        }

        return null;
    }

    /**
     * @return array<string,mixed>
     */
    private function formData(): array
    {
        return [
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'positions' => JobPosition::orderBy('title')->get(['id', 'title']),
            'shifts' => Shift::orderBy('name')->get(['id', 'name']),
        ];
    }
}
