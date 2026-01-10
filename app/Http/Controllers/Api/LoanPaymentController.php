<?php

namespace App\Http\Controllers\Api;

use App\Models\LoanPayment;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\UtrCollection;
use App\Models\LoanApplication;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\LoanApproval;
use App\Models\LoanAddressDetails;
use App\Models\LoanKYCDetails;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Models\CollectionConfiguration;

class LoanPaymentController extends Controller
{
    public function initiatePayment(Request $request)
    {
        $request->validate([
            'application_id' => 'required',
            'loan_account_no' => 'required',
            'loan_application_no' => 'required',
            'current_repayment_amount' => 'required|numeric|min:1',
            'repayment_amount' => 'required|numeric|min:1',
            'loan_amount' => 'required|numeric|min:1',
            'overdue_amount' => 'required|numeric',
            'interestAmount' => 'required|numeric',
            'penalAmount' => 'required|numeric',
        ]);

        try {
            // Get loan details
            $loan = LoanApplication::where('id', $request->application_id)
                        ->with(['loanDisbursal']) 
                        ->firstOrFail();

            $disbursal = $loan->loanDisbursal;
            $user = auth()->user();

            $payment_reference = 'LNPAY-' . time() . Str::random(4);

            // Create payment record
            $payment = LoanPayment::create([
                'user_id' => $user->id,
                'loan_application_id' => $loan->id,
                'loan_application_no' => $loan->loan_no,
                'loan_disbursal_id' => $disbursal->id,
                'loan_disbursal_no' => $disbursal->loan_disbursal_number,
                'payment_reference' => $payment_reference,
                'name' => $user->firstname . " " . $user->lastname,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'current_repayment_amount' => $request->current_repayment_amount,
                'repayment_amount' => $request->repayment_amount,
                'loan_amount' => $request->loan_amount,
                'overdue_amount' => $request->overdue_amount,
                'interestAmount' => $request->interestAmount,
                'penalAmount' => $request->penalAmount,
                'currency' => 'INR',
                'status' => 'pending',
            ]);

            // Prepare data for Cashfree
            $paymentData = [
                'order_id' => $payment->payment_reference,
                'amount' => $request->current_repayment_amount,
                'name' => $payment->name,
                'email' => $payment->email,
                'mobile' => $payment->mobile,
                'loan_application_id' => $loan->id,
            ];

            $cashfreeResult = $this->generateCashfreeUrl($paymentData);

            if (isset($cashfreeResult['error'])) {
                return response()->json([
                    'message' => 'Failed to generate payment link',
                    'error' => $cashfreeResult['error'],
                ], 500);
            }

            $cashfreeUrl = $cashfreeResult['payment_link'];

            // Update order ID to payment record
            $payment->update([
                'cf_order_id' => $paymentData['order_id'],
                'payment_request' => json_encode($paymentData),
            ]);

            return response()->json([
                'payment_link' => $cashfreeUrl,
                'payment_reference' => $payment->payment_reference,
            ]);

        } catch (\Exception $e) {
            \Log::error('Payment initiation error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'message' => 'Payment initiation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    private function generateCashfreeUrl(array $paymentData, $platform = null)
    {
        try {

            if(empty($platform)){
              $returnUrl = config('services.cashfree.app_url') . 'payment/status?payment_reference={order_id}';
            }else{
              $returnUrl = config('services.cashfree.app_url') . 'paymentstatus?payment_reference={order_id}';
            }

            $url = config('services.cashfree.base_url') . '/pg/orders';

            $headers = [
                "Content-Type: application/json",
                "x-api-version: " . config('services.cashfree.api_version'),
                "x-client-id: " . config('services.cashfree.app_id'),
                "x-client-secret: " . config('services.cashfree.secret_key'),
            ];

            $data = [
                'order_id' => $paymentData['order_id'],
                'order_amount' => $paymentData['amount'],
                'order_currency' => 'INR',
                'customer_details' => [
                    'customer_id' => 'cust_' . $paymentData['loan_application_id'],
                    'customer_name' => $paymentData['name'],
                    'customer_email' => $paymentData['email'],
                    'customer_phone' => $paymentData['mobile'],
                ],
                'order_meta' => [
                    'return_url' => $returnUrl,
                    'notify_url' => config('services.cashfree.app_url') . 'payment/webhook',  
                ],
                'order_note' => 'Loan repayment for account: ' . $paymentData['loan_application_id'],
            ];

            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_TIMEOUT => 30,
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            // Log request and response for debugging
            \Log::info('Cashfree Request Data:', $data);
            \Log::info('Cashfree Response:', ['response' => $response, 'error' => $err]);

            if ($err) {
                return ['error' => $err];
            }

            if (!$response) {
                return ['error' => 'No response from Cashfree'];
            }

            $responseData = json_decode($response, true);

            if (!$responseData || !isset($responseData['payment_link'])) {
                return ['error' => 'Invalid response from Cashfree', 'response' => $responseData];
            }

            return ['payment_link' => $responseData['payment_link']];

        } catch (\Exception $e) {
            \Log::error('Cashfree URL generation error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ['error' => $e->getMessage()];
        }
    }


    public function verifyPayment(Request $request)
    {
        $orderId = $request->query('order_id');

        try {
            $payment = LoanPayment::where('payment_reference', $orderId)->firstOrFail();

            if (!empty($payment->payment_details)) {
                $paymentDetails = json_decode($payment->payment_details, true);
                $paymentStatus = strtoupper($paymentDetails['payment_status'] ?? 'PENDING');

                return response()->json([
                    'status' => $paymentStatus === 'SUCCESS' ? 'success' : 'failed',
                    'message' => $paymentStatus === 'SUCCESS' ? 'Payment successful' : 'Payment failed or is pending',
                    'order_id' => $orderId,
                ]);
            }

            $url = config('services.cashfree.base_url') . "/pg/orders/{$orderId}/payments";

            $headers = [
                "Content-Type: application/json",
                "x-api-version: " . config('services.cashfree.api_version'),
                "x-client-id: " . config('services.cashfree.app_id'),
                "x-client-secret: " . config('services.cashfree.secret_key'),
            ];

            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 30,
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cashfree API error: ' . $err,
                    'order_id' => $orderId
                ], 500);
            }

            $paymentDetails = json_decode($response, true);
            $paymentStatus = strtoupper($paymentDetails[0]['payment_status'] ?? 'PENDING');

            $payment->update([
                'status' => strtolower($paymentStatus),
                'cf_payment_id' => $paymentDetails['cf_payment_id'] ?? null,
                'payment_details' => json_encode($paymentDetails),
                'updated_at' => now(),
            ]);

            if ($paymentStatus === 'SUCCESS') {
                $loan = LoanApplication::where([['user_id', $payment->user_id], ['id', $payment->loan_application_id]])->first();

                if ($loan) {
                    $utrCollection = UtrCollection::create([
                        'loan_application_id' => $payment->loan_application_id,
                        'user_id' => $payment->user_id,
                        'principal' => $payment->loan_amount,
                        'interest' => $payment->interestAmount,
                        'overdue_intrest' => $payment->overdue_amount,
                        'penal' => $payment->penalAmount,
                        'collection_amt' => $payment->current_repayment_amount,
                        'collection_date' => now(),
                        'mode' => "PG",
                        'discount_principal' => 0,
                        'discount_interest' => 0,
                        'discount_penal' => 0,
                        'payment_id' => $orderId,
                        'status' => "closed",
                        'created_by' => $payment->user_id, 
                    ]);
                    
                    $loan->loan_closed_status = "closed";
                    $loan->loan_closed_date = now();
                    $loan->save();
                }

                $loanData = LoanApplication::where([['user_id', $payment->user_id], ['id', $payment->loan_application_id]])->first();
                $approvalData = LoanApproval::where('loan_application_id', $payment->loan_application_id)->first();
                $userData = User::where('id', $payment->user_id)->first();
                $loanAddressDetailsData = LoanAddressDetails::where('loan_application_id', $payment->loan_application_id)->first();
                $pandata = DB::table('pan_data')->where('user_id', $payment->user_id)->first();
        

                $subject = "Loan Closure Confirmation - NOC Attached | Loan Application No. $loanData->loan_no";
                $message = "Dear $userData->firstname $userData->lastname,<br><br>
We are pleased to inform you that your loan amount of Rs. ".number_format(($approvalData->approval_amount), 0)." has been successfully repaid and your loan account has been closed with all dues cleared. Please find the attached No Objection Certificate (NOC) confirming the closure of your loan account.<br><br>
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

                $pdf = Pdf::loadView('admin.leads.noc', $data);

                Storage::put('noc/'.$loanData->loan_no.'_noc.pdf', $pdf->output());
                $fullPathToPDF = storage_path('app/noc/'.$loanData->loan_no.'_noc.pdf');
                $mailSend = sendMailViaSMTP($subject, $message, $userData->email, $fullPathToPDF);

                Log::info("Mail Send Via SMTP and te response is : {$mailSend}");
            }

            return response()->json([
                'status' => $paymentStatus === 'SUCCESS' ? 'success' : 'failed',
                'message' => $paymentStatus === 'SUCCESS' ? 'Payment successful' : 'Payment failed or is pending',
                'order_id' => $orderId,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'order_id' => $orderId
            ], 500);
        }
    }

    public function generatePaymentLink($id)
    {
        $lead = LoanApplication::with(['user'])->where('id', base64_decode($id))->first();

        if(empty($lead->loan_no)){
            return response()->json([
                'status' => false,
                'message' => 'You do not have a application no...',
            ]);
        }

        $today = Carbon::today()->toDateString();

        $loans = DB::table('loan_applications as la')
            ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
            ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
            ->leftJoin(DB::raw('(
                SELECT 
                    loan_application_id,
                    SUM(principal) AS total_principal_paid,
                    SUM(interest) AS total_interest_paid,
                    SUM(penal) AS total_penal_paid,
                    SUM(discount_interest) AS total_interest_discount,
                    SUM(discount_penal) AS total_penal_discount,
                    MAX(created_at) AS last_payment_date
                FROM utr_collections
                GROUP BY loan_application_id
            ) as uc'), 'uc.loan_application_id', '=', 'la.id')
            ->select([
                'la.loan_no',
                'ld.loan_disbursal_number',
                'lap.approval_amount',

                // Remaining principal
                DB::raw('(lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) as remaining_principal'),

                // Days since disbursal & days after due
                DB::raw("DATEDIFF('$today', ld.created_at) as days_since_disbursal"),
                DB::raw("DATEDIFF('$today', lap.repay_date) as days_after_due"),

                // Interest
                DB::raw('(
                    ((lap.approval_amount * lap.roi / 100)
                        * DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), ld.created_at)
                    )
                    +
                    (
                        ((lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) * lap.roi / 100)
                        * GREATEST(DATEDIFF("' . $today . '", IFNULL(uc.last_payment_date, "' . $today . '")), 0)
                    )
                    - IFNULL(uc.total_interest_paid, 0)
                    - IFNULL(uc.total_interest_discount, 0)
                ) as interest'),

                // Penal Interest
                DB::raw('(
                    (
                        IF(
                            DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), lap.repay_date) > 0,
                            lap.approval_amount * 0.0025 * DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), lap.repay_date),
                            0
                        )
                    )
                    +
                    (
                        IF(
                            uc.last_payment_date IS NOT NULL AND DATEDIFF("' . $today . '", uc.last_payment_date) > 0 AND DATEDIFF("' . $today . '", lap.repay_date) > 0,
                            (lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) * 0.0025 * DATEDIFF("' . $today . '", uc.last_payment_date),
                            0
                        )
                    )
                    - IFNULL(uc.total_penal_paid, 0)
                    - IFNULL(uc.total_penal_discount, 0)
                ) as penal_interest'),

                // Total Dues
                DB::raw('(
                    (lap.approval_amount - IFNULL(uc.total_principal_paid, 0))
                    +
                    (
                        (
                            (lap.approval_amount * lap.roi / 100)
                            * DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), ld.created_at)
                        )
                        +
                        (
                            ((lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) * lap.roi / 100)
                            * GREATEST(DATEDIFF("' . $today . '", IFNULL(uc.last_payment_date, "' . $today . '")), 0)
                        )
                        - IFNULL(uc.total_interest_paid, 0)
                        - IFNULL(uc.total_interest_discount, 0)
                    )
                    +
                    (
                        IF(
                            DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), lap.repay_date) > 0,
                            lap.approval_amount * 0.0025 * DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), lap.repay_date),
                            0
                        )
                        +
                        IF(
                            uc.last_payment_date IS NOT NULL AND DATEDIFF("' . $today . '", uc.last_payment_date) > 0 AND DATEDIFF("' . $today . '", lap.repay_date) > 0,
                            (lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) * 0.0025 * DATEDIFF("' . $today . '", uc.last_payment_date),
                            0
                        )
                        - IFNULL(uc.total_penal_paid, 0)
                        - IFNULL(uc.total_penal_discount, 0)
                    )
                ) as total_dues')
            ])
            ->where('la.id', $lead->id)
            ->where('la.loan_closed_status', 'pending')
            ->first();


        $loan = LoanApplication::where('id', base64_decode($id))
                    ->with(['loanDisbursal']) 
                    ->firstOrFail();

        $disbursal = $loan->loanDisbursal;
        $user = $loan->user;

        $payment_reference = 'LNPAY-' . time() . Str::random(4);

        // Create payment record
        $payment = LoanPayment::create([
            'user_id' => $user->id,
            'loan_application_id' => $loan->id,
            'loan_application_no' => $loan->loan_no,
            'loan_disbursal_id' => $disbursal->id,
            'loan_disbursal_no' => $disbursal->loan_disbursal_number,
            'payment_reference' => $payment_reference,
            'name' => $user->firstname . " " . $user->lastname,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'current_repayment_amount' => (!empty($loans->total_dues)) ? (int)$loans->total_dues : 0,
            'repayment_amount' => (!empty($loans->total_dues)) ? (int)$loans->total_dues : 0,
            'loan_amount' => (!empty($loans->approval_amount)) ? (int)$loans->approval_amount : 0,
            'overdue_amount' => (!empty($loans->approval_amount)) ? ($loans->total_dues - $loans->approval_amount) : 0,
            'interestAmount' => (!empty($loans->interest)) ? (int)$loans->interest : 0,
            'penalAmount' => (!empty($loans->penal_interest)) ? (int)$loans->penal_interest : 0,
            'currency' => 'INR',
            'status' => 'pending',
        ]);

        $paymentData = [
            'order_id' => $payment_reference,
            'amount' => (!empty($loans->total_dues)) ? (int)$loans->total_dues : 0,
            'name' => $lead->user->firstname.' '.$lead->user->lastname,
            'email' => $lead->user->email,
            'mobile' => $lead->user->mobile,
            'loan_application_id' => $lead->id,
        ];

        $cashfreeResult = $this->generateCashfreeUrl($paymentData, $platform = 'unique');
        //dd($cashfreeResult);
        if(!empty($cashfreeResult['payment_link'])){
            $paymentLink = $cashfreeResult['payment_link'];
            return redirect()->away($paymentLink);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'No payment link available'
            ]);
        }
    }

    public function generateSettlementPaymentLink($id)
    {
        $lead = LoanApplication::with(['user'])->where('id', base64_decode($id))->first();

        if(empty($lead->loan_no)){
            return response()->json([
                'status' => false,
                'message' => 'You do not have a application no...',
            ]);
        }

        $today = Carbon::today()->toDateString();

        $loans = DB::table('loan_applications as la')
            ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
            ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
            ->leftJoin(DB::raw('(
                SELECT 
                    loan_application_id,
                    SUM(principal) AS total_principal_paid,
                    SUM(interest) AS total_interest_paid,
                    SUM(penal) AS total_penal_paid,
                    SUM(discount_interest) AS total_interest_discount,
                    SUM(discount_penal) AS total_penal_discount,
                    MAX(created_at) AS last_payment_date
                FROM utr_collections
                GROUP BY loan_application_id
            ) as uc'), 'uc.loan_application_id', '=', 'la.id')
            ->select([
                'la.loan_no',
                'ld.loan_disbursal_number',
                'lap.approval_amount',
                'lap.repayment_amount',

                // Remaining principal
                DB::raw('(lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) as remaining_principal'),

                // Days since disbursal & days after due
                DB::raw("DATEDIFF('$today', ld.created_at) as days_since_disbursal"),
                DB::raw("DATEDIFF('$today', lap.repay_date) as days_after_due"),

                // Interest
                DB::raw('(
                    ((lap.approval_amount * lap.roi / 100)
                        * DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), ld.created_at)
                    )
                    +
                    (
                        ((lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) * lap.roi / 100)
                        * GREATEST(DATEDIFF("' . $today . '", IFNULL(uc.last_payment_date, "' . $today . '")), 0)
                    )
                    - IFNULL(uc.total_interest_paid, 0)
                    - IFNULL(uc.total_interest_discount, 0)
                ) as interest'),

                // Penal Interest
                DB::raw('(
                    (
                        IF(
                            DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), lap.repay_date) > 0,
                            lap.approval_amount * 0.0025 * DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), lap.repay_date),
                            0
                        )
                    )
                    +
                    (
                        IF(
                            uc.last_payment_date IS NOT NULL AND DATEDIFF("' . $today . '", uc.last_payment_date) > 0 AND DATEDIFF("' . $today . '", lap.repay_date) > 0,
                            (lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) * 0.0025 * DATEDIFF("' . $today . '", uc.last_payment_date),
                            0
                        )
                    )
                    - IFNULL(uc.total_penal_paid, 0)
                    - IFNULL(uc.total_penal_discount, 0)
                ) as penal_interest'),

                // Total Dues
                DB::raw('(
                    (lap.approval_amount - IFNULL(uc.total_principal_paid, 0))
                    +
                    (
                        (
                            (lap.approval_amount * lap.roi / 100)
                            * DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), ld.created_at)
                        )
                        +
                        (
                            ((lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) * lap.roi / 100)
                            * GREATEST(DATEDIFF("' . $today . '", IFNULL(uc.last_payment_date, "' . $today . '")), 0)
                        )
                        - IFNULL(uc.total_interest_paid, 0)
                        - IFNULL(uc.total_interest_discount, 0)
                    )
                    +
                    (
                        IF(
                            DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), lap.repay_date) > 0,
                            lap.approval_amount * 0.0025 * DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), lap.repay_date),
                            0
                        )
                        +
                        IF(
                            uc.last_payment_date IS NOT NULL AND DATEDIFF("' . $today . '", uc.last_payment_date) > 0 AND DATEDIFF("' . $today . '", lap.repay_date) > 0,
                            (lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) * 0.0025 * DATEDIFF("' . $today . '", uc.last_payment_date),
                            0
                        )
                        - IFNULL(uc.total_penal_paid, 0)
                        - IFNULL(uc.total_penal_discount, 0)
                    )
                ) as total_dues')
            ])
            ->where('la.id', $lead->id)
            ->where('la.loan_closed_status', 'pending')
            ->first();

        $loan = LoanApplication::where('id', base64_decode($id))
                    ->with(['loanDisbursal']) 
                    ->firstOrFail();

        $disbursal = $loan->loanDisbursal;
        $user = $loan->user;

        $existing = CollectionConfiguration::where('status', 1)->first();
        if(!empty($existing)){
           $pr_off = (isset($existing->pr_off) && $existing->pr_off > 0)
                ? $loans->approval_amount * (1 - ($existing->pr_off / 100))
                : $loans->approval_amount;

            $in_off = (isset($existing->in_off) && $existing->in_off > 0)
                ? $loans->interest * (1 - ($existing->in_off / 100))
                : $loans->interest;

            $pe_off = (isset($existing->pe_off) && $existing->pe_off > 0)
                ? $loans->penal_interest * (1 - ($existing->pe_off / 100))
                : $loans->penal_interest;

            $total_dues = max(0, $pr_off + $in_off + $pe_off);
        }else{
            $total_dues = $loans->repayment_amount;
        }

        $payment_reference = 'LNPAY-' . time() . Str::random(4);

        // Create payment record
        $payment = LoanPayment::create([
            'user_id' => $user->id,
            'loan_application_id' => $loan->id,
            'loan_application_no' => $loan->loan_no,
            'loan_disbursal_id' => $disbursal->id,
            'loan_disbursal_no' => $disbursal->loan_disbursal_number,
            'payment_reference' => $payment_reference,
            'name' => $user->firstname . " " . $user->lastname,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'current_repayment_amount' => (!empty($total_dues)) ? (int)$total_dues : 0,
            'repayment_amount' => $total_dues,
            'loan_amount' => (!empty($loans->approval_amount)) ? (int)$loans->approval_amount : 0,
            'overdue_amount' => (!empty($loans->repayment_amount)) ? ($total_dues - $loans->repayment_amount) : 0,
            'interestAmount' => (!empty($loans->interest)) ? (int)$loans->interest : 0,
            'penalAmount' => (!empty($loans->penal_interest)) ? (int)$loans->penal_interest : 0,
            'currency' => 'INR',
            'status' => 'pending',
        ]);

        $paymentData = [
            'order_id' => $payment_reference,
            'amount' => (!empty($total_dues)) ? (int)$total_dues : 0,
            'name' => $lead->user->firstname.' '.$lead->user->lastname,
            'email' => $lead->user->email,
            'mobile' => $lead->user->mobile,
            'loan_application_id' => $lead->id,
        ];

        $cashfreeResult = $this->generateCashfreeUrl($paymentData, $platform = 'unique');
        //dd($cashfreeResult);
        if(!empty($cashfreeResult['payment_link'])){
            $paymentLink = $cashfreeResult['payment_link'];
            return redirect()->away($paymentLink);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'No payment link available'
            ]);
        }
    }

    public function generatePartPaymentLink($id, $amount)
    {
        //echo $id;
        $lead = LoanApplication::with(['user'])->where('id', $id)->first();
        //dd($lead);
        if(empty($lead->loan_no)){
            return response()->json([
                'status' => false,
                'message' => 'You do not have a application no...',
            ]);
        }

        $today = Carbon::today()->toDateString();

        $loans = DB::table('loan_applications as la')
            ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
            ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
            ->leftJoin(DB::raw('(
                SELECT 
                    loan_application_id,
                    SUM(principal) AS total_principal_paid,
                    SUM(interest) AS total_interest_paid,
                    SUM(penal) AS total_penal_paid,
                    SUM(discount_interest) AS total_interest_discount,
                    SUM(discount_penal) AS total_penal_discount,
                    MAX(created_at) AS last_payment_date
                FROM utr_collections
                GROUP BY loan_application_id
            ) as uc'), 'uc.loan_application_id', '=', 'la.id')
            ->select([
                'la.loan_no',
                'ld.loan_disbursal_number',
                'lap.approval_amount',

                // Remaining principal
                DB::raw('(lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) as remaining_principal'),

                // Days since disbursal & days after due
                DB::raw("DATEDIFF('$today', ld.created_at) as days_since_disbursal"),
                DB::raw("DATEDIFF('$today', lap.repay_date) as days_after_due"),

                // Interest
                DB::raw('(
                    ((lap.approval_amount * lap.roi / 100)
                        * DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), ld.created_at)
                    )
                    +
                    (
                        ((lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) * lap.roi / 100)
                        * GREATEST(DATEDIFF("' . $today . '", IFNULL(uc.last_payment_date, "' . $today . '")), 0)
                    )
                    - IFNULL(uc.total_interest_paid, 0)
                    - IFNULL(uc.total_interest_discount, 0)
                ) as interest'),

                // Penal Interest
                DB::raw('(
                    (
                        IF(
                            DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), lap.repay_date) > 0,
                            lap.approval_amount * 0.0025 * DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), lap.repay_date),
                            0
                        )
                    )
                    +
                    (
                        IF(
                            uc.last_payment_date IS NOT NULL AND DATEDIFF("' . $today . '", uc.last_payment_date) > 0 AND DATEDIFF("' . $today . '", lap.repay_date) > 0,
                            (lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) * 0.0025 * DATEDIFF("' . $today . '", uc.last_payment_date),
                            0
                        )
                    )
                    - IFNULL(uc.total_penal_paid, 0)
                    - IFNULL(uc.total_penal_discount, 0)
                ) as penal_interest'),

                // Total Dues
                DB::raw('(
                    (lap.approval_amount - IFNULL(uc.total_principal_paid, 0))
                    +
                    (
                        (
                            (lap.approval_amount * lap.roi / 100)
                            * DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), ld.created_at)
                        )
                        +
                        (
                            ((lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) * lap.roi / 100)
                            * GREATEST(DATEDIFF("' . $today . '", IFNULL(uc.last_payment_date, "' . $today . '")), 0)
                        )
                        - IFNULL(uc.total_interest_paid, 0)
                        - IFNULL(uc.total_interest_discount, 0)
                    )
                    +
                    (
                        IF(
                            DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), lap.repay_date) > 0,
                            lap.approval_amount * 0.0025 * DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), lap.repay_date),
                            0
                        )
                        +
                        IF(
                            uc.last_payment_date IS NOT NULL AND DATEDIFF("' . $today . '", uc.last_payment_date) > 0 AND DATEDIFF("' . $today . '", lap.repay_date) > 0,
                            (lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) * 0.0025 * DATEDIFF("' . $today . '", uc.last_payment_date),
                            0
                        )
                        - IFNULL(uc.total_penal_paid, 0)
                        - IFNULL(uc.total_penal_discount, 0)
                    )
                ) as total_dues')
            ])
            ->where('la.id', $lead->id)
            ->where('la.loan_closed_status', 'pending')
            ->first();

        $loan = LoanApplication::where('id', $id)
                    ->with(['loanDisbursal']) 
                    ->firstOrFail();

        $disbursal = $loan->loanDisbursal;
        $user = $loan->user;

        if(!empty($loans->total_dues)){
            $total_dues = $amount;
        }else{
            $total_dues = $loans->approval_amount;
        }
        //dd($total_dues);
        $payment_reference = 'LNPAY-' . time() . Str::random(4);

        // Create payment record
        $payment = LoanPayment::create([
            'user_id' => $user->id,
            'loan_application_id' => $loan->id,
            'loan_application_no' => $loan->loan_no,
            'loan_disbursal_id' => $disbursal->id,
            'loan_disbursal_no' => $disbursal->loan_disbursal_number,
            'payment_reference' => $payment_reference,
            'name' => $user->firstname . " " . $user->lastname,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'current_repayment_amount' => (!empty($total_dues)) ? (int)$total_dues : 0,
            'repayment_amount' => $total_dues,
            'loan_amount' => (!empty($loans->approval_amount)) ? (int)$loans->approval_amount : 0,
            'overdue_amount' => (!empty($loans->approval_amount)) ? ($total_dues - $loans->approval_amount) : 0,
            'interestAmount' => (!empty($loans->interest)) ? (int)$loans->interest : 0,
            'penalAmount' => (!empty($loans->penal_interest)) ? (int)$loans->penal_interest : 0,
            'currency' => 'INR',
            'status' => 'pending',
        ]);

        $paymentData = [
            'order_id' => $payment_reference,
            'amount' => (!empty($total_dues)) ? (int)$total_dues : 0,
            'name' => $lead->user->firstname.' '.$lead->user->lastname,
            'email' => $lead->user->email,
            'mobile' => $lead->user->mobile,
            'loan_application_id' => $lead->id,
        ];

        $cashfreeResult = $this->generateCashfreeUrl($paymentData, $platform = 'unique');
        //dd($cashfreeResult);
        if(!empty($cashfreeResult['payment_link'])){
            $paymentLink = $cashfreeResult['payment_link'];
            return redirect()->away($paymentLink);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'No payment link available'
            ]);
        }
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
