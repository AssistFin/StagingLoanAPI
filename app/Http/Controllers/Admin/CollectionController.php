<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\LoanApplication;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CollectionController extends Controller
{
    public function collectionPredue(Request $request)
    {
        $today = now()->toDateString();

        // 1️⃣ Base query
        $query = LoanApplication::whereHas('loanDisbursal')
            ->where('status', '!=', 'closed')
            ->whereHas('loanApproval', function ($query) use ($today) {
                $query->whereDate('repay_date', '>', $today);
            })
            ->with([
                'user',
                'personalDetails',
                'employmentDetails',
                'kycDetails',
                'loanDocument',
                'addressDetails',
                'bankDetails',
                'loanDisbursal',
                'loanApproval',
            ])
            ->orderByRaw('created_at DESC');

        $searchTerm = $request->get('search');
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('user', function ($userQuery) use ($searchTerm) {
                    $userQuery->where('firstname', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhere('mobile', 'like', "%{$searchTerm}%");
                })->orWhere('loan_no', 'like', "%{$searchTerm}%");
            });
        }

        // 2️⃣ Paginate first
        $leads = $query->paginate(25);

        // 3️⃣ For each lead, add your computed data
        $leads->getCollection()->transform(function ($lead) use ($today) {

            // Your specific raw query
            $loans = DB::table('loan_applications as la')
                ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
                ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
                ->leftJoin(DB::raw('(SELECT loan_application_id, SUM(collection_amt) as total_paid FROM utr_collections GROUP BY loan_application_id) as uc'), 'uc.loan_application_id', '=', 'la.id')
                ->select([
                    'la.loan_no',
                    'ld.loan_disbursal_number',
                    'lap.approval_amount',
                    DB::raw("DATEDIFF('$today', ld.created_at) as days_since_disbursal"),
                    DB::raw("DATEDIFF('$today', lap.repay_date) as days_after_due"),
                    DB::raw('IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount) as remaining_principal'),
                    DB::raw('(lap.approval_amount * lap.roi / 100 ) * DATEDIFF("' . $today . '", ld.created_at) as interest'),
                    DB::raw('
                        IF(DATEDIFF("' . $today . '", lap.repay_date) > 0,
                            (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date),
                            0
                        ) as penal_interest'),
                    DB::raw('
                        (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) +
                        ((lap.approval_amount * lap.roi / 100 ) * DATEDIFF("' . $today . '", ld.created_at)) +
                        IF(DATEDIFF("' . $today . '", lap.repay_date) > 0,
                            (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date),
                            0
                        ) as total_dues')
                ])
                ->where('la.id', $lead->id)
                ->where('la.loan_closed_status', 'pending')
                ->first();

            $totalDues = !empty($loans->total_dues) ? (int)$loans->total_dues : 0;

            // Generate payment link
            $paymentData = [
                'order_id' => 'LNPAY-' . time() . Str::random(4),
                'amount' => $totalDues,
                'name' => $lead->user->firstname . ' ' . $lead->user->lastname,
                'email' => $lead->user->email,
                'mobile' => $lead->user->mobile,
                'loan_application_id' => $lead->id,
            ];

            $cashfreeResult = $this->generateCashfreeUrlFromHistory($paymentData);
            $paymentLink = !empty($cashfreeResult['payment_link']) ? $cashfreeResult['payment_link'] : "No Link Available";

            // Add to the lead
            $lead->total_dues = $totalDues;
            $lead->payment_link = $paymentLink;

            return $lead;
        });

        return view('admin.collection.collection-predue', compact('leads'));
    }

    public function collectionOverdue(Request $request)
    {
        $today = now()->toDateString();

        // 1️⃣ Base query
        $query = LoanApplication::whereHas('loanDisbursal')
            ->where('status', '!=', 'closed')
            ->whereHas('loanApproval', function ($query) use ($today) {
                $query->whereDate('repay_date', '<', $today);
            })
            ->with([
                'user',
                'personalDetails',
                'employmentDetails',
                'kycDetails',
                'loanDocument',
                'addressDetails',
                'bankDetails',
                'loanDisbursal',
                'loanApproval',
            ])
            ->orderByRaw('created_at DESC');

        $searchTerm = $request->get('search');
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('user', function ($userQuery) use ($searchTerm) {
                    $userQuery->where('firstname', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhere('mobile', 'like', "%{$searchTerm}%");
                })->orWhere('loan_no', 'like', "%{$searchTerm}%");
            });
        }

        // 2️⃣ Paginate first
        $leads = $query->paginate(25);

        // 3️⃣ For each lead, add your computed data
        $leads->getCollection()->transform(function ($lead) use ($today) {

            // Your specific raw query
            $loans = DB::table('loan_applications as la')
                ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
                ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
                ->leftJoin(DB::raw('(SELECT loan_application_id, SUM(collection_amt) as total_paid FROM utr_collections GROUP BY loan_application_id) as uc'), 'uc.loan_application_id', '=', 'la.id')
                ->select([
                    'la.loan_no',
                    'ld.loan_disbursal_number',
                    'lap.approval_amount',
                    DB::raw("DATEDIFF('$today', ld.created_at) as days_since_disbursal"),
                    DB::raw("DATEDIFF('$today', lap.repay_date) as days_after_due"),
                    DB::raw('IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount) as remaining_principal'),
                    DB::raw('(lap.approval_amount * lap.roi / 100 ) * DATEDIFF("' . $today . '", ld.created_at) as interest'),
                    DB::raw('
                        IF(DATEDIFF("' . $today . '", lap.repay_date) > 0,
                            (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date),
                            0
                        ) as penal_interest'),
                    DB::raw('
                        (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) +
                        ((lap.approval_amount * lap.roi / 100 ) * DATEDIFF("' . $today . '", ld.created_at)) +
                        IF(DATEDIFF("' . $today . '", lap.repay_date) > 0,
                            (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date),
                            0
                        ) as total_dues')
                ])
                ->where('la.id', $lead->id)
                ->where('la.loan_closed_status', 'pending')
                ->first();

            $totalDues = !empty($loans->total_dues) ? (int)$loans->total_dues : 0;
            $days_after_due = !empty($loans->days_after_due) ? (int)$loans->days_after_due : 0;
            // Generate payment link
            $paymentData = [
                'order_id' => 'LNPAY-' . time() . Str::random(4),
                'amount' => $totalDues,
                'name' => $lead->user->firstname . ' ' . $lead->user->lastname,
                'email' => $lead->user->email,
                'mobile' => $lead->user->mobile,
                'loan_application_id' => $lead->id,
            ];

            $cashfreeResult = $this->generateCashfreeUrlFromHistory($paymentData);
            $paymentLink = !empty($cashfreeResult['payment_link']) ? $cashfreeResult['payment_link'] : "No Link Available";

            // Add to the lead
            $lead->total_dues = $totalDues;
            $lead->payment_link = $paymentLink;
            $lead->dpd = $days_after_due;

            return $lead;
        });

        return view('admin.collection.collection-overdue', compact('leads'));
    }

    private function generateCashfreeUrlFromHistory(array $paymentData)
    {
        try {
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
                    'return_url' => config('services.cashfree.app_url') . 'payment/status?payment_reference={order_id}',
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

}
