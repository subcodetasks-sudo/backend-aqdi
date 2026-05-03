<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OfferResource;
use App\Http\Resources\UserResource;
use App\Http\Traits\Responser;
use App\Models\Offer;
use App\Models\User;
use App\Models\SmsLog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use TaqnyatSms;

class AuthController extends Controller
{
    use Responser;

    public function handleGoogleCallback(Request $request)
    {
        $rules = [
            'google_token' => 'required'
        ];
        $this->validate($request, $rules);

        $token = $request->google_token;
        try {
            $user = Socialite::driver('google')->userFromToken($token);

            $fullname = explode(' ', $user->getName());
            $user = User::firstOrCreate(
                [
                    'email' => $user->getEmail()
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

    //    public function login(Request $request)
    // {
    //     $rules = [
    //         'mobile' => 'required|exists:users,mobile',
    //         'password' => 'required',
    //         'fcm_token' => 'sometimes',
    //     ];

    //     $this->validate($request, $rules);

    //     // Format the mobile number
    //     $mobile = $request->mobile;
    //     if (str_starts_with($mobile, '00966')) {
    //         $mobile = substr($mobile, 5);
    //     } elseif (str_starts_with($mobile, '966')) {
    //         $mobile = substr($mobile, 3);
    //     } elseif (str_starts_with($mobile, '0')) {
    //         $mobile = ltrim($mobile, '0');
    //     }

    //     $formattedMobile = '00966' . $mobile;

    //     $user = User::where('mobile', $formattedMobile)->first();


    //     if (!$user->isVerified()) {
    //         $verificationCode = rand(1000, 9999);
    //         $user->verification_code = $verificationCode;
    //         $user->save();


    //         $recipients = $user->mobile;
    //         $sender = 'AqdiCo';
    //         $smsId = '25489';
    //         $smsResult = $this->sendSmsMessage($body, $recipients, $sender, $smsId);

    //          $result = [
    //             'user' => new UserResource($user),
    //         ];
    //         return $this->apiResponse($result, trans('api.unverified_account'));
    //     }

    //     // Check if the user is active
    //     if (!$user->isActive()) {
    //         return $this->errorMessage(trans('api.block_account'));
    //     }


    //     if (Hash::check($request->password, $user->password)) {
    //         if ($request->has('fcm_token')) {
    //             $user->fcm_token = $request->fcm_token;
    //             $user->save();
    //         }

    //         $user->refresh();
    //         $result = [
    //             'user' => new UserResource($user),
    //             'token' => $user->createToken('user_token')->plainTextToken,
    //         ];

    //         return $this->apiResponse($result, trans('api.login_success'));
    //     }


    //     return $this->errorMessage(trans('api.credentials_error'));
    // }



    public function login(Request $request)
    {
        $rules = [
            'mobile' => 'required|exists:users,mobile',
            'password' => 'required',
            'fcm_token' => 'sometimes',
        ];

        $this->validate($request, $rules);

        // Format the mobile number
        $mobile = $request->mobile;
        if (str_starts_with($mobile, '00966')) {
            $mobile = substr($mobile, 5);
        } elseif (str_starts_with($mobile, '966')) {
            $mobile = substr($mobile, 3);
        } elseif (str_starts_with($mobile, '0')) {
            $mobile = ltrim($mobile, '0');
        }

        $formattedMobile = '00966' . $mobile;

        $user = User::where('mobile', $formattedMobile)->first();

        if (!$user->isVerified()) {
            $otpType = 'login_account_verification';  // OTP type for verification during login/signup

            // Check recent OTP sent to this user of the same type
            $recentOtp = SmsLog::where('phone_number', $user->mobile)
                ->where('type', $otpType)
                ->where('sent_at', '>=', now()->subMinutes(2))
                ->first();

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
            $body = "كود تأكيد حسابك الخاص في عقدي هو: " . $verificationCode;
            $sender = 'AqdiCo';
            $smsId = '25489';

            $smsResult = $this->sendSmsMessage($body, $recipients, $sender, $smsId, $otpType);

            $result = [
                'user' => new UserResource($user),
            ];

            return $this->apiResponse($result, trans('api.unverified_account'));
        }

        // Check if the user is active
        if (!$user->isActive()) {
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
        $bearer = '5ed5a6f23fb215fa7c1a38ec12f58491';
        $taqnyt = new TaqnyatSms($bearer);

        try {
            $message = $taqnyt->sendMsg($body, $recipients, $sender, $smsId);

            SmsLog::create([
                'user_id' => auth()->id() ?? null,
                'phone_number' => $recipients,
                'message' => $body,
                'sms_id' => $smsId,
                'type' => $type,
                'sent_at' => now(),
            ]);

            return $message ? true : false;
        } catch (\Exception $e) {
            SmsLog::create([
                'user_id' => auth()->id() ?? null,
                'phone_number' => $recipients,
                'message' => 'SMS Error: ' . $e->getMessage(),
                'sms_id' => $smsId,
                'type' => $type,
                'sent_at' => now(),
            ]);
            return 'SMS Error: ' . $e->getMessage();
        }
    }


    // public function sendSmsMessage($body, $recipients, $sender, $smsId){


    //     $bearer = '5ed5a6f23fb215fa7c1a38ec12f58491';
    //     $taqnyt = new TaqnyatSms($bearer);

    //     try 
    //     {
    //         $message = $taqnyt->sendMsg($body, $recipients, $sender, $smsId);
    //         return $message ? true : false;
    //     }

    //      catch (\Exception $e) {
    //         return 'SMS Error: ' . $e->getMessage();
    //     }
    // }

    public function signup(Request $request)
    {
        $rules = [
            'fname' => 'required|string|max:255',
            'mobile' => 'required|string|max:15|unique:users,mobile',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'fcm_token' => 'sometimes',
        ];

        $this->validate($request, $rules);

        $otpType = 'signup';
        $mobile = $request->mobile;

        // Check if the same mobile received an OTP of this type recently (last 5 minutes)
        $recentOtp = SmsLog::where('phone_number', $mobile)
            ->where('type', $otpType)
            ->where('sent_at', '>=', now()->subMinutes(2))
            ->first();

        if ($recentOtp) {
            // Log blocked resend
            SmsLog::create([
                'user_id' => null,
                'phone_number' => $mobile,
                'message' => 'Resend blocked: cooldown not expired',
                'type' => $otpType,
                'sms_id' => null,
                'sent_at' => now(),
            ]);
            return $this->errorMessage('يرجى الانتظار قبل طلب رمز تحقق جديد.', 429);
        }

        $data = $request->only(['fname', 'mobile', 'email']);
        $data['password'] = bcrypt($request->password);

        $verificationCode = rand(1000, 9999);
        $data['verification_code'] = $verificationCode;

        $user = User::create($data);

        $recipients = $user->mobile;
        $body = "كود تأكيد حسابك الخاص في عقدي هو: " . $verificationCode;
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

        $mobile = $request->mobile;

        if (str_starts_with($mobile, '00966')) {
            $mobile = substr($mobile, 5);
        } elseif (str_starts_with($mobile, '966')) {
            $mobile = substr($mobile, 3);
        } elseif (str_starts_with($mobile, '0')) {
            $mobile = ltrim($mobile, '0');
        }

        $formattedMobile = '00966' . $mobile;

        $user = User::where('mobile', $formattedMobile)->firstOrFail();

        if ($user->isVerified()) {
            return $this->errorMessage(trans('api.verified_account'), 409);
        }

        $otpType = 'resend_account_verification';

        // Check if an OTP of this type was sent recently (within last 5 minutes)
        $recentOtp = SmsLog::where('phone_number', $formattedMobile)
            ->where('type', $otpType)
            ->where('sent_at', '>=', now()->subMinutes(2))
            ->first();

        if ($recentOtp) {
            // Log blocked resend
            SmsLog::create([
                'user_id' => null,
                'phone_number' => $mobile,
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

        $recipients = '966' . $mobile;
        $body = "كود تأكيد حسابك الخاص في عقدي هو: " . $user->verification_code;
        $sender = 'AqdiCo';
        $smsId = '25489';

        $smsResult = $this->sendSmsMessage($body, $recipients, $sender, $smsId, $otpType);

        // Log the SMS send action
        if ($smsResult === true) {
            SmsLog::create([
                'phone_number' => $formattedMobile,
                'type' => $otpType,
                'sent_at' => now(),
            ]);
            return $this->successMessage(trans('api.send_otp_success'));
        } else {
            return $this->errorMessage($smsResult ?: trans('api.error_sending_sms'));
        }
    }



    // public function forgotPassword(Request $request)
    // {
    //     // Validation rules
    //     $rules = [
    //         'mobile' => 'required',
    //     ];
    //     $this->validate($request, $rules);


    //     $mobile = $request->mobile;


    //     if (str_starts_with($mobile, '00966')) {

    //         $mobile = substr($mobile, 5);
    //     } elseif (str_starts_with($mobile, '966')) {

    //         $mobile = substr($mobile, 3);
    //     } elseif (str_starts_with($mobile, '0')) {

    //         $mobile = ltrim($mobile, '0');
    //     }

    //     $formattedMobile = '00966' . $mobile;


    //     $recipients = '966' . $mobile;


    //     $user = User::where('mobile', $formattedMobile)->firstOrFail();


    //     $user->reset_password_code = User::generateResetPasswordCode();
    //     $user->save();

    //     // SMS body
    //     $sender = 'AqdiCo';
    //     $smsId = '25489';


    //     // Send SMS
    //     $smsResult = $this->sendSmsMessage($body, $recipients, $sender, $smsId);

    //     if ($smsResult === true) {
    //         return $this->successMessage(trans('api.send_reset_password_code_success'));
    //     } else {
    //         return $this->errorMessage($smsResult ?: trans('api.send_reset_password_code_failed'));
    //     }
    // }


    public function forgotPassword(Request $request)
    {
        // Validation rules
        $rules = [
            'mobile' => 'required',
        ];
        $this->validate($request, $rules);

        $mobile = $request->mobile;

        if (str_starts_with($mobile, '00966')) {
            $mobile = substr($mobile, 5);
        } elseif (str_starts_with($mobile, '966')) {
            $mobile = substr($mobile, 3);
        } elseif (str_starts_with($mobile, '0')) {
            $mobile = ltrim($mobile, '0');
        }

        $formattedMobile = '00966' . $mobile;
        $recipients = '966' . $mobile;

        $user = User::where('mobile', $formattedMobile)->firstOrFail();

        $otpType = 'forgot_password';

        // Check cooldown: prevent resending if sent within last 2 minutes
        $recentOtp = SmsLog::where('phone_number', $formattedMobile)
            ->where('type', $otpType)
            ->where('sent_at', '>=', now()->subMinutes(2))
            ->first();

        if ($recentOtp) {
            // Log blocked resend
            SmsLog::create([
                'user_id' => null,
                'phone_number' => $mobile,
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

        $body = "الكود الخاص بتغير كلمة مرور حسابك في عقدي هو : " . $user->reset_password_code;
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
            return $this->errorMessage(trans("api.wrong_otp"));
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
            return $this->errorMessage(trans("api.wrong_code_to_reset_password"));
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
        if (!$user) {
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

        if (!$user) {
            return $this->apiResponse(null, trans('api.user_not_found'), 404);
        }

        $rules = [
            'fname'  => 'nullable|string|max:255',
            'email'  => 'nullable|email|unique:users,email,' . $user->id,
            'mobile' => 'nullable|string|max:20',
            'photo'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
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
            'fcm_token' => $request->fcm_token
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
