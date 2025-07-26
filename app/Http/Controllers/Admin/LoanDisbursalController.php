<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\LoanDisbursal;
use App\Models\LoanApplication;
use App\Models\LoanApproval;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LoanDisbursalController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'disbursal_amount' => 'required|numeric|min:0',
            'account_no' => 'required|string',
            'ifsc' => 'required|string|max:20',
            'account_type' => 'required|string',
            'bank_name' => 'required|string',
            'branch' => 'required|string',
            'disbursal_date' => 'required|date',
            'final_remark' => 'nullable|string',
            'disbursed_by' => 'required|string',
            'utr_no' => 'nullable|string',
        ]);

        $lastLoanId = LoanDisbursal::latest('id')->value('id'); 

        $date = now()->format('Ymd'); 

        $sequentialNumber = $lastLoanId ? ($lastLoanId + 1) : 1; 
        $sequentialNumber = str_pad($sequentialNumber, 3, '0', STR_PAD_LEFT); 

        $loanDisbursalNumber = "ALT-{$request->user_id}-{$date}-{$sequentialNumber}";

        $requestData = $request->all();
        $requestData['loan_disbursal_number'] = $loanDisbursalNumber;
        $disbursal = LoanDisbursal::create($requestData);

        $approvalData = LoanApproval::where('loan_application_id', $request->loan_application_id)->first();
        $userData = User::where('id', $request->user_id)->first();

        $loan = LoanApplication::where([['user_id', $request->user_id], ['id', $request->loan_application_id]])->first();

        if ($loan) {
            $loan->current_step = "viewloan";
            $loan->next_step = "loandisbursal";
            $loan->loan_disbursal_status = "disbursed";
            $loan->loan_disbursal_date = now();
            $loan->save();
        }
        $subject = "Loan App. No. ".$request['loan_number']." | Loan Disbursement Confirmation";
        $message = "Dear $userData->firstname $userData->lastname,<br><br>
We are pleased to inform you that your loan of Rs. ".number_format(($approvalData->approval_amount), 0)." has been successfully disbursed after deducting PF and GST.<br><br>
Please find attached the accepted and duly executed copy of Sanction Letter includes Summary of Loan Product, Key Fact Statement (KFS) includes Terms and Conditions, and Loan Agreement for your reference. Please refer the link to get the <a href='https://loanone.in/privacy-policy' target='_blank'>Privacy Policy of LoanOne</a> and <a href='https://alturafinancials.com/privacy.html' target='_blank'>Privacy Policy of Altura</a>.<br><br>
You may also login on<a href='https://loanone.in' target='_blank'> LoanOne.in</a> to check your loan account details and statement and make the repayment.<br><br>
For any queries, feel free to contact us at 9211717788 or email care@loanone.in.<br><br><br>
Thank you for choosing LoanOne,<br>
powered by Altura Financial Services Ltd.";

$basePath = "/var/www/loan1documents/documents/loan_$request->loan_application_id/kfs/updated_";
$fullPathToPDF = $basePath . $approvalData->kfs_path;
//$pdfPath = "public/experian/credit_reports/Tilak_20250623_053856_QuNf4pcGiw.pdf";

        //$mailSend = sendMailViaSMTP($subject, $message, $to = null, Storage::path($pdfPath));
        $mailSend = sendMailViaSMTP($subject, $message, $userData->email, $fullPathToPDF);

        Log::info("Mail Send Via SMTP and te response is : {$mailSend}");

        $adminData = auth('admin')->user();
        
        if ($adminData) {
            eventLog($adminData->id, $userData->id, 'Loan Disbursed', json_encode($request->all()));
        }
        return redirect()->back()->with('success', 'Loan Disbursal Recorded Successfully');
    }
}
