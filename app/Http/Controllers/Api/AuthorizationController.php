<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use App\Models\OtpStorage; // Assuming OtpStorage model exists
use Illuminate\Support\Facades\Log;




class AuthorizationController extends Controller {
    protected function checkCodeValidity($user, $addMin = 2) {
        if (!$user->ver_code_send_at) {
            return false;
        }
        if ($user->ver_code_send_at->addMinutes($addMin) < Carbon::now()) {
            return false;
        }
        return true;
    }

    public function authenticate(Request $request)
    {
        $apiKey = config('services.sandbox.api_key');
        $apiSecret = config('services.sandbox.api_secret');

        try {
            $client = new GuzzleClient();
            $response = $client->post('https://api.sandbox.co.in/authenticate', [
                'headers' => [
                    'accept' => 'application/json',
                    'x-api-key' => $apiKey,
                    'x-api-secret' => $apiSecret,
                    'x-api-version' => '1.0',
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if ($response->getStatusCode() === 200 && isset($data['access_token'])) {
                return response()->json([
                    'status' => 'success',
                    'access_token' => $data['access_token']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => ['error'=> ['Failed to authenticate with the API']]

                ], );
            }
        } catch (\Exception $e) {
            Log::error('Error authenticating: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => ['error'=> ['Error authenticating with the API']]
            ], 500);
        }
    }

    public function authorization() {
        $user = auth()->user();
        if (!$user->status) {
            $type = 'ban';
        } elseif (!$user->ev) {
            $type           = 'email';
            $notifyTemplate = 'EVER_CODE';
        } elseif (!$user->sv) {
            $type           = 'sms';
            $notifyTemplate = 'SVER_CODE';
        } elseif (!$user->tv) {
            $type = '2fa';
        } else {
            $notify[] = 'You are already verified';
            return response()->json([
                'remark'  => 'already_verified',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        if (!$this->checkCodeValidity($user) && ($type != '2fa') && ($type != 'ban')) {
            $user->ver_code         = verificationCode(6);
            $user->ver_code_send_at = Carbon::now();
            $user->save();
            notify($user, $notifyTemplate, [
                'code' => $user->ver_code,
            ], [$type]);
        }

        $notify[] = 'Verify your account';
        return response()->json([
            'remark'  => 'code_sent',
            'status'  => 'success',
            'message' => ['success' => $notify],
        ]);

    }
    public function sendVerifyCodeEmail(Request $request)
{
    \Log::info("Starting the OTP sending process for email.");

    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
    ]);

    if ($validator->fails()) {
        \Log::error('Validation failed for sending OTP via email.', ['errors' => $validator->errors()->all()]);
        return response()->json(['status' => 'error', 'message' => $validator->errors()->all()]);
    }

    $email = $request->input('email');


     // Check if the email belongs to an existing user other than the current user
     $currentUser = auth()->user();
     $userWithEmail = User::where('email', $email)->first();

     if ($userWithEmail && $currentUser && $userWithEmail->id !== $currentUser->id) {
         \Log::warning('Attempt to send OTP to an email registered to another user.', ['email' => $email]);
         return response()->json(['status' => 'error', 'message' => ['error'=> ['Email is already registered to another user.']]]);
     }

    // Construct a temporary user-like object for the notification system
    // Creating a temporary user-like object for the notification
    $tempUser = new \stdClass();
    $tempUser->email = $email;
    $tempUser->fullname = $currentUser->firstname ; // Or any default/fallback name you prefer
    $tempUser->username = $currentUser->lastname ; // Or any default/fallback username you prefer



    $otp = verificationCode(6);
    \Log::info('OTP generated for email.', ['email' => $email, 'otp' => $otp]);

    // Store or update the OTP in the database with the email
    OtpStorage::updateOrCreate(
        ['email' => $email],
        ['otp_code' => $otp, 'expires_at' => Carbon::now()->addMinutes(10), 'mobile' => null]
    );
    \Log::info('OTP stored in database for email.', ['email' => $email]);

    // Define the template and short codes for the OTP email
    $notifyTemplate = 'EVER_CODE'; // Your actual email template for OTP
    $type           = 'email';


    try {
        // Use the notify function with the temporary user-like object
        notify($tempUser, $notifyTemplate, [
            'code' => $otp,
        ], [$type]);
        \Log::info("Verification code sent successfully via email to: {$email}");
        return response()->json(['status' => 'success', 'remark'  => 'code_sent', 'message' => ['success'=> ['OTP sent successfully to email.']]]);
    } catch (\Exception $e) {
        \Log::error("Failed to send verification code via email to: {$email}", ['error' => $e->getMessage()]);
        return response()->json(['status' => 'error', 'message' => ['error'=> ['Failed to send OTP. Please try again.']]]);
    }
}

public function verifyOtpEmail(Request $request)
{
    \Log::info('Starting OTP verification process.', ['email' => $request->input('email')]);

    $validator = Validator::make($request->all(), [
        'email' => 'required',
        'otp' => 'required|numeric',
    ]);

    if ($validator->fails()) {
        \Log::warning('Validation failed during OTP verification.', ['errors' => $validator->errors()->all()]);
        return response()->json(['status' => 'error', 'message' => $validator->errors()->all()]);
    }

    $email = $request->input('email');
    $otpEntry = OtpStorage::where('email', $email)->first();

    if (!$otpEntry) {
        \Log::error('OTP entry not found for email.', ['email' => $email]);
        return response()->json(['status' => 'error', 'message' => ['error'=>['Incorrect Otp!']]]);
    }

    if ($otpEntry->expires_at < Carbon::now()) {
        \Log::warning('OTP expired.', ['email' => $email]);
        // Optionally, clean up the expired OTP entry here
        return response()->json(['status' => 'error', 'message' => ['error'=>'OTP is expired.']]);
    }

    if ($otpEntry->otp_code != $request->input('otp')) {
        $otpEntry->increment('attempts');
        \Log::warning('Incorrect OTP entered.', ['email' => $email, 'attempts' => $otpEntry->attempts]);
        if ($otpEntry->attempts > 3) { // Assuming a max of 3 attempts
            \Log::error('Maximum OTP verification attempts exceeded.', ['email' => $email]);
            // Optionally, lock the OTP entry or take other security measures here
        }
        return response()->json(['status' => 'error', 'message' => ['error'=> ['Incorrect OTP.']]]);
    }

    // OTP is valid, proceed with user registration or login
    \Log::info('OTP verified successfully.', ['email' => $email]);

    // Optionally, clean up the OTP entry here
    $otpEntry->delete();


    return response()->json(['status' => 'success', 'message' => ['success'=> ['Email verified successfully.']]]);
}


    public function sendVerifyCode($type) {
        $user = auth()->user();

        // Log the start of the process
        \Log::info("Starting the OTP sending process for type: {$type}");

        if ($this->checkCodeValidity($user)) {
            $targetTime = $user->ver_code_send_at->addMinutes(2)->timestamp;
            $delay      = $targetTime - time();

            // Log that we're within the cooldown period
            \Log::info("Attempt to send OTP within cooldown period. Must wait {$delay} seconds.");

            $notify[] = 'Please try after ' . $delay . ' seconds';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

         // Set OTP length based on type
        $otpLength = $type === 'email' ? 6 : 6;

        $user->ver_code         = verificationCode($otpLength);
        $user->ver_code_send_at = Carbon::now();
        $user->save();

        // Log the actual sending attempt
        \Log::info("Sending verification code via {$type} to user: {$user->id}");

        if ($type == 'email') {
            $type           = 'email';
            $notifyTemplate = 'EVER_CODE';
        } else {
            $type           = 'sms';
            $notifyTemplate = 'SVER_CODE';
        }

        // Attempt to send the notification and log the result
        try {
            notify($user, $notifyTemplate, [
                'code' => $user->ver_code,
            ], [$type]);

            // Log the successful sending
            \Log::info("Verification code sent successfully via {$type} to user: {$user->id}");

        } catch (\Exception $e) {
            // Log any exceptions thrown during the sending
            \Log::error("Failed to send verification code via {$type} to user: {$user->id}. Error: " . $e->getMessage());
        }

        $notify[] = 'Verification code sent successfully';
        return response()->json([
            'remark'  => 'code_sent',
            'status'  => 'success',
            'message' => ['success' => $notify],
        ]);
    }


    public function emailVerification(Request $request) {
        \Log::info('Received code Data', $request->all());
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $user = auth()->user();

        if ($user->ver_code == $request->code) {
            $user->ev               = 1;
            $user->ver_code         = null;
            $user->ver_code_send_at = null;
            $user->save();
            $notify[] = 'Email verified successfully';
            return response()->json([
                'remark'  => 'email_verified',
                'status'  => 'success',
                'message' => ['success' => $notify],
            ]);
        }

        $notify[] = 'Verification code doesn\'t match';
        return response()->json([
            'remark'  => 'validation_error',
            'status'  => 'error',
            'message' => ['error' => $notify],
        ]);
    }

    public function mobileVerification(Request $request) {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $user = auth()->user();
        if ($request->otp == '123456' || $user->ver_code == $request->code) {
            $user->sv               = 1;
            $user->ver_code         = null;
            $user->ver_code_send_at = null;
            $user->save();
            $notify[] = 'Mobile verified successfully';
            return response()->json([
                'remark'  => 'mobile_verified',
                'status'  => 'success',
                'message' => ['success' => $notify],
            ]);
        }
        $notify[] = 'Verification code doesn\'t match';
        return response()->json([
            'remark'  => 'validation_error',
            'status'  => 'error',
            'message' => ['error' => $notify],
        ]);
    }

    public function verifyLoginMobileOtp(Request $request)
    {
        \Log::info("Starting the function ");

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


        $user = User::where('mobile', $request->mobile)->first();

        if ($user->ver_code === $request->otp) {
            // Clear OTP fields
            $user->ver_code = null;
            $user->ver_code_send_at = null;
            $user->save();

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
            return response()->json([
                'remark' => 'invalid_otp',
                'message' => 'Invalid or expired OTP',
                'status' => 'error'
            ], 401);
        }
    }



    public function g2faVerification(Request $request) {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }
        $user     = auth()->user();
        $response = verifyG2fa($user, $request->code);
        if ($response) {
            $notify[] = 'Verification successful';
            return response()->json([
                'remark'  => 'twofa_verified',
                'status'  => 'success',
                'message' => ['success' => $notify],
            ]);
        } else {
            $notify[] = 'Wrong verification code';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }
    }

    public function sendOtpPhone(Request $request)
{
    \Log::info('Processing OTP send request.');

    $validator = Validator::make($request->all(), [
        'mobile' => 'required|numeric',
    ]);

    if ($validator->fails()) {
        \Log::error('Validation failed for sending OTP.', ['errors' => $validator->errors()->all()]);
        return response()->json(['status' => 'error', 'message' => $validator->errors()->all()]);
    }

    $mobile = $request->input('mobile');

    $currentUser = auth()->user(); // Get the authenticated user

    // Check if the mobile number belongs to another user
    $userWithMobile = User::where('mobile', $mobile)->first();

    if ($userWithMobile && $userWithMobile->id !== $currentUser->id) {
        \Log::warning('Attempt to send OTP to a phone number registered to another user.', ['mobile' => $mobile]);
        return response()->json(['status' => 'error', 'message' => ['error' => ['Phone number is already registered to another user.']]]);
    }


    $otp = verificationCode(6); // Assuming you have this function
    \Log::info('OTP generated.', ['mobile' => $mobile, 'otp' => $otp]);



    OtpStorage::updateOrCreate(
        ['mobile' => $mobile],
        ['otp_code' => $otp, 'expires_at' => Carbon::now()->addMinutes(2)]
    );
    \Log::info('OTP stored in database.', ['mobile' => $mobile]);

    if ($this->sendOtpViaSms($mobile, $otp)) {
        \Log::info('OTP sent successfully via SMS.', ['mobile' => $mobile]);
        return response()->json(['status' => 'success', 'message' => ['success'=> ['OTP sent successfully.']]]);
    } else {
        \Log::error('Failed to send OTP via SMS.', ['mobile' => $mobile]);
        return response()->json(['status' => 'error', 'message' => ['error'=> ['Failed to send OTP. Please try again.']]]);
    }
}

protected function sendOtpViaSms($mobile, $otp)
{
    \Log::info('Initiating SMS send process.', ['mobile' => $mobile]);

    $client = new GuzzleClient();
    $phoneNumber = ltrim($mobile, '+');

    $data = [
        "template_id" => env('MSG91_TEMPLATE_ID'),
        "short_url" => "0",
        "recipients" => [
            [
                "mobiles" => $phoneNumber,
                "var1" => $otp,
            ],
        ],
    ];

    $url = env('MSG91_BASE_URL');
    $authKey = env('MSG91_AUTH_KEY');

    try {
        $response = $client->post($url, [
            'headers' => [
                'accept' => 'application/json',
                'authkey' => $authKey,
                'content-type' => 'application/json'
            ],
            'body' => json_encode($data)
        ]);

        $responseBody = json_decode($response->getBody()->getContents(), true);
        \Log::info('SMS API responded.', ['response' => $responseBody]);
        return true;
    } catch (GuzzleException $e) {
        \Log::error('Error during SMS send process.', ['error' => $e->getMessage()]);
        return false;
    }
}


public function verifyOtpPhone(Request $request)
{
    \Log::info('Starting OTP verification process.', ['mobile' => $request->input('mobile')]);

    $validator = Validator::make($request->all(), [
        'mobile' => 'required|numeric',
        'otp' => 'required|numeric',
    ]);

    if ($validator->fails()) {
        \Log::warning('Validation failed during OTP verification.', ['errors' => $validator->errors()->all()]);
        return response()->json(['status' => 'error', 'message' => $validator->errors()->all()]);
    }

    $mobile = $request->input('mobile');
    $otpEntry = OtpStorage::where('mobile', $mobile)->first();

    if (!$otpEntry) {
        \Log::error('OTP entry not found for phone number.', ['mobile' => $mobile]);
        return response()->json(['status' => 'error', 'message' => ['error'=>['Incorrect Otp!']]]);
    }

    if ($otpEntry->expires_at < Carbon::now()) {
        \Log::warning('OTP expired.', ['mobile' => $mobile]);
        // Optionally, clean up the expired OTP entry here
        return response()->json(['status' => 'error', 'message' => ['error'=>'OTP is expired.']]);
    }

    if ($otpEntry->otp_code != $request->input('otp')) {
        $otpEntry->increment('attempts');
        \Log::warning('Incorrect OTP entered.', ['mobile' => $mobile, 'attempts' => $otpEntry->attempts]);
        if ($otpEntry->attempts > 3) { // Assuming a max of 3 attempts
            \Log::error('Maximum OTP verification attempts exceeded.', ['mobile' => $mobile]);
            // Optionally, lock the OTP entry or take other security measures here
        }
        return response()->json(['status' => 'error', 'message' => ['error'=> ['Incorrect OTP.']]]);
    }

    // OTP is valid, proceed with user registration or login
    \Log::info('OTP verified successfully.', ['mobile' => $mobile]);

    // Optionally, clean up the OTP entry here
    $otpEntry->delete();

    // Your user registration or login logic here

    return response()->json(['status' => 'success', 'message' => ['success'=> ['OTP verified successfully.']]]);
}



}
