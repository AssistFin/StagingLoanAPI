<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ScoreMeWebhook;
use Illuminate\Support\Facades\Log;
use App\Models\DigitapBankRequest;
use App\Services\DigitapBankStatementService;
use App\Models\LoanApplication;
use App\Models\LoanDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DigitapController extends Controller
{
    protected $baseUrl;
    protected $clientId;
    protected $clientSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.digitap.base_url');
        $this->clientId = config('services.digitap.client_id');
        $this->clientSecret = config('services.digitap.client_secret');
    }

    public function checkBSAReportByDigitap(Request $request, DigitapBankStatementService $digitap)
    {
        $request->validate([
            'loan_id' => 'required'
        ]);

        $bank_statement_filename = $request->post('bank_statement_filename');
        $bank_statement = $request->post('bank_statement');
        $bank_statement_pass = $request->post('bank_statement_pass');
        $loan_id = $request->post('loan_id');
        $bank_name = $request->post('bank_name');
        $stateCode = '0';

        try {
            if(strtolower($bank_name) == strtolower('HDFC')){ $stateCode = '1';}
            if(strtolower($bank_name) == strtolower('SBI')){ $stateCode = '2';}
            if(strtolower($bank_name) == strtolower('ICICI')){ $stateCode = '3';}
            if(strtolower($bank_name) == strtolower('Axis')){ $stateCode = '4';}
            if(strtolower($bank_name) == strtolower('Kotak')){ $stateCode = '5';}
            if(strtolower($bank_name) == strtolower('Andhra Bank')){ $stateCode = '6';}
            if(strtolower($bank_name) == strtolower('IDBI')){ $stateCode = '7';}
            if(strtolower($bank_name) == strtolower('Canara')){ $stateCode = '8';}
            if(strtolower($bank_name) == strtolower('PNB')){ $stateCode = '9';}
            if(strtolower($bank_name) == strtolower('Central')){ $stateCode = '10';}
            if(strtolower($bank_name) == strtolower('Yes')){ $stateCode = '11';}
            if(strtolower($bank_name) == strtolower('Indian')){ $stateCode = '12';}
            if(strtolower($bank_name) == strtolower('Federal')){ $stateCode = '13';}
            if(strtolower($bank_name) == strtolower('Citi')){ $stateCode = '14';}
            if(strtolower($bank_name) == strtolower('BOI')){ $stateCode = '15';}
            if(strtolower($bank_name) == strtolower('Union')){ $stateCode = '16';}
            if(strtolower($bank_name) == strtolower('Baroda')){ $stateCode = '17';}
            if(strtolower($bank_name) == strtolower('Dena Bank')){ $stateCode = '19';}
            if(strtolower($bank_name) == strtolower('Vijaya Bank')){ $stateCode = '20';}
            if(strtolower($bank_name) == strtolower('Corporation Bank')){ $stateCode = '21';}
            if(strtolower($bank_name) == strtolower('Oriental Bank of Commerce')){ $stateCode = '22';}
            if(strtolower($bank_name) == strtolower('United Bank of India')){ $stateCode = '23';}
            if(strtolower($bank_name) == strtolower('Syndicate')){ $stateCode = '24';}
            if(strtolower($bank_name) == strtolower('Standard')){ $stateCode = '25';}
            if(strtolower($bank_name) == strtolower('Induslnd')){ $stateCode = '26';}
            if(strtolower($bank_name) == strtolower('Allahabad Bank')){ $stateCode = '27';}
            if(strtolower($bank_name) == strtolower('Karnataka')){ $stateCode = '28';}
            if(strtolower($bank_name) == strtolower('IDFC')){ $stateCode = '29';}
            if(strtolower($bank_name) == strtolower('Overseas')){ $stateCode = '30';}
            if(strtolower($bank_name) == strtolower('Paytm')){ $stateCode = '31';}
            if(strtolower($bank_name) == strtolower('Karur Vysya Bank')){ $stateCode = '32';}
            if(strtolower($bank_name) == strtolower('Ujjivan')){ $stateCode = '33';}
            if(strtolower($bank_name) == strtolower('UCO')){ $stateCode = '34';}
            if(strtolower($bank_name) == strtolower('South')){ $stateCode = '35';}
            if(strtolower($bank_name) == strtolower('RBL')){ $stateCode = '36';}
            if(strtolower($bank_name) == strtolower('Fino')){ $stateCode = '37';}
            if(strtolower($bank_name) == strtolower('Maharashtra')){ $stateCode = '38';}
            if(strtolower($bank_name) == strtolower('AUSFB')){ $stateCode = '39';}
            if(strtolower($bank_name) == strtolower('FincareSFB')){ $stateCode = '41';}
            if(strtolower($bank_name) == strtolower('DBS')){ $stateCode = '42';}
            if(strtolower($bank_name) == strtolower('Bandhan')){ $stateCode = '43';}
            if(strtolower($bank_name) == strtolower('Municipal Bank')){ $stateCode = '44';}
            if(strtolower($bank_name) == strtolower('UtkarshSFB')){ $stateCode = '45';}
            if(strtolower($bank_name) == strtolower('Jana')){ $stateCode = '46';}
            if(strtolower($bank_name) == strtolower('ESAFSFB')){ $stateCode = '47';}
            if(strtolower($bank_name) == strtolower('Equitas')){ $stateCode = '48';}
            if(strtolower($bank_name) == strtolower('City')){ $stateCode = '49';}
            if(strtolower($bank_name) == strtolower('IndiaPost')){ $stateCode = '50';}
            if(strtolower($bank_name) == strtolower('DCB')){ $stateCode = '51';}
            if(strtolower($bank_name) == strtolower('Saraswat')){ $stateCode = '69';}
            if(strtolower($bank_name) == strtolower('Airtel')){ $stateCode = '70';}
            if(strtolower($bank_name) == strtolower('Tamilnad')){ $stateCode = '71';}
            if(strtolower($bank_name) == strtolower('Rajasthan')){ $stateCode = '72';}
            if(strtolower($bank_name) == strtolower('Abhyudaya')){ $stateCode = '73';}
            if(strtolower($bank_name) == strtolower('Jammu')){ $stateCode = '74';}
            if(strtolower($bank_name) == strtolower('KarnatakaG')){ $stateCode = '75';}
            if(strtolower($bank_name) == strtolower('APGVB')){ $stateCode = '76';}
            if(strtolower($bank_name) == strtolower('PunjabSind')){ $stateCode = '77';}
            if(strtolower($bank_name) == strtolower('Thane Janata Sahakari Bank')){ $stateCode = '78';}
            if(strtolower($bank_name) == strtolower('Cosmos')){ $stateCode = '79';}
            if(strtolower($bank_name) == strtolower('CSB')){ $stateCode = '80';}
            if(strtolower($bank_name) == strtolower('GP Parsik Bank')){ $stateCode = '81';}
            if(strtolower($bank_name) == strtolower('Dhanlaxmi Bank')){ $stateCode = '82';}
            if(strtolower($bank_name) == strtolower('SVC')){ $stateCode = '83';}
            if(strtolower($bank_name) == strtolower('Telangana State Co-operative Apex Bank Ltd')){ $stateCode = '84';}
            if(strtolower($bank_name) == strtolower('Janata Sahakari Bank Ltd')){ $stateCode = '85';}
            if(strtolower($bank_name) == strtolower('Kalyan Janata Sahakari Bank Ltd')){ $stateCode = '86';}
            if(strtolower($bank_name) == strtolower('kallapana awade Bank')){ $stateCode = '87';}
            if(strtolower($bank_name) == strtolower('Sarva Haryana Gramin Bank')){ $stateCode = '88';}
            if(strtolower($bank_name) == strtolower('Post Office Saving Bank')){ $stateCode = '89';}
            if(strtolower($bank_name) == strtolower('Sarvodaya Commercial Co-Operactive Bank Limited')){ $stateCode = '90';}
            if(strtolower($bank_name) == strtolower('NKGSB')){ $stateCode = '91';}
            if(strtolower($bank_name) == strtolower('HSBC')){ $stateCode = '92';}
            if(strtolower($bank_name) == strtolower('Tripura Gramin Bank')){ $stateCode = '98';}
            if(strtolower($bank_name) == strtolower('Andhra Pragathi Grameena Bank')){ $stateCode = '101';}
            if(strtolower($bank_name) == strtolower('Chhattisgarh Rajya Gramin Bank')){ $stateCode = '109';}
            if(strtolower($bank_name) == strtolower('Kerala')){ $stateCode = '116';}
            if(strtolower($bank_name) == strtolower('MaharashtraG')){ $stateCode = '118';}
            if(strtolower($bank_name) == strtolower('Apna Sahakari Bank')){ $stateCode = '135';}
            if(strtolower($bank_name) == strtolower('NorthEastSFB')){ $stateCode = '138';}
            if(strtolower($bank_name) == strtolower('Suryoday Small Finance Bank')){ $stateCode = '139';}
            if(strtolower($bank_name) == strtolower('Kalupur Commercial Co-operative Bank')){ $stateCode = '141';}
            if(strtolower($bank_name) == strtolower('Jio Payments Bank Ltd')){ $stateCode = '169';}
            if(strtolower($bank_name) == strtolower('Tumkur Grain Merchants Co-operative Bank')){ $stateCode = '277';}
            if(strtolower($bank_name) == strtolower('Bharat Co-operative Bank')){ $stateCode = '278';}
            

            // Step 1: Start Upload & Save in DB
            $bankReq = $digitap->initiateForCustomer($loan_id, $stateCode, 'Loan1' . $loan_id);

            // Step 2: Get file path from your storage or DB
            $newbank_statement = str_replace("/admin/", "/api/", $bank_statement);
            $filePath = $newbank_statement;

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

        $data = json_decode($record->report_json_data, true);

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

    public function analyzeReports($leadId, DigitapBankStatementService $service)
    {
        $statusResponse = $service->processReports($leadId);
        return response()->json([
            'status'  => 'success',
            'message' => 'BSA Report Process Completed !!',
            'data'    => $statusResponse ?? []
        ]);
    }

    public function analyzeReportData($id)
    {
        $record = DB::table('bre_data')->where('lead_id', $id)->first();

        if (!$record) {
            return response('Record not found.', 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'BSA Report Process Completed !!',
            'data'    => $record ?? []
        ]);
    }

    public function aacallback(Request $request, DigitapBankStatementService $digitap)
    {
        Log::info('Digitap AA Callback Received', $request->all());

        Log::channel('webhook')->info(
            "========== Digitap AA API Callback Response ==========\n\n" .
            json_encode($request->all(), JSON_PRETTY_PRINT) .
            "\n\n===================================================="
        );

        $txn_id = $request->input('txn_id');
        $code = !empty($request->input('code')) ? $request->input('code') : 'processing';
        $status = !empty($request->input('status')) ? $request->input('status') : 'Failed';
        $request_id = !empty($request->input('request_id')) ? $request->input('request_id') : null;


        if($request_id && $txn_id){
            $digitapBankRequest = DigitapBankRequest::where(['request_id' => $request_id])
            ->update([
                "status" => $code,
                "txn_id" => $txn_id,
            ]);
            $loanData = DigitapBankRequest::where('request_id', $request_id)->first();

            $reportData = $digitap->retrieveReport($loanData);

            return response()->json(['message' => 'Webhook handled OK'], 200);
        }else{
            return response()->json(['message' => 'Webhook Failed'], 301);
        }
    }

    public function handleCallback(Request $request)
    {
        // Example fields from query string
        $event = $request->event;
        $success = $request->success;
        $transactionId = $request->transactionId;

        // 🔹 Save or process the callback
        Log::info('Selfie callback', $request->all());

        $selfieData = DB::table('selfie_data')->where('txn_id', $transactionId)->orderBy('id','desc')->first();
        //Log::info('Selfie Data', $selfieData ? (array) $selfieData : []);
        if(!empty($selfieData) && $success == true){
            return response()->json([
                'success' => true,
                'message' => 'Callback received successfully'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Callback not updated'
            ]);
        }
    }


    public function selfiecallback(Request $request, DigitapBankStatementService $digitap)
    {
        Log::info('Digitap AA Callback Received', $request->all());

        Log::channel('webhook')->info(
            "========== Digitap Selefie API Callback Response ==========\n\n" .
            json_encode($request->all(), JSON_PRETTY_PRINT) .
            "\n\n===================================================="
        );

        $uniqueId = $request->input('uniqueId');
        $transactionId = !empty($request->input('transactionId')) ? $request->input('transactionId') : 0;
        $status = !empty($request->input('status')) ? $request->input('status') : 'FAILED';

        DB::table('selfie_data')->updateOrInsert(
            ['txn_id' => $transactionId],  
            [
                'status' => $status
            ]
        );

        $selfieData = DB::table('selfie_data')->where('txn_id', $transactionId)->orderBy('id','desc')->first();

        $loan = LoanApplication::where([
                    ['user_id', $selfieData->user_id],
                    ['id', $selfieData->lead_id]
                ])->first();

        if($selfieData && $status == 'SUCCESS'){
            if ($loan) {
                $loan->current_step = 'submitselfie';
                $loan->next_step = 'addressconfirmation';
                $loan->save();
            }

            $resData = $this->getSelfieData($uniqueId, $transactionId);

            if(!empty($resData) && !empty($resData['model']['base64Image'])){
                $this->uploadSelfie($resData['model'], $transactionId);
            }
        } else {
            if ($loan) {
                $loan->current_step = 'verifyotp';
                $loan->next_step = 'submitselfie';
                $loan->save();
            }
        }

        if($uniqueId && $transactionId){
            return response()->json(['message' => 'Webhook handled OK'], 200);
        }else{
            return response()->json(['message' => 'Webhook Failed'], 301);
        }
    }

    public function getSelfieData($uniqueId , $transactionId)
    {
        $payload = [
            'uniqueId'            => $uniqueId,
            'transactionId'       => $transactionId,
        ];

        $response = $this->httpPost('/selfie/v1/get-selfie-data', $payload);

        \Log::info('Digitap generateurl Payload', ['payload' => $payload]);
        //\Log::info('Digitap generateurl Response', ['response' => $response]);

        DB::table('selfie_data')->updateOrInsert(
            ['txn_id' => $transactionId],  
            [
                'selfie_response' => $response
            ]
        );

        return $response ?? null;
    }

    protected function httpPost($endpoint, $payload)
    {
        $res = Http::withHeaders([
            'ent_authorization' => base64_encode(config('services.digitap.client_id'). ':' . config('services.digitap.client_secret')),
            'content-type'  => 'application/json',
        ])->post(rtrim(config('services.digitap.db_url'), '/') . $endpoint, $payload);

        // Try to decode JSON
        $json = $res->json();
        return $json ?? [
            'status_code' => $res->status(),
            'raw'         => $res->body()
        ];
    }

    protected function uploadSelfie($data, $txn_id)
    {
        //Log::info('Received selfie upload request', $data);

        try {
            
            $selfieLoanAppData = DB::table('selfie_data')->where('txn_id', $txn_id)->orderBy('id','desc')->first();

            $base64 = $data['base64Image'] ?? null;

            if (!$base64) {
                Log::error('Missing base64 image in selfie response');
                return;
            }

            // Remove prefix if present
            if (str_contains($base64, 'base64,')) {
                $base64 = explode('base64,', $base64)[1];
            }

            $imageData = base64_decode($base64);

            if ($imageData === false) {
                Log::error('Invalid base64 image received');
                return;
            }

            // Directory
            $securePath = config('services.docs.upload_kfs_doc');

            if (!file_exists($securePath)) {
                mkdir($securePath, 0777, true);
            }

            // File name
            $fileName = 'selfie_' . time() . '.png';
            $filePath = $securePath . '/' . $fileName;

            // Save file
            file_put_contents($filePath, $imageData);

            Log::info('Selfie saved locally', ['path' => $filePath]);

            // Save to DB
            LoanDocument::updateOrCreate(
                ['loan_application_id' => $selfieLoanAppData->lead_id],
                ['selfie_image' => $fileName]
            );

            // Update loan step
            $loan = LoanApplication::where([
                ['user_id', auth()->id()],
                ['id', $selfieLoanAppData->lead_id]
            ])->first();

            if ($loan) {
                $loan->current_step = 'submitselfie';
                $loan->next_step   = 'addressconfirmation';
                $loan->save();
            }

            Log::info('Selfie updated in loan workflow', [
                'loan_application_id' => $selfieLoanAppData->lead_id
            ]);

        } catch (\Exception $e) {
            Log::error('Selfie processing failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
}
