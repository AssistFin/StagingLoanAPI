<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use setasign\Fpdi\Fpdi;
use App\Models\LoanApproval;
use App\Models\LoanDocument;
use Illuminate\Http\Request;
use setasign\Fpdi\PdfReader;
use App\Models\LoanDisbursal;
use App\Models\LoanKYCDetails;
use App\Models\LoanApplication;
use App\Models\LoanBankDetails;
use App\Models\LoanAddressDetails;
use App\Models\CashfreeEnachRequestResponse;
use Illuminate\Support\Facades\DB;
use App\Models\LoanPersonalDetails;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\LoanEmploymentDetails;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class LoanApplyController extends Controller
{
    public function index()
    {
        try {
            Log::info('Fetching loan application data for user', ['user_id' => auth()->id()]);

            $loans = LoanApplication::with([
                'personalDetails', 
                'employmentDetails', 
                'kycDetails', 
                'loanDocument',
                'addressDetails', 
                'bankDetails'
            ])
            ->where([
                ['user_id', auth()->id()],
                ['loan_closed_status', 'pending']
            ])
            ->first();

            $aadharAddress = DB::table('aadhaar_data')
                ->where('user_id', auth()->id())
                ->select('full_address')
                ->first();

            if ($loans) {
                $enachData = DB::table('cashfree_enach_request_response_data')
                ->where('subscription_id', $loans['loan_no'])
                ->select('status', 'created_at')
                ->orderBy('id', 'desc')
                ->first();

                if (!empty($enachData)) {
                    $status = $enachData->status;
                    $createdAt = Carbon::parse($enachData->created_at);
                    $now = Carbon::now();

                    // Check if status is INITIALIZED and time diff > 60 seconds
                    if ($status === 'INITIALIZED' && $createdAt->diffInSeconds($now) > 60) {
                        $status = 'FAILED';
                    }

                    $loans["enachData"] = [
                        'status' => $status,
                        'created_at' => $createdAt
                    ];
                } else {
                    $loans["enachData"] = '';
                }
                $loans["aadharAddress"] = $aadharAddress;

                $loanApprovalData = DB::table('loan_approvals')->where('loan_application_id', $loans['id'])->first();
                if(!empty($loanApprovalData->kfs_path)){
                    $loanNo = $loans['id'];
                    $outputPath = config('services.docs.upload_kfs_doc') . "/documents/loan_{$loanNo}/kfs/updated_{$loanApprovalData->kfs_path}";
                    if (!file_exists($outputPath)) {
                        $arrayData["loan_application_id"] = $loans['id'];
                        $arrayData["current_step"] = 'loanstatus';
                        $arrayData["next_step"] = 'viewloan';
                        $requestObj = Request::create('', 'POST', $arrayData);
                        $this->updateLoanStep($requestObj);
                    }
                }
            }

            return response()->json(['status' => true, 'data' => $loans]);
        } catch (\Exception $e) {
            Log::error('Error fetching loan application data', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching loan data. Please try again later.'
            ], 500);
        }
    }


    public function progress()
    {
        $loan = LoanApplication::where('user_id', auth()->id())->first();

        return response()->json([
            'status' => true,
            'data'   => $loan ? [
                'loan_id' => $loan->id,
                'current_step' => $loan->current_step, 
            ] : [],
        ]);
    }

    public function store(Request $request)
    {
        Log::info('Received loan application request', $request->all());

        try {
            $validator = Validator::make($request->all(), [
                'loan_amount' => 'required|numeric',
                'purpose_of_loan' => 'required|string',
                'running_loan' => 'string',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }

            $lastLoanId = LoanApplication::latest('id')->value('id');
            $userId = auth()->id(); 
            $date = now()->format('Ymd'); 
            $sequentialNumber = $lastLoanId ? ($lastLoanId + 1) : 1;
            $sequentialNumber = str_pad($sequentialNumber, 3, '0', STR_PAD_LEFT); 
            $loanNumber = "LA-{$userId}-{$date}-{$sequentialNumber}";

            $loan = LoanApplication::create([
                'user_id' => $userId,
                'loan_no' => $loanNumber,
                'loan_amount' => $request->loan_amount,
                'purpose_of_loan' => $request->purpose_of_loan,
                'running_loan' => $request->running_loan,
                'current_step' => 'applyforaloan',
                'next_step' => 'proofofaddress',
                'status' => 'pending',
            ]);

            return response()->json(['status' => true, 'message' => 'Loan application created.', 'data' => $loan], 201);
        } catch (\Exception $e) {
            Log::error('Error in loan application creation', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }

    public function storePersonalDetails(Request $request)
    {
        Log::info('Received personal details submission', $request->all());

        try {
            $validator = Validator::make($request->all(), [
                'loan_application_id' => 'required|exists:loan_applications,id',
                // 'date_of_birth' => 'required|date',
                'pin_code' => 'required|string',
                'city' => 'required|string',
                'employment_type' => 'required|string',
                'monthly_income' => 'required|numeric',
                'income_received_in' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }

            $personalDetails = LoanPersonalDetails::updateOrCreate(
                ['loan_application_id' => $request->loan_application_id],
                $request->all()
            );

            $loan = LoanApplication::where([['user_id', auth()->id()], ['id', $request->loan_application_id]])->first();

            if ($loan) {
                $loan->current_step = 'proofofaddress';
                $loan->next_step = 'completekyc';
                $loan->save();
            }

            if ($loan) {
                $recentClosedLoan = LoanApplication::where('user_id', auth()->id())
                    ->where('id', '!=', $loan->id) 
                    ->where('loan_disbursal_status', 'disbursed')
                    ->where('loan_closed_status', 'closed')
                    ->whereNotNull('loan_closed_date')
                    ->where('loan_closed_date', '>=', now()->subMonths(6))
                    ->orderBy('loan_closed_date', 'desc')
                    ->first();
        
                if ($recentClosedLoan) {
                    $loan->current_step = 'proofofaddress';
                    $loan->next_step = 'addressconfirmation'; 
                } else {
                    $loan->current_step = 'proofofaddress';
                    $loan->next_step = 'completekyc';
                }
        
                $loan->save();
            }

            return response()->json(['status' => true, 'message' => 'Personal details saved.', 'data' => $personalDetails]);
        } catch (\Exception $e) {
            Log::error('Error saving personal details', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }

    public function storeKYCDetails(Request $request)
    {
        Log::info('Received KYC details submission', $request->all());

        try {
            $validator = Validator::make($request->all(), [
                'loan_application_id' => 'required|exists:loan_applications,id',
                'pan_number' => 'string',
                'aadhar_number' => 'string',
                'aadhar_otp' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }

            $data = $request->all();

            $kycDetails = LoanKYCDetails::updateOrCreate(
                ['loan_application_id' => $request->loan_application_id],
                $data
            );

            $loan = LoanApplication::where([['user_id', auth()->id()], ['id', $request->loan_application_id]])->first();

            if ($loan) {
                $loan->current_step = 'completekyc';
                $loan->next_step = 'aadharverification';
                $loan->save();
            }

            return response()->json(['status' => true, 'message' => 'KYC details saved.', 'data' => $kycDetails]);
        } catch (\Exception $e) {
            Log::error('Error saving KYC details', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }


    public function uploadLoanDocument(Request $request)
    {
        Log::info('Received loan document upload request', $request->all());

        try {
            $validator = Validator::make($request->all(), [
                'loan_application_id' => 'required|exists:loan_applications,id',
                'selfie_image' => 'required|image|max:2048',
            ]);

            if ($validator->fails()) {
                Log::info('Uploaded selfie:', [
                    'hasFile' => $request->hasFile('selfie_image'),
                    'file' => $request->file('selfie_image')
                ]);
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }

            $securePath = config('services.docs.upload_kfs_doc');

            if (!file_exists($securePath)) {
                mkdir($securePath, 0777, true);
            }

            $fileName = uniqid() . '.' . $request->file('selfie_image')->getClientOriginalExtension();

            $request->file('selfie_image')->move($securePath, $fileName);

            $loanDocument = LoanDocument::updateOrCreate(
                ['loan_application_id' => $request->loan_application_id],
                ['selfie_image' => $fileName]
            );

            $loan = LoanApplication::where([['user_id', auth()->id()], ['id', $request->loan_application_id]])->first();
            if ($loan) {
                $loan->current_step = 'submitselfie';
                $loan->next_step = 'addressconfirmation';
                $loan->save();
            }

            return response()->json([
                'status' => true,
                'message' => 'Loan document uploaded successfully.',
                'data' => $loanDocument
            ]);
        } catch (\Exception $e) {
            Log::error('Error uploading loan document', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }


    public function storeAddressDetails(Request $request)
    {
        Log::info('Received address details request', $request->all());
    
        try {
            $validator = Validator::make($request->all(), [
                'loan_application_id' => 'required|exists:loan_applications,id',
                'address_type' => 'required|string',
            ]);
    
            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }
    
            // If address_type is 'Both', fetch Aadhaar address fields
            if ($request->address_type === 'Both') {
                $aadhaarData = DB::table('aadhaar_data')
                    ->where('user_id', auth()->id())
                    ->select('house', 'street', 'pincode', 'district as city', 'state')
                    ->first();
    
                if ($aadhaarData) {
                    $request->merge([
                        'house_no' => $aadhaarData->house ?? null,
                        'locality' => $aadhaarData->street ?? null,
                        'pincode' => $aadhaarData->pincode ?? null,
                        'city' => $aadhaarData->city ?? null,
                        'state' => $aadhaarData->state ?? null,
                    ]);
                } else {
                    return response()->json(['status' => false, 'message' => 'Aadhaar address data not found.'], 404);
                }
            }
    
            // Save or update the address details
            $addressDetails = LoanAddressDetails::updateOrCreate(
                ['loan_application_id' => $request->loan_application_id],
                $request->all()
            );
    
            // Update loan application steps
            $loan = LoanApplication::where([['user_id', auth()->id()], ['id', $request->loan_application_id]])->first();
            if ($loan) {
                $loan->current_step = 'addressconfirmation';
                $loan->next_step = 'otherinformation';
                $loan->save();
            }
    
            return response()->json([
                'status' => true,
                'message' => 'Address details saved.',
                'data' => $addressDetails
            ]);
        } catch (\Exception $e) {
            Log::error('Error storing address details', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }
    

    public function storeEmploymentDetails(Request $request)
    {
        Log::info('Received employment details request', $request->all());

        try {
            $validator = Validator::make($request->all(), [
                'loan_application_id' => 'required|exists:loan_applications,id',
                'company_name' => 'required|string',
                'designation' => 'required|string',
                'email' => 'required|email',
                'office_address' => 'required|string',
                'education_qualification' => 'required|string',
                'marital_status' => 'required|string',
                'work_experience_years' => 'required|numeric',
                'salary_date' => 'required|integer|min:1|max:31',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }

            // ✅ Determine actual salary date
            $today = now();
            $selectedDay = (int)$request->salary_date;

            // Generate salary_date based on whether selected day is passed or upcoming
            $salaryDate = now()->setDay($selectedDay);

            // If selected day already passed in current month, move to next month
            if ($salaryDate->isPast() && $salaryDate->day !== now()->day) {
                $salaryDate->addMonthNoOverflow();
            }

            $repayDate = $salaryDate->copy()->addDay();
            $tenureDays = $today->diffInDays($salaryDate);

            // ✅ Calculate date difference in days
            $diffInDays = $today->diffInDays($salaryDate);

            if ($diffInDays < 15 || $diffInDays > 45) {
                return response()->json([
                    'status' => false,
                    'message' => 'Salary date must be between 15 and 45 days from today.'
                ], 422);
            }

            

            $employmentDetails = LoanEmploymentDetails::updateOrCreate(
                ['loan_application_id' => $request->loan_application_id],
                $request->all()
            );

            User::where('id', auth()->id())->update([
                'email' => $request->email
            ]);

            $loan = LoanApplication::where([
                ['user_id', auth()->id()],
                ['id', $request->loan_application_id]
            ])->first();

            if ($loan) {
                $loan->current_step = 'otherinformation';
                $loan->next_step = 'bankinfo';
                $loan->save();
            }

            // Merge computed salary_date into request data
            $data = [];
            $data['salary_date'] = $salaryDate->format('Y-m-d');
            $data['repay_date'] =  $repayDate->format('Y-m-d'); // store full date
            $data['loan_tenure_days'] = $tenureDays;
            $data['loan_application_id'] = $request->loan_application_id;
            $data['user_id'] = auth()->id();
            $data['loan_number'] = $loan->loan_no;
            $data['loan_type'] = 'Personal Loan';
            $data['branch'] = 'DELHI';
            $data['approval_amount'] = 0.00;
            $data['repayment_amount'] = 0.00;
            $data['disbursal_amount'] = 0.00;
            $data['roi'] = 1;
            $data['processing_fee'] = 10;
            $data['monthly_income'] = 0;
            $data['status'] = 0;

            $loanApprovalDetails = loanApproval::updateOrCreate(
                ['loan_application_id' => $request->loan_application_id],
                $data
            );

            return response()->json([
                'status' => true,
                'message' => 'Employment details saved.',
                'data' => $employmentDetails
            ]);
        } catch (\Exception $e) {
            Log::error('Error storing employment details', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }


    public function storeBankDetails(Request $request)
    {
        Log::info('Received bank details request', $request->all());

        try {
            $validator = Validator::make($request->all(), [
                'loan_application_id' => 'required|exists:loan_applications,id',
                'bank_name' => 'required|string',
                'bank_statement' => 'nullable|file|mimes:pdf|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }

            $data = $request->only(['loan_application_id', 'bank_name', 'bank_statement_password']);

            if ($request->hasFile('bank_statement')) {
                $securePath = config('services.docs.upload_kfs_doc');

                if (!file_exists($securePath)) {
                    mkdir($securePath, 0777, true);
                }

                $fileName = uniqid() . '.' . $request->file('bank_statement')->getClientOriginalExtension();
                $request->file('bank_statement')->move($securePath, $fileName);

                $data['bank_statement'] = $securePath . '/' . $fileName;
            }

            $bankDetails = LoanBankDetails::updateOrCreate(
                ['loan_application_id' => $request->loan_application_id],
                $data
            );

            $loan = LoanApplication::where([
                ['user_id', auth()->id()],
                ['id', $request->loan_application_id]
            ])->first();

            if ($loan) {
                $loan->current_step = 'bankinfo';
                $loan->next_step = 'loanstatus';
                $loan->save();
            }

            return response()->json([
                'status' => true,
                'message' => 'Bank details saved.',
                'data' => $bankDetails
            ]);
        } catch (\Exception $e) {
            Log::error('Error storing bank details', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }


    public function loanApproval(Request $request) {

        $approval = LoanApproval::where([
            ['loan_application_id', $request->loan_application_id],
            ['user_id', $request->user_id],
            ['loan_number', $request->loan_number],
        ])->first();

        return response()->json(['status' => true, 'message' => 'Loan is approved.', 'data' => $approval]);
    }

    public function loanDisbursal(Request $request) {

        $disbursal = LoanDisbursal::where([
            ['loan_application_id', $request->loan_application_id],
            ['user_id', $request->user_id],
        ])->first();

        return response()->json(['status' => true, 'message' => 'Loan is disbursal.', 'data' => $disbursal]);
    }

    public function loanAcceptance(Request $request) {
        $user = auth()->user();
        $ip = $request->ip();
        $fileName = $request->file_name;
        $loanNo = $request->loan_application_id;

        $filePath = config('services.docs.upload_kfs_doc') . "/documents/loan_{$loanNo}/kfs/{$fileName}";

        if (!file_exists($filePath)) {
            return response()->json(['status' => false, 'message' => 'File not found.'], 404);
        }

        $outputPath = config('services.docs.upload_kfs_doc') . "/documents/loan_{$loanNo}/kfs/updated_{$fileName}";

        $pdf = new Fpdi();

        $pageCount = $pdf->setSourceFile($filePath);

        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false); 

        $pageCount = $pdf->setSourceFile($filePath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            // Setup text
            $userName = "{$user->firstname} {$user->lastname}";
            $dateTime = now()->format('d/m/Y H:i:s');
            $ipAddress = $ip;

            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetTextColor(50, 50, 50);

            $text1 = "Accepted and Signed by {$userName}";
            $text2 = "{$dateTime} | {$ipAddress}";

            $textWidth = 70;
            $x = $size['width'] - $textWidth - 10; 
            $y = $size['height'] - 20;

            $pdf->SetXY($x, $y);
            $pdf->MultiCell($textWidth, 4, "{$text1}\n{$text2}", 0, 'L');
        }     

        $pdf->Output($outputPath, 'F');

        $cashfreeExistingData = CashfreeEnachRequestResponse::where('subscription_id', $request->loan_number)->where('reference_id', '!=', '')->orderBy('id','desc')->first();

        $cashfreeExistingActiveDataStatus = 0;

        $bankDetails = LoanBankDetails::where('loan_application_id', $request->loan_application_id)->first();

        $cashfreeExistingActiveData = CashfreeEnachRequestResponse::where('subscription_id', $request->loan_number)->where('reference_id', '!=', '')->where('status', 'INACTIVE')->orderBy('id','desc')->get();

            if(!empty($cashfreeExistingActiveData)){
                foreach($cashfreeExistingActiveData as $key => $value){
                    $new_subscription_id = $value['subscription_id'];
                    $new_alt_subscription_id = $value['alt_subscription_id'];
                    $response_data = json_decode($value['response_data'], true);
                    $status = $response_data['authorization_details']['authorization_status'] ?? '';
                    $bank_account_no = $response_data['authorization_details']['payment_method']['enach']['account_number'] ?? '';

                    if($status == 'ACTIVE' && $bankDetails->account_number == $bank_account_no){
                        $loanApproval = CashfreeEnachRequestResponse::updateOrCreate(
                            [
                                'subscription_id' => $new_subscription_id,
                                'alt_subscription_id' => $new_alt_subscription_id
                            ],
                            ['status' => 'ACTIVE']
                        );
                        $cashfreeExistingActiveDataStatus = 1;
                    }
                }
            }

        if(($cashfreeExistingData && $cashfreeExistingData->status == 'ACTIVE') || $cashfreeExistingActiveDataStatus){
            $approval = LoanApplication::where([
                ['id', $request->loan_application_id],
                ['user_id', $request->user_id],
                ['loan_no', $request->loan_number],
            ])->update([
                "current_step" => "viewloan",
                "next_step" => "loandisbursal",
                "user_acceptance_status" => "accepted",
                "user_acceptance_date" => now()
            ]);
        }else{
            $approval = LoanApplication::where([
                ['id', $request->loan_application_id],
                ['user_id', $request->user_id],
                ['loan_no', $request->loan_number],
            ])->update([
                "current_step" => "viewloan",
                "next_step" => "enachmandate",
                "user_acceptance_status" => "accepted",
                "user_acceptance_date" => now()
            ]);
        }

        $LoanApproval = LoanApproval::where([
                ['user_id', auth()->id()],
                ['loan_application_id', $request->loan_application_id]
            ])->first();

            if ($LoanApproval) {
                $LoanApproval->loan_purpose = 'yes';
                $LoanApproval->save();
            }

        if($cashfreeExistingData){
            return response()->json(['status' => false, 'message' => 'Loan offer accepted.', 'data' => $approval]);
        }
        return response()->json(['status' => true, 'message' => 'Loan offer accepted.', 'data' => $approval]);
    }

    public function updateLoanStep(Request $request)
    {
        $request->validate([
            'loan_application_id' => 'required|exists:loan_applications,id',
            'current_step' => 'required|string',
            'next_step' => 'required|string'
        ]);

        $loan = LoanApplication::where('id', $request->loan_application_id)
                            ->where('user_id', auth()->id())
                            ->first();

        if ($loan) {
            $loan->current_step = $request->current_step;
            $loan->next_step = $request->next_step;
            $loan->save();

            return response()->json([
                'status' => true,
                'message' => 'Loan step updated successfully',
                'loan' => $loan
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Loan not found'
        ], 404);
    }

    public function getAadharAddress()
    {
        $aadharAddresss = DB::table('aadhaar_data')
                            ->where('user_id', auth()->id())
                            ->select('full_address')
                            ->first();

        return response()->json(['status' => true, 'message' => 'Aadhar Address.', 'data' => $aadharAddresss]);
    }

    public function createEnachMandate(Request $request)
    {
        $loanId = $request->loan_number;
        $parts = explode('-', $loanId);
        $lastPart = end($parts); 
        $loanApprovalData = DB::table('loan_approvals')->where('loan_application_id', $lastPart)->first();
        if(!empty($loanApprovalData->kfs_path)){
            $outputPath = config('services.docs.upload_kfs_doc') . "/documents/loan_{$lastPart}/kfs/updated_{$loanApprovalData->kfs_path}";
            if (!file_exists($outputPath) || $loanApprovalData->loan_purpose == 'no' ) {
                $arrayData["loan_application_id"] = $lastPart;
                $arrayData["current_step"] = 'loanstatus';
                $arrayData["next_step"] = 'viewloan';
                $requestObj = Request::create('', 'POST', $arrayData);
                $this->updateLoanStep($requestObj);

                return response()->json([
                    'status' => true,
                    'message' => 'Due to modify loan',
                ]);
            }
        }

        $cashfreeData = DB::table('cashfree_enach_request_response_data')
                ->where('subscription_id', $request->loan_number)
                ->get();

        if(!empty($cashfreeData)){
            $cashfreeDataCount = $cashfreeData->count();
        }else{
            $cashfreeDataCount = 0;
        }

        $user = User::where('id', $request->user_id)->first();
        $bankDetails = LoanBankDetails::where('loan_application_id', $request->loan_application_id)->first();

        $url = config('services.cashfree.base_url') . '/pg/subscriptions';



        $data = [
            "customer_details" => [
                "customer_name" => $user->firstname." ".substr($user->lastname, 0, 20),
                "customer_email" => $user->email,
                "customer_phone" => $user->mobile,
                "customer_bank_account_holder_name" => $user->firstname." ".substr($user->lastname, 0, 20),
                "customer_bank_account_number" => $bankDetails->account_number,
                "customer_bank_ifsc" => $bankDetails->ifsc_code,
                "customer_bank_account_type" => "SAVINGS"
            ],
            "plan_details" => [
                "plan_id" => "100000",
                "plan_name" => "LoanOne Repayment",
                "plan_type" => "ON_DEMAND",
                "plan_currency" => "INR",
                "plan_amount" => 100000,
                "plan_max_amount" => 100000,
                "plan_max_cycles" => 10,
                "plan_note" => "One-time charge manually triggered"
            ],
            "subscription_id" => $request->loan_number.'-'.$cashfreeDataCount+100,
            "authorization_details" => [
            "authorization_amount" => 100,
            "authorization_amount_refund" => true,
                "payment_methods" => [
                    "enach",
                    "upi",
                    "netbanking",
                    "aadhaar"
                ]
            ],
            "subscription_meta" => [
            "return_url" => config('services.cashfree.return_url'),
            ],
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "x-api-version: " . config('services.cashfree.api_version'),
                "x-client-id: " . config('services.cashfree.app_id'),
                "x-client-secret: " . config('services.cashfree.secret_key'),
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        $responseData = json_decode($response, true);

        if (isset($responseData['subscription_session_id'])) {
            $loan = LoanApplication::where('id', $request->loan_application_id)->where('user_id', $request->user_id)->first();

            if ($loan) {
                $loan->current_step = 'enachmandate';
                $loan->next_step = 'cashfreeredirect';
                $loan->save();
            }

            $cashfreeData = CashfreeEnachRequestResponse::create([
                'subscription_id' => $request->loan_number,
                'alt_subscription_id' => $request->loan_number.'-'.$cashfreeDataCount+100,
                'request_data' => json_encode($data),
                'status' => 'INITIALIZED',
            ]);

            //dd($cashfreeData);

            $mandateLink = $responseData['subscription_session_id'];
            return response()->json([
                'status' => true,
                'link' => $mandateLink,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Cashfree API failed',
                'details' => $responseData,
            ], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        Log::channel('webhook')->info(
            "========== Cashfree Enach Webhook Response ==========\n\n" .
            json_encode($request->all(), JSON_PRETTY_PRINT) .
            "\n\n===================================================="
        );

        $data = $request->input('data');
        $subscription_id = isset($data['subscription_id']) ? $data['subscription_id'] : ($data['subscription_details']['subscription_id'] ?? null);
        $reference_id = isset($data['cf_subscription_id']) ? $data['cf_subscription_id'] : ($data['subscription_details']['cf_subscription_id'] ?? null);
        $status = $data['authorization_details']['authorization_status'] ? $data['authorization_details']['authorization_status'] : $data['subscription_details']['subscription_status'];

        if($data){
            $cashfree = CashfreeEnachRequestResponse::where('alt_subscription_id', $subscription_id)
            ->update([
                "reference_id" => $reference_id,
                "response_data" => json_encode($data),
                "status" => $status,
            ]);
            return response()->json(['message' => 'Webhook handled OK'], 200);
        }else{
            return response()->json(['message' => 'Webhook Failed'], 301);
        }
    }

    public function getcity($id)
    {
        if(!empty($id)){
         $allindiapincode = DB::table('allindiapincode')->where('pincode', $id)->first();

            return response()->json([
                'Status' => 'Success',
                'data' => $allindiapincode,
            ]);
        }else{
            return response()->json([
                'Status' => false,
                'data' => 'District not found'
            ], 404);
        }
    }

}
