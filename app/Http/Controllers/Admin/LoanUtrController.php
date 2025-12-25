<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\UtrCollection;
use App\Models\LoanApplication;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Models\LoanApproval;
use App\Models\LoanAddressDetails;
use App\Models\LoanKYCDetails;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use DB;
use Illuminate\Support\Facades\Http;

class LoanUtrController extends Controller
{
    public function store(Request $request)
    {
        // Validate Request
        $request->validate([
            'loan_application_id' => 'required|exists:loan_applications,id',
            'user_id' => 'required|exists:users,id',
            'principal' => 'required|numeric|min:0',
            'interest' => 'required|numeric|min:0',
            'penal' => 'required|numeric|min:0',
            'collection_amt' => 'required|numeric|min:0',
            'collection_date' => 'required|date',
            'mode' => 'required|in:PG,Bank,Cash',
            'discount_principal' => 'nullable|numeric|min:0',
            'discount_interest' => 'nullable|numeric|min:0',
            'discount_penal' => 'nullable|numeric|min:0',
            'payment_id' => 'required|string|max:255',
            'status' => 'required|in:Closed,Part Payment,Settlement',
        ]);

        // Create UTR Collection Record
        $utrCollection = UtrCollection::create([
            'loan_application_id' => $request->loan_application_id,
            'user_id' => $request->user_id,
            'principal' => $request->principal,
            'interest' => $request->interest,
            'penal' => $request->penal,
            'collection_amt' => $request->collection_amt,
            'collection_date' => $request->collection_date,
            'mode' => $request->mode,
            'discount_principal' => $request->discount_principal,
            'discount_interest' => $request->discount_interest,
            'discount_penal' => $request->discount_penal,
            'payment_id' => $request->payment_id,
            'status' => $request->status,
            'created_by' => $request->created_by, 
        ]);

        if($request->status === "Closed" || $request->status === "Settlement") {
        
        $loanData = LoanApplication::where([['user_id', $request->user_id], ['id', $request->loan_application_id]])->first();
        $approvalData = LoanApproval::where('loan_application_id', $request->loan_application_id)->first();
        $userData = User::where('id', $request->user_id)->first();
        $loanAddressDetailsData = LoanAddressDetails::where('loan_application_id', $request->loan_application_id)->first();
        $pandata = DB::table('pan_data')->where('user_id', $request->user_id)->first();
        

        $subject = "Loan Closure Confirmation - NOC Attached | Loan Application No. $loanData->loan_no";
        $message = "Dear $userData->firstname $userData->lastname,<br><br>
We are pleased to inform you that your loan amount of Rs. ".number_format(($approvalData->approval_amount), 0)." has been successfully repaid and your loan account has been closed with all dues cleared. Please find
the attached No Objection Certificate (NOC) confirming the closure of your loan account.<br><br>
We thank you for choosing LoanOne powered by Altura Financial Services Ltd. and look
forward to serving you again.<br><br>
Warm regards,<br>
Customer Support | LoanOne";

        $data = [
                    'date' => now()->format('d/m/Y'),
                    'borrower_name' => $userData->firstname.' '.$userData->lastname,
                    'address' => $loanAddressDetailsData->house_no.' '.$loanAddressDetailsData->city.' '.$loanAddressDetailsData->state.' '.$loanAddressDetailsData->pincode,
                    'loan_app_no' => $loanData->loan_no,
                    'loan_disbursement_date' =>Carbon::parse($approvalData->approval_date)->format('d/m/Y'),
                    'loan_closure_date' => now()->format('d/m/Y'),
                    'ref_no' => $this->generateRefNo(),
                    'pan_number' => $pandata->pan,
                ];
        // 2️⃣ Generate PDF
        $pdf = Pdf::loadView('admin.leads.noc', $data);

        // 3️⃣ Save PDF to storage if needed (optional)
        Storage::put('noc/'.$loanData->loan_no.'_noc.pdf', $pdf->output());
        $fullPathToPDF = storage_path('app/noc/'.$loanData->loan_no.'_noc.pdf');
        $mailSend = sendMailViaSMTP($subject, $message, $userData->email, $fullPathToPDF);
        //$mailSend = sendMailViaSMTP($subject, $message, $userData->email, $fullPathToPDF);

        Log::info("Mail Send Via SMTP and te response is : {$mailSend}");

            // $loan = LoanApplication::where('user_id', $request->user_id)->first();
            $loan = LoanApplication::where([['user_id', $request->user_id], ['id', $request->loan_application_id]])->first();

            if ($loan) {
                $loan->loan_closed_status = "closed";
                $loan->loan_closed_date = now();
                $loan->save();
            }

            $loanApplicationId = $request->loan_application_id;

            $payments = DB::table('subscription_payment_requests')
                ->where('subscription_id', 'LIKE', $loanApplicationId . '%')
                ->get();

                if(!empty($payments)){
                    foreach ($payments as $sub) {

                        $response = Http::withHeaders([
                            'Content-Type' => 'application/json',
                            'x-api-version' => config('services.cashfree.api_version'),
                            'x-client-id' => config('services.cashfree.app_id'),
                            'x-client-secret' => config('services.cashfree.secret_key'),
                        ])->post(config('services.cashfree.base_url') . '/pg/subscriptions/'.$sub->subscription_id.'/payments/CF-'.$sub->subscription_id.'/manage', [
                            'subscription_id' => $sub->subscription_id,
                            'action' => 'CANCEL',
                        ]);

                        if ($response->successful()) {
                            // Prepare update data
                            $updateData = [
                                'status' => 'Cancelled',
                            ];

                            // Update the existing record (based on subscription_id & payment_id)
                            DB::table('subscription_payment_requests')
                                ->where('subscription_id', $sub->subscription_id)
                                ->update($updateData);

                            Log::info("Payment request cancelled successfully.");
                        } else {
                            Log::error('Subscription cancel failed : ', [
                                'subscription_id' => $sub->subscription_id,
                                'response' => $response->json()
                            ]);
                        }
                    }
                }
                
        }
        $adminData = auth('admin')->user();
        
        if ($adminData) {
            eventLog($adminData->id, $request->user_id, 'Loan '.$request->status, json_encode($request->all()));
        }
        return redirect()->back()->with('success', 'UTR Collection added successfully.');
    }

    private function generateRefNo()
    {
        // Get current financial year
        $now = Carbon::now();
        $month = $now->month;

        if ($month >= 4) {
            // FY starts in April
            $fyStart = $now->year;
            $fyEnd = $now->year + 1;
        } else {
            $fyStart = $now->year - 1;
            $fyEnd = $now->year;
        }

        $fyString = $fyStart . '-' . substr($fyEnd, -2); // e.g. 2024-25

        $currentMonth = str_pad($month, 2, '0', STR_PAD_LEFT);

        // Count closed loans this FY + month
        $count = DB::table('loan_applications')
            ->where('loan_closed_status', 'closed')
            ->whereYear('loan_closed_date', '>=', $fyStart)
            ->whereYear('loan_closed_date', '<=', $fyEnd)
            ->whereMonth('loan_closed_date', $month)
            ->count();

        $next = str_pad($count + 1, 5, '0', STR_PAD_LEFT);

        return "{$fyString}/{$currentMonth}/{$next}";
    }
}
