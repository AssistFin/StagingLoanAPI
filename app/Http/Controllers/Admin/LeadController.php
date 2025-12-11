<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\LoanApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Models\LoanDisbursal;
use App\Models\UtrCollection;
use App\Models\LoanApplication;
use App\Models\CreditBureau;
use App\Models\CashfreeEnachRequestResponse;
use App\Models\LoanDocument;
use App\Models\DigitapBankRequest;
use App\Models\Underwriting;
use App\Models\UnderwritingConfig;
use App\Models\LoanAddressDetails;
use App\Models\LoanBankDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class LeadController extends Controller
{
    public function leadsAll(Request $request)
    {
        ini_set('memory_limit', '4096M');
        $excludedUserIds = ['591','592','593','594','595','697','1003','1680'];
        // Step 1: Fetch all loan_application_ids which have KYC details
        $usersWithKyc = DB::table('loan_kyc_details')
            ->pluck('loan_application_id');

        // Step 2: Get distinct user_ids from loan applications that have KYC details
        $userIdsWithKyc = LoanApplication::whereIn('id', $usersWithKyc)
            ->pluck('user_id')
            ->unique()
            ->toArray();

        // Step 3: Start building the loan applications query
        $query = LoanApplication::with(['user:id,firstname,lastname,mobile','user.utmTracking:id,user_id,utm_source','loanApproval','loanApproval.creditedBy:id,name','personalDetails:id,loan_application_id,employment_type,monthly_income,income_received_in'])
            ->withExists([
                'personalDetails',
                'employmentDetails',
                'loanDocument',
                'addressDetails',
                'bankDetails',
                'bredata'
            ])->whereNotIn('user_id', $excludedUserIds)
            ->orderByDesc('user_id');

        // Step 4: Apply search filter (search by name, email, mobile, loan_no)
        $searchTerm = $request->get('search');
        if ($searchTerm) {
            $query->where(function ($subQuery) use ($searchTerm) {
                $subQuery->whereHas('user', function ($userQuery) use ($searchTerm) {
                    $userQuery->where('firstname', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhere('mobile', 'like', "%{$searchTerm}%");
                })
                ->orWhere('loan_no', 'like', "%{$searchTerm}%");
            });
        }

        // Step 5 & 6: Date Range and Loan Type Filtering
        $dateRange = $request->get('date_range');
        $loanType = $request->get('loan_type');

        if ($loanType === 'complete_app_loan') {
            if ($dateRange) {
                switch ($dateRange) {
                    case 'today':
                        $query->whereHas('bankDetails', function ($q) {
                            $q->whereDate('created_at', Carbon::today());
                        });
                        break;
                    case 'yesterday':
                        $query->whereHas('bankDetails', function ($q) {
                            $q->whereDate('created_at', Carbon::yesterday());
                        });
                        break;
                    case 'last_3_days':
                        $query->whereHas('bankDetails', function ($q) {
                            $q->whereBetween('created_at', [now()->subDays(3), now()]);
                        });
                        break;
                    case 'last_7_days':
                        $query->whereHas('bankDetails', function ($q) {
                            $q->whereBetween('created_at', [now()->subDays(7), now()]);
                        });
                        break;
                    case 'last_15_days':
                        $query->whereHas('bankDetails', function ($q) {
                            $q->whereBetween('created_at', [now()->subDays(15), now()]);
                        });
                        break;
                    case 'current_month':
                        $query->whereHas('bankDetails', function ($q) {
                            $q->whereBetween('created_at', [
                                Carbon::now()->startOfMonth(),
                                Carbon::now()->endOfMonth()
                            ]);
                        });
                        break;
                    case 'previous_month':
                        $query->whereHas('bankDetails', function ($q) {
                            $q->whereBetween('created_at', [
                                Carbon::now()->subMonth()->startOfMonth(),
                                Carbon::now()->subMonth()->endOfMonth()
                            ]);
                        });
                        break;
                    case 'custom':
                        if ($request->get('from_date') && $request->get('to_date')) {
                            $query->whereHas('bankDetails', function ($q) use ($request) {
                                $q->whereBetween('created_at', [$request->get('from_date'), $request->get('to_date')]);
                            });
                        }
                        break;
                }
            }
        } else if ($loanType === 'approved_loan') {
            $query->where('admin_approval_status', 'approved');
            if ($dateRange) {
                switch ($dateRange) {
                    case 'today':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereDate('updated_at', Carbon::today());
                        });
                        break;
                    case 'yesterday':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereDate('updated_at', Carbon::yesterday());
                        });
                        break;
                    case 'last_3_days':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereBetween('updated_at', [now()->subDays(3), now()]);
                        });
                        break;
                    case 'last_7_days':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereBetween('updated_at', [now()->subDays(7), now()]);
                        });
                        break;
                    case 'last_15_days':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereBetween('updated_at', [now()->subDays(15), now()]);
                        });
                        break;
                    case 'current_month':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereBetween('updated_at', [
                                Carbon::now()->startOfMonth(),
                                Carbon::now()->endOfMonth()
                            ]);
                        });
                        break;
                    case 'previous_month':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereBetween('updated_at', [
                                Carbon::now()->subMonth()->startOfMonth(),
                                Carbon::now()->subMonth()->endOfMonth()
                            ]);
                        });
                        break;
                    case 'custom':
                        if ($request->get('from_date') && $request->get('to_date')) {
                            $query->whereHas('loanApproval', function ($q) use ($request) {
                                $q->whereBetween('updated_at', [$request->get('from_date'), $request->get('to_date')]);
                            });
                        }
                        break;
                }
            }
        } else if ($loanType === 'approved_not_ineterested_loan') {
            $query->where('admin_approval_status', 'approvednotinterested');
            if ($dateRange) {
                switch ($dateRange) {
                    case 'today':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereDate('updated_at', Carbon::today());
                        });
                        break;
                    case 'yesterday':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereDate('updated_at', Carbon::yesterday());
                        });
                        break;
                    case 'last_3_days':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereBetween('updated_at', [now()->subDays(3), now()]);
                        });
                        break;
                    case 'last_7_days':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereBetween('updated_at', [now()->subDays(7), now()]);
                        });
                        break;
                    case 'last_15_days':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereBetween('updated_at', [now()->subDays(15), now()]);
                        });
                        break;
                    case 'current_month':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereBetween('updated_at', [
                                Carbon::now()->startOfMonth(),
                                Carbon::now()->endOfMonth()
                            ]);
                        });
                        break;
                    case 'previous_month':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereBetween('updated_at', [
                                Carbon::now()->subMonth()->startOfMonth(),
                                Carbon::now()->subMonth()->endOfMonth()
                            ]);
                        });
                        break;
                    case 'custom':
                        if ($request->get('from_date') && $request->get('to_date')) {
                            $query->whereHas('loanApproval', function ($q) use ($request) {
                                $q->whereBetween('updated_at', [$request->get('from_date'), $request->get('to_date')]);
                            });
                        }
                        break;
                }
            }
        } else if ($loanType === 'rejected_loan') {
            $query->where('admin_approval_status', 'rejected');
            if ($dateRange) {
                switch ($dateRange) {
                    case 'today':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereDate('updated_at', Carbon::today());
                        });
                        break;
                    case 'yesterday':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereDate('updated_at', Carbon::yesterday());
                        });
                        break;
                    case 'last_3_days':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereBetween('updated_at', [now()->subDays(3), now()]);
                        });
                        break;
                    case 'last_7_days':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereBetween('updated_at', [now()->subDays(7), now()]);
                        });
                        break;
                    case 'last_15_days':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereBetween('updated_at', [now()->subDays(15), now()]);
                        });
                        break;
                    case 'current_month':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereBetween('updated_at', [
                                Carbon::now()->startOfMonth(),
                                Carbon::now()->endOfMonth()
                            ]);
                        });
                        break;
                    case 'previous_month':
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereBetween('updated_at', [
                                Carbon::now()->subMonth()->startOfMonth(),
                                Carbon::now()->subMonth()->endOfMonth()
                            ]);
                        });
                        break;
                    case 'custom':
                        if ($request->get('from_date') && $request->get('to_date')) {
                            $query->whereHas('loanApproval', function ($q) use ($request) {
                                $q->whereBetween('updated_at', [$request->get('from_date'), $request->get('to_date')]);
                            });
                        }
                        break;
                }
            }
        } else if ($loanType === 'disbursed_loan') {
            $query->where('loan_disbursal_status', 'disbursed');
            if ($dateRange) {
                switch ($dateRange) {
                    case 'today':
                        $query->whereHas('loanDisbursal', function ($q) {
                            $q->whereDate('created_at', Carbon::today());
                        });
                        break;
                    case 'yesterday':
                        $query->whereHas('loanDisbursal', function ($q) {
                            $q->whereDate('created_at', Carbon::yesterday());
                        });
                        break;
                    case 'last_3_days':
                        $query->whereHas('loanDisbursal', function ($q) {
                            $q->whereBetween('created_at', [now()->subDays(3), now()]);
                        });
                        break;
                    case 'last_7_days':
                        $query->whereHas('loanDisbursal', function ($q) {
                            $q->whereBetween('created_at', [now()->subDays(7), now()]);
                        });
                        break;
                    case 'last_15_days':
                        $query->whereHas('loanDisbursal', function ($q) {
                            $q->whereBetween('created_at', [now()->subDays(15), now()]);
                        });
                        break;
                    case 'current_month':
                        $query->whereHas('loanDisbursal', function ($q) {
                            $q->whereBetween('created_at', [
                                Carbon::now()->startOfMonth(),
                                Carbon::now()->endOfMonth()
                            ]);
                        });
                        break;
                    case 'previous_month':
                        $query->whereHas('loanDisbursal', function ($q) {
                            $q->whereBetween('created_at', [
                                Carbon::now()->subMonth()->startOfMonth(),
                                Carbon::now()->subMonth()->endOfMonth()
                            ]);
                        });
                        break;
                    case 'custom':
                        if ($request->from_date && $request->to_date) {
                            $query->whereHas('loanDisbursal', function ($q) use ($request) {
                                $q->whereBetween('created_at', [$request->from_date, $request->to_date]);
                            });
                        }
                        break;
                }
            }
        } else if ($loanType === 'closed_loan') {
            $query->where('loan_closed_status', 'closed');
            if ($dateRange) {
                switch ($dateRange) {
                    case 'today':
                        $query->whereDate('loan_applications.loan_closed_date', Carbon::today());
                        break;
                    case 'yesterday':
                        $query->whereDate('loan_applications.loan_closed_date', Carbon::yesterday());
                        break;
                    case 'last_3_days':
                        $query->whereBetween('loan_applications.loan_closed_date', [now()->subDays(3), now()]);
                        break;
                    case 'last_7_days':
                        $query->whereBetween('loan_applications.loan_closed_date', [now()->subDays(7), now()]);
                        break;
                    case 'last_15_days':
                        $query->whereBetween('loan_applications.loan_closed_date', [now()->subDays(15), now()]);
                        break;
                    case 'current_month':
                        $query->whereBetween('loan_applications.loan_closed_date', [
                                Carbon::now()->startOfMonth(),
                                Carbon::now()->endOfMonth()
                            ]);
                        break;
                    case 'previous_month':
                        $query->whereBetween('loan_applications.loan_closed_date', [
                                Carbon::now()->subMonth()->startOfMonth(),
                                Carbon::now()->subMonth()->endOfMonth()
                            ]);
                        break;
                    case 'custom':
                        if ($request->from_date && $request->to_date) {
                            $query->whereBetween('loan_applications.loan_closed_date', [$request->from_date, $request->to_date]);
                        }
                        break;
                }
            }
        } else {
            if ($dateRange) {
                switch ($dateRange) {
                    case 'today':
                        $query->whereDate('loan_applications.created_at', Carbon::today());
                        break;
                    case 'yesterday':
                        $query->whereDate('loan_applications.created_at', Carbon::yesterday());
                        break;
                    case 'last_3_days':
                        $query->whereBetween('loan_applications.created_at', [now()->subDays(2), now()]);
                        break;
                    case 'last_7_days':
                        $query->whereBetween('loan_applications.created_at', [now()->subDays(6), now()]);
                        break;
                    case 'last_15_days':
                        $query->whereBetween('loan_applications.created_at', [now()->subDays(14), now()]);
                        break;
                    case 'current_month':
                        $query->whereBetween('loan_applications.created_at', [
                                Carbon::now()->startOfMonth(),
                                Carbon::now()->endOfMonth()
                            ]);
                        break;
                    case 'previous_month':
                        $query->whereBetween('loan_applications.created_at', [
                                Carbon::now()->subMonth()->startOfMonth(),
                                Carbon::now()->subMonth()->endOfMonth()
                            ]);
                        break;
                    case 'custom':
                        if ($request->from_date && $request->to_date) {
                            $query->whereBetween('loan_applications.created_at', [$request->from_date, $request->to_date]);
                        }
                        break;
                }
            }

            // Apply other loan type filters (rest of your Step 6)
            if ($loanType) {
                switch ($loanType) {
                    case 'active_loan':
                        $query->where('loan_closed_status', 'pending')->where('loan_disbursal_status', 'disbursed');
                        break;
                    case 'overdue_loan':
                        $query->where('loan_closed_status', 'pending')->where('loan_disbursal_status', 'disbursed');
                        $query->whereHas('loanApproval', function ($q) {
                            $q->whereDate('repay_date', '<', now());
                        })->whereHas('loanDisbursal');
                        break;
                }
            }
        }

        // Step 7: Customer Type Filtering
        $customerType = $request->get('customer_type');
        if ($customerType) {
            if ($customerType === 'new_cust') {
                $query->has('user.loanApplications', '=', 1); // Only 1 loan
            } elseif ($customerType === 'exist_cust') {
                $query->has('user.loanApplications', '>', 1); // Multiple loans
            }
        }

        if ($request->has('export') && $request->export === 'csv') {

            $query->with(['user', 'loanApproval', 'loanDisbursal', 'collections']);

            $loanTypeText = $loanType ?? 'all';
            $dateRangeText = $dateRange ?? 'alltime';
            $timestamp = now()->format('Ymd_His');
            $filename = "{$dateRangeText}_{$loanTypeText}_leads_export_{$timestamp}.csv";

            $tempPath = storage_path("app/{$filename}");
            $file = fopen($tempPath, 'w');

            $headerWritten = false;

            $query->chunk(2000, function ($leads) use (&$headerWritten, $file, $loanType) {

                foreach ($leads as $lead) {

                    // == SAME CSV DATA MAPPING YOU ALREADY HAVE ==
                    // NOTHING IS REMOVED, ONLY MOVED HERE

                    $loanAmount = $lead->loan_amount;
                    $disbursedDate = $lead->created_at;

                    switch ($loanType) {
                        case 'approved_loan':
                            $loanAmount = optional($lead->loanApproval)->approval_amount ?? 0;
                            $disbursedDate = optional($lead->loanApproval)->approval_date ?? 0;
                            $dateHead = 'Approved Date';
                            $purpose_head = 'Purpose Of Loan';
                            $purpose = $lead->purpose_of_loan;
                            break;
                        case 'rejected_loan':
                            $loanAmount = optional($lead->loanApproval)->approval_amount ?? 0;
                            $disbursedDate = optional($lead->loanApproval)->approval_date ?? 0;
                            $dateHead = 'Rejected Date';
                            $purpose_head = 'Rejection Remark';
                            $purpose = optional($lead->loanApproval)->final_remark ?? 0;
                            break;
                        case 'disbursed_loan':
                            $loanAmount = optional($lead->loanApproval)->approval_amount ?? 0;
                            $disbursedDate = optional($lead->loanDisbursal)->disbursal_date ?? 0;
                            $dateHead = 'Disbursed Date';
                            $purpose_head = 'Purpose Of Loan';
                            $purpose = $lead->purpose_of_loan;
                            break;
                        case 'closed_loan':
                            $loanAmount = $lead->collections->sum('collection_amt');
                            $disbursedDate = $lead->loan_closed_date ? $lead->loan_closed_date : 0;
                            $dateHead = 'Closed Date';
                            $purpose_head = 'Purpose Of Loan';
                            $purpose = $lead->purpose_of_loan;
                            break;
                        default:
                            $dateHead = 'Loan Date';
                            $purpose_head = 'Purpose Of Loan';
                            $purpose = $lead->purpose_of_loan;
                    }

                    if ($loanType == 'disbursed_loan') {
                        $row = [
                            'Loan Application No' => $lead->loan_no,
                            'Customer Name' => $lead->user->firstname . ' ' . $lead->user->lastname,
                            'Customer Mobile' => "'" . $lead->user->mobile,
                            'Email Id' => $lead->user->email,
                            $purpose_head => $purpose,
                            'Loan Amount' => number_format($loanAmount, 2),
                            'Disbursed Amount' => optional($lead->loanDisbursal)->disbursal_amount ?? 0,
                            $dateHead => $disbursedDate,
                            'Repayment Date' => optional($lead->loanApproval)->repay_date ?? 0,
                            'Repayment Amount' => optional($lead->loanApproval)->repayment_amount ?? 0,
                            'source' => $lead->user->utmTracking->utm_source ?? '',
                            'Monthly Income' => $lead->personalDetails->monthly_income ?? '',
                            'UTR No' => optional($lead->loanDisbursal)->utr_no ?? '',
                            'CPA' => optional(optional($lead->loanApproval)->creditedBy)->name ?? 'N/A',
                        ];
                    } else {
                        $row = [
                            'Customer Name' => $lead->user->firstname . ' ' . $lead->user->lastname,
                            'Customer Mobile' => "'" . $lead->user->mobile,
                            'Loan Application No' => $lead->loan_no,
                            'Loan Amount' => number_format($loanAmount, 2),
                            'Disbursed Amount' => optional($lead->loanDisbursal)->disbursal_amount ?? 0,
                            $dateHead => $disbursedDate,
                            $purpose_head => $purpose,
                            'source' => $lead->user->utmTracking->utm_source ?? '',
                            'Monthly Income' => $lead->personalDetails->monthly_income ?? '',
                            'UTR No' => optional($lead->loanDisbursal)->utr_no ?? '',
                            'Approved By' => optional(optional($lead->loanApproval)->creditedBy)->name ?? 'N/A',
                        ];
                    }

                    if (!$headerWritten) {
                        fputcsv($file, array_keys($row));
                        $headerWritten = true;
                    }

                    fputcsv($file, $row);
                }
            });

            fclose($file);

            // ZIP if file is too large
            if (filesize($tempPath) > 20 * 1024 * 1024) {   // 20MB
                $zipName = "{$filename}.zip";
                $zipPath = storage_path("app/{$zipName}");
                $zip = new ZipArchive;
                $zip->open($zipPath, ZipArchive::CREATE);
                $zip->addFile($tempPath, $filename);
                $zip->close();
                unlink($tempPath);
                return response()->download($zipPath)->deleteFileAfterSend(true);
            }

            return response()->download($tempPath)->deleteFileAfterSend(true);
        }

        // Determine totals based on loan type
        if ($loanType === 'disbursed_loan') {
            // Get IDs of matched loan applications
            $loanAppIds = (clone $query)->pluck('id');

            // Fetch totals from loan_disbursal table
            $totalAmount = DB::table('loan_approvals')
                ->whereIn('loan_application_id', $loanAppIds)
                ->sum('approval_amount');

            $totalRecords = DB::table('loan_disbursals')
                ->whereIn('loan_application_id', $loanAppIds)
                ->count();

        } elseif ($loanType === 'approved_loan') {
            // Get IDs of matched loan applications
            $loanAppIds = (clone $query)->pluck('id');

            // Fetch totals from loan_approval table
            $totalAmount = DB::table('loan_approvals')
                ->whereIn('loan_application_id', $loanAppIds)
                ->sum('approval_amount');

            $totalRecords = DB::table('loan_approvals')
                ->whereIn('loan_application_id', $loanAppIds)
                ->where('status', 1)
                ->count();

        } elseif ($loanType === 'rejected_loan') {
            // Get IDs of matched loan applications
            $loanAppIds = (clone $query)->pluck('id');

            // Fetch totals from loan_approval table
            $totalAmount = DB::table('loan_approvals')
                ->whereIn('loan_application_id', $loanAppIds)
                ->sum('approval_amount');

            $totalRecords = DB::table('loan_approvals')
                ->whereIn('loan_application_id', $loanAppIds)
                ->where('status', 2)
                ->count();

        } elseif ($loanType === 'complete_app_loan') {
            // Get IDs of matched loan applications
            $loanAppIds = (clone $query)->pluck('id');
            $state = ['loanstatus','noteligible','viewloan','loandisbursal'];

            // Fetch totals from loan_approval table
            $totalAmount = DB::table('loan_applications')
                ->whereIn('id', $loanAppIds)
                ->whereIn('current_step',$state)
                ->sum('loan_amount');

            $totalRecords = DB::table('loan_applications')
                ->whereIn('id', $loanAppIds)
                ->whereIn('current_step',$state)
                ->count();

        } elseif ($loanType === 'closed_loan') {
            $loanAppIds = (clone $query)
                ->where('loan_closed_status', 'closed')
                ->pluck('id');

            // Fetch total amount from utr_collections (summing all payments)
            $totalAmount = DB::table('utr_collections')
                ->whereIn('loan_application_id', $loanAppIds)
                ->sum('collection_amt');

            // Count unique loan application IDs (one per closed loan)
            $totalRecords = DB::table('utr_collections')
                ->whereIn('loan_application_id', $loanAppIds)
                ->select('loan_application_id')
                ->distinct()
                ->count();

        } elseif ($loanType === 'active_loan') {
            // Get IDs of matched loan applications
            $loanAppIds = (clone $query)
                ->where('loan_closed_status', 'pending')
                ->where('loan_disbursal_status', 'disbursed')
                ->pluck('id');

            // Fetch totals from loan_approval table
            $totalAmount = DB::table('loan_approvals')
                ->whereIn('loan_application_id', $loanAppIds)
                ->sum('repayment_amount');

            $totalRecords = DB::table('loan_approvals')
                ->whereIn('loan_application_id', $loanAppIds)
                ->count();

        } elseif ($loanType === 'overdue_loan') {
            // Get IDs of matched loan applications
            $today = Carbon::today()->toDateString();
            $loanAppIds = (clone $query)
                ->where('loan_closed_status', 'pending')
                ->where('loan_disbursal_status', 'disbursed')
                ->pluck('id');

            $loanApplicationsWithDues = DB::table('loan_applications as la')
                ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
                ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
                ->leftJoin(DB::raw('(SELECT loan_application_id, SUM(collection_amt) as total_paid FROM utr_collections GROUP BY loan_application_id) as uc'), 'uc.loan_application_id', '=', 'la.id')
                ->select([
                    'lap.approval_amount',
                    DB::raw('
                        (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) +
                        ((lap.approval_amount * lap.roi / 100 ) * DATEDIFF("' . $today . '", ld.created_at)) +
                        IF(DATEDIFF("' . $today . '", lap.repay_date) > 0,
                            (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date),
                            0
                        ) as total_dues')
                ])->whereIn('la.id', $loanAppIds)
                ->get();

            $totalAmount = $loanApplicationsWithDues->sum('total_dues');
            
            $totalRecords = DB::table('loan_applications as la')
                ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
                ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
                ->leftJoin(DB::raw('(SELECT loan_application_id, SUM(collection_amt) as total_paid FROM utr_collections GROUP BY loan_application_id) as uc'), 'uc.loan_application_id', '=', 'la.id')
                ->select([
                    'lap.approval_amount',
                    DB::raw('
                        (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) +
                        ((lap.approval_amount * lap.roi / 100 ) * DATEDIFF("' . $today . '", ld.created_at)) +
                        IF(DATEDIFF("' . $today . '", lap.repay_date) > 0,
                            (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date),
                            0
                        ) as total_dues')
                ])->whereIn('la.id', $loanAppIds)
                ->count();
        
            } else {
            // Default totals from loan_application table
            //$totalAmount = (clone $query)->sum('loan_amount');
            $totalAmount = 0;
            $totalRecords = (clone $query)->count();
        }
        // Step 9: Default - Return paginated leads to blade
        //$sql = vsprintf(str_replace('?', "'%s'", $query->toSql()), $query->getBindings());
        //dd($sql);

        $totalRecordsQuery = clone $query;
        $totalRecords = $totalRecordsQuery->count();

        $leads = $query->paginate(25);

        return view('admin.leads.leads-all', compact('leads', 'usersWithKyc', 'userIdsWithKyc','totalAmount', 'totalRecords'));
    }

    public function leadsWBS(Request $request){
        ini_set('memory_limit', '2048M');

       $query = LoanApplication::with([
            'user',
            'personalDetails', 
            'employmentDetails', 
            'kycDetails', 
            'loanDocument',
            'addressDetails',
            'bankDetails'
        ])->where('admin_approval_status', 'pending')
        ->orderByRaw('created_at DESC');

        $searchTerm = $request->get('search');
        $dateRange = $request->get('date_range');
        $missingInfo = $request->get('missing_info');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        // Filter by Missing Information
        if ($missingInfo) {
            if ($missingInfo === 'kyc_details') {
                $query->whereDoesntHave('kycDetails');
            } elseif ($missingInfo === 'selfie_docs') {
                $query->whereHas('kycDetails');
                $query->whereDoesntHave('loanDocument');
            } elseif ($missingInfo === 'address_details') {
                $query->whereHas('kycDetails');
                $query->whereHas('loanDocument');
                $query->whereDoesntHave('addressDetails');
            } elseif ($missingInfo === 'employment_details') {
                $query->whereHas('kycDetails');
                $query->whereHas('loanDocument');
                $query->whereHas('addressDetails');
                $query->whereDoesntHave('employmentDetails');
            } elseif ($missingInfo === 'bank_details') {
                $query->whereHas('kycDetails');
                $query->whereHas('loanDocument');
                $query->whereHas('addressDetails');
                $query->whereHas('employmentDetails');
                $query->whereDoesntHave('bankDetails');
            }
        }

        // Filter by Date Range
        if ($dateRange) {
            if ($dateRange === 'today') {
                $query->whereDate('loan_applications.created_at', now()->today());
            } elseif ($dateRange === 'yesterday') {
                $query->whereDate('loan_applications.created_at', now()->yesterday());
            } elseif ($dateRange === 'last_3_days') {
                $query->whereBetween('loan_applications.created_at', [now()->subDays(3), now()]);
            } elseif ($dateRange === 'last_7_days') {
                $query->whereBetween('loan_applications.created_at', [now()->subDays(7), now()]);
            } elseif ($dateRange === 'last_15_days') {
                $query->whereBetween('loan_applications.created_at', [now()->subDays(15), now()]);
            } elseif ($dateRange === 'current_month') {
                $query->whereBetween('loan_applications.created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
            } elseif ($dateRange === 'previous_month') {
                $query->whereBetween('loan_applications.created_at', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()]);
            } elseif ($dateRange === 'custom' && $fromDate && $toDate) {
                $query->whereBetween('loan_applications.created_at', [$fromDate, $toDate]);
            }
        }

        // Search functionality
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

        // Clone the query to get the total count before pagination
        $totalRecordsQuery = clone $query;
        $totalRecords = $totalRecordsQuery->count();

        // Modify the main query for existing users (those with KYC details)
        // $query->orWhere(function ($q) {
        //     $q->with(['bankDetails'])
        //     ->without(['personalDetails', 'employmentDetails', 'loanDocument', 'addressDetails', 'kycDetails'])
        //     ->where('admin_approval_status', 'pending'); // Ensure pending status for existing users as well
        // })
        // ->orderByRaw('loan_applications.created_at DESC'); // Keep the order

        if ($request->has('export') && $request->export === 'csv') {
            $leads = $query->get();

            $csvData = [];

            foreach ($leads as $lead) {
                // Default amount value
                $loanAmount = $lead->loan_amount;
                $loandate = $lead->created_at;

                $csvData[] = [
                    'Customer Name' => $lead->user->firstname . ' ' . $lead->user->lastname,
                    'Customer Mobile' => "'" . $lead->user->mobile,
                    'Loan Application No' => $lead->loan_no,
                    'Loan Amount' => number_format($loanAmount, 0),
                    'Apply Date' => $loandate,
                    'Purpose Of Loan' => $lead->purpose_of_loan,
                ];
            }

            $missingInfoText = $missingInfo ?? 'all';
            $dateRangeText = $dateRange ?? 'alltime';
            $timestamp = now()->format('Ymd_His');

            $filename = "{$dateRangeText}_{$missingInfoText}_leads_export_{$timestamp}.csv";

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

        return view('admin.leads.leads-wbs', compact('leads', 'totalRecords'));
    }

    public function leadsBSA(Request $request)
    {
        ini_set('memory_limit', '2048M');
        $usersWithKyc = DB::table('loan_kyc_details')
                ->pluck('loan_application_id');

        $userIdsWithKyc = LoanApplication::whereIn('id', $usersWithKyc)
                ->pluck('user_id')
                ->unique()
                ->toArray();
                
        $searchTerm = $request->get('search');

        $query = LoanApplication::with([
                'user:id,firstname,lastname,mobile,email',
                'user.utmTracking:id,user_id,utm_source',
                'personalDetails:id,loan_application_id,employment_type,monthly_income,income_received_in',
                'kycDetails',
                'loanDocument',
                'addressDetails',
                'employmentDetails',
                'bankDetails',
                'digitapRequest:id,customer_id,status,report_json_data',
            ])
            ->where('admin_approval_status', 'pending')
            ->whereHas('digitapRequest', function ($q) {
                $q->where('status', 'xlsx_report_saved');
            })
            ->where(function ($q) {
                $q->where(function ($q1) {
                    // New user with all details filled in current (pending) loan
                    $q1->whereHas('personalDetails')
                        ->whereHas('kycDetails')
                        ->whereHas('loanDocument')
                        ->whereHas('addressDetails')
                        ->whereHas('employmentDetails')
                        ->whereHas('bankDetails');
                })
                ->orWhereExists(function ($sub) {
                    // Existing user with older closed loan with all details filled
                    $sub->select(DB::raw(1))
                        ->from('loan_applications as la2')
                        ->join('loan_kyc_details as kyc', 'kyc.loan_application_id', '=', 'la2.id')
                        ->join('loan_personal_details as pd', 'pd.loan_application_id', '=', 'la2.id')
                        ->join('loan_documents as doc', 'doc.loan_application_id', '=', 'la2.id')
                        ->join('loan_address_details as addr', 'addr.loan_application_id', '=', 'la2.id')
                        ->join('loan_employment_details as emp', 'emp.loan_application_id', '=', 'la2.id')
                        ->join('loan_bank_details as bank', 'bank.loan_application_id', '=', 'loan_applications.id')
                        ->whereRaw('la2.user_id = loan_applications.user_id')
                        ->whereRaw('la2.id != loan_applications.id')
                        ->where('la2.admin_approval_status', 'approved');
                });
            });

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('user', function ($userQuery) use ($searchTerm) {
                    $userQuery->where('firstname', 'like', "%{$searchTerm}%")
                        ->orWhere('lastname', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhere('mobile', 'like', "%{$searchTerm}%");
                })->orWhere('loan_no', 'like', "%{$searchTerm}%");
            });
        }
        
        // If you want to inspect SQL:
        // dd($query->toSql(), $query->getBindings());

        $totalRecordsQuery = clone $query;
        $totalRecords = $totalRecordsQuery->count();

        if ($request->has('export') && $request->export === 'csv') {
            $leads = $query->get();
            
            $csvData = [];

            foreach ($leads as $lead) {
                // Default amount value
                $loanAmount = $lead->loan_amount;
                $loandate = $lead->created_at;

                $csvData[] = [
                    'Customer Name' => $lead->user->firstname . ' ' . $lead->user->lastname,
                    'Customer Mobile' => "'" . $lead->user->mobile,
                    'Loan Application No' => $lead->loan_no,
                    'Loan Amount' => number_format($loanAmount, 0),
                    'Apply Date' => $loandate,
                    'Employment Type' => !empty($lead->personalDetails->employment_type) ? $lead->personalDetails->employment_type : '',
                    'Montly Income' => !empty($lead->personalDetails->monthly_income) ? $lead->personalDetails->monthly_income : '',
                    'Income Received In' => !empty($lead->personalDetails->income_received_in) ? $lead->personalDetails->income_received_in : '',
                    'Purpose Of Loan' => $lead->purpose_of_loan,
                    'Source' => $lead->user->utmTracking->utm_source ?? '',
                ];
            }
            $timestamp = now()->format('Ymd_His');

            $filename = "bsa_leads_export_{$timestamp}.csv";

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

        $leads = $query->orderBy('created_at','asc')->paginate(25);
        return view('admin.leads.leads-bsa', compact('leads', 'userIdsWithKyc','totalRecords'));
    }

    public function leadsNotInterested(Request $request)
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
        ])->where('admin_approval_status', 'notinterested')
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

    public function leadsRejectFileUpload(Request $request)
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
            ->whereHas('loanApproval', function ($q) {
            $q->where('final_remark', 'rejected by system');
        })->orderByRaw('created_at DESC');

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

        return view('admin.leads.leads-rejectfileupload', compact('leads'));
    }

    public function leadsVerify($id = null)
    {
        ini_set('memory_limit', '2048M');
        $lead = LoanApplication::with([
            'user',
            'personalDetails', 
            'employmentDetails', 
            'kycDetails', 
            'loanDocument',
            'addressDetails', 
            'bankDetails'
        ])->where('id', $id)->first();

        $aadharData = DB::table('aadhaar_data')->where('user_id', $lead->user->id)->first();

        $panData = DB::table('pan_data')->where('user_id', $lead->user->id)->first();

        $loanApproval = LoanApproval::where('loan_application_id', $id)->first();

        $loanDisbursal = LoanDisbursal::where('loan_application_id', $id)->first();
        
        $experianCreditBureau = CreditBureau::where('lead_id', $id)->first();

        $bankDetailsData = LoanBankDetails::where('loan_application_id', $id)->where('account_number', '!=', '')->orderBy('id','desc')->first();

        $cashfreeData = CashfreeEnachRequestResponse::where('subscription_id', $lead->loan_no)->where('reference_id', '!=', '')->orderBy('id','desc')->first();
        $cfreeSubsData = $allcfreeSubData = $allcfreeSubPayReqData = [];
        if(!empty($cashfreeData)){
            $allcfreeSubData = CashfreeEnachRequestResponse::where('subscription_id', $lead->loan_no)->where('reference_id', '!=', '')->get();

            $allcfreeSubPayReqData = DB::table('subscription_payment_requests')->where('subscription_id', 'like', "%{$lead->loan_no}%")->get();

            $cfreeSubsData = DB::table('subscription_payment_requests')->where('subscription_id', $cashfreeData->alt_subscription_id)->first();
        }

        $digitapBankRequestData = DigitapBankRequest::where(['customer_id'=> $id, 'status' => 'xlsx_report_saved'])->orderBy('id','desc')->first();
        //dd($digitapBankRequestData);
        
        $cashfreeExistingActiveData = CashfreeEnachRequestResponse::where('subscription_id', $lead->loan_no)->where('reference_id', '!=', '')->where('status', 'ACTIVE')->orderBy('id','desc')->get();
        //dd($cashfreeExistingActiveData);
        if(!empty($cashfreeExistingActiveData)){
            foreach($cashfreeExistingActiveData as $key => $value){
                $new_alt_subscription_id = $value['alt_subscription_id'];
                $response_data = json_decode($value['response_data'], true);
                $status = $response_data['authorization_details']['authorization_status'] ?? '';
                $bank_account_no = $response_data['authorization_details']['payment_method']['enach']['account_number'] ?? '';

                if($status == 'ACTIVE' && $lead->bankDetails->account_number == $bank_account_no){
                    $cashfreeData = CashfreeEnachRequestResponse::where('alt_subscription_id', $new_alt_subscription_id)->first();
                    //dd($cashfreeData);
                    break;
                }
            }
        }
        
        $hasPreviousClosedLoan = LoanApplication::where('user_id', $lead->user->id)
        ->where('id', '!=', $lead->id) // Exclude current loan
        ->where('admin_approval_status', 'approved')
        ->exists();
        
        $selfieDoc = '';
        if($hasPreviousClosedLoan){
            $preloanData = LoanApplication::where('user_id', $lead->user->id)
            ->where('id', '!=', $lead->id) // Exclude current loan
            ->where('admin_approval_status', 'approved')
            ->first();

            $selfieDoc = LoanDocument::where('loan_application_id', $preloanData->id)->first();
        }

        $loanUtrCollections = UtrCollection::select(
            'utr_collections.*',
            'loan_applications.loan_no',
            'loan_disbursals.loan_disbursal_number',
            'loan_approvals.repay_date',
            DB::raw("CONCAT(users.firstname, ' ', users.lastname) as user_name")
        )
        ->join('loan_applications', 'loan_applications.id', '=', 'utr_collections.loan_application_id')
        ->join('loan_disbursals', 'loan_disbursals.loan_application_id', '=', 'utr_collections.loan_application_id')
        ->join('loan_approvals', 'loan_approvals.loan_application_id', '=', 'utr_collections.loan_application_id')
        ->join('users', 'users.id', '=', 'utr_collections.user_id')
        ->where('users.id', $lead->user->id)
        ->where('utr_collections.loan_application_id', $id)
        ->orWhere('utr_collections.user_id', $lead->user->id)
        ->orderByRaw('utr_collections.created_at DESC')
        ->get();
        
        $loans = []; $paymentLink = '';
        
        //BOC for check current dues of customer
        if(!empty($loanApproval) && !empty($loanDisbursal)){
            $today = Carbon::today()->toDateString();

            $loans = DB::table('loan_applications as la')
                    ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
                    ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
                    ->leftJoin(DB::raw('(
                        SELECT 
                            loan_application_id,
                            SUM(principal) AS total_principal_paid,
                            SUM(interest) AS total_interest_paid,
                            SUM(penal) AS total_penal_paid,
                            SUM(discount_interest) AS total_interest_discount,
                            SUM(discount_penal) AS total_penal_discount,
                            MAX(created_at) AS last_payment_date
                        FROM utr_collections
                        GROUP BY loan_application_id
                    ) as uc'), 'uc.loan_application_id', '=', 'la.id')
                    ->select([
                        'la.loan_no',
                        'ld.loan_disbursal_number',
                        'lap.approval_amount',

                        // Remaining principal
                        DB::raw('(lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) as remaining_principal'),

                        // Days since disbursal
                        DB::raw("DATEDIFF('$today', ld.created_at) as days_since_disbursal"),

                        DB::raw("DATEDIFF('$today', lap.repay_date) as days_after_due"),

                        // ---------------- INTEREST CALCULATION ----------------
                        DB::raw('(
                            ((lap.approval_amount * lap.roi / 100)
                                * DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), ld.created_at)
                            )
                            +
                            (
                                 ((lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) * lap.roi / 100)
                                * GREATEST(DATEDIFF("' . $today . '", IFNULL(uc.last_payment_date, "' . $today . '")), 0)
                            )
                            - IFNULL(uc.total_interest_paid, 0)
                            - IFNULL(uc.total_interest_discount, 0)
                        ) as interest'),

                        // ---------------- PENAL CALCULATION ----------------
                        DB::raw('(
                            (
                                IF(
                                    DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), lap.repay_date) > 0,
                                    lap.approval_amount * 0.0025 * DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), lap.repay_date),
                                    0
                                )
                            )
                            +
                            (
                                IF(
                                    uc.last_payment_date IS NOT NULL AND DATEDIFF("' . $today . '", uc.last_payment_date) > 0 AND DATEDIFF("' . $today . '", lap.repay_date) > 0,
                                    (lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) * 0.0025 * DATEDIFF("' . $today . '", uc.last_payment_date),
                                    0
                                )
                            ) - IFNULL(uc.total_penal_paid, 0) - IFNULL(uc.total_penal_discount, 0)
                        ) as penal_interest'),

                        // ---------------- TOTAL DUES ----------------
                        DB::raw('(
                            (lap.approval_amount - IFNULL(uc.total_principal_paid, 0))
                            + (
                                (
                                    (lap.approval_amount * lap.roi / 100)
                                    * DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), ld.created_at)
                                )
                                +
                                (
                                    ((lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) * lap.roi / 100)
                                    * GREATEST(DATEDIFF("' . $today . '", IFNULL(uc.last_payment_date, "' . $today . '")), 0)
                                )
                                - IFNULL(uc.total_interest_paid, 0)
                                - IFNULL(uc.total_interest_discount, 0)
                            ) + (
                                IF(
                                    DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), lap.repay_date) > 0,
                                    lap.approval_amount * 0.0025 * DATEDIFF(LEAST(IFNULL(uc.last_payment_date, "' . $today . '"), "' . $today . '"), lap.repay_date),
                                    0
                                )
                                +
                                IF(
                                    uc.last_payment_date IS NOT NULL AND DATEDIFF("' . $today . '", uc.last_payment_date) > 0 AND DATEDIFF("' . $today . '", lap.repay_date) > 0,
                                    (lap.approval_amount - IFNULL(uc.total_principal_paid, 0)) * 0.0025 * DATEDIFF("' . $today . '", uc.last_payment_date),
                                    0
                                )
                                - IFNULL(uc.total_penal_paid, 0)
                                - IFNULL(uc.total_penal_discount, 0)
                            )
                        ) as total_dues')
                    ])
                    ->where('la.id', $lead->id)
                    ->where('la.loan_closed_status', 'pending')
                    ->first();


            //For payment link generation
            $paymentData = [
                'order_id' => 'LNPAY-' . time() . Str::random(4),
                'amount' => (!empty($loans->total_dues)) ? (int)$loans->total_dues : 0,
                'name' => $lead->user->firstname.' '.$lead->user->lastname,
                'email' => $lead->user->email,
                'mobile' => $lead->user->mobile,
                'loan_application_id' => $id,
            ];

            $paymentLink = config('services.docs.app_url') . '/api/pay/'.base64_encode($lead->id);
            
           /* $cashfreeResult = $this->generateCashfreeUrlFromHistory($paymentData);
           // dd($cashfreeResult);
            if(!empty($cashfreeResult['payment_link'])){
                $paymentLink = $cashfreeResult['payment_link'];
            }else{
                $paymentLink = "No Link Available";
            }*/
        }
        //EOC for check current dues of customer
        
        return view('admin.leads.leads-verify', compact('lead', 'loanApproval', 'loanDisbursal', 'loanUtrCollections', 'aadharData', 'panData', 'hasPreviousClosedLoan', 'loans', 'paymentLink', 'experianCreditBureau','cashfreeData', 'selfieDoc','digitapBankRequestData','cfreeSubsData', 'allcfreeSubData', 'allcfreeSubPayReqData', 'bankDetailsData'));
    }

    public function deleteLead($id)
    {
        DB::beginTransaction(); 

        try {
            $lead = LoanApplication::with([
                'personalDetails', 
                'employmentDetails', 
                'kycDetails', 
                'loanDocument',
                'addressDetails', 
                'bankDetails'
            ])->findOrFail($id);

            $lead->personalDetails()->delete();
            $lead->employmentDetails()->delete();
            $lead->kycDetails()->delete();
            $lead->loanDocument()->delete();
            $lead->addressDetails()->delete();
            $lead->bankDetails()->delete();

            $lead->delete();

            DB::commit(); 

            return redirect()->route('admin.leads.all')->with('success', 'Loan lead and related data deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack(); 
            return redirect()->route('admin.leads.all')->with('error', 'Failed to delete the lead. Please try again.');
        }
    }

    private function generateCashfreeUrlFromHistory(array $paymentData)
    {
        try {
            $url = config('services.cashfree.base_url') . '/pg/orders';

            $headers = [
                "Content-Type: application/json",
                "x-api-version: " . config('services.cashfree.api_version'),
                "x-client-id: " . config('services.cashfree.app_id'),
                "x-client-secret: " . config('services.cashfree.secret_key'),
            ];

            $data = [
                'order_id' => $paymentData['order_id'],
                'order_amount' => $paymentData['amount'],
                'order_currency' => 'INR',
                'customer_details' => [
                    'customer_id' => 'cust_' . $paymentData['loan_application_id'],
                    'customer_name' => $paymentData['name'],
                    'customer_email' => $paymentData['email'],
                    'customer_phone' => $paymentData['mobile'],
                ],
                'order_meta' => [
                    'return_url' => config('services.cashfree.app_url') . 'payment/status?payment_reference={order_id}',
                    'notify_url' => config('services.cashfree.app_url') . 'payment/webhook',  
                ],
                'order_note' => 'Loan repayment for account: ' . $paymentData['loan_application_id'],
            ];

            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_TIMEOUT => 30,
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            // Log request and response for debugging
            \Log::info('Cashfree Request Data:', $data);
            \Log::info('Cashfree Response:', ['response' => $response, 'error' => $err]);

            if ($err) {
                return ['error' => $err];
            }

            if (!$response) {
                return ['error' => 'No response from Cashfree'];
            }

            $responseData = json_decode($response, true);

            if (!$responseData || !isset($responseData['payment_link'])) {
                return ['error' => 'Invalid response from Cashfree', 'response' => $responseData];
            }

            return ['payment_link' => $responseData['payment_link']];

        } catch (\Exception $e) {
            \Log::error('Cashfree URL generation error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ['error' => $e->getMessage()];
        }
    }

    public function leadsGeneratereport(Request $request)
    {
        // 1. Load XML Request from Blade
        $xmlRequestBody = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:cbv2">
            <soapenv:Header/>
            <soapenv:Body>
                <urn:process>
                    <urn:in>
                        <INProfileRequest>
                        <Identification>
                        <XMLUser>cpu2altura_uat01</XMLUser>
                            <XMLPassword>Fcs11267#@$</XMLPassword>
                        </Identification>
                        <Application>
                            <EnquiryReason>6</EnquiryReason>
                            <AmountFinanced>0</AmountFinanced>
                            <DurationOfAgreement>0</DurationOfAgreement>
                            <ScoreFlag>3</ScoreFlag>
                            <PSVFlag>0</PSVFlag>
                        </Application>
                        <Applicant>
                            <Surname>Sen</Surname>
                            <FirstName>Tilak</FirstName>
                            <MiddleName/>
                            <GenderCode>2</GenderCode>
                            <IncomeTaxPAN>TFPPS4289C</IncomeTaxPAN>
                            <PassportNumber/>
                            <VoterIdentityCard/>
                            <Driver_License_Number/>
                            <Ration_Card_Number/>
                            <Universal_ID_Number/>
                            <DateOfBirth>19760817</DateOfBirth>
                            <MobilePhone>9295390875</MobilePhone>
                            <EMailId></EMailId>
                        </Applicant>
                        <Address>
                            <FlatNoPlotNoHouseNo>Chinar5 CHS</FlatNoPlotNoHouseNo>
                            <BldgNoSocietyName/>
                            <City>Mumbai</City>
                            <State>27</State>
                            <PinCode>400005</PinCode>
                        </Address>
                        </INProfileRequest>
                    </urn:in>
                </urn:process>
            </soapenv:Body>
            </soapenv:Envelope>';

        // 2. Send Request to API (fake here, you can replace with actual endpoint)
        $response = Http::withHeaders([
            'Content-Type' => 'text/xml; charset=utf-8',
        ])->withBody($xmlRequestBody, 'text/xml')
        ->post('https://connectuat.experian.in/nextgen-ind-pds-webservices-cbv2/endpoint');

        // 3. Extract inner XML from response
        // $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);
        // $body = $xml->children('http://schemas.xmlsoap.org/soap/envelope/')->Body;
        // $innerXml = (string) $body->children('urn:cbv2')->processResponse->out;
        // $jsonData = json_decode(json_encode(simplexml_load_string($innerXml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        try {
            $soapXml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);
            $body = $soapXml->children('http://schemas.xmlsoap.org/soap/envelope/')->Body;
            $rawOut = (string) $body->children('urn:cbv2')->processResponse->out ?? null;

            if (!$rawOut || !str_contains($rawOut, '<?xml')) {
                throw new \Exception('Invalid inner XML in <out> tag');
            }

            $parsed = simplexml_load_string($rawOut, 'SimpleXMLElement', LIBXML_NOCDATA);
            $jsonData = json_decode(json_encode($parsed), true);

        } catch (\Exception $e) {
            \Log::error('XML Parsing Failed: ' . $e->getMessage());
            dd('Failed to parse XML', $response->body());
        }


        // 4. Store raw request & response
        $filename = Str::random(10);
        $timestamp = now()->format('Ymd_His');
        Storage::put("experian/Tilak_$timestamp._.$filename-request.xml", $xmlRequestBody);
        Storage::put("experian/Tilak_$timestamp._.$filename-response.xml", $rawOut);

        // 5. Generate PDF with key data
        $pdf = Pdf::loadView('admin.creditbureau.pdf-template', [ 'data' => $jsonData ]);
        $pdfPath = "experian/reports/Tilak_$timestamp._.$filename.pdf";
        Storage::put($pdfPath, $pdf->output());

        // 6. Save report path in DB if needed...
        // DB::table('experian_reports')->insert([
        //     'user_id' => auth()->id(),
        //     'pan' => $jsonData['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['IncomeTaxPan'] ?? null,
        //     'name' => ($jsonData['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['First_Name'] ?? '') . ' ' . ($jsonData['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['Last_Name'] ?? ''),
        //     'dob' => $jsonData['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['Date_Of_Birth_Applicant'] ?? null,
        //     'mobile' => $jsonData['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['MobilePhoneNumber'] ?? null,
        //     'score' => $jsonData['Current_Application']['Scores']['Score']['Value'] ?? null,
        //     'raw_json' => json_encode($jsonData),
        //     'report_pdf' => $pdfPath,
        //     'created_at' => now(),
        // ]);

        return view('admin.creditbureau.pdf-template', [
            'data' => $jsonData,
            'pdfUrl' => Storage::url($pdfPath)
        ] );
    }

    public function leadsRaisePayReq(Request $request)
    {
        $request->validate([
            'subscription_id' => 'required|string',
            'payment_amount' => 'required|numeric|min:1',
            'schedule_on' => 'required|date',
            'remarks' => 'nullable|string|max:200',
        ]);

        try {
            // Example Cashfree API endpoint
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-api-version' => config('services.cashfree.api_version'),
                'x-client-id' => config('services.cashfree.app_id'),
                'x-client-secret' => config('services.cashfree.secret_key'),
            ])->post(config('services.cashfree.base_url') . '/pg/subscriptions/pay', [
                'subscription_id' => $request->subscription_id,
                "payment_id" => "CF-".$request->subscription_id,
                'payment_amount' => floatval(preg_replace('/[^\d.]/', '', $request->payment_amount)),
                'payment_schedule_date' => Carbon::parse($request->schedule_on)->toIso8601String(),
                'payment_remarks' => $request->remarks,
                "payment_type" => "CHARGE"
            ]);
            
            $data = [
                'subscription_id' => $request->subscription_id,
                'payment_id' => "CF-".$request->subscription_id,
                'payment_amount' => floatval(preg_replace('/[^\d.]/', '', $request->payment_amount)),
                'payment_schedule_date' => $request->schedule_on,
                'payment_remarks' => $request->remarks,
                'payment_type' => 'CHARGE',
                'response_status' => $response->successful() ? 'Success' : 'Failed',
                'response_data' => $response,
                'error_message' => $response->successful() ? null : json_encode($response),
                'status' => $response->successful() ? 'Raised' : 'Pending',
            ];

            DB::table('subscription_payment_requests')->insert($data);

            if ($response->successful()) {
                return response()->json(['status' => true, 'message' => 'Payment request raised successfully.']);
            } else {
                Log::error('Raise Payment Error: ' . json_encode($response->json()));
                return response()->json(['status' => false, 'message' => 'Failed to raise payment.', 'error' => $response->json()]);
            }

        } catch (\Exception $e) {
            Log::error('Raise Payment Exception: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error.']);
        }
    }

    public function leadsCancelRaisePayReq(Request $request)
    {
        $request->validate([
            'subscription_id' => 'required|string',
        ]);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-api-version' => config('services.cashfree.api_version'),
                'x-client-id' => config('services.cashfree.app_id'),
                'x-client-secret' => config('services.cashfree.secret_key'),
            ])->post(config('services.cashfree.base_url') . '/pg/subscriptions/'.$request->subscription_id.'/payments/CF-'.$request->subscription_id.'/manage', [
                'subscription_id' => $request->subscription_id,
                'action' => 'CANCEL',
            ]);

            if ($response->successful()) {
                // Prepare update data
                $updateData = [
                    'status' => 'Cancelled',
                ];

                // Update the existing record (based on subscription_id & payment_id)
                DB::table('subscription_payment_requests')
                    ->where('subscription_id', $request->subscription_id)
                    ->where('payment_id', 'CF-'.$request->subscription_id)
                    ->update($updateData);

                return response()->json(['status' => true, 'message' => 'Payment request cancelled successfully.']);
            } else {
                return response()->json(['status' => false, 'message' => 'Failed to cancel payment.', 'error' => $response->json()]);
            }

        } catch (\Exception $e) {
            Log::error('Cancel Payment Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error.']);
        }
    }

    public function leadsunderwritingstore(Request $request)
    {
        $data = $request->validate([
            'admin_id' => 'required',
            'loan_id' => 'required',
            'user_id' => 'required',
            'salary_month1' => 'required',
            'salary_month2' => 'required',
            'salary_month3' => 'required',
            'average_salary' => 'required',
            'bank_score' => 'required',
            'min_balance' => 'required',
            'avg_balance' => 'required',
            'bounce_1_month' => 'required',
            'bounce_3_month' => 'required',
            'bureau_score' => 'required',
            'dpd_30_1' => 'required',
            'dpd_30_amt1' => 'required',
            'dpd_30_2' => 'required',
            'dpd_30_amt2' => 'required',
            'dpd_90_1' => 'required',
            'dpd_90_amt1' => 'required',
            'dpd_90_2' => 'required',
            'dpd_90_amt2' => 'required',
            'unsecured_loan_experience' => 'required',
            'loan_open_6m' => 'required',
            'loan_closed_6m' => 'required',
            'last2_open_1' => 'required',
            'last2_open_2' => 'required',
            'last2_closed_1' => 'required',
            'last2_closed_2' => 'required',
            'leverage_avg_salary' => 'required',
            'leverage_unsecured_loan' => 'required',
            'status' => 'active',
        ]);

        $saveData = Underwriting::create($data);

        if($saveData){
            $confData = UnderwritingConfig::first();

            $p1 = $p2 = $p3 = $p4 = $p5 = $p6 = $p7 = $p8 = $p9 = false;

            if($request->average_salary >= $confData->avgSalary)
            {
                $p1 = true;
            }

            if($request->min_balance >= $confData->minBalance)
            {
                $p2 = true;
            }

            if($request->bounce_1_month <= $confData->bounceLast1Month)
            {
                $p3 = true;
            }

            if($request->bounce_3_month <= $confData->bounceLast3Month)
            {
                $p4 = true;
            }

            if($request->bureau_score >= $confData->bureauScore)
            {
                $p5 = true;
            }

            if($request->dpd_30_1 <= $confData->dpdLast30Days || $request->dpd_30_amt1 <= $confData->dpdamtLast30Days)
            {
                $p6 = true;
            }

            if($request->dpd_90_1 <= $confData->dpdLast90Days || $request->dpd_90_amt1 <= $confData->dpdamtLast90Days)
            {
                $p7 = true;
            }

            if($request->unsecured_loan_experience == $confData->expUnsecureLoan)
            {
                $p8 = true;
            }

            if($request->leverage_avg_salary *  $confData->leverage <= $request->leverage_unsecured_loan)
            {
                $p9 = true;
            }

            if($p1 && $p2 && $p3 && $p4 && $p5 && $p6 && $p7 && $p8 && $p9)
            {
                $maxClosed = max($request->last2_closed_1, $request->last2_closed_2);
                $twentyFivePercent = $request->average_salary * 0.25;
                $maxamt = $confData->maxLoanAmt;

                $finalMinValue = min($maxClosed, $twentyFivePercent, $maxamt);
                $decision = 'Approved';
            }else{
                $finalMinValue = null;
                $decision = 'Rejected';
            }

            return response()->json([
                'status' => 'success',
                'decision' => $decision,
                'eligible_amount' => $finalMinValue,
                'message' => 'Underwriting saved successfully!',
            ]);

        }
        
        return response()->json(['status' => 'success', 'message' => 'Underwriting saved successfully!']);
    }

    public function leadsUpdateAddress(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|int',
        ]);

        if ($request) {
            // Prepare update data
            $updateData = [
                'relation' => $request->relation,
                'relative_name' => $request->relative_name,
                'contact_number' => $request->contact_number,
            ];

            DB::table('loan_address_details')
                ->where('loan_application_id', $request->lead_id)
                ->update($updateData);

            $address = LoanAddressDetails::find($request->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Address updated successfully!',
                'data' => $address
            ]);
        } else {
            return response()->json([
                'status' => 'Error',
                'message' => 'Address not updated!',
                'data' => ''
            ]);
        }
    }
}
