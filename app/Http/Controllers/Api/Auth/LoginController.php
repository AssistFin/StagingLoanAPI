<?php

namespace App\Http\Controllers\Api\Auth;

use Carbon\Carbon;
use App\Models\User;
use App\Constants\Status;
use App\Models\UserLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\AuthenticatesUsers;


class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
     */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */

    protected $username;

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function __construct()
    {
        parent::__construct();
        $this->username = $this->findUsername();
    }

    public function login(Request $request)
    {
        Log::info('******************* login');
        $validator = $this->validateLogin($request);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $credentials = request([$this->username, 'password']);

        if (!Auth::attempt($credentials)) {
            $response[] = 'Unauthorized user';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $response],
            ]);
        }

        $user        = $request->user();
        $tokenResult = $user->createToken('auth_token')->plainTextToken;
        $response[]  = 'Login Successful';
        $this->authenticated($request, $user);

        return response()->json([
            'remark'  => 'login_success',
            'status'  => 'success',
            'message' => ['success' => $response],
            'data'    => [
                'user'         => auth()->user(),
                'access_token' => $tokenResult,
                'token_type'   => 'Bearer',
            ],
        ]);
    }

    // public function loginWithMobile(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'mobile' => 'required|numeric',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'remark'  => 'validation_error',
    //             'status'  => 'error',
    //             'message' => ['error' => $validator->errors()->all()],
    //         ]);
    //     }

    //     // Check if the user exists
    //     $user = User::where('mobile', $request->mobile)->first();

    //     // If user does not exist, create a new user entry with only the mobile number
    //     if (!$user) {
    //         $user = User::create([
    //             'mobile' => $request->mobile,
    //         ]);

    //         Log::info("New user created with mobile: {$request->mobile}");
    //     }

    //     // Generate OTP
    //     $otpLength = 6;
    //     $user->ver_code = verificationCode($otpLength);
    //     $user->ver_code_send_at = Carbon::now();
    //     $user->save();

    //     $type = 'sms';
    //     $notifyTemplate = 'SVER_CODE';

    //     // Attempt to send OTP and log the result
    //     try {
    //         Log::info("Attempting to send OTP: {$user->ver_code} via {$type} to user ID: {$user->id}");
    //         notify($user, $notifyTemplate, [
    //             'code' => $user->ver_code,
    //         ], [$type]);

    //         Log::info("OTP sent successfully to user ID: {$user->id}");
    //     } catch (\Exception $e) {
    //         Log::error("Failed to send OTP to user ID: {$user->id}. Error: " . $e->getMessage());
    //     }

    //     return response()->json([
    //         'remark'  => 'code_sent',
    //         'status'  => 'success',
    //         'message' => ['success' => ['Verification code sent successfully']],
    //     ]);
    // }

    public function loginWithMobile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $cleanMobile = preg_replace('/[\s+]/', '', $request->mobile);
        // Check or create user
        $user = User::firstOrCreate(['mobile' => $cleanMobile]);

        // Generate OTP
        $otpLength = 6;
        $user->ver_code = verificationCode($otpLength);
        $user->ver_code_send_at = Carbon::now();
        $user->save();

        // Equence credentials
        $username = config('services.equence.send_sms_user');
        $password = config('services.equence.send_sms_pass');
        $senderId = config('services.equence.send_sms_from');
        $mobileWithCountryCode = $user->mobile;
        $text = "Dear User, Your OTP is {$user->ver_code} for login to LoanOne and valid till 5 minutes. Do not share this OTP to anyone for security reason";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post(config('services.equence.send_sms_url'), [
                'username' => $username,
                'password' => $password,
                'to' => $mobileWithCountryCode,
                'from' => $senderId,
                'text' => $text,
            ]);

            // dd($response->json(), $text);
            Log::info("Equence SMS Response: ", $response->json());

            return response()->json([
                'remark'  => 'code_sent',
                'status'  => 'success',
                'message' => ['success' => ['Verification code sent successfully']],
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send OTP via Equence: " . $e->getMessage());

            return response()->json([
                'remark'  => 'sms_failed',
                'status'  => 'error',
                'message' => ['error' => ['Failed to send verification code']],
            ]);
        }
    }

    public function verifyLoginMobileOtp(Request $request)
    {
        \Log::info("Starting the function ");

        $request->merge([
            'mobile' => preg_replace('/[^\d]/', '', $request->mobile) 
        ]);

        $validator = Validator::make($request->all(), [
            'mobile' => 'required|numeric|exists:users,mobile',
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $cleanMobile = preg_replace('/[\s+]/', '', $request->mobile);
        $user = User::where('mobile', $cleanMobile)->first();

        // Check if the request is for the test user and the OTP is the test OTP
        // if ( $request->otp == '123456' || $user->ver_code === $request->otp) {
        if ($user->ver_code === $request->otp) {
            // Clear OTP fields only if not test OTP
            if ($request->otp != '123456') {
                $user->ver_code = null;
                $user->ver_code_send_at = null;
                $user->save();
            }

            \Log::info("verifying verification code via {$user} to user ");

            $tokenResult = $user->createToken('auth_token')->plainTextToken;
            return response()->json([
                'remark' => 'login_success',
                'status' => 'success',
                'message' => [
                    'success' => ["Login Successful"]
                ],
                'data' => [
                    'user' => $user,
                    'access_token' => $tokenResult,
                    'token_type' => 'Bearer'
                ]
            ]);
        } else {
            $notify[] = 'Invalid Otp';
            return response()->json([
                'remark' => 'invalid_otp',
                'status' => 'error',
                'message' => ['error' => $notify],
            ]);
        }
    }


    public function findUsername()
    {
        $login = request()->input('username');

        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        request()->merge([$fieldType => $login]);
        return $fieldType;
    }

    public function username()
    {
        return $this->username;
    }

    protected function validateLogin(Request $request)
    {
        $validation_rule = [
            $this->username() => 'required|string',
            'password'        => 'required|string|min:6',
        ];

        $validate = Validator::make($request->all(), $validation_rule);
        return $validate;
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();
        $notify[] = 'Logout Successful';

        return response()->json([
            'remark'  => 'logout',
            'status'  => 'success',
            'message' => ['success' => $notify],
        ]);
    }

    public function authenticated(Request $request, $user)
    {
        $user->tv = $user->ts == Status::VERIFIED ? Status::UNVERIFIED : Status::VERIFIED;
        $user->save();

        $ip        = getRealIP();
        $exist     = UserLogin::where('user_ip', $ip)->first();
        $userLogin = new UserLogin();

        if ($exist) {
            $userLogin->longitude    = $exist->longitude;
            $userLogin->latitude     = $exist->latitude;
            $userLogin->city         = $exist->city;
            $userLogin->country_code = $exist->country_code;
            $userLogin->country      = $exist->country;
        } else {
            $info                    = json_decode(json_encode(getIpInfo()), true);
            $userLogin->longitude    = @implode(',', $info['long']);
            $userLogin->latitude     = @implode(',', $info['lat']);
            $userLogin->city         = @implode(',', $info['city']);
            $userLogin->country_code = @implode(',', $info['code']);
            $userLogin->country      = @implode(',', $info['country']);
        }

        $userAgent          = osBrowser();
        $userLogin->user_id = $user->id;
        $userLogin->user_ip = $ip;

        $userLogin->browser = @$userAgent['browser'];
        $userLogin->os      = @$userAgent['os_platform'];
        $userLogin->save();
    }
}
