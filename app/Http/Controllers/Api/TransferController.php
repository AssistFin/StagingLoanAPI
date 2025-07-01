<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Loan;
use Illuminate\Http\Request;
use App\Services\EasebuzzService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransferController extends Controller
{
    protected $easebuzzService;

    public function __construct(EasebuzzService $easebuzzService)
    {
        $this->easebuzzService = $easebuzzService;
    }

    public function initiateTransfer(Request $request)
    {
        // Use detailed and precise validation
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'repayment_amount' => 'required|numeric|min:1',
            'payment_mode' => 'required|string|in:UPI,NEFT,RTGS,IMPS',
            'currency' => 'sometimes|string|size:3|in:INR',
            'source_virtual_account' => 'nullable|string|max:255',
            'queue_on_low_balance' => 'nullable|boolean',
            'narration' => 'nullable|string|max:255',
            'scheduled_for' => 'nullable|date_format:Y-m-d H:i:s',
            'udf1' => 'nullable|string|max:255',
            'udf2' => 'nullable|string|max:255',
            'udf3' => 'nullable|string|max:255',
            'udf4' => 'nullable|string|max:255',
            'udf5' => 'nullable|string|max:255',
        ]);

        // Return validation errors
        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ], 422);
        }

        // Get the authenticated user
        $user = auth()->user();

        // Ensure that the user has valid bank details
        if (empty($user->bank_account_holder_name) || empty($user->bank_account_number) || empty($user->bank_ifsc_code)) {
            Log::error('Incomplete bank details for user ID: ' . $user->id);
            return response()->json([
                'remark' => 'transfer_failed',
                'status' => 'error',
                'message' => ['error' => ['Bank account details are incomplete']],
            ]);

        }

        // Retrieve loan record and ensure there's an active loan
        $loan = Loan::where('user_id', $user->id)->where('status', '1')->first();

        if (!$loan) {
            return response()->json([
                'remark' => 'transfer_failed',
                'status' => 'error',
                'message' => ['error' => ['No active loan found']],
            ]);

        }

        // Check if the user has sufficient loan amount available
        $amount = (float) $request->amount;  // Explicitly cast amount to float
        $repaymentAmount = (float) $request->repayment_amount;  // Explicitly cast repayment Amount to float
        $availableLoanAmount = $loan->amount - $loan->transferred_amount;

        if ($amount > $availableLoanAmount) {

            return response()->json([
                'remark' => 'transfer_failed',
                'status' => 'error',
                'message' => ['error' => ['Insufficient Available amount available']],
            ]);


        }

        // Start transaction
        DB::beginTransaction();

        try {
            // Generate a unique request number for this transfer
            $uniqueRequestNumber = strtoupper(Str::random(10));

            // Prepare the transfer data
            $transferData = [
                'user_id' => $user->id,
                'amount' => $amount,
                'unique_request_number' => $uniqueRequestNumber,
                'beneficiary_name' => $user->bank_account_holder_name,
                'account_number' => $user->bank_account_number,
                'ifsc' => $user->bank_ifsc_code,
                'upi_handle' => $user->upi_id,
                'payment_mode' => $request->payment_mode,
                'status' => 'pending',
                'currency' => $request->input('currency', 'INR'),
                'source_virtual_account' => $request->input('source_virtual_account'),
                'queue_on_low_balance' => $request->input('queue_on_low_balance', 0),
                'narration' => $request->input('narration'),
                'scheduled_for' => $request->input('scheduled_for'),
                'created_by' => $user->id,
                'status_change_reason' => $request->input('status_change_reason'),
            ];

            // Create the transfer record
            $transfer = Transfer::create($transferData);

            // Initiate transfer using Easebuzz service
            $transferResponse = $this->easebuzzService->initiateTransfer(
                $user->email,
                $user->phone,
                $uniqueRequestNumber,
                $user->bank_account_holder_name,
                $user->bank_account_number,
                $user->bank_ifsc_code,
                $user->upi_id,
                $amount,
                $request->payment_mode,
                [
                    'narration' => $request->input('narration'),
                    'scheduled_for' => $request->input('scheduled_for'),
                    'udf1' => $request->input('udf1'),
                    'udf2' => $request->input('udf2'),
                    'udf3' => $request->input('udf3'),
                    'udf4' => $request->input('udf4'),
                    'udf5' => $request->input('udf5'),
                ]
            );

            // Check if the transfer via API failed
            if (!$transferResponse['success']) {
                DB::rollBack();
                Log::error('Transfer initiation failed for request number: ' . $uniqueRequestNumber);
                return response()->json([
                    'remark' => 'transfer_failed',
                    'status' => 'error',
                    'message' => ['error' => ['Transfer initiation failed']],
                ]);

            }

            // Update loan record after successful transfer
            $loan->transferred_amount += $repaymentAmount;
            $loan->save();

            // Commit the transaction
            DB::commit();

            // Update transfer status
            $transfer->status = 'success';
            $transfer->save();

            return response()->json([
                'remark'  => 'transfer_info',
                'status'  => 'success',
                'message' => ['success' => ['Amount Transferred successfully']],
                'data'    => [
                    'transfer' => $transfer,
                ],
            ]);



        } catch (\Exception $e) {
            // Rollback on error
            DB::rollBack();
            Log::error('Transfer initiation failed: ' . $e->getMessage());
            return response()->json([
                'remark' => 'transfer_failed',
                'status' => 'error',
                'message' => ['error' => ['Internal Server Error']],
            ]);

        }
    }
}
