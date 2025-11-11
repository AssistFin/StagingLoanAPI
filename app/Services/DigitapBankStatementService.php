<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\DigitapBankRequest;
use App\Models\ExperianCreditReport;
use Illuminate\Support\Facades\Storage;
use App\Models\BreData;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

    public function processReports($id)
    {
        $resData = [];

        // =======================
        // 1️⃣ DIGITAP REPORT PART
        // =======================
        $requests = DigitapBankRequest::where('status', 'xlsx_report_saved')
            ->whereNotNull(DB::raw("JSON_EXTRACT(report_json_data, '$.analysis_data.Overall')"))
            ->where('customer_id', $id)
            ->first();

        $monthlySalary = 0;

        if ($requests) {
            $data = json_decode($requests->report_json_data, true);

            $overall = $data['analysis_data']['Overall'] ?? [];
            $startDate = $data['statement_start_date'] ?? null;
            $endDate = $data['statement_end_date'] ?? null;

            $start = $startDate ? Carbon::parse($startDate) : null;
            $end = $endDate ? Carbon::parse($endDate) : null;

            $totalDays = $end && $start ? $end->diffInDays($start) + 1 : 0;
            $numFullMonths = floor($totalDays / 30);
            $remainingDays = $totalDays % 30;
            $numMonths = $remainingDays >= 20 ? $numFullMonths + 1 : $numFullMonths;

            $salaryTotal = (float)($overall["Total Amount of Salary Credits"] ?? 0);
            $bizTotal = (float)($overall["Total Amount of Business Credit Transactions"] ?? 0);
            $salaryCount = (int)($overall["Total No of Salary Credits"] ?? 0);
            $bizCount = (int)($overall["Total No. of Business Credit Transactions"] ?? 0);
            $bounced = (int)($overall["Total No. of I/W ECS Bounced"] ?? 0);
            $confidence = $data['tamper_detection_details'][0]['original_pdf_confidence_score'] ?? 0;

            $monthlySalary = $numMonths ? $salaryTotal / $numMonths : 0;
            $monthlyBiz = $numMonths ? $bizTotal / $numMonths : 0;
            $maxMonthlyCredit = max($monthlySalary, $monthlyBiz);

            $approvedAmount = 0;
            $tag = 'Rejected';
            $reason = null;

            if ($confidence < 100) {
                $tag = 'Hard Rejected';
                $reason = "Hard Reject: PDF confidence score is $confidence (less than 100).";
            } elseif ($totalDays < 85) {
                $tag = 'Hard Rejected';
                $reason = "Hard Reject: Statement gap is $totalDays days, which is less than 85.";
            } elseif ($bounced > 3) {
                $tag = 'Hard Rejected';
                $reason = "Hard Reject: Total Inward Bounced transactions ($bounced) exceeded 3.";
            } elseif ($maxMonthlyCredit < 25000) {
                $tag = 'Hard Rejected';
                $reason = "Hard Reject: Maximum monthly income ($maxMonthlyCredit) < 25,000.";
            } elseif ($salaryCount >= 3 && $salaryTotal >= 75000) {
                $approvedAmount = round($monthlySalary * 0.25, 2);
                //$approvedAmount = min($approvedAmount, 40000); // ✅ Cap at 40,000
                $tag = 'Salary';
            } elseif ($numMonths && ($bizCount / $numMonths) > 4 && $monthlyBiz > 100000) {
                $approvedAmount = round($monthlyBiz * 0.20, 2);
                //$approvedAmount = min($approvedAmount, 40000); // ✅ Cap at 40,000
                $tag = 'Business';
            } else {
                $tag = 'Rejected';
                if ($salaryCount < 3 || $salaryTotal < 75000) {
                    $reason = 'Soft Reject: Failed to meet Salary criteria (Credits < 3 or Amount < 75,000).';
                } else {
                    $reason = "Soft Reject: Business Rules failed (Txn Count " .
                        round($bizCount / max($numMonths, 1), 2) .
                        " or Amount $monthlyBiz).";
                }
            }

            // ✅ Store first result
            $resData['digitap'] = [
                'approved_amount' => $approvedAmount,
                'salary_or_business_tag' => $tag,
                'rejected_reason' => $reason,
                'monthly_salary' => $monthlySalary,
                'confidence_score' => $confidence,
                'bounced' => $bounced,
                'salary_count' => $salaryCount,
                'biz_count' => $bizCount,
                'salary_total' => $salaryTotal,
                'biz_total' => $bizTotal,
                'max_monthly_credit' => $maxMonthlyCredit,
                'total_days' => $totalDays,
            ];
        }

        // =======================
        // 2️⃣ EXPERIAN REPORT PART
        // =======================
        $requests2 = ExperianCreditReport::whereNotNull(DB::raw("JSON_EXTRACT(response_data, '$.SCORE.BureauScore')"))
            ->where('lead_id', $id)
            ->first();

        if ($requests2) {
            $data2 = json_decode($requests2->response_data, true);
            $score = $data2['SCORE']['BureauScore'] ?? 0;

            $approvedAmount2 = 0;
            $tag2 = 'Rejected';
            $reason2 = null;

            if ($score < 550) {
                $tag2 = 'Hard Rejected';
                $reason2 = "Hard Reject: Credit Bureau score is $score (less than 550).";
            } else {
                $approvedAmount2 = 15000;
                $tag2 = 'Salary';
            }

            // ✅ Store second result
            $resData['experian'] = [
                'request_id' => $requests2->id,
                'customer_id' => $requests2->lead_id,
                'approved_amount' => $approvedAmount2,
                'salary_or_business_tag' => $tag2,
                'rejected_reason' => $reason2,
                'score' => $score,
            ];
        }

        // =======================
        // 3️⃣ MONTHLY SALARY × 0.35 CHECK
        // =======================
        $monthlySalary = $resData['digitap']['monthly_salary'] ?? 0;
        $bscore = $resData['experian']['score'] ?? 0;

        if ($monthlySalary > 0 && $bscore >= 550) {
            $approvedAmount3 = round($monthlySalary * 0.35, 2);
            //$approvedAmount3 = min($approvedAmount3, 40000); // ✅ Cap at 40,000
            $tag3 = 'Salary';
            $reason3 = "Approved based on credit score $bscore and monthly salary ₹" . number_format($monthlySalary) . ".";
        } else {
            $approvedAmount3 = 0;
            $tag3 = 'Rejected';
            $reason3 = "Insufficient data for combined salary-based approval.";
        }

        $resData['monthly_salary_check'] = [
            'approved_amount' => $approvedAmount3,
            'salary_or_business_tag' => $tag3,
            'rejected_reason' => $reason3,
            'bureau_score' => $bscore,
        ];

        // =======================
        // 4️⃣ FINAL DECISION (DEPENDENT ON APPROVAL STATUS)
        // =======================
        $digitapAmt = $resData['digitap']['approved_amount'] ?? 0;
        $digitapTag = $resData['digitap']['salary_or_business_tag'] ?? null;

        $monthlyAmt = $resData['monthly_salary_check']['approved_amount'] ?? 0;
        $monthlyTag = $resData['monthly_salary_check']['salary_or_business_tag'] ?? null;

        // ✅ Determine which ones are approved
        $isDigitapApproved = in_array($digitapTag, ['Salary', 'Business']);
        $isMonthlyApproved = in_array($monthlyTag, ['Salary', 'Business']);

        $finalAmt = 0;
        $finalTag = 'Rejected';
        $finalReason = 'No approved source found.';

        // ✅ Case 1: Both approved → take minimum
        if ($isDigitapApproved && $isMonthlyApproved) {
            $finalAmt = min($digitapAmt, $monthlyAmt);
            $finalAmt = min($finalAmt, 40000);
            $finalTag = 'Approved';
            $finalReason = "Both approved. Minimum of Digitap (₹$digitapAmt) and Monthly (₹$monthlyAmt) taken.";
        }else {
            $finalTag = 'Rejected';
            $finalReason = "Both Digitap and Monthly evaluations were rejected.";
        }

        $resData['final'] = [
            'final_approved_amount' => $finalAmt,
            'decision' => $finalTag,
            'reason' => $finalReason,
            'logic' => "Dependent on approval sources only",
        ];

        $bre = BreData::where('lead_id', $id)->first();

        if(empty($bre)){
            BreData::updateOrCreate(
                ['lead_id' => $id],
                [
                    'digitap_monthly_salary' => $resData['digitap']['monthly_salary'] ?? null,
                    'digitap_confidence_score' => $resData['digitap']['confidence_score'] ?? null,
                    'digitap_bounced_count' => $resData['digitap']['bounced'] ?? null,
                    'digitap_salary_count' => $resData['digitap']['salary_count'] ?? null,
                    'digitap_biz_count' => $resData['digitap']['biz_count'] ?? null,
                    'digitap_salary_total' => $resData['digitap']['salary_total'] ?? null,
                    'digitap_biz_total' => $resData['digitap']['biz_total'] ?? null,
                    'digitap_max_monthly_credit' => $resData['digitap']['max_monthly_credit'] ?? null,
                    'digitap_total_days' => $resData['digitap']['total_days'] ?? null,
                    'bureau_score' => $resData['monthly_salary_check']['bureau_score'] ?? null,
                    'final_decision' => $resData['final']['decision'] ?? 'Rejected',
                    'final_approved_amount' => $resData['final']['final_approved_amount'] ?? 0,
                    'digitap_result' => json_encode($resData['digitap'] ?? []),
                    'experian_result' => json_encode($resData['experian'] ?? []),
                    'monthly_salary_check_result' => json_encode($resData['monthly_salary_check'] ?? []),
                    'final_result' => json_encode($resData['final'] ?? []),
                ]
            );
        }

        // =======================
        // FINAL RETURN
        // =======================
        if (!empty($resData)) {
            return $resData;
        }

        return ['message' => 'No record found.'];
    }

}
