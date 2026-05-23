<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Http\Traits\Responser;
use App\Models\Contract;
use Illuminate\Http\Request;

class FilterContract extends Controller
{
    use Responser;

    public function filter(Request $request)
    {
        return $this->allcontracts($request);
    }

    public function allcontracts(Request $request)
    {
        $contractsQuery = Contract::query();

        $createdAtFilter = $request->query('created_at');
        if ($createdAtFilter) {
            $contractsQuery = $contractsQuery->when(
                in_array($createdAtFilter, ['today', 'week', 'month', 'year']),
                function ($query) use ($createdAtFilter) {
                    $now = now();

                    $ranges = [
                        'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
                        'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
                        'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
                        'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
                    ];

                    [$start, $end] = $ranges[$createdAtFilter] ?? [null, null];

                    if ($start && $end) {
                        $query->whereBetween('created_at', [$start, $end]);
                    }
                }
            );
        }

        if ($request->filled('search')) {
            $contractsQuery->adminSearch($request->string('search')->toString());
        }

        $contracts = $contractsQuery->latest()->paginate($this->perPageFromRequest($request));

        return $this->paginatedApiResponse(
            $contracts,
            ContractResource::collection($contracts)
        );
    }
}
