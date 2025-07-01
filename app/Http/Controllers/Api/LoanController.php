<?php

namespace App\Http\Controllers\Api;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Models\AdminNotification;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanPlan;
use App\Models\Transaction;
use App\Models\UserPersonalDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;


class LoanController extends Controller {
    public function list() {
        $status = request()->status;
        $loans  = Loan::where('user_id', auth()->id());

        if($status !=null){
            $loans->where('status', $status);
        }

        $loans = $loans->with('nextInstallment')->with('plan')->apiQuery();

        $notify[] = 'My Loan List';
        return response()->json([
            'remark'  => 'loan_list',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'loans' => $loans,
            ],
        ]);
    }

    public function plans() {

        $notify[] = 'Loan Plans';
        $categories = Category::where('Status', Status::ENABLE)->with('plans')->whereHas('plans', function ($query) {
            $query->where('status', Status::ENABLE);
        })->latest()->get();


        $plans  = LoanPlan::active()->latest()->apiQuery();

        return response()->json([
            'remark'  => 'loan_plans',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'categories' => $categories,
                'loan_plans' => $plans,
            ],
        ]);
    }

    public function canCheckEligibility()
    {
        $userId = auth()->id(); // Get the authenticated user's ID

        // Fetch the most recent eligibility check by the user
    $mostRecentCheck = UserPersonalDetails::where('user_id', $userId)
    ->orderBy('created_at', 'desc')
    ->first();

        // Check if the user has made an eligibility check in the last 30 days
        $canCheck = !UserPersonalDetails::where('user_id', $userId)
                            ->where('created_at', '>=', now()->subDays(30))
                            ->exists();



         if($canCheck){

            return response()->json([
                'status' => 'success',
                // 'data' => $canCheck,
                'message' =>[
                    'success'=> ['User can check eligibility.']
                ]
            ]);

         }
         else{

            return response()->json([
                'status' => 'error',
                // 'data' => $canCheck,
                'data' => [
                    'eligibility' => (string) $mostRecentCheck->eligibility_amount ,
                    'cibilscore' =>  $mostRecentCheck->cibilscore ,
                ],
                'message' => [
                    'error'=> ['Please wait 30 days before checking eligibility again.'],
                ]
            ]);
         }


    }


    public function retrieveLastUserData(Request $request)
{
    // No need for validation rules here if you're only fetching data for the authenticated user

    $userId = auth()->id(); // Get the current user's ID

    // Retrieve the most recent user data based on userID and order by created_at or updated_at
    $latestUserData = UserPersonalDetails::where('user_id', $userId)
                        ->orderBy('created_at', 'desc') // or use 'updated_at' if more appropriate
                        ->first();

    if (!$latestUserData) {
        return response()->json([
            'status' => 'error',
            'message' => [
                'error'=> ['User data not found.']
            ],
        ],); // Not Found
    }

    return response()->json([
        'status' => 'success',
        'message' => [
            'success' => ['Loan application data fetched successfully.']
        ],
        'data' => $latestUserData,
    ]);
}


    public function  checkEligibility(Request $request)
    {
        $rules = [
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:Male,Female,Other',
            'mobile_number' => 'required|digits:10',
            'email' => 'required|email|max:255',
            'marital_status' => 'required|in:Single,Married',
            'spouse_name' => 'nullable|string|max:255',
            'number_of_kids' => 'nullable|integer|min:0',
            'mother_name' => 'nullable|string|max:255',
            'qualification' => 'required|string|max:255',
            'pan_number' => 'required|string|size:10',
            'aadhar_number' => 'required|string|digits:12',
            'purpose_of_loan' => 'required|string|max:1000',

        ];

        $validator = Validator::make($request->all(), $rules);



        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        // Check the number of inquiries in the last 30 days
    $userId = auth()->id(); // Or however you obtain the current user's ID

        // Check if the user has made an eligibility check in the last 30 days
        $hasCheckedRecently = UserPersonalDetails::where('user_id', $userId)
        ->where('created_at', '>=', now()->subDays(30))
        ->exists();

        if ($hasCheckedRecently) {
        return response()->json([
                'status' => 'error',
                'message' => [
                'error'=> [
                'Oops!, You can only check your eligibility once every 30 days.'
                ]
        ],
        ], ); // HTTP status code for Unprocessable Entity
        }

        // Assume getCibilScore and calculateEligibility are methods that
        // fetch the CIBIL score based on PAN and calculate eligibility
        $cibilScore = $this->getCibilScore($request->pan_number);
        $eligibilityStatus = $this->calculateEligibility($cibilScore);

        $eligibilityStatusAsString = (string) $eligibilityStatus; // Cast to string

        // Check if the eligibility status is not 'Rejected.'
    if ($eligibilityStatus !== 'Rejected.') {
            try {
                // Store user personal details
                $userPersonalDetails = new UserPersonalDetails([
                    'user_id' => auth()->id(), // Or however you obtain the current user's ID
                    'full_name' => $request->input('full_name'),
                    'date_of_birth' => $request->input('date_of_birth'),
                    'gender' => $request->input('gender'),
                    'mobile_number' => $request->input('mobile_number'),
                    'email' => $request->input('email'),
                    'marital_status' => $request->input('marital_status'),
                    'spouse_name' => $request->input('spouse_name'), // Make sure to handle optional fields appropriately
                    'number_of_kids' => $request->input('number_of_kids'),
                    'mother_name' => $request->input('mother_name'),
                    'qualification' => $request->input('qualification'),
                    'pan_number' => $request->input('pan_number'),
                    'aadhar_number' => $request->input('aadhar_number'),
                    'purpose_of_loan' => $request->input('purpose_of_loan'),
                    'eligibility_amount' => $eligibilityStatus,
                    'cibilscore' => $cibilScore
                ]);
                $userPersonalDetails->save();

                // // Create loan application
                // $loan = new Loan([
                //     'user_id' => Auth::id(),
                //     'plan_id' => $id,
                //     'amount' => $request->amount,
                //     // Include other necessary fields
                // ]);
                // $loan->save();

                return response()->json([
                    'status' => 'success',
                    'message' => [
                        'success' => ['Loan application submitted successfully.']
                    ],
                    'data' => [
                        'eligibility' => $eligibilityStatusAsString,
                        'cibilscore'=> $cibilScore
                    ],
                ]);
            } catch (\Exception $e) {
                // Log the exception
            Log::error('Loan eligibility check failed: ' . $e->getMessage(), [
                'stackTrace' => $e->getTraceAsString(),
                'user' => auth()->user() ? auth()->user()->id : null
            ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'An error occurred while processing your application.',
                ], 500);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Loan application rejected based on eligibility criteria.',
                'data' => [
                    'eligibility' => $eligibilityStatus,
                ],
            ]);
        }
    }


    private function getCibilScore($panNumber) {
       // Return a random CIBIL score between 300 and 900
          return rand(600, 900);
    }

    /**
     * Calculate eligibility based on CIBIL score.
     */
    private function calculateEligibility($cibilScore) {
        if ($cibilScore >= 820) {
            return 10000; // Returning integer value
        } elseif ($cibilScore >= 778) {
            return 5000;
        } elseif ($cibilScore >= 765) {
            return 4000;
        } elseif ($cibilScore >= 748) {
            return 3000;
        } elseif ($cibilScore >= 723) {
            return 2000;
        } elseif ($cibilScore >= 681) {
            return 1000;
        } elseif ($cibilScore >= 650) {
            return 500;
        } else {
            return 'Rejected';
        }
    }


    /**
 * Get the latest user personal details.
 */
public function getUserPersonalDetails(Request $request)
{
    try {
        $userId = auth()->id(); // Or however you obtain the current user's ID

        // Fetch the latest entry of the user's personal details
        $userPersonalDetails = UserPersonalDetails::where('user_id', $userId)
                                ->orderBy('created_at', 'desc')
                                ->first();

        if (!$userPersonalDetails) {
            return response()->json([
                'status' => 'error',
                'message' => [
                    'error'=> ['User  details not found.']
                ],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message'=> [
                'success'=> ['User details found!']
            ],
            'data' => $userPersonalDetails,
        ]);
    } catch (\Exception $e) {
        Log::error('Fetching user  details failed: ' . $e->getMessage(), [
            'stackTrace' => $e->getTraceAsString(),
            'userId' => $userId ?? null
        ]);

        return response()->json([
            'status' => 'error',
            'message' => [
                'error' => ['An error occurred while fetching user personal details.']
            ],
        ], 500);
    }
}


    public function applyLoan(Request $request, $id) {
        $plan = LoanPlan::active()->with('form')->where('id', $id)->first();

        if (!$plan) {
            $notify[] = 'Plan not found';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $validator = Validator::make($request->all(), [
            'amount' => "required|numeric|min:$plan->minimum_amount|max:$plan->maximum_amount",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $percentCharge = $request->amount * $plan->application_percent_charge / 100;
        $applicationFee = $plan->application_fixed_charge + $percentCharge;

        $notify[]    = 'Plan Information';
        return response()->json([
            'remark'  => 'plan',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'plan'            => $plan,
                'delay_charge'    => getAmount($plan->delayCharge),
                'application_fee' => $applicationFee,
                'amount'          => getAmount($request->amount),
            ],
        ]);
    }

    public function loanConfirm(Request $request, $id) {

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|gt:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $plan = LoanPlan::active()->where('id', $id)->first();

        if (!$plan) {
            $notify[] = 'No such plan found';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $amount = $request->amount;
        $percentCharge = $request->amount * $plan->application_percent_charge / 100;
        $applicationFee = $plan->application_fixed_charge + $percentCharge;

        $user   = auth()->user();

        if ($applicationFee > $user->balance) {
            $notify[] = 'Insufficient balance. You have to pay the application fee.';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        if ($plan->minimum_amount > $amount || $amount > $plan->maximum_amount) {
            $notify[] = 'Please follow the minium & maximum limit for this plan';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        if (@$plan->form->form_data) {
            $formData           = $plan->form->form_data;
            $formProcessor      = new FormProcessor();
            $validationRule     = $formProcessor->valueValidation($formData);
            $formDataValidation = Validator::make($request->all(), $validationRule);

            if ($formDataValidation->fails()) {
                return response()->json([
                    'remark'  => 'validation_error',
                    'status'  => 'error',
                    'message' => ['error' => $formDataValidation->errors()->all()],
                ]);
            }
            $applicationForm = $formProcessor->processFormData($request, $formData);
        }


        $user->balance -=  $applicationFee;
        $user->bank_verified=true;
        $user->upi_verified=true;
        $user->save();
        $trxNumber = getTrx();

        //transaction
        $general = gs();
        $transaction = new Transaction();
        $transaction->user_id      = $user->id;
        $transaction->amount       = $applicationFee;
        $transaction->post_balance = $user->balance;
        $transaction->charge       = 0;
        $transaction->trx_type     = '-';
        $transaction->details      = $general->cur_sym . showAmount($amount) . ' '   . 'Charged for application fee ' . $plan->name;
        $transaction->trx          = $trxNumber;
        $transaction->remark       = 'application_fee';
        $transaction->save();

        $perInstallment = $amount * $plan->per_installment / 100;
        $percentCharge = $plan->per_installment * $plan->percent_charge / 100;
        $charge        = $plan->fixed_charge + $percentCharge;

        $loan                         = new Loan();
        $loan->loan_number            = $trxNumber;
        $loan->user_id                = $user->id;
        $loan->plan_id                = $plan->id;
        $loan->amount                 = $amount;
        $loan->per_installment        = $perInstallment;
        $loan->installment_interval   = $plan->installment_interval;
        $loan->delay_value            = $plan->delay_value;
        $loan->charge_per_installment = $charge;
        $loan->total_installment      = $plan->total_installment;
        $loan->application_form       = $applicationForm;
        $loan->save();

        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = $user->id;
        $adminNotification->title     = 'New loan request';
        $adminNotification->click_url = urlPath('admin.loan.index') . '?search=' . $loan->loan_number;
        $adminNotification->save();

        $notify[] = 'Loan request submitted successfully';
        return response()->json([
            'remark'  => 'loan_success',
            'status'  => 'success',
            'message' => ['success' => $notify],
        ]);
    }

    public function installments($loanNumber) {
        $loan = Loan::where('id', $loanNumber)->with('plan:id,name')->where('user_id', auth()->id())->first();
        if (!$loan) {
            $notify[] = 'Loan not found';
            return response()->json([
                'remark'  => 'loan_not_found',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }
        $installments  = $loan->installments()->paginate(getPaginate());
        $payableAmount = @$loan->payable_amount;
        $notify[]      = 'Loan Installments';
        return response()->json([
            'remark'  => 'loan_installment',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'installments'  => $installments,
                'loan'          => $loan,
                'payableAmount' => $payableAmount,
            ],
        ]);
    }
}
