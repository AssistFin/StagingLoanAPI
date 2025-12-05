<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\LoanApplication;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use App\Models\CashfreeEnachRequestResponse;
use Illuminate\Support\Facades\Http;

class BankingController extends Controller
{
    public function index(Request $request)
    {
        $query = LoanApplication::with([
            'user:id,firstname,lastname,mobile,email',
            'personalDetails', 
            'employmentDetails', 
            'kycDetails', 
            'loanDocument',
            'addressDetails', 
            'bankDetails',
            'loanDisbursal',
            'loanApproval',
        ])->where('loan_disbursal_status', 'pending')
          ->where('admin_approval_status', 'approved')
          ->where('user_acceptance_status', 'accepted')
          ->whereHas('cashfreeEnachRequests', function ($q) {
                $q->where('status', 'ACTIVE')->whereNotNull('reference_id');
            })
          ->addSelect([
                'latest_reference_id' => CashfreeEnachRequestResponse::select('reference_id')
                    ->whereColumn('subscription_id', 'loan_applications.loan_no')
                    ->where('status', 'ACTIVE')
                    ->whereNotNull('reference_id')
                    ->orderByDesc('created_at')
                    ->limit(1),
            ])
          ->orderByRaw('created_at DESC');
        
        // dd(vsprintf(
        //     str_replace('?', '%s', $query->toSql()), 
        //     collect($query->getBindings())->map(function ($binding) {
        //         return is_numeric($binding) ? $binding : "'{$binding}'";
        //     })->toArray()
        // ));
        $searchTerm = $request->get('search');
            
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('user', function ($userQuery) use ($searchTerm) {
                    $userQuery->where('firstname', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhere('mobile', 'like', "%{$searchTerm}%");
                })
                ->orWhere('loan_no', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->has('export') && $request->export === 'csv') {
            $leads = $query->get();
            $csvData = [];

            foreach ($leads as $lead) {

                $accountNumber = $lead->bankDetails->account_number ?? null;
                $ifsc = $lead->bankDetails->ifsc_code ?? null;
                $customerName = $lead->user->firstname . ' ' . $lead->user->lastname;

                // --------------------------------------------
                // 🔍 1. CALL PENNY DROP API FOR EACH LEAD
                // --------------------------------------------
                $pennyResult = $this->checkPennyDrop(
                    $accountNumber,
                    $ifsc,
                    $customerName,
                    $lead->loan_no
                );
                //return $pennyResult;
                // Default amount value
                $loanAmount = $lead->loan_amount;
                $loandate = $lead->created_at;
                $today = Carbon::today()->toDateString();

                $csvData[] = [
                    'Loan Application No' => $lead->loan_no,
                    'E-mandate Reference No' => $lead->latest_reference_id ?? '',
                    'Beneficiary Name' => $lead->user->firstname . ' ' . $lead->user->lastname,
                    'Beneficiary Account Number' => "'".$lead->bankDetails->account_number ?? '',
                    'IFSC' => $lead->bankDetails->ifsc_code ?? '',
                    'Transaction Type' => 'NEFT',
                    'Debit Account Number' => '10223292778',
                    'Transaction Date' => $today,
                    'Amount' => number_format($lead->loanApproval->disbursal_amount, 2),
                    'Currency' => 'INR',
                    'Beneficiary Email ID' => $lead->user->email,
                    'Name Matched Status' => $pennyResult['status'] ?? null,
                    'Name Matched' => $pennyResult['data']['model']['isNameMatch'] ?? null,
                    'Name Matched (%)' => $pennyResult['data']['model']['matchingScore'] ?? null,
                    'PennyDrop Desc' => $pennyResult['data']['model']['desc'] ?? null,
                    'Remarks' => '',
                    'Custom Header 1' => '',
                    'Custom Header 2' => '',
                    'Custom Header 3' => '',
                    'Custom Header 4' => '',
                    'Custom Header 5' => '',
                ];
            }
            $timestamp = now()->format('dmy');

            $filename = "DISB_{$timestamp}.csv";

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function () use ($csvData) {
                $file = fopen('php://output', 'w');
                fputcsv($file, array_keys($csvData[0]));
                foreach ($csvData as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            };

            return Response::stream($callback, 200, $headers);
        }

        $bankings = $query->paginate(25);

        return view('admin.banking.index', compact('bankings'));
    }

    private function checkPennyDrop($accountNumber, $ifsc, $customerName, $ref_no)
    {
        if (!$accountNumber || !$ifsc) {
            return [
                'status' => 'failed',
                'matched' => false,
                'api_name' => null,
            ];
        }

        try {

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode(config('services.digitap.client_id'). ':' . config('services.digitap.client_secret')),
            ])->post(('https://api.digitap.ai/penny-drop/v2/check-valid'), [
                'accNo' => $accountNumber,
                'ifsc' => $ifsc,
                'benificiaryName' => $customerName,
                'clientRefNum' => $ref_no,
            ]);

            if (!$response->successful()) {
                return [
                    'status' => 'failed',
                    'data' => [],
                ];
            }

            $data = $response->json();

            return [
                'status'  => $data['status'] ?? 'failed',
                'data' => $data
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'data' => [],
            ];
        }
    }

}
