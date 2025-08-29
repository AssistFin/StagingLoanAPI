<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ScoreMeWebhook;
use Illuminate\Support\Facades\Log;
use App\Models\DigitapBankRequest;
use App\Services\DigitapBankStatementService;
use App\Models\LoanApplication;
use Illuminate\Support\Facades\DB;

class DigitapController extends Controller
{

    public function checkBSAReportByDigitap(Request $request, DigitapBankStatementService $digitap)
    {
        $request->validate([
            'loan_id' => 'required'
        ]);

        $bank_statement_filename = $request->post('bank_statement_filename');
        $bank_statement = $request->post('bank_statement');
        $bank_statement_pass = $request->post('bank_statement_pass');
        $loan_id = $request->post('loan_id');
        //dd($loanApplicationData);

        try {
            // Step 1: Start Upload & Save in DB
            $bankReq = $digitap->initiateForCustomer($loan_id, 25, 'Loan1' . $loan_id);

            // Step 2: Get file path from your storage or DB
            $newbank_statement = str_replace("/admin/", "/api/", $bank_statement);
            $filePath = $newbank_statement;
            //return $filePath;
            // if (!file_exists($filePath)) {
            //     return response()->json([
            //         'status'  => 'error',
            //         'message' => "Bank statement PDF not found for user {$customer->id}"
            //     ], 404);
            // }

            // Step 3: Upload Statement
            $digitap->uploadStatement($bankReq, $filePath, bin2hex($bank_statement_pass));

            // Step 4: Complete Upload
            $digitap->completeUpload($bankReq);

            // Step 5: Check Status
            $statusResponse = $digitap->checkStatus($bankReq);

            // Step 6: Retrieve Report if generated
            $reportData = null;
            if ($bankReq->status === 'report_generated') {
                $reportData = $digitap->retrieveReport($bankReq);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'BSA Report process completed',
                'data'    => $reportData ?? $statusResponse
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function checkStatusBSAReportByDigitap(Request $request, DigitapBankStatementService $digitap)
    {
        $request->validate([
            'loan_id' => 'required'
        ]);

        $loan_id = $request->post('loan_id');

        $loanData = DigitapBankRequest::where('customer_id', $loan_id)->orderBy('id','desc')->first();

        try {
            // Step 5: Check Status
            $statusResponse = $digitap->checkStatus($loanData);
            //dd($statusResponse);
            // Step 6: Retrieve Report if generated
            $reportData = null;
            if (!empty($statusResponse['txn_status'][0]['code']) && $statusResponse['txn_status'][0]['code'] === 'ReportGenerated') {
                $reportData = $digitap->retrieveReport($loanData);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'BSA Report process completed',
                'data'    => $reportData ?? $statusResponse
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function bsaDataShow($id)
    {
        $record = DB::table('digitap_bank_requests')->where('customer_id', $id)->first();

        if (!$record) {
            return response('Record not found.', 404);
        }

        $data = json_decode($record->report_data, true);

        return view('admin.digitap.bsa-pdf-template', compact('data'));
    }

    public function callback(Request $request)
    {
        Log::info('Digitap Callback Received', $request->all());

                Log::channel('webhook')->info(
            "========== Digitap Bank Upload API Callback Response ==========\n\n" .
            json_encode($request->all(), JSON_PRETTY_PRINT) .
            "\n\n===================================================="
        );

        $txn_id = $request->input('txn_id');
        $code = !empty($request->input('code')) ? $request->input('code') : 'processing';
        $status = !empty($request->input('status')) ? $request->input('status') : 'Failed';
        $request_id = !empty($request->input('request_id')) ? $request->input('request_id') : null;


        if($request_id && $txn_id){
            $digitapBankRequest = DigitapBankRequest::where(['txn_id' => $txn_id, 'request_id' => $request_id])
            ->update([
                "status" => $code,
            ]);
            return response()->json(['message' => 'Webhook handled OK'], 200);
        }else{
            return response()->json(['message' => 'Webhook Failed'], 301);
        }
    }
}
