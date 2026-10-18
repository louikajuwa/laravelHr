<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StoreSalaryStructureRequest;
use App\Models\SalaryStructure;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryStructureController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $structures = SalaryStructure::query()
            ->with('user:id,name,employee_code')
            ->where('is_active', true)
            ->when($request->filled('q'), fn ($q) => $q->whereHas('user', fn ($u) => $u->search($request->query('q'))))
            ->orderByDesc('effective_from')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $structures->getCollection()->map(fn ($s) => [
                    'id'                         => $s->id,
                    'user_name'                  => $s->user->name,
                    'employee_code'              => $s->user->employee_code,
                    'basic_salary'               => number_format($s->basic_salary, 2),
                    'overtime_rate_multiplier'   => $s->overtime_rate_multiplier,
                    'late_deduction_per_minute'  => number_format($s->late_deduction_per_minute, 2),
                    'perfect_attendance_bonus'   => number_format($s->perfect_attendance_bonus, 2),
                    'effective_from'             => $s->effective_from->format('M d, Y'),
                    'urls'                       => ['edit' => route('hr.salary-structures.edit', $s)],
                ]),
                'pagination' => $this->paginationMeta($structures),
            ]);
        }

        return view('hr.salary_structures.index', ['structures' => $structures]);
    }

    public function create(Request $request): View
    {
        return view('hr.salary_structures.create', [
            'employees' => User::active()->orderBy('name')->get(['id', 'name', 'employee_code']),
            'selectedUser' => $request->integer('user_id'),
        ]);
    }

    public function store(StoreSalaryStructureRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            // Deactivate previous structures for this employee (versioning).
            SalaryStructure::where('user_id', $data['user_id'])->where('is_active', true)
                ->update(['is_active' => false, 'effective_to' => now()->toDateString()]);

            $structure = SalaryStructure::create(array_merge($data, ['is_active' => true]));
            $this->audit->log('salary_structure.updated', $structure, [], [
                'basic_salary' => $structure->basic_salary,
            ], "Set salary structure for user #{$structure->user_id}");
        });

        return redirect()->route('hr.salary-structures.index')->with('status', 'Salary structure saved.');
    }

    public function edit(SalaryStructure $salaryStructure): View
    {
        return view('hr.salary_structures.edit', [
            'structure' => $salaryStructure->load('user:id,name,employee_code'),
            'employees' => User::active()->orderBy('name')->get(['id', 'name', 'employee_code']),
        ]);
    }

    public function update(StoreSalaryStructureRequest $request, SalaryStructure $salaryStructure): RedirectResponse
    {
        $before = $salaryStructure->only(['basic_salary', 'overtime_rate_multiplier']);
        $salaryStructure->update($request->validated());
        $this->audit->log('salary_structure.updated', $salaryStructure, $before, $salaryStructure->only(array_keys($before)), "Updated salary structure #{$salaryStructure->id}");

        return redirect()->route('hr.salary-structures.index')->with('status', 'Salary structure updated.');
    }
}
