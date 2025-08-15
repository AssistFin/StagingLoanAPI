<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ScoreMeWebhook;
use Illuminate\Support\Facades\Log;
use App\Models\DigitapBankRequest;
use App\Services\DigitapBankStatementService;
use App\Models\LoanApplication;

class ScoreMeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Log incoming payload
        Log::channel('webhook')->info('ScoreMe Webhook Payload', $request->all());

        // Validate expected structure
        $data = $request->input('data');
        $responseCode = $request->input('responseCode');

        if ($responseCode === 'SRC001' && $data && isset($data['referenceId'])) {
            // Save to DB
            ScoreMeWebhook::updateOrCreate(
                ['reference_id' => $data['referenceId']],
                [
                    'json_url'   => $data['jsonUrl'] ?? null,
                    'excel_url'  => $data['excelUrl'] ?? null,
                ]
            );

            return response()->json(['message' => 'Webhook received and stored.'], 200);
        }

        return response()->json(['message' => 'Invalid or unrecognized webhook payload.'], 400);
    }


    public function checkBSAReportByScoreMeTest( Request $request )
    {
        $bank_statement_filename = $request->post('bank_statement_filename');
        $bank_statement = $request->post('bank_statement');
        $bank_statement_pass = $request->post('bank_statement_pass');
        $loan_id = $request->post('loan_id');
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => config('services.scoremebsa.smbsa_upload_doc_url'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('file'=> $bank_statement,'data' => '{ "filePassword": { "$bank_statement_filename": "$bank_statement_pass" } }'),
            CURLOPT_HTTPHEADER => array(
                'ClientId: '.config('services.scoremebsa.smbsa_cid'),
                'ClientSecret: '.config('services.scoremebsa.smbsa_csec'),
                'Content-Type: multipart/form-data'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $result = json_decode($response,true);
        dd($response);
        if($result['responseCode'] === 'SRS016' && $result['data']['referenceId']){
            ScoreMeWebhook::updateOrCreate(
                ['reference_id' => $result['data']['referenceId']],
                [
                    'json_url'   => null,
                    'excel_url'  => null,
                    'loan_id'    => $loan_id
                ]
            );
        }
    }

    public function checkBSAReportByScoreMe(Request $request, DigitapBankStatementService $digitap)
    {
        $request->validate([
            'loan_id' => 'required'
        ]);

        $bank_statement_filename = $request->post('bank_statement_filename');
        $bank_statement = $request->post('bank_statement');
        $bank_statement_pass = $request->post('bank_statement_pass');
        $loan_id = $request->post('loan_id');

        $customer = LoanApplication::find($loan_id);

        try {
            // Step 1: Start Upload
            $bankReq = $digitap->initiateForCustomer(
                $customer->id,
                1, // institution_id
                'REF-' . $customer->id
            );

            // Step 2: Upload Statement (assuming file path stored in DB)
            $filePath = $bank_statement;
            $digitap->uploadStatement($bankReq, $filePath);

            // Step 3: Complete Upload
            $digitap->completeUpload($bankReq);

            // Step 4: Check Status
            $statusResponse = $digitap->checkStatus($bankReq);

            // Step 5: If Report Ready → Retrieve
            $reportData = null;
            if ($bankReq->status === 'report_generated') {
                $reportData = $digitap->retrieveReport($bankReq);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'BSA Report Processed',
                'data' => $reportData ?? $statusResponse
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function callback(Request $request)
    {
        Log::info('Digitap Callback Received', $request->all());

        return response()->json(['status' => 'ok'], 200);
    }
}
