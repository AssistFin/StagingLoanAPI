<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\DigitapBankRequest;

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
            'txn_completed_cburl' => '',
            'start_month'         => now()->subMonths(3)->format('Y-m'),
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
            ->post($this->baseUrl . '/uploadstatement', $multipart)
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
    public function retrieveReport(DigitapBankRequest $request, $reportType = 'json', $reportSubtype = 'type1')
    {
        $response = $this->httpPost('/retrievereport', [
            'txn_id'        => $request->txn_id,
            'report_type'   => $reportType,
            'report_subtype'=> $reportSubtype
        ]);

        $request->update([
            'report_data' => $response,
            'status'      => 'report_saved'
        ]);

        return $response;
    }

    /**
     * Helper: POST request with Header Auth
     */
    protected function httpPost($endpoint, $payload)
    {
        return Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            'Content-Type'  => 'application/json',
        ])->post($this->baseUrl . $endpoint, $payload)->json();
    }
}
