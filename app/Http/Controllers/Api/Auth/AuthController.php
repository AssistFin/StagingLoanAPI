<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function loginOrRegister(Request $request)
{
    Log::info('loginOrRegister method started.', $request->all());

    // Validate mobile number
    $validator = Validator::make($request->all(), [
        'mobile' => 'required|numeric',
    ]);

    if ($validator->fails()) {
        Log::error('Validation failed.', [
            'errors' => $validator->errors()->all()
        ]);
        return response()->json([
            'remark'  => 'validation_error',
            'status'  => 'error',
            'message' => ['error' => $validator->errors()->all()],
        ]);
    }

    $mobile = $request->input('mobile');
    Log::info('Mobile number validated successfully.', ['mobile' => $mobile]);

    try {
        // Check if user exists
        $user = User::where('mobile', $mobile)->first();
        Log::info('User lookup completed.', ['user_exists' => (bool)$user]);

        if (!$user) {
            Log::info('User not found. Registering new user.', ['mobile' => $mobile]);
            // Register new user
            $user = User::create([
                'mobile'       => $mobile,
                'username'     => 'user_' . uniqid(),
                'password'     => Hash::make('default_password'),
                'country_code' => 'IN',
                'status'       => 1,
                'sv'           => 0,
            ]);
            Log::info('New user registered successfully.', ['user_id' => $user->id]);
        }

        // Generate OTP
        $otpLength = 6;
        $otp = verificationCode($otpLength);

        $user->update([
            'ver_code'          => $otp,
            'ver_code_send_at'  => Carbon::now(),
        ]);
        Log::info('OTP generated and saved successfully.', ['otp' => $otp, 'user_id' => $user->id]);

        // Send OTP (Implement your notification logic here)
        $notifyTemplate = 'SVER_CODE';
        notify($user, $notifyTemplate, ['code' => $otp], ['sms']);

    } catch (\Exception $e) {
        Log::error('An error occurred during login or registration.', [
            'exception' => $e->getMessage()
        ]);
        return response()->json([
            'status' => 'error',
            'message' => ['error' => ['An error occurred. Please try again.']],
        ]);
    }

    return response()->json([
        'status'  => 'success',
        'message' => ['success' => ['OTP sent successfully.']],
    ]);
}


    public function verifyOtp(Request $request)
    {
        Log::info('verifyOtp method started. Request data:', $request->all());

        // Validate OTP
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|numeric',
            'otp' => 'required|numeric|digits:6',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed in verifyOtp.', [
                'errors' => $validator->errors()->all()
            ]);
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $user = User::where('mobile', $request->mobile)->first();
        Log::info('User lookup completed for OTP verification.', ['user_exists' => $user ? true : false]);

        if (!$user || $user->ver_code != $request->otp) {
            Log::warning('Invalid OTP or user not found.', ['mobile' => $request->mobile, 'otp' => $request->otp]);
            return response()->json([
                'status' => 'error',
                'message' => ['error' => ['Invalid OTP.']],
            ]);
        }

        // Check OTP expiration (5 minutes validity)
        if (Carbon::now()->diffInMinutes($user->ver_code_send_at) > 5) {
            Log::info('OTP expired.', ['mobile' => $request->mobile]);
            return response()->json([
                'status' => 'error',
                'message' => ['error' => ['OTP expired. Please request a new one.']],
            ]);
        }

        // Clear OTP and log in the user
        try {
            $user->ver_code = null;
            $user->ver_code_send_at = null;
            $user->sv = 1; // Mark mobile as verified
            $user->save();
            Log::info('OTP verified and user marked as verified.', ['user_id' => $user->id]);

            $token = $user->createToken('auth_token')->plainTextToken;
            Log::info('Access token generated.', ['user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::error('Error during OTP verification or token generation.', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => ['error' => ['Failed to verify OTP. Please try again.']],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => ['success' => ['OTP verified successfully.']],
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }
}
