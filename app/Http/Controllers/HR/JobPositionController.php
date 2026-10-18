<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\JobPosition;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class JobPositionController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $positions = JobPosition::query()
            ->with('department:id,name')->withCount('employees')
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->query('q').'%'))
            ->orderBy('title')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $positions->getCollection()->map(fn ($p) => [
                    'id'              => $p->id,
                    'title'           => $p->title,
                    'department'      => $p->department?->name,
                    'employees_count' => $p->employees_count,
                    'urls'            => [
                        'edit'    => route('hr.positions.edit', $p),
                        'destroy' => route('hr.positions.destroy', $p),
                    ],
                ]),
                'pagination' => $this->paginationMeta($positions),
            ]);
        }

        return view('hr.positions.index', ['positions' => $positions]);
    }

    public function create(): View
    {
        return view('hr.positions.create', ['departments' => Department::orderBy('name')->get(['id', 'name'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $position = JobPosition::create($data);
        $this->audit->log('position.created', $position, [], $position->only(['title']), "Created position {$position->title}");

        return redirect()->route('hr.positions.index')->with('status', 'Position created.');
    }

    public function edit(JobPosition $position): View
    {
        return view('hr.positions.edit', [
            'position' => $position,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, JobPosition $position): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $position->update($data);
        $this->audit->log('position.updated', $position, [], $position->only(['title']), "Updated position {$position->title}");

        return redirect()->route('hr.positions.index')->with('status', 'Position updated.');
    }

    public function destroy(JobPosition $position): RedirectResponse
    {
        $position->delete();

        return redirect()->route('hr.positions.index')->with('status', 'Position deleted.');
    }
}
