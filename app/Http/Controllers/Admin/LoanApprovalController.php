<?php

namespace App\Http\Controllers\Admin;

use App\Models\LoanApproval;
use Illuminate\Http\Request;
use App\Models\LoanApplication;
use App\Models\LoanBankDetails;
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

        return redirect()->back()->with('success', 'Loan has been rejected successfully');
    }

    public function store(Request $request)
    {
        if ($request->status == "2") {
            return $this->handleRejectedLoan($request);
        }

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
            'status' => 'required|in:0,1,2',
            'approval_date' => 'nullable|date',
            'loan_purpose' => 'nullable|string',
            'final_remark' => 'nullable|string',
            'additional_remark' => 'nullable|string',
            'bank_acc_no' => 'required|string',
            'ifsccode' => 'required|string',
            'bank_name' => 'required|string',
        ]);
        //dd($request->input('ifsccode'));
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
        $loanApproval = LoanApproval::updateOrCreate(
            [
                'loan_application_id' => $request->loan_application_id,
                'user_id' => $request->user_id
            ],
            array_merge($request->all(), ['kfs_path' => $fileName])
        );

        // Update Loan Application Steps
        if ($loan) {
            $loan->current_step = "loanstatus";
            $loan->next_step = $request->status == "1" ? "viewloan" : "noteligible";
            $loan->admin_approval_status = $request->status == "1" ? "approved" : "rejected";
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
        
        $mailSend = sendMailViaSMTP($subject, $message, null, null);
        Log::info("Mail Send Via SMTP For Loan Approval and the response is : {$mailSend}");
        //EOC By Ankit Tiwari

        return redirect()->back()->with('success', 'Loan Approved and KFS PDF Generated Successfully');
    }
}
