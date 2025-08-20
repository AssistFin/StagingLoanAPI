<?php

namespace App\Http\Controllers\Admin;

use App\Models\LoanApproval;
use Illuminate\Http\Request;
use App\Models\LoanApplication;
use App\Models\LoanBankDetails;
use App\Models\CashfreeEnachRequestResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LoanApprovalController extends Controller
{
    function calculateAPRUsingStandardFormula($loanAmount, $dailyInterestRate, $processingFeePercent, $gstPercent, $otherCharges, $loanTermDays = 30)
    {
        $interest = ($dailyInterestRate / 100) * $loanAmount * $loanTermDays;
        $processingFee = ($processingFeePercent / 100) * $loanAmount;
        $gstOnProcessing = ($gstPercent / 100) * $processingFee;
        // $totalFees = $processingFee + $gstOnProcessing + $otherCharges;
        $totalFees = $processingFee + $otherCharges;
        $apr = (($interest + $totalFees) / $loanAmount) / $loanTermDays * 365 * 100;

        return round($apr, 2); 
    }

    protected function handleRejectedLoan(Request $request)
    {
        $data = [
            'loan_application_id' => $request->loan_application_id,
            'user_id' => $request->user_id,
            'loan_number' => $request->loan_number,
            'credited_by' => $request->credited_by,
            'status' => 2, 
            'final_remark' => $request->final_remark,
            'additional_remark' => $request->additional_remark,
            'approval_date' => now(),
            'loan_type' => "",
            'branch' => "",
            'approval_amount' => 0,
            'repayment_amount' => 0,
            'disbursal_amount' => 0,
            'loan_tenure' => "",
            'tentative_disbursal_date' => "",
            'loan_tenure_days' => 0,
            'loan_tenure_date' => "",
            'roi' => 0,
            'salary_date' => "",
            'repay_date' => "",
            'processing_fee' => 0,
            'processing_fee_amount' => 0,
            'gst' => 0,
            'gst_amount' => 0,
            'cibil_score' => "",
            'monthly_income' => 0,
            'kfs_path' => "",
            'loan_purpose' => "",
        ];

        $loanApproval = LoanApproval::updateOrCreate(
            [
                'loan_application_id' => $request->loan_application_id,
                'user_id' => $request->user_id
            ],
            $data
        );
        
        $loan = LoanApplication::where([
            ['user_id', $request->user_id],
            ['id', $request->loan_application_id]
        ])->first();

        if ($loan) {
            $loan->current_step = "loanstatus";
            $loan->next_step = "noteligible";
            $loan->admin_approval_status = "rejected";
            $loan->admin_approval_date = now();
            $loan->save();
        }

        $adminData = auth('admin')->user();
        
        if ($adminData) {
            eventLog($adminData->id, $request->user_id, 'Loan Approval - rejected', json_encode($request->all()));
        }

        return redirect()->back()->with('success', 'Loan has been rejected successfully');
    }

    protected function handleNotInterestedLoan(Request $request)
    {
        $data = [
            'loan_application_id' => $request->loan_application_id,
            'user_id' => $request->user_id,
            'loan_number' => $request->loan_number,
            'credited_by' => $request->credited_by,
            'status' => 3, 
            'final_remark' => $request->final_remark,
            'additional_remark' => $request->additional_remark,
            'approval_date' => now(),
            'loan_type' => "",
            'branch' => "",
            'approval_amount' => 0,
            'repayment_amount' => 0,
            'disbursal_amount' => 0,
            'loan_tenure' => "",
            'tentative_disbursal_date' => "",
            'loan_tenure_days' => 0,
            'loan_tenure_date' => "",
            'roi' => 0,
            'salary_date' => "",
            'repay_date' => "",
            'processing_fee' => 0,
            'processing_fee_amount' => 0,
            'gst' => 0,
            'gst_amount' => 0,
            'cibil_score' => "",
            'monthly_income' => 0,
            'kfs_path' => "",
            'loan_purpose' => "",
        ];

        $loanApproval = LoanApproval::updateOrCreate(
            [
                'loan_application_id' => $request->loan_application_id,
                'user_id' => $request->user_id
            ],
            $data
        );
        
        $loan = LoanApplication::where([
            ['user_id', $request->user_id],
            ['id', $request->loan_application_id]
        ])->first();

        if ($loan) {
            $loan->current_step = "loanstatus";
            $loan->next_step = "loanstatus";
            $loan->admin_approval_status = "notinterested";
            $loan->admin_approval_date = now();
            $loan->save();
        }

        $adminData = auth('admin')->user();
        
        if ($adminData) {
            eventLog($adminData->id, $request->user_id, 'Loan Approval - notinterested', json_encode($request->all()));
        }

        return redirect()->back()->with('success', 'Loan has been not interested successfully');
    }

    public function store(Request $request)
    {
        if ($request->status == "2") {
            return $this->handleRejectedLoan($request);
        }

        if ($request->status == "3") {
            return $this->handleNotInterestedLoan($request);
        }

        if ($request->status != "0") {
            $request->validate([
                'loan_type' => 'required|string',
                'branch' => 'required|string',
                'approval_amount' => 'required|numeric|min:0',
                'roi' => 'required|numeric|min:0',
                'salary_date' => 'required|date',
                'repay_date' => 'required|date|after_or_equal:salary_date',
                'processing_fee' => 'nullable|numeric|min:0',
                'cibil_score' => 'nullable|numeric|min:300|max:900',
                'monthly_income' => 'nullable|numeric|min:0',
                'status' => 'required|in:0,1,2,3,4',
                'approval_date' => 'nullable|date',
                'loan_purpose' => 'nullable|string',
                'final_remark' => 'nullable|string',
                'additional_remark' => 'nullable|string',
                'bank_acc_no' => 'required|string',
                'ifsccode' => 'required|string',
                'bank_name' => 'required|string',
            ]);
        }

        // Calculate financial details
        $approvalAmount = $request->input('approval_amount'); 
        $processingFeePercentage = $request->input('processing_fee'); 
        $gstPercentage = $request->input('gst'); 

        $processingFeeAmount = ($approvalAmount * $processingFeePercentage) / 100;
        $gstAmount = ($processingFeeAmount * $gstPercentage) / 100;
        $disbursalAmount = $approvalAmount - $processingFeeAmount - $gstAmount;

        $request->merge(['disbursal_amount' => $disbursalAmount]);

        // Fetch User & Loan Details
        $loan = LoanApplication::where([['user_id', $request->user_id], ['id', $request->loan_application_id]])->first();
        $user = $loan->user; // Assuming relationship exists
        
        $bankData = DB::table('loan_bank_details')->where('loan_application_id', $request->loan_application_id)->first();

        $loanApprovalData = DB::table('loan_approvals')->where('loan_application_id', $request->loan_application_id)->first();

        $cashfreeData = CashfreeEnachRequestResponse::where('subscription_id', $loan->loan_no)->where('reference_id', '!=', '')->orderBy('id','desc')->first();

        $bank_details = $approve_details = 0; 

        if(!empty($bankData->account_number) && $bankData->account_number != $request->input('bank_acc_no')){
            $bank_details = 1;
        }

        if(!empty($bankData->ifsc_code) && $bankData->ifsc_code != $request->input('ifsccode')){
            $bank_details = 1;
        }

        if(!empty($bankData->bank_name) && $bankData->bank_name != $request->input('bank_name')){
            $bank_details = 1;
        }

        if($loan->branch != $request->input('branch')){
            $approve_details = 1;
        }

        if($loan->approval_amount != $request->input('approval_amount')){
            $approve_details = 1;
        }

        if($loan->processing_fee != $request->input('processing_fee')){
            $approve_details = 1;
        }

        if($loan->processing_fee_amount != $request->input('processing_fee_amount')){
            $approve_details = 1;
        }

        if($loan->gst != $request->input('gst')){
            $approve_details = 1;
        }

        if($loan->gst_amount != $request->input('gst_amount')){
            $approve_details = 1;
        }

        if($loan->salary_date != $request->input('salary_date')){
            $approve_details = 1;
        }

        if($loan->tentative_disbursal_date != $request->input('tentative_disbursal_date')){
            $approve_details = 1;
        }

        if($loan->repay_date != $request->input('repay_date')){
            $approve_details = 1;
        }

        if($loan->loan_tenure_days != $request->input('loan_tenure_days')){
            $approve_details = 1;
        }

        if($loan->loan_tenure_date != $request->input('loan_tenure_date')){
            $approve_details = 1;
        }

        if($loan->roi != $request->input('roi')){
            $approve_details = 1;
        }

        if($loan->repayment_amount != $request->input('repayment_amount')){
            $approve_details = 1;
        }

        if($loan->cibil_score != $request->input('cibil_score')){
            $approve_details = 1;
        }

        if($loan->monthly_income != $request->input('monthly_income')){
            $approve_details = 1;
        }

        if($loan->approval_date != $request->input('approval_date')){
            $approve_details = 1;
        }

        if($loan->final_remark != $request->input('final_remark')){
            $approve_details = 1;
        }

        if($loan->additional_remark != $request->input('additional_remark')){
            $approve_details = 1;
        }

        if(!empty($loanApprovalData->kfs_path)){
            $kfsDoc = $loanApprovalData->kfs_path;
        }

        if($bank_details && $cashfreeData){

            $cashfreeExistingData = CashfreeEnachRequestResponse::where('subscription_id', $loan->loan_no)->where('reference_id', '!=', '')->orderBy('id','desc')->first();
            if ($cashfreeExistingData) {
                $cashfreeExistingData->status = 'INACTIVE';
                $cashfreeExistingData->save();
            }
            
            $cashfreeExistingActiveData = CashfreeEnachRequestResponse::where('subscription_id', $loan->loan_no)->where('reference_id', '!=', '')->where('status', 'INACTIVE')->orderBy('id','desc')->get();

            if(!empty($cashfreeExistingActiveData)){
                foreach($cashfreeExistingActiveData as $key => $value){
                    $new_subscription_id = $value['subscription_id'];
                    $new_alt_subscription_id = $value['alt_subscription_id'];
                    $response_data = json_decode($value['response_data'], true);
                    $status = $response_data['authorization_details']['authorization_status'] ?? '';
                    $bank_account_no = $response_data['authorization_details']['payment_method']['enach']['account_number'] ?? '';

                    if($status == 'ACTIVE' && $request->input('bank_acc_no') == $bank_account_no){
                        $loanApproval = CashfreeEnachRequestResponse::updateOrCreate(
                            [
                                'subscription_id' => $new_subscription_id,
                                'alt_subscription_id' => $new_alt_subscription_id
                            ],
                            ['status' => 'ACTIVE']
                        );
                    }
                }
            }
        }

        if($approve_details && !empty($kfsDoc)){
            $fileName = null;
        }
            // Define Secure Storage Path
            $securePath = config('services.docs.upload_kfs_doc') . "/documents/loan_" . $request->loan_application_id . "/kfs";
            if (!file_exists($securePath)) {
                mkdir($securePath, 0777, true);
            }

            $userAddress = DB::table('aadhaar_data')->where('user_id', $user->id)->first();
            
            $loanAmount = $request->approval_amount;
            $totalInterest = $request->repayment_amount - $request->approval_amount;
            $loanTenureDays = $request->loan_tenure_days;

            $fileName = "KFS_" . uniqid() . ".pdf";

            if($request->status == "1") {
                $apr = $this->calculateAPRUsingStandardFormula($loanAmount, $request->roi, $request->processing_fee, 18, 0, $loanTenureDays);

                $pdf = Pdf::loadView('templates.kfs', [
                    'borrower_name' => $user->firstname . " " . $user->lastname,
                    'sanction_date' => now()->format('Y-m-d'),
                    'borrower_address' => isset($userAddress) ? $userAddress->full_address : "",
                    'loan_application_no' => $request->loan_number,
                    'application_date' => $loan->created_at->format('Y-m-d'),
                    'loan_amount' => $loanAmount,
                    'loan_sanctioned_amount' => $request->disbursal_amount,
                    'rate_of_interest' => $request->roi,
                    'loan_tenure' => $loanTenureDays,
                    'processing_fee' => $request->processing_fee,
                    'processingFeeAmount' => $processingFeeAmount,
                    'disbursal_amount' => $request->disbursal_amount,
                    'total_repayment_amount' => $request->repayment_amount,
                    'ECSGST' => $request->gst_amount,
                    'totalInterest' => $totalInterest,
                    'penal_charges' => 0, 
                    'apr' => round($apr, 2) 
                ], ['encoding' => 'UTF-8'])->setPaper('A4')
                ->setOption('defaultFont', 'Arial Unicode MS')
                ->setOption('enable_local_file_access', true);

                $pdf->save($securePath . "/" . $fileName);
            }

        // Save Loan Approval with KFS Path
        if( $approve_details ){
            $loanApproval = LoanApproval::updateOrCreate(
                [
                    'loan_application_id' => $request->loan_application_id,
                    'user_id' => $request->user_id
                ],
                array_merge($request->all(), ['kfs_path' => $fileName, 'loan_purpose' => 'no'])
            );
        }else{
            $loanApproval = LoanApproval::updateOrCreate(
                [
                    'loan_application_id' => $request->loan_application_id,
                    'user_id' => $request->user_id
                ],
                array_merge($request->all(), ['kfs_path' => $fileName])
            );
        }

        if($request->status == "0"){
            $next_step = 'loanstatus';
            $admin_approval_status = 'pending';
        }else if($request->status == "1"){
            $next_step = 'viewloan';
            $admin_approval_status = 'approved';
        }else if($request->status == "2"){
            $next_step = 'noteligible';
            $admin_approval_status = 'rejected';
        }else if($request->status == "3"){
            $next_step = 'loanstatus';
            $admin_approval_status = 'notinterested';
        }else if($request->status == "4"){
            $next_step = 'viewloan';
            $admin_approval_status = 'approvednotinterested';
        }

        // Update Loan Application Steps
        if ($loan) {
            $loan->current_step = "loanstatus";
            $loan->next_step = $next_step;
            $loan->admin_approval_status = $admin_approval_status;
            $loan->admin_approval_date = now();
            $loan->save();
        }

        //BOC By Ankit Tiwari
        LoanBankDetails::where('loan_application_id',$request->loan_application_id)->update(['account_number'=>$request->input('bank_acc_no'), 'ifsc_code'=>$request->input('ifsccode'), 'bank_name'=>$request->input('bank_name')]);

        $subject = "Loan App. No. ".$loan->loan_no." | Loan Approved Confirmation";
        $message = "Dear $user->firstname $user->lastname,<br><br>
        We are pleased to inform you that your loan has been approved successfully.<br>
        Please refer the link to login from <a href='https://loanone.in' target='_blank'> LoanOne.in</a> and check the status of your loan application.<br><br>
        For any queries, feel free to contact us at 9211717788 or email care@loanone.in.<br><br><br>
        Thank you for choosing LoanOne,<br>
        powered by Altura Financial Services Ltd.";
        
        $mailSend = sendMailViaSMTP($subject, $message, $user->email, null);
        Log::info("Mail Send Via SMTP For Loan Approval and the response is : {$mailSend}");
        //EOC By Ankit Tiwari
        $adminData = auth('admin')->user();
        
        if ($adminData) {
            eventLog($adminData->id, $user->id, 'Loan Approval - '.$admin_approval_status, json_encode($request->all()));
        }
        return redirect()->back()->with('success', 'Loan Approved and KFS PDF Generated Successfully');
    }
}
