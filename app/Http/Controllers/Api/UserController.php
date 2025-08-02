<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Form;
use App\Models\Loan;
use GuzzleHttp\Client;
use App\Models\Frontend;
use App\Models\Language;
use App\Constants\Status;
use App\Lib\FormProcessor;
use App\Models\DeviceToken;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\GeneralSetting;
use App\Models\LoanApplication;
use App\Rules\FileTypeValidate;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class UserController extends Controller {

    public function dashboard() {
        $user = auth()->user();

        // Fetch running loans with the transferred amount
        $runningLoans = Loan::running()
            ->where('user_id', $user->id)
            ->select('id', 'plan_id', 'user_id', 'loan_number', 'amount', 'per_installment', 'installment_interval', 'delay_value', 'charge_per_installment', 'delay_charge', 'given_installment', 'status', 'transferred_amount')
            ->with('nextInstallment', 'plan:id,name')
            ->get();

        // Calculate remaining loan amount
        $remainLoans = Loan::running()
            ->where('user_id', $user->id)
            ->selectRaw('SUM((per_installment * total_installment) - (per_installment * given_installment)) as remain_loan')
            ->first();

        // Get the next installment
        $nextInstallment = $runningLoans->pluck('nextInstallment')->sortBy('installment_date')->first();

        // Total withdrawn amount from all running loans
        $withdrawnAmount = Loan::running()
            ->where('user_id', $user->id)
            ->sum('transferred_amount');

        // Insights section with new withdrawn_amount field
        $insights['remaining_loan_amount'] = getAmount($remainLoans->remain_loan ?? 0);
        $insights['withdrawn_amount']      = getAmount($withdrawnAmount);  // Added withdrawn amount
        $insights['running_loan']          = Loan::running()->where('user_id', $user->id)->count() * 1;
        $insights['pending_loan']          = Loan::pending()->where('user_id', $user->id)->count() * 1;
        $insights['next_installment_amount'] = getAmount($nextInstallment->loan->per_installment ?? 0);
        $insights['next_installment_date'] = $nextInstallment->installment_date ?? null;

        $filePath = getFilePath('userProfile');
        $notify[] = 'User dashboard data';

        return response()->json([
            'remark'  => 'dashboard',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'user'           => $user,
                'insights'       => $insights,
                'running_loans'  => $runningLoans,  // running loans with transferred_amount
                'filePath'       => $filePath,
            ],
        ]);
    }


    public function userInfo()
    {
        $notify[] = 'User information';
        $user = auth()->user();
        $userId = auth()->id();

        $user = DB::table('users')
        ->leftJoin('pan_data', 'users.id', '=', 'pan_data.user_id')
        ->leftJoin('aadhaar_data', 'users.id', '=', 'aadhaar_data.user_id')
        ->where('users.id', $userId)
        ->select(
            'users.id',
            'users.firstname',
            'users.lastname',
            'users.email',
            'users.mobile',
            'pan_data.pan',
            'pan_data.date_of_birth',
            'aadhaar_data.aadhaar_number'
        )
        ->first();

        // Get all loans for the user with their repayment history
        $loans = LoanApplication::where('user_id', $userId)
        ->with([
            'collections' => function ($query) {
                $query->orderBy('collection_date', 'asc');
            },
            'loanApproval',
            'loanDisbursal'
        ])
        ->get()
        ->map(function ($loan, $userId) {
            $approvalAmount = optional($loan->loanApproval)->approval_amount;
            $repaymentDate = optional($loan->loanApproval)->repay_date;
            $disbursalDate = optional($loan->loanDisbursal)->disbursal_date;
            $roi = optional($loan->loanApproval)->roi; 
            $dailyRate = ($roi ?? 0) / 100; 
            $daysSinceDisbursal = 0;
            $currentRepaymentAmount = 0;
            $penalAmount = 0;
            $interestAmount = 0;
            $overdueAmount = 0;

            if ($approvalAmount && $disbursalDate && $dailyRate) {
                $daysSinceDisbursal = \Carbon\Carbon::parse($disbursalDate)->diffInDays(\Carbon\Carbon::today());
                $today = Carbon::today();
                $repaymentDate = \Carbon\Carbon::parse($loan->loanApproval->repay_date);
                $today = \Carbon\Carbon::today();
                if ($repaymentDate->lt($today)) {
                    $overDueDate = $repaymentDate->diffInDays($today); 
                } else {
                    $overDueDate = 0;
                }
                $totalInterestTillNow = $approvalAmount * $dailyRate * $daysSinceDisbursal;
                $penalAmount = ($approvalAmount * 0.0025) * $overDueDate;
                $interestAmount = $totalInterestTillNow;
                $currentRepaymentAmount = $approvalAmount + $totalInterestTillNow + $penalAmount;
                if($overDueDate > 0) {
                    $overdueAmount = round($currentRepaymentAmount, 2) - round(optional($loan->loanApproval)->repayment_amount, 2);
                }
            }

             $utr = DB::table('utr_collections')
                ->where('loan_application_id', $loan->id)
                ->where('user_id', $userId)
                ->where('status', 'closed')
                ->latest('collection_date')
                ->first();

            if ($utr) {
                $penalAmount = $utr->penal ?? 0;
                $interestAmount = $utr->interest ?? 0;
                $currentRepaymentAmount = $utr->collection_amt ?? 0;
                $overdueAmount = $utr->overdue_intrest ?? 0;
            }

            return [
                'id' => $loan->id,
                'loan_no' => $loan->loan_no,
                'loan_account_no' => $loan->loan_account_no,
                'loan_amount' => optional($loan->loanApproval)->approval_amount,
                'loan_disbursal_date' => optional($loan->loanDisbursal)->disbursal_date,
                'repayment_amount' => isset($loan->loanApproval->repayment_amount) && ($loan->loanApproval->repayment_amount != "0.00") ? optional($loan->loanApproval)->repayment_amount : round($currentRepaymentAmount, 2),
                'penalAmount' => $penalAmount,
                'interestAmount' => $interestAmount,
                'current_repayment_amount' => round($currentRepaymentAmount, 2),
                'repayment_due_date' => optional($loan->loanApproval)->repay_date,
                'overdue_amount' => $overdueAmount,
                'purpose_of_loan' => $loan->purpose_of_loan,
                'loan_disbursal_status' => $loan->loan_disbursal_status,
                'admin_approval_status' => $loan->admin_approval_status,
                'loan_closed_status' => $loan->loan_closed_status,
                'loan_closed_date' => $loan->loan_closed_date,
                'application_date' => $loan->created_at->format('Y-m-d'),
                'kfs_filename' => optional($loan->loanApproval)->kfs_path,
                'loan_disbursal_number' => optional($loan->loanDisbursal)->loan_disbursal_number,
                'repaymentHistory' => $loan->collections->map(function ($collection, $index) {
                    return [
                        'id' => $collection->id,
                        'sl_no' => $index + 1,
                        'loan_account_no' => $collection->loanApplication->loan_account_no,
                        'payment_date' => $collection->collection_date,
                        'payment_amount' => $collection->collection_amt,
                        'principal_payment' => $collection->principal,
                        'interest_payment' => $collection->interest,
                        'penal' => $collection->penal,
                    ];
                }),
            ];
        });

        return response()->json([
            'remark'  => 'user_info',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'user' => $user,
                'loans' => $loans
            ],
        ]);
    }


    public function userDataSubmit(Request $request) {
        $user = auth()->user();

        if ($user->profile_complete == 1) {
            $notify[] = 'You\'ve already completed your profile';
            return response()->json([
                'remark'  => 'already_completed',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $validator = Validator::make($request->all(), [
            'firstname' => 'required',
            'lastname'  => 'required',
            'address'   => 'nullable|string',
            'state'     => 'nullable|string',
            'zip'       => 'nullable|string',
            'city'      => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        if ($request->hasFile('image')) {
            try {
                $old         = $user->image;
                $user->image = fileUploader($request->image, getFilePath('userProfile'), getFileSize('userProfile'), $old);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload your image'];
                return back()->withNotify($notify);
            }
        }

        $user->firstname = $request->firstname;
        $user->lastname  = $request->lastname;
        $user->address   = [
            'country' => @$user->address->country,
            'address' => $request->address,
            'state'   => $request->state,
            'zip'     => $request->zip,
            'city'    => $request->city,
        ];

        $user->profile_complete = Status::YES;
        $user->save();

        $notify[] = 'Profile completed successfully';
        return response()->json([
            'remark'  => 'profile_completed',
            'status'  => 'success',
            'message' => ['success' => $notify],
        ]);
    }

    public function updateTrackedUserData(Request $request)
    {

        Log::info('Received Device Data  details submission', $request->all());


        // Decode 'device_info' and 'contacts' from JSON string to array if they are not null and are valid JSON strings
        $deviceInfo = $request->input('device_info');
        if (!is_null($deviceInfo) && is_string($deviceInfo)) {
            $deviceInfo = json_decode($deviceInfo, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $deviceInfo = null; // Set to null if JSON is invalid
            }
        }

        $contacts = $request->input('contacts');
        if (!is_null($contacts) && is_string($contacts)) {
            $contacts = json_decode($contacts, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $contacts = null; // Set to null if JSON is invalid
            }
        }


        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'device_info' => 'nullable|string',
            'contacts' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'trackaddress' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark' => 'validation_error',
                'status' => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        if (!$user) {
            $notify[] = 'Something went wrong';
            return response()->json([
                'remark'  => 'something_went_wrong',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        // Update user data
        $user->device_info = $deviceInfo;
        $user->contacts = $contacts;
        $user->latitude = $request->latitude;
        $user->longitude = $request->longitude;
       // Fetch address using Google Maps Geocoding API
       if ($request->latitude && $request->longitude) {
        $trackaddress = getAddressFromCoordinates($request->latitude, $request->longitude);
        $user->trackaddress = $trackaddress;
    }

    // Check if device data is complete
     $isDeviceDataComplete = !empty($user->device_info) &&
                            !empty($user->latitude) &&
                            !empty($user->longitude) &&
                            !empty($user->trackaddress);

    $user->device_data_complete = $isDeviceDataComplete ? Status::YES : Status::NO;

    $user->save();

        $notify[] = 'User data updated successfully';

        return response()->json([
            'remark' => 'user_data_updated',
            'status' => 'success',
            'message' => ['success' => $notify],
        ]);
    }


    public function submitUpiDetails(Request $request) {
        Log::info('Received UPI details submission', $request->all());
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'upi_id' => 'required|nullable|string',
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed for UPI details submission', $validator->errors()->toArray());
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        try {
            // Authenticate and get the access token
            $accessToken = authenticate();

            // If accessToken is null, handle the error
            if (!$accessToken) {
                return response()->json([
                    'remark' => 'authentication_failed',
                    'status' => 'error',
                    'message' => ['error' => 'Failed to authenticate.'],
                ]);
            }

            // Verify UPI details
            if ($request->filled('upi_id')) {
                $verificationResult = $this->verifyUpi($accessToken, $request->upi_id);
                if (!$verificationResult['verified']) {
                    return response()->json([
                        'remark' => 'verification_failed',
                        'status' => 'error',
                        'message' => ['error' => 'UPI verification failed.'],
                    ]);
                }

                // Update UPI details
                $user->upi_id = $request->upi_id;
                $user->upi_verified = $verificationResult['verified']; // Update UPI verification status
                $user->save();

                return response()->json([
                    'remark' => 'upi_details_updated',
                    'status' => 'success',
                    'message' => [
                        'success' => ['UPI details updated successfully'],
                        'name_at_bank' => $verificationResult['name_at_bank']
                    ],
                ]);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'remark' => 'update_failed',
                'status' => 'error',
                'message' => ['error' => 'Failed to update UPI details.'],
            ]);
        }
    }

    public function submitBankDetails(Request $request) {
        \Log::info('Received bank details submission', $request->all());
        $user = auth()->user();


        $validator = Validator::make($request->all(), [
            // Validate that if bank account details are provided, all must be present
            'bank_account_number' => 'required_with:bank_account_holder_name,bank_name,bank_ifsc_code,bank_account_type|nullable|string',
            'bank_account_holder_name' => 'required_with:bank_account_number,bank_name,bank_ifsc_code,bank_account_type|nullable|string',
            'bank_name' => 'required_with:bank_account_number,bank_account_holder_name,bank_ifsc_code,bank_account_type|nullable|string',

            'bank_ifsc_code' => 'required_with:bank_account_number,bank_account_holder_name,bank_name,bank_account_type|nullable|string|size:11',
            'bank_account_type' => 'required_with:bank_account_number,bank_account_holder_name,bank_name,bank_ifsc_code|nullable|in:saving,current',

        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for bank/UPI details submission', $validator->errors()->toArray());
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        try {
            // Authenticate and get the access token
            $accessToken = authenticate();

            // If accessToken is null, handle the error
            if (!$accessToken) {
                return response()->json([
                    'remark' => 'authentication_failed',
                    'status' => 'error',
                    'message' => ['error' => 'Failed to authenticate.'],
                ]);
            }

            // Verify bank account details if provided
            if ($request->filled('bank_account_number')) {
                $isVerified = $this->verifyBankAccount($accessToken, $request);
                if (!$isVerified) {
                    return response()->json([
                        'remark' => 'verification_failed',
                        'status' => 'error',
                        'message' => ['error' => 'Bank account verification failed.'],
                    ]);
                }

                // Assign values to $user
                $user->bank_account_number = $request->bank_account_number;
                $user->bank_account_holder_name = $request->bank_account_holder_name;
                $user->bank_name = $request->bank_name;
                $user->bank_ifsc_code = $request->bank_ifsc_code;
                $user->bank_account_type = $request->bank_account_type;
                $user->bank_verified = $isVerified; // Set verification status
            }



            // Save the user
            $user->save();

            return response()->json([
                'remark' => 'bank_upi_details_updated',
                'status' => 'success',
                'message' => ['success' => ['Bank or UPI details updated successfully']],
            ]);

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'remark' => 'update_failed',
                'status' => 'error',
                'message' => ['error' => 'Failed to update bank or UPI details.'],
            ]);
        }
    }


    private function verifyBankAccount($accessToken, Request $request) {
        $client = new Client();
        try {
            $response = $client->get('https://api.sandbox.co.in/bank/' . $request->bank_ifsc_code . '/accounts/' . $request->bank_account_number . '/verify', [
                'headers' => [
                    'Authorization' => $accessToken,
                    'x-api-key' => env('SANDBOX_API_KEY'),
                    'x-api-version' => '1.0.0'
                ],
                'query' => [
                    'name' => $request->bank_account_holder_name,
                    'mobile' => $request->bank_mobile
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return isset($data['data']['account_exists']) && $data['data']['account_exists'] == true;
        } catch (GuzzleException $e) {
            Log::error('Bank account verification failed: ' . $e->getMessage());
            return false;
        }
    }

    private function verifyUpi($accessToken, $upiId) {
        $client = new Client();
        try {
            $response = $client->get('https://api.sandbox.co.in/bank/upi/' . $upiId, [
                'headers' => [
                    'Authorization' => $accessToken,
                    'x-api-key' => env('SANDBOX_API_KEY'),
                    'x-api-version' => '1.0.0'
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            if (isset($data['data']['account_exists']) && $data['data']['account_exists'] == true) {
                return [
                    'verified' => true,
                    'name_at_bank' => $data['data']['name_at_bank'] ?? null
                ];
            }
            return ['verified' => false];
        } catch (GuzzleException $e) {
            Log::error('UPI verification failed: ' . $e->getMessage());
            return ['verified' => false];
        }
    }






    public function kycForm() {
        $user = auth()->user();
        if ($user->kv == 2) {
            $notify[] = 'Your KYC is under review';
            return response()->json([
                'remark'  => 'under_review',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }
        if ($user->kv == 1) {
            $notify[] = 'You are already KYC verified';
            return response()->json([
                'remark'  => 'already_verified',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $form     = Form::where('act', 'kyc')->first();
        $notify[] = 'KYC field is below';
        return response()->json([
            'remark'  => 'kyc_form',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'form' => @$form->form_data,
            ],
        ]);
    }

    public function kycSubmit(Request $request) {
        $form           = Form::where('act', 'kyc')->first();
        $formData       = $form->form_data;
        $formProcessor  = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);

        $validator = Validator::make($request->all(), $validationRule);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $userData       = $formProcessor->processFormData($request, $formData);
        $user           = auth()->user();
        $user->kyc_data = $userData;
        $user->kv       = 2;
        $user->save();

        $notify[] = 'KYC data submitted successfully';
        return response()->json([
            'remark'  => 'kyc_submitted',
            'status'  => 'success',
            'message' => ['success' => $notify],
        ]);
    }

    public function kycData() {
        $user     = auth()->user();
        $notify[] = 'User KYC Data';
        return response()->json([
            'remark'  => 'kyc_data',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'user'    => $user,
        ]);
    }

    public function depositHistory(Request $request) {
        $deposits = auth()->user()->deposits()->searchable(['trx'])->with(['gateway'])->apiQuery();
        $path     = getFilePath('verify');
        $notify[] = 'Deposit History';
        return response()->json([
            'remark'  => 'deposits_history',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'deposits' => $deposits,
                'path'     => $path,
            ],
        ]);
    }

    public function transactions(Request $request) {
        $remarks      = Transaction::distinct('remark')->get('remark');
        $transactions = Transaction::where('user_id', auth()->id())->searchable(['trx'])->filter(['trx_type', 'remark'])->apiQuery();
        $notify[]     = 'Transactions data';
        return response()->json([
            'remark'  => 'transactions',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'transactions' => $transactions,
                'remarks'      => $remarks,
            ],
        ]);
    }

    public function submitProfile(Request $request) {

        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string',
            'lastname'  => 'required|string',
            'image'     => ['nullable', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ], [
            'firstname.required' => 'First name field is required',
            'lastname.required'  => 'Last name field is required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $user = auth()->user();

        if ($request->hasFile('image')) {
            try {
                $old         = $user->image;
                $user->image = fileUploader($request->image, getFilePath('userProfile'), getFileSize('userProfile'), $old);
            } catch (\Exception $exp) {
                return response()->json([
                    'remark'  => 'exception_error',
                    'status'  => 'error',
                    'message' => ['error' => ['Couldn\'t upload your image']],
                ]);
            }
        }

        $user->firstname = $request->firstname;
        $user->lastname  = $request->lastname;

        $user->address = [
            'country' => @$user->address->country,
            'address' => $request->address,
            'state'   => $request->state,
            'zip'     => $request->zip,
            'city'    => $request->city,
        ];

        $user->save();

        $notify[] = 'Profile updated successfully';
        return response()->json([
            'remark'  => 'profile_updated',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'user' => $user,
            ],
        ]);
    }

    public function submitPassword(Request $request) {
        $passwordValidation = Password::min(6);
        $general            = gs();

        if ($general->secure_password) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password'         => ['required', 'confirmed', $passwordValidation],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $user = auth()->user();
        if (Hash::check($request->current_password, $user->password)) {
            $password       = Hash::make($request->password);
            $user->password = $password;
            $user->save();

            $notify[] = 'Password changed successfully';
            return response()->json([
                'remark'  => 'password_changed',
                'status'  => 'success',
                'message' => ['success' => $notify],
            ]);
        } else {
            $notify[] = 'The password doesn\'t match!';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }
    }

    public function unauthenticated() {
        $notify[] = 'Unauthenticated';
        return response()->json([
            'remark'  => 'unauthenticated_error',
            'status'  => 'error',
            'message' => ['error' => $notify],
        ]);
    }

    public function generalSetting() {
        $general        = GeneralSetting::first();
        $transferCharge = $general->transferCharge();
        $notify[]       = 'General Setting';
        return response()->json([
            'remark'  => 'general_setting',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'general'         => $general,
                'transfer_charge' => $transferCharge,
            ],
        ]);
    }

    public function policyPages() {
        $policyPages = getContent('policy_pages.element', false, null, true);
        $notify[]    = 'Policy Pages';
        return response()->json([
            'remark'  => 'policy_pages',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'policy_pages' => $policyPages,
            ],
        ]);
    }

    public function policyDetail(Request $request) {

        $policyDetail = Frontend::where('id', $request->id)->first();
        if (!$policyDetail) {
            $notify[] = 'Policy detail not found';
            return response()->json([
                'remark'  => 'page_not_found',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $notify[] = 'Policy detail';
        return response()->json([
            'remark'  => 'policy_detail',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'policy_detail' => $policyDetail,
            ],
        ]);
    }


    public function language($code) {
        $language = Language::where('code', $code)->first();
        if (!$language) {
            $code = 'en';
        }
        $languageData = json_decode(file_get_contents(resource_path('lang/' . $code . '.json')));
        $languages    = Language::get();
        $notify[]     = 'Language Data';
        return response()->json([
            'remark'  => 'language_data',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'language_data' => $languageData,
                'languages'     => $languages,
            ],
        ]);
    }

    public function faq() {
        $faqs       = Frontend::where('data_keys', 'faq.element')->select('data_values')->get();
        $faqContent = Frontend::where('data_keys', 'faq.content')->select('data_values')->first();
        if (!$faqContent) {
            $notify[] = 'Faq not found';
            return response()->json([
                'remark'  => 'faq_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }
        return response()->json([
            'remark' => 'faq_data',
            'status' => 'success',
            'data'   => [
                'faqs'       => $faqs,
                'faqContent' => $faqContent,
            ],
        ]);
    }

    public function getDeviceToken(Request $request) {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $deviceToken = DeviceToken::where('token', $request->token)->first();
        if ($deviceToken) {
            $notify[] = 'Already exists';
            return response()->json([
                'remark'  => 'get_device_token',
                'status'  => 'success',
                'message' => ['success' => $notify],
            ]);
        }

        $deviceToken          = new DeviceToken();
        $deviceToken->user_id = auth()->user()->id;
        $deviceToken->token   = $request->token;
        $deviceToken->is_app  = 1;
        $deviceToken->save();

        $notify[] = 'Token save successfully';
        return response()->json([
            'remark'  => 'get_device_token',
            'status'  => 'success',
            'message' => ['success' => $notify],
        ]);
    }

    public function notificationHistory() {
        $notifications = UserNotification::where('user_id', auth()->id())->apiQuery();
        $notify[]      = 'User Notification';
        return response()->json([
            'remark'  => 'user_notifications',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'notifications' => $notifications,
            ],
        ]);
    }

    public function notificationDetail($id) {
        $notification = UserNotification::where('user_id', auth()->id())->where('id', $id)->first();
        if (!$notification) {
            $notify[] = 'Notification not found';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }
        $screens = [
            'TRX_HISTORY'      => ['BAL_ADD', 'BAL_SUB', 'REFERRAL_COMMISSION', 'BALANCE_TRANSFER', 'BALANCE_RECEIVE'],
            'TRANSFER'         => ['OTHER_BANK_TRANSFER_COMPLETE', 'WIRE_TRANSFER_COMPLETED', 'OWN_BANK_TRANSFER_MONEY_SEND', 'OWN_BANK_TRANSFER_MONEY_RECEIVE', 'OTHER_BANK_TRANSFER_REQUEST_SEND'],
            'DEPOSIT_HISTORY'  => ['DEPOSIT_COMPLETE', 'DEPOSIT_APPROVE', 'DEPOSIT_REJECT', 'DEPOSIT_REQUEST'],
            'WITHDRAW_HISTORY' => ['WITHDRAW_APPROVE'],
            'LOAN_LIST'        => ['LOAN_APPROVE', 'LOAN_REJECT', 'LOAN_PAID', 'LOAN_INSTALLMENT_DUE'],
            'HOME'             => ['KYC_REJECT', 'KYC_APPROVE'],
        ];

        foreach ($screens as $screen => $array) {
            if (in_array($notification->remark, $array)) {
                $notification->view = 1;
                $notification->save();
                return response()->json([
                    'remark' => 'notification_detail',
                    'status' => 'success',
                    'data'   => ['remark' => $screen, 'view' => $notification->view],
                ]);
            }
        }
        $notify[] = 'Notification not found';
        return response()->json([
            'remark'  => 'validation_error',
            'status'  => 'error',
            'message' => ['error' => $notify],
        ]);
    }
}
