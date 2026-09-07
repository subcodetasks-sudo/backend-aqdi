<?php

namespace App\Modules\Users\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OfferResource;
use App\Models\Offer;
use App\Modules\Users\Actions\DeactivateOwnAccountAction;
use App\Modules\Users\Actions\UpdateOwnProfileAction;
use App\Modules\Users\Requests\Api\UpdateFcmTokenRequest;
use App\Modules\Users\Requests\Api\UpdatePasswordRequest;
use App\Modules\Users\Resources\UserResource;
use App\Shared\Responses\Responser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    use Responser;

    public function profile(Request $request)
    {
        $user = $request->user('api');

        return $this->apiResponse(new UserResource($user), trans('api.success'));
    }

    public function deactivateUser(DeactivateOwnAccountAction $action)
    {
        $outcome = $action->execute(Auth::user());

        if ($outcome['ok']) {
            return $this->successMessage(trans('api.success_remove'));
        }

        return $this->errorMessage($outcome['message']);
    }

    public function updateProfile(Request $request, UpdateOwnProfileAction $action)
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->apiResponse(null, trans('api.user_not_found'), 404);
        }

        $request->validate([
            'fname' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,'.$user->id,
            'mobile' => 'nullable|string|max:20',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $action->execute($user, $request);

        return $this->apiResponse(new UserResource($user), trans('api.success'));
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = $request->user('api');
        $data = $request->all();
        $data['password'] = bcrypt($request->password);

        $user->update($data);

        return $this->successMessage(trans('api.success'));
    }

    public function updateFCMToken(UpdateFcmTokenRequest $request)
    {
        $user = $request->user('api');

        $user->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return $this->successMessage(trans('api.success'));
    }

    public function notifications(Request $request)
    {
        $user = $request->user('api');

        $notifications = Offer::orderBy('created_at', 'desc')->paginate(15);

        $unread_count = Offer::whereNull('read_at')->count();

        dispatch(function () use ($notifications, $user) {
            $user->notifications()->whereIn('id', $notifications->pluck('id')->toArray())->whereNull('read_at')->update(['read_at' => now()]);
        })->afterResponse();

        $data['unread_notifications'] = $unread_count;
        $data['data'] = count($notifications) ? OfferResource::collection($notifications) : null;
        $data['pagination'] = count($notifications) ? $this->paginate($notifications) : null;

        return $this->apiResponse($data, trans('api.success'));
    }
}
