<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\LoanApplication;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use App\Models\CashfreeEnachRequestResponse;

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
          ->addSelect([
            'latest_reference_id' => CashfreeEnachRequestResponse::select('reference_id')
                ->whereColumn('subscription_id', 'loan_applications.loan_no')
                ->whereNotNull('reference_id')
                ->orderByDesc('created_at')
                ->limit(1)
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

}
