<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $logs = AuditLog::query()
            ->with('actor:id,name')
            ->byAction($request->query('action'))
            ->between($request->query('from'), $request->query('to'))
            ->when($request->filled('actor_id'), fn ($q) => $q->where('actor_id', $request->integer('actor_id')))
            ->orderByDesc('created_at')
            ->paginate(20)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $logs->getCollection()->map(fn ($l) => [
                    'id'            => $l->id,
                    'created_epoch' => $l->epoch('created_at'),
                    'actor'         => $l->actor?->name ?? 'System',
                    'action'        => $l->action,
                    'subject_type'  => $l->subject_type ? class_basename($l->subject_type) : null,
                    'subject_id'    => $l->subject_id,
                    'description'   => $l->description,
                    'new_values'    => $l->new_values,
                ]),
                'pagination' => $this->paginationMeta($logs),
            ]);
        }

        return view('hr.audit.index', [
            'logs'    => $logs,
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
            'actors'  => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
