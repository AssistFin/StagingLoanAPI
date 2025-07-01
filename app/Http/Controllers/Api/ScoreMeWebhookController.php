<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ScoreMeWebhook;
use Illuminate\Support\Facades\Log;

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


    public function checkBSAReportByScoreMe( Request $request )
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
}
