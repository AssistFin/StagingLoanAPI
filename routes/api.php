<?php

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\KycController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Admin\UTMController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\LoanApplyController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\LoanPaymentController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\ScoreMeWebhookController;

Route::post('/scoreme/webhook', [ScoreMeWebhookController::class, 'handle']);
Route::post('/cashfree/webhook', [LoanApplyController::class, 'handleWebhook']);
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::namespace('Api')->name('api.')->group(function () {

    Route::get('/kfs-document/{filename}/{loanon}', function ($filename, $loanon) {
        $filePath = config('services.docs.upload_kfs_doc') . '/documents/loan_'. $loanon . '/kfs/' . $filename;
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }
    
        return response()->file($filePath);
    })->where('filename', '.*');

    Route::get('/updated/kfs-document/{filename}/{loanon}', function ($filename, $loanon) {
        $filePath = config('services.docs.upload_kfs_doc') . '/documents/loan_'. $loanon . '/kfs/updated_' . $filename;
    
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }
    
        return response()->file($filePath);
    })->where('filename', '.*');

    Route::get('general-setting', function () {
        $general  = GeneralSetting::first();
        $notify[] = 'General setting data';

        return response()->json([
            'remark'  => 'general_setting',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'general_setting' => $general,
            ],
        ]);
    });

    Route::get('get-countries', function () {
        $c = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $notify[] = 'General setting data';
        foreach ($c as $k => $country) {
            $countries[] = [
                'country' => $country->country,
                'dial_code' => $country->dial_code,
                'country_code' => $k,
            ];
        }
        return response()->json([
            'remark' => 'country_data',
            'status' => 'success',
            'message' => ['success' => $notify],
            'data' => [
                'countries' => $countries,
            ],
        ]);
    });

    Route::namespace('Auth')->group(function () {
        Route::post('login', [LoginController::class, 'login']);
        Route::post('loginwithmobile', [LoginController::class, 'loginWithMobile']);
        Route::post('verify-login-otp', [LoginController::class, 'verifyLoginMobileOtp']);
        Route::get('logout', [LoginController::class, 'logout']);

        Route::post('register', [RegisterController::class, 'register']);

        Route::post('auth', [AuthController::class, 'loginOrRegister']);
        Route::post('verify-otp', [AuthController::class, 'verifyOtp']);

        Route::controller(ForgotPasswordController::class)->group(function () {
            Route::post('password/email', 'sendResetCodeEmail')->name('password.email');
            Route::post('password/verify-code', 'verifyCode')->name('password.verify.code');
            Route::post('password/reset', 'reset')->name('password.update');
        });
    });

    // Route::middleware('auth:sanctum')->group(function () {

    Route::post('/utm/store', [UTMController::class, 'store']);
    Route::middleware('customSanctumAuth')->group(function () {
        Route::post('/loans/initiate-payment', [LoanPaymentController::class, 'initiatePayment']);

        Route::get('/loans/payment/verify', [LoanPaymentController::class, 'verifyPayment']);

        Route::post('/utm/link-user', [UTMController::class, 'linkUser']);

        Route::prefix('loans')->group(function () {
            Route::get('/apply', [LoanApplyController::class, 'index']); 
            Route::post('/enach', [LoanApplyController::class, 'createEnachMandate']);

            Route::get('/progress', [LoanApplyController::class, 'progress']); 
            
            Route::post('/update-loan-step', [LoanApplyController::class, 'updateLoanStep']); 

            Route::post('/apply', [LoanApplyController::class, 'store']); 

            Route::post('/personal-details', [LoanApplyController::class, 'storePersonalDetails']);
            Route::get('/personal-details/{loan_application_id}', [LoanApplyController::class, 'getPersonalDetails']);
        
            Route::post('/kyc-details', [LoanApplyController::class, 'storeKYCDetails']);
            Route::get('/kyc-details/{loan_application_id}', [LoanApplyController::class, 'getKYCDetails']);

            Route::post('/documents', [LoanApplyController::class, 'uploadLoanDocument']);
        
            Route::post('/address-details', [LoanApplyController::class, 'storeAddressDetails']);
            Route::get('/address-details/{loan_application_id}', [LoanApplyController::class, 'getAddressDetails']);

            Route::post('/employment-details', [LoanApplyController::class, 'storeEmploymentDetails']);
            Route::get('/employment-details/{loan_application_id}', [LoanApplyController::class, 'getEmploymentDetails']);
        
            Route::post('/bank-details', [LoanApplyController::class, 'storeBankDetails']);
            Route::get('/bank-details/{loan_application_id}', [LoanApplyController::class, 'getBankDetails']);

            Route::post('/approval', [LoanApplyController::class, 'loanApproval']); 
            Route::post('/disbursal', [LoanApplyController::class, 'loanDisbursal']); 
            Route::post('/acceptance', [LoanApplyController::class, 'loanAcceptance']); 
            Route::post('/getaadharaddress', [LoanApplyController::class, 'getAadharAddress']); 
        });

        //authorization
        Route::controller('AuthorizationController')->group(function () {
            Route::get('authorization', 'authorization');
            Route::get('resend-verify/{type}', 'sendVerifyCode');
            Route::post('verify-email', 'emailVerification');
            Route::post('verify-mobile', 'mobileVerification');
            Route::post('verify-g2fa', 'g2faVerification');
            Route::post('authenticate', 'authenticate');

         // Add route for sending OTP to an email

         Route::post('send-otp-email', 'sendVerifyCodeEmail');

         // Add route for verifying OTP sent to an email
        Route::post('verify-otp-email', 'verifyOtpEmail');

            // Add route for sending OTP to a phone number
        Route::post('send-otp-phone', 'AuthorizationController@sendOtpPhone')->name('send.otp.phone');

        // Add route for verifying OTP sent to a phone number
        Route::post('verify-otp-phone', 'AuthorizationController@verifyOtpPhone')->name('verify.otp.phone');
        });

        // KYC Routes
        Route::prefix('kyc')->group(function () {
            Route::post('/aadhaar/otp', [KycController::class, 'requestAadharOtp']);
            Route::post('/aadhaar/otp/verify', [KycController::class, 'verifyAadharOtp']);
            Route::post('/pan/verify', [KycController::class, 'verifyPan']);
        });

        Route::middleware(['check.status'])->group(function () {
            Route::post('user-data-submit', 'UserController@userDataSubmit')->name('data.submit');
            Route::post('get/device/token', 'UserController@getDeviceToken')->name('get.device.token');
            Route::post('submit-bank-details', 'UserController@submitBankDetails')->name('submit.bank.details');
            Route::post('submit-upi-details', 'UserController@submitUpiDetails')->name('submit.upi.details');
            Route::post('updateUserData', 'UserController@updateTrackedUserData')->name('user.update');
            Route::post('initiate-transfer', 'TransferController@initiateTransfer')->name('transfer.initiate');

            Route::middleware('registration.complete')->group(function () {
                Route::prefix('users')->group(function () {
                    Route::get('/user-info', [UserController::class, 'userInfo']); 
                });
                // Route::controller('UserController')->group(function () {
                //     Route::get('dashboard', 'dashboard');
                //     Route::get('user-info', 'userInfo');

                //     //KYC
                //     Route::get('kyc-form', 'kycForm');
                //     Route::post('kyc-submit', 'kycSubmit');
                //     Route::get('kyc-data', 'kycData');

                //     //Report
                //     Route::any('deposit/history', 'depositHistory');
                //     Route::get('transactions', 'transactions');
                //     Route::get('notification/history', 'notificationHistory');
                //     Route::get('notification/detail/{id}', 'notificationDetail');

                //     //Profile setting
                //     Route::post('profile-setting', 'submitProfile');
                //     Route::post('change-password', 'submitPassword');
                // });

                // Payment
                Route::controller('PaymentController')->group(function () {
                    Route::get('deposit/methods', 'methods')->name('deposit');
                    Route::post('deposit/insert', 'depositInsert')->name('deposit.insert');
                });

                // Withdraw
                Route::controller('WithdrawController')->group(function () {
                    Route::get('withdraw-method', 'withdrawMethod')->name('withdraw.method')->middleware('kyc');
                    Route::get('withdraw/history', 'withdrawLog')->name('withdraw.history');
                    Route::post('withdraw-request', 'withdrawStore')->name('withdraw.money')->middleware('kyc');
                    Route::post('withdraw-request/confirm', 'withdrawSubmit')->name('withdraw.submit')->middleware('kyc');
                });

                // Loan
                Route::controller('LoanController')->group(function () {
                    Route::get('eligibility/check', 'canCheckEligibility');
                    Route::get('eligibility/last-data', 'retrieveLastUserData');
                    Route::get('loan/plans', 'plans');
                    Route::get('loan/my-loans', 'list');
                    Route::post('loan/check-eligibility', 'checkEligibility');
                    Route::post('loan/apply/{id}', 'applyLoan');
                    Route::post('loan/confirm/{id}', 'loanConfirm');
                    Route::get('loan/instalment/logs/{id}', 'installments');
                });
            });
        });
    });
    // });

    Route::get('unauthenticated', 'UserController@unauthenticated');
    Route::get('language/{code}', 'UserController@language');
    Route::get('policy-pages', 'UserController@policyPages');
    Route::get('policy-detail', 'UserController@policyDetail');
    Route::get('faq', 'UserController@faq');
});
