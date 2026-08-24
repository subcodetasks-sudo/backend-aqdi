<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OfferResource;
use App\Http\Resources\UserResource;
use App\Http\Traits\Responser;
use App\Models\Offer;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\TaqnyatSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    use Responser;

    private function normalizeSaudiMobile(string $mobile): string
    {
        if (str_starts_with($mobile, '00966')) {
            $national = substr($mobile, 5);
        } elseif (str_starts_with($mobile, '966')) {
            $national = substr($mobile, 3);
        } elseif (str_starts_with($mobile, '0')) {
            $national = ltrim($mobile, '0');
        } else {
            $national = $mobile;
        }

        return '00966'.$national;
    }

    /**
     * @return list<string>
     */
    private function mobileLookupVariants(string $mobile): array
    {
        $formattedMobile = $this->normalizeSaudiMobile($mobile);

        if (str_starts_with($mobile, '00966')) {
            $national = substr($mobile, 5);
        } elseif (str_starts_with($mobile, '966')) {
            $national = substr($mobile, 3);
        } elseif (str_starts_with($mobile, '0')) {
            $national = ltrim($mobile, '0');
        } else {
            $national = $mobile;
        }

        return array_values(array_unique(array_filter([
            $mobile,
            $formattedMobile,
            '966'.$national,
            '0'.$national,
            $national,
        ])));
    }

    private function recentSmsLog(string $type, string $mobile, int $minutes = 2): ?SmsLog
    {
        return SmsLog::query()
            ->whereIn('phone_number', $this->mobileLookupVariants($mobile))
            ->where('type', $type)
            ->where('sent_at', '>=', now()->subMinutes($minutes))
            ->first();
    }

    public function handleGoogleCallback(Request $request)
    {
        $rules = [
            'google_token' => 'required',
        ];
        $this->validate($request, $rules);

        $token = $request->google_token;
        try {
            $user = Socialite::driver('google')->userFromToken($token);

            $fullname = explode(' ', $user->getName());
            $user = User::firstOrCreate(
                [
                    'email' => $user->getEmail(),
                ],
                [
                    'email_verified_at' => now(),
                    'fname' => $fullname[0],
                    'lname' => count($fullname) == 2 ? $fullname[1] : (count($fullname) == 3 ? $fullname[2] : ''),
                    'google_id' => $user->getId(),
                ]
            );
            $user->refresh();
            $result['user'] = new UserResource($user);
            $result['token'] = $user->createToken('user_token')->plainTextToken;

            return $this->apiResponse($result, trans('api.success'));
        } catch (\Exception $e) {
            return $this->errorMessage($e->getMessage());
        }
    }

    public function login(Request $request)
    {
        $rules = [
            'mobile' => 'required|exists:users,mobile',
            'password' => 'required',
            'fcm_token' => 'sometimes',
        ];

        $this->validate($request, $rules);

        $formattedMobile = $this->normalizeSaudiMobile($request->mobile);

        $user = User::whereIn('mobile', $this->mobileLookupVariants($request->mobile))->first();

        if (! $user) {
            return $this->errorMessage(trans('api.credentials_error'));
        }

        if (! $user->isVerified()) {
            $otpType = 'login_account_verification';

            $recentOtp = $this->recentSmsLog($otpType, $formattedMobile);

            if ($recentOtp) {

                // Log blocked resend
                SmsLog::create([
                    'user_id' => $user->id,
                    'phone_number' => $formattedMobile,
                    'message' => 'Resend blocked: cooldown not expired',
                    'type' => $otpType,
                    'sms_id' => null,
                    'sent_at' => now(),
                ]);

                return $this->errorMessage('يرجى الانتظار قبل طلب رمز تحقق جديد.', 429);
            }

            $verificationCode = rand(1000, 9999);
            $user->verification_code = $verificationCode;
            $user->save();

            $recipients = $user->mobile;
            $body = 'كود تأكيد حسابك الخاص في عقدي هو: '.$verificationCode;
            $sender = 'AqdiCo';
            $smsId = '25489';

            $smsResult = $this->sendSmsMessage($body, $recipients, $sender, $smsId, $otpType);

            $result = [
                'user' => new UserResource($user),
            ];

            return $this->apiResponse($result, trans('api.unverified_account'));
        }

        // Check if the user is active
        if (! $user->isActive()) {
            return $this->errorMessage(trans('api.block_account'));
        }

        if (Hash::check($request->password, $user->password)) {
            if ($request->has('fcm_token')) {
                $user->fcm_token = $request->fcm_token;
                $user->save();
            }

            $user->refresh();
            $result = [
                'user' => new UserResource($user),
                'token' => $user->createToken('user_token')->plainTextToken,
            ];

            return $this->apiResponse($result, trans('api.login_success'));
        }

        return $this->errorMessage(trans('api.credentials_error'));
    }

    public function sendSmsMessage($body, $recipients, $sender, $smsId, $type = null)
    {
        return app(TaqnyatSmsService::class)->sendAndLog(
            $body,
            $recipients,
            $type,
            auth()->id(),
            $sender,
            $smsId,
            $this->normalizeSaudiMobile((string) $recipients)
        );
    }

    public function signup(Request $request)
    {
        $rules = [
            'fname' => 'required|string|max:255',
            'mobile' => 'required|string|max:15|unique:users,mobile',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:8',
            'fcm_token' => 'sometimes',
            'platform' => 'nullable|string|in:website,web,google_play,google,android,apple_store,ios,apple,appstore,app_store',
            'utm_source' => 'nullable|string|max:64',
            'utm_medium' => 'nullable|string|max:64',
            'utm_campaign' => 'nullable|string|max:191',
            'utm_term' => 'nullable|string|max:191',
            'utm_content' => 'nullable|string|max:191',
            'gclid' => 'nullable|string|max:191',
            'fbclid' => 'nullable|string|max:191',
            'ttclid' => 'nullable|string|max:191',
            'twclid' => 'nullable|string|max:191',
            'sccid' => 'nullable|string|max:191',
        ];

        $this->validate($request, $rules);

        $otpType = 'signup';
        $formattedMobile = $this->normalizeSaudiMobile($request->mobile);

        // Check if the same mobile received an OTP of this type recently (last 5 minutes)
        $recentOtp = $this->recentSmsLog($otpType, $formattedMobile);

        if ($recentOtp) {
            // Log blocked resend
            SmsLog::create([
                'user_id' => null,
                'phone_number' => $formattedMobile,
                'message' => 'Resend blocked: cooldown not expired',
                'type' => $otpType,
                'sms_id' => null,
                'sent_at' => now(),
            ]);

            return $this->errorMessage('يرجى الانتظار قبل طلب رمز تحقق جديد.', 429);
        }

        $data = $request->only(['fname', 'mobile']);
        $data['mobile'] = $formattedMobile;
        if ($request->filled('email')) {
            $data['email'] = $request->email;
        }
        $data['password'] = bcrypt($request->password);

        $verificationCode = rand(1000, 9999);
        $data['verification_code'] = $verificationCode;

        if ($request->filled('platform')) {
            $data['platform'] = User::normalizePlatform((string) $request->input('platform'));
        }

        $user = User::create($data);

        $recipients = $user->mobile;
        $body = 'كود تأكيد حسابك الخاص في عقدي هو: '.$verificationCode;
        $sender = 'AqdiCo';
        $smsId = '25489';

        // Send SMS with OTP type
        $smsResult = $this->sendSmsMessage($body, $recipients, $sender, $smsId, $otpType);

        if ($smsResult === true) {
            return $this->apiResponse(new UserResource($user), trans('api.success'));
        } else {
            return $this->errorMessage($smsResult ?: trans('api.error_sending_sms'));
        }
    }

    public function verification(Request $request)
    {
        $rules = [
            'mobile' => 'required|exists:users,mobile',
            'verification_code' => 'required',
        ];
        $this->validate($request, $rules);
        $user = User::where('mobile', $request->mobile)->firstOrFail();

        if ($user->verification_code == $request->verification_code) {
            $user->email_verified_at = now();
            $user->save();

            return $this->successMessage(trans('api.verification_success'));
        } else {
            return $this->errorMessage(trans('api.verification_faild'));
        }
    }

    public function resend(Request $request)
    {
        $rules = [
            'mobile' => 'required',
        ];
        $this->validate($request, $rules);

        $formattedMobile = $this->normalizeSaudiMobile($request->mobile);

        $user = User::whereIn('mobile', $this->mobileLookupVariants($request->mobile))->first();

        if (! $user) {
            return $this->errorMessage(trans('api.user_not_found'), 404);
        }

        if ($user->isVerified()) {
            return $this->errorMessage(trans('api.verified_account'), 409);
        }

        $otpType = 'resend_account_verification';

        // Check if an OTP of this type was sent recently (within last 2 minutes)
        $recentOtp = $this->recentSmsLog($otpType, $formattedMobile);

        if ($recentOtp) {
            // Log blocked resend
            SmsLog::create([
                'user_id' => $user->id,
                'phone_number' => $formattedMobile,
                'message' => 'Resend blocked: cooldown not expired',
                'type' => $otpType,
                'sms_id' => null,
                'sent_at' => now(),
            ]);

            return $this->errorMessage('يرجى الانتظار قبل طلب رمز تحقق جديد.', 429);
        }

        // Generate new verification code
        $user->verification_code = rand(1000, 9999);
        $user->save();

        $recipients = $user->mobile;
        $body = 'كود تأكيد حسابك الخاص في عقدي هو: '.$user->verification_code;
        $sender = 'AqdiCo';
        $smsId = '25489';

        $smsResult = $this->sendSmsMessage($body, $recipients, $sender, $smsId, $otpType);

        if ($smsResult === true) {
            return $this->successMessage(trans('api.send_otp_success'));
        }

        return $this->errorMessage($smsResult ?: trans('api.error_sending_sms'));
    }

    public function forgotPassword(Request $request)
    {
        // Validation rules
        $rules = [
            'mobile' => 'required',
        ];
        $this->validate($request, $rules);

        $formattedMobile = $this->normalizeSaudiMobile($request->mobile);

        $user = User::whereIn('mobile', $this->mobileLookupVariants($request->mobile))->firstOrFail();
        $recipients = $user->mobile;

        $otpType = 'forgot_password';

        // Check cooldown: prevent resending if sent within last 2 minutes
        $recentOtp = $this->recentSmsLog($otpType, $formattedMobile);

        if ($recentOtp) {
            // Log blocked resend
            SmsLog::create([
                'user_id' => $user->id,
                'phone_number' => $formattedMobile,
                'message' => 'Resend blocked: cooldown not expired',
                'type' => $otpType,
                'sms_id' => null,
                'sent_at' => now(),
            ]);

            return $this->errorMessage('يرجى الانتظار قبل طلب رمز جديد.', 429);
        }

        // Generate reset password code (assumed method)
        $user->reset_password_code = User::generateResetPasswordCode();
        $user->save();

        $body = 'الكود الخاص بتغير كلمة مرور حسابك في عقدي هو : '.$user->reset_password_code;
        $sender = 'AqdiCo';
        $smsId = '25489';

        // Send SMS
        $smsResult = $this->sendSmsMessage($body, $recipients, $sender, $smsId, $otpType);

        if ($smsResult === true) {

            return $this->successMessage(trans('api.send_reset_password_code_success'));
        } else {
            return $this->errorMessage($smsResult ?: trans('api.send_reset_password_code_failed'));
        }
    }

    public function resetPasswordCode(Request $request)
    {
        $rules = [
            'mobile' => 'required|exists:users,mobile',
            'code' => 'required',
        ];
        $this->validate($request, $rules);

        $user = User::where('mobile', $request->mobile)->firstOrFail();

        if ($user->reset_password_code != $request->code) {
            return $this->errorMessage(trans('api.wrong_otp'));
        }

        return $this->successMessage(trans('api.valid_code_to_reset_password'));
    }

    public function resetPassword(Request $request)
    {
        $rules = [
            'mobile' => 'required|exists:users,mobile',
            'code' => 'required',
            'password' => 'required|confirmed',
        ];
        $this->validate($request, $rules);

        $user = User::where('mobile', $request->mobile)->firstOrFail();

        if ($user->reset_password_code != $request->code) {
            return $this->errorMessage(trans('api.wrong_code_to_reset_password'));
        }

        $user->password = bcrypt($request->password);
        $user->save();

        return $this->successMessage(trans('api.success'));
    }

    public function logout(Request $request)
    {
        $request->user('api')->tokens()->delete();

        return $this->successMessage(trans('api.logout_success'));
    }

    public function profile(Request $request)
    {
        $user = $request->user('api');

        return $this->apiResponse(new UserResource($user), trans('api.success'));
    }

    public function deactivateUser()
    {
        $user = Auth::user();
        if (! $user) {
            return $this->errorMessage(trans('api.profile_not_exist'));
        }

        $user->is_active = false;

        if ($user->save()) {

            $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();

            return $this->successMessage(trans('api.success_remove'));
        } else {
            return $this->errorMessage(trans('api.error_deactivating'));
        }
    }

    public function updateProfile(Request $request)
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->apiResponse(null, trans('api.user_not_found'), 404);
        }

        $rules = [
            'fname' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,'.$user->id,
            'mobile' => 'nullable|string|max:20',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];

        $this->validate($request, $rules);

        // Only take the allowed fields
        $data = $request->only(['fname', 'email', 'mobile']);

        // Handle file upload
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $path = fileUploader($file, 'users');

            if ($path) {
                // Delete old photo after successful upload
                deleteFile($user->photo);
                $data['photo'] = $path;
            }
        }

        // Update user profile
        $user->update($data);

        return $this->apiResponse(new UserResource($user), trans('api.success'));
    }

    public function updatePassword(Request $request)
    {
        $rules = [
            'password' => 'required|confirmed',
        ];
        $this->validate($request, $rules);

        $user = $request->user('api');
        $data = $request->all();
        $data['password'] = bcrypt($request->password);

        $user->update($data);

        return $this->successMessage(trans('api.success'));
    }

    public function updateFCMToken(Request $request)
    {
        $rules = [
            'fcm_token' => 'required',
        ];
        $this->validate($request, $rules);

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
