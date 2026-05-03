<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V2\Api\AllUserResource;
use App\Http\Traits\Responser;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use Responser;

    /**
     * Get all users
     */
    public function allusers(Request $request)
    {
        $usersQuery = User::query();

        $createdAtFilter = $request->query('created_at');
        if ($createdAtFilter) {
            $usersQuery = $usersQuery->when(
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

        $users = $usersQuery->latest()->paginate(20);

        return $this->apiResponse(
            AllUserResource::collection($users),
            trans('api.success')
        );
    }

    /**
     * Get today's new users
     */
    public function newcommersUser()
    {
        $users = User::whereDate('created_at', now()->toDateString())
                     ->latest()
                     ->paginate(20);

        return $this->apiResponse(
            AllUserResource::collection($users),
            trans('api.success')
        );
    }

  public function usersCompleteContracts()
  {
      $users = User::whereHas('contracts', function ($q) {
                      $q->where('is_completed', 1);
                  })
                  ->orderBy('updated_at', 'asc')
                  ->get();

      return $this->apiResponse(
          AllUserResource::collection($users),
          trans('api.success')
      );
  }

    public function block($id)
    {
        // Find the user
        $user = User::find($id);

        if ($user) {
            $user->update(['is_active' => 0]);

            return $this->apiResponse(
                [],
                trans('api.user_blocked_successfull')
            );
        }

        return $this->apiResponse(
            [],
            trans('api.user_not_found'),
            404
        );
    }


    public function deleteUser($id)
    {
        $user = User::find($id);

        if ($user) {
            $user->delete();
            return $this->apiResponse(
                [],
                trans('api.user_deleted_successfull')
            );
        }

        return $this->apiResponse(
            [],
            trans('api.user_not_found'),
            404
        );
    }

 
}
