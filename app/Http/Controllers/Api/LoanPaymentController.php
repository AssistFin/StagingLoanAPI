<?php

namespace App\Http\Controllers\Api;

use App\Models\LoanPayment;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\UtrCollection;
use App\Models\LoanApplication;
use App\Http\Controllers\Controller;

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


    private function generateCashfreeUrl(array $paymentData)
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

}
