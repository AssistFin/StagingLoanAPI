<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\DigitapBankRequest;
use Illuminate\Support\Facades\Storage;

class DigitapBankStatementService
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

    /**
     * Start Upload for a customer
     */
    public function initiateForCustomer($customerId, $institutionId, $refNum)
    {
        $payload = [
            'institution_id'      => $institutionId,
            'client_ref_num'      => $refNum,
            'txn_completed_cburl' => config('services.docs.app_url').'/api/digitap/bsu/webhook',
            'start_month'         => now()->subMonths(4)->format('Y-m'),
            'end_month'           => now()->subMonth()->format('Y-m'),
            'acceptance_policy'   => 'atLeastOneTransactionInRange',
            'relaxation_days'     => '2'
        ];

        $response = $this->httpPost('/startupload', $payload);
        
        \Log::info('Digitap StartUpload Response', ['response' => $response]);
        if (!is_array($response) || empty($response)) {
            return [
                'status'  => 'error',
                'message' => 'Invalid response from Digitap StartUpload API',
                'raw'     => $response
            ];
        }
        return DigitapBankRequest::create([
            'customer_id'           => $customerId,
            'request_id'            => $response['request_id'] ?? null,
            'txn_id'                => $response['txn_id'] ?? null,
            'token'                 => $response['token'] ?? null,
            'status'                => $response['status'] === 'success' ? 'pending_upload' : 'failed',
            'start_upload_response' => $response
        ]);
    }

    /**
     * Upload Statement
     */
    public function uploadStatement(DigitapBankRequest $request, $filePath, ?string $filePasswordB16 = null)
    {
        $multipart = [
            [
                'name'     => 'token',
                'contents' => $request->token
            ],
            [
                'name'     => 'request_id',
                'contents' => $request->request_id
            ],
            [
                'name'     => 'file',
                'contents' => fopen($filePath, 'r'),
                'filename' => basename($filePath),
            ]
        ];

        if ($filePasswordB16) {
            $multipart[] = [
                'name'     => 'file_password_b16',
                'contents' => $filePasswordB16
            ];
        }

        $response = Http::asMultipart()
            ->post($this->baseUrl . '/uploadstmt', $multipart)
            ->json();

        $request->update([
            'status' => $response['status'] === 'success' ? 'uploaded' : 'upload_failed'
        ]);

        return $response;
    }

    /**
     * Complete Upload
     */
    public function completeUpload(DigitapBankRequest $request)
    {
        $response = $this->httpPost('/completeupload', [
            'request_id' => $request->request_id
        ]);

        $request->update([
            'status' => $response['status'] === 'success' ? 'processing' : 'complete_failed'
        ]);

        return $response;
    }

    /**
     * Check Status
     */
    public function checkStatus(DigitapBankRequest $request)
    {
        $response = $this->httpPost('/statuscheck', [
            'request_id' => $request->request_id
        ]);

        $status = $request->status;
        if (!empty($response['txn_status'][0]['code']) && $response['txn_status'][0]['code'] === 'ReportGenerated') {
            $status = 'report_generated';
        }

        $request->update([
            'status'          => $status,
            'status_response' => $response
        ]);

        return $response;
    }

    /**
     * Retrieve Report
     */
    public function retrieveReport(DigitapBankRequest $request, $reportType = 'json', $reportSubtype = 'type3')
    {
        // 1. JSON report
        $response = $this->httpPost('/retrievereport', [
            'txn_id'        => $request->txn_id,
            'report_type'   => $reportType,
            'report_subtype'=> $reportSubtype
        ]);

        DigitapBankRequest::where('txn_id', $request->txn_id)
            ->update([
                'report_json_data' => json_encode($response, JSON_UNESCAPED_UNICODE),
                'status'           => 'json_report_saved'
            ]);

        // 2. XLSX report
        $response2 = $this->httpPost('/retrievereport', [
            'txn_id'        => $request->txn_id,
            'report_type'   => 'xlsx',
            'report_subtype'=> $reportSubtype
        ]);

        $xlsx_filePath = null;

        if (!empty($response2['raw'])) {
            $xlsxData = $response2['raw']; // binary data

            // relative path in storage/app/public/
            $xlsx_filePath = $request->txn_id.'_report.xlsx';

            // make sure directory exists in storage/app/public/
            Storage::disk('public')->makeDirectory('digitap_reports');

            // save file using Laravel Storage
            Storage::disk('public')->put($xlsx_filePath, $xlsxData);

            // update db with only the path
            DigitapBankRequest::where('txn_id', $request->txn_id)
                ->update([
                    'report_xlsx_data' => $xlsx_filePath, // store relative path
                    'status'           => 'xlsx_report_saved'
                ]);
        }

        // return clean response (without binary)
        return $response;
    }


    /**
     * Helper: POST request with Header Auth
     */
    protected function httpPost($endpoint, $payload)
    {
        $res = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            'Content-Type'  => 'application/json',
        ])->post(rtrim($this->baseUrl, '/') . $endpoint, $payload);

        // Try to decode JSON
        $json = $res->json();

        if ($json === null && $payload['report_type'] != 'xlsx') {
            \Log::error("Digitap API ($endpoint) returned non-JSON", [
                'status'   => $res->status(),
                'body'     => $res->body(),
                'payload'  => $payload,
                'endpoint' => $this->baseUrl . $endpoint
            ]);
        }

        return $json ?? [
            'status_code' => $res->status(),
            'raw'         => $res->body()
        ];
    }

}
