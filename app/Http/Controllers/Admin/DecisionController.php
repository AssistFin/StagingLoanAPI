<?php

namespace App\Http\Controllers\Admin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\LoanApplication;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;

class DecisionController extends Controller
{
    public function decisionApproved(Request $request)
    {
        $query = LoanApplication::with([
            'user',
            'personalDetails', 
            'employmentDetails', 
            'kycDetails', 
            'loanDocument',
            'addressDetails', 
            'bankDetails',
            'loanApproval'
        ])->where('admin_approval_status', 'approved')
          ->where('loan_disbursal_status', '!=', 'disbursed')
          ->where('loan_closed_status', '!=', 'closed')
          ->orderByRaw('created_at DESC');

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
        $leads = $query->paginate(25);

        return view('admin.decision.decision-approved', compact('leads'));
    }

    public function decisionpendingDisbursed(Request $request)
    {
        $query = LoanApplication::with([
            'user',
            'personalDetails', 
            'employmentDetails', 
            'kycDetails', 
            'loanDocument',
            'addressDetails', 
            'bankDetails',
            'loanDisbursal',
            'loanApproval'
        ])->where('loan_disbursal_status', 'pending')
          ->where('admin_approval_status', 'approved')
          ->where('user_acceptance_status', 'accepted')
          ->orderByRaw('created_at DESC');

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
        $leads = $query->paginate(25);

        return view('admin.decision.decision-disbursed', compact('leads'));
    }

    public function decisionDisbursed(Request $request)
    {
        $query = LoanApplication::with([
            'user',
            'personalDetails', 
            'employmentDetails', 
            'kycDetails', 
            'loanDocument',
            'addressDetails', 
            'bankDetails',
            'loanDisbursal',
            'loanApproval'
        ])->where('loan_disbursal_status', 'disbursed')
          ->orderByRaw('created_at DESC');

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
        $leads = $query->paginate(25);

        return view('admin.decision.decision-disbursed', compact('leads'));
    }

    public function decisionRejected(Request $request)
    {
        $query = LoanApplication::with([
            'user',
            'personalDetails', 
            'employmentDetails', 
            'kycDetails', 
            'loanDocument',
            'addressDetails', 
            'bankDetails',
            'loanApproval'
        ])->where('admin_approval_status', 'rejected')
          ->orderByRaw('created_at DESC');

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
        $leads = $query->paginate(25);

        return view('admin.decision.decision-rejected', compact('leads'));
    }

    public function decisionPendingHold(Request $request)
    {
        $query = LoanApplication::with([
            'user',
            'personalDetails', 
            'employmentDetails', 
            'kycDetails', 
            'loanDocument',
            'addressDetails', 
            'bankDetails',
            'loanApproval'
        ])->where('admin_approval_status', 'pending')
          ->orderByRaw('created_at DESC');

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
        $leads = $query->paginate(25);

        return view('admin.decision.decision-pendingHold', compact('leads'));
    }

    public function decisionApprovedNotInterested(Request $request)
    {
        $query = LoanApplication::with([
            'user',
            'personalDetails', 
            'employmentDetails', 
            'kycDetails', 
            'loanDocument',
            'addressDetails', 
            'bankDetails',
            'loanApproval'
        ])->where('admin_approval_status', 'approvednotinterested')
          ->orderByRaw('created_at DESC');

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
        $leads = $query->paginate(25);

        return view('admin.decision.decision-approvedNotInterested', compact('leads'));
    }

    public function decisionClosed(Request $request)
    {
        $query = LoanApplication::with([
            'user',
            'personalDetails', 
            'employmentDetails', 
            'kycDetails', 
            'loanDocument',
            'addressDetails', 
            'bankDetails',
            'collections',
            'loanApproval'
        ])->where('loan_closed_status', 'closed')
          ->orderByRaw('created_at DESC');

        $searchTerm = $request->get('search');
        $dateRange = $request->get('date_range');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

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

        if ($dateRange) {
            $query->whereHas('collections', function ($collectionQuery) use ($dateRange, $fromDate, $toDate) {
                if ($dateRange === 'today') {
                    $collectionQuery->whereDate('collection_date', now()->today());
                } elseif ($dateRange === 'yesterday') {
                    $collectionQuery->whereDate('collection_date', now()->yesterday());
                } elseif ($dateRange === 'last_3_days') {
                    $collectionQuery->whereBetween('collection_date', [now()->subDays(3), now()]);
                } elseif ($dateRange === 'last_7_days') {
                    $collectionQuery->whereBetween('collection_date', [now()->subDays(7), now()]);
                } elseif ($dateRange === 'last_15_days') {
                    $collectionQuery->whereBetween('collection_date', [now()->subDays(15), now()]);
                } elseif ($dateRange === 'current_month') {
                    $collectionQuery->whereBetween('collection_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
                } elseif ($dateRange === 'previous_month') {
                    $collectionQuery->whereBetween('collection_date', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()]);
                } elseif ($dateRange === 'custom' && $fromDate && $toDate) {
                    $collectionQuery->whereBetween('collection_date', [$fromDate, $toDate]);
                }
            });
        }

        if ($request->has('export') && $request->export === 'csv') {
            $query->with(['user', 'loanApproval', 'loanDisbursal', 'collections']);
            $leads = $query->get();

            $csvData = [];

            foreach ($leads as $lead) {

                $loanAmount = $lead->collections->sum('collection_amt');
                
                $csvData[] = [
                    'Customer Name' => $lead->user->firstname . ' ' . $lead->user->lastname,
                    'Customer Mobile' => "'" . $lead->user->mobile,
                    'Loan Application No' => $lead->loan_no,
                    'Loan Amount' => optional($lead->loanApproval)->approval_amount ?? 0,
                    'Repayment Amount' =>  optional($lead->loanApproval)->repayment_amount ?? 0,
                    'Paid Amount' => number_format($loanAmount, 2),
                    'Payment ID' => $lead->collections->pluck('payment_id')->implode(', '),
                    'Closed Date' => $lead->loan_closed_date ? $lead->loan_closed_date : 0,
                ];
            }

            $loanTypeText = $loanType ?? 'all';
            $dateRangeText = $dateRange ?? 'alltime';
            $timestamp = now()->format('Ymd_His');

            $filename = "{$dateRangeText}_{$loanTypeText}_closed_loans_export_{$timestamp}.csv";

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

        $leads = $query->paginate(25);

        return view('admin.decision.decision-closed', compact('leads'));
    }
}
