<?php

namespace App\Http\Controllers;

use Illuminate\Pagination\LengthAwarePaginator;

abstract class Controller
{
    protected function paginationMeta(LengthAwarePaginator $p): array
    {
        return [
            'current_page' => $p->currentPage(),
            'last_page' => $p->lastPage(),
            'per_page' => $p->perPage(),
            'total' => $p->total(),
            'from' => $p->firstItem() ?? 0,
            'to' => $p->lastItem() ?? 0,
        ];
    }
}
