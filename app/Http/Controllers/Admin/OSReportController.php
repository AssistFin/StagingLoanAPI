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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class OSReportController extends Controller
{
    private $excludedUserIds = ['591','592','593','594','595','697','1003','601','1379','1680'];

    public function index(Request $request)
    {
        $excludedUserIds = ['591','592','593','594','595','697','1003','601','1379','1680'];
        // Step 1: Fetch all loan_application_ids which have KYC details
        $usersWithKyc = DB::table('loan_kyc_details')
            ->pluck('loan_application_id');

        // Step 2: Get distinct user_ids from loan applications that have KYC details
        $userIdsWithKyc = LoanApplication::whereIn('id', $usersWithKyc)
            ->pluck('user_id')
            ->unique()
            ->toArray();

        // Step 3: Start building the loan applications query
        $query = LoanApplication::with(['user:id,firstname,lastname,mobile','loanApproval','loanDisbursal'])
            ->withExists([
                'personalDetails',
                'employmentDetails',
                'loanDocument',
                'addressDetails',
                'bankDetails'
            ])->whereNotIn('user_id', $excludedUserIds)
            ->where('loan_disbursal_status', 'disbursed')
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

        if ($loanType === 'disbursed_loan') {
            $query->where('loan_disbursal_status', 'disbursed');
            if ($dateRange) {
                switch ($dateRange) {
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
                        $query->where('loan_closed_status', 'pending');
                        break;
                    case 'overdue_loan':
                        $query->where('loan_closed_status', 'pending');
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

        // Step 8: Handle CSV Export
        if ($request->has('export') && $request->export === 'csv') {
            $query->with(['user', 'loanApproval', 'loanDisbursal', 'collections']);
            $leads = $query->get();

            $csvData = [];

            foreach ($leads as $index => $lead) {

                    $today = Carbon::today()->toDateString();

                    $loans = DB::table('loan_applications as la')
                    ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
                    ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
                    ->leftJoin(DB::raw('(
                        SELECT 
                            loan_application_id,
                            SUM(collection_amt) as total_paid,
                            SUM(principal) as total_principal_paid,
                            SUM(interest) as total_interest_paid,
                            SUM(penal) as total_penal_paid,
                            MAX(created_at) as last_payment_date
                        FROM utr_collections
                        GROUP BY loan_application_id
                    ) as uc'), 'uc.loan_application_id', '=', 'la.id')
                    ->select([
                        'la.loan_no',
                        'ld.loan_disbursal_number',
                        'lap.approval_amount',
                        'uc.total_paid',
                        'uc.total_principal_paid',
                        'uc.total_interest_paid',
                        'uc.total_penal_paid',
                        'uc.last_payment_date',
                        DB::raw("DATEDIFF('$today', ld.created_at) as days_since_disbursal"),
                        DB::raw("DATEDIFF('$today', lap.repay_date) as days_after_due"),
                        DB::raw('IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount) as remaining_principal'),

                        DB::raw("DATEDIFF('$today', IFNULL(uc.last_payment_date, ld.created_at)) as days_since_payment"),

                        DB::raw('(
                            (IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount) * lap.roi / 100)
                            * DATEDIFF("' . $today . '", IFNULL(uc.last_payment_date, ld.created_at))
                        ) as interest'),

                        DB::raw('
                            IF(DATEDIFF("' . $today . '", lap.repay_date) > 0,
                                (IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date),
                                0
                            ) as penal_interest'),

                        DB::raw('
                            (IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount))
                            + ((IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount) * lap.roi / 100) * DATEDIFF("' . $today . '", IFNULL(uc.last_payment_date, ld.created_at)))
                            + IF(DATEDIFF("' . $today . '", lap.repay_date) > 0,
                                (IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date),
                                0
                            ) as total_dues
                        ')
                    ])
                    ->where('la.id', $lead->id)
                    ->first();

                $loanType = isset($loanType) ? $loanType : 'Active';
                $totalos = (!empty($loans->total_dues) || !empty($loans->total_paid)) ? $loans->total_dues-$loans->total_paid : 0;
                $totalprncos = (!empty($loans->remaining_principal) || !empty($loans->total_principal_paid)) ? $loans->remaining_principal - $loans->total_principal_paid : 0;
                $totalintos = (!empty($loans->interest) || !empty($loans->total_interest_paid)) ? $loans->total_dues - $loans->total_interest_paid : 0;
                $totalpenalos = (!empty($loans->penal_interest) || !empty($loans->total_penal_paid)) ? $loans->penal_interest - $loans->total_penal_paid : 0;
                
                if (!empty($lead->loanApproval->repay_date) && !empty($loans->last_payment_date)) {
                    $repayDate = \Carbon\Carbon::parse($lead->loanApproval->repay_date);
                    $lastPaymentDate = \Carbon\Carbon::parse($loans->last_payment_date);

                    $diffInDays = $repayDate->diffInDays($lastPaymentDate); // Always positive
                    // Or if you want signed difference (last_payment_date - repay_date)
                    $signedDiff = $lastPaymentDate->diffInDays($repayDate, false);
                    $collectionTenure = $signedDiff;
                } else {
                    $collectionTenure = 0;
                }

                $csvData[] = [
                    'Sr No' => $index++,
                    'Disb Month' => !empty($lead->loanDisbursal->disbursal_date) ? date('M-Y',strtotime($lead->loanDisbursal->disbursal_date)) : '',
                    'Collection Month' => !empty($loans->last_payment_date) ? date('M-Y',strtotime($loans->last_payment_date)) : '',
                    'Paid Month' => !empty($loans->last_payment_date) ? date('M-Y',strtotime($loans->last_payment_date)) : '',
                    'Appl. No.' => $lead->loan_no,
                    'Appl. ID' => $lead->id,
                    'Customer Name' => $lead->user->firstname . ' ' . $lead->user->lastname,
                    'Mobile Number' => "'" . $lead->user->mobile,
                    'Email Id' => $lead->user->email,
                    'Disbursed date' => optional($lead->loanDisbursal)->disbursal_date ?? 0,
                    'Loan Amount' => !empty($lead->loanApproval->approval_amount) ? number_format($lead->loanApproval->approval_amount, 2) : '',
                    'Process fee' => !empty($lead->loanApproval->processing_fee_amount) ? $lead->loanApproval->processing_fee_amount : '',
                    'GST' => !empty($lead->loanApproval->gst_amount) ? $lead->loanApproval->gst_amount : '',
                    'Disbursed amount' => !empty($lead->loanDisbursal->disbursal_amount) ? number_format($lead->loanDisbursal->disbursal_amount, 2) : '',
                    'Rate of Interest' => !empty($lead->loanApproval->roi) ? $lead->loanApproval->roi : '',
                    'Tenure' => !empty($lead->loanApproval->loan_tenure_days) ? $lead->loanApproval->loan_tenure_days : '',
                    'Repayment Due Date' => !empty($lead->loanApproval->repay_date) ? $lead->loanApproval->repay_date : '',
                    'Total Due Amount' => (!empty($loans->total_dues)) ? number_format($loans->total_dues, 2) : 0,
                    'Principal Due' => (!empty($loans->remaining_principal)) ? number_format($loans->remaining_principal, 2) : 0,
                    'Interest Due' => (!empty($loans->interest)) ? number_format($loans->interest, 2) : 0,
                    'Penal Due' => (!empty($loans->penal_interest)) ? number_format($loans->penal_interest, 2) : 0,
                    'Paid Date' => (!empty($loans->last_payment_date)) ? date('d-m-Y',strtotime($loans->last_payment_date)) : '',
                    'Total Amt Collected' => !empty($loans->total_paid) ? number_format($loans->total_paid, 2) : 0,
                    'Principal Collect.' => (!empty($loans->total_principal_paid)) ? number_format($loans->total_principal_paid, 2) : 0 ,
                    'Interest Collect.' => (!empty($loans->total_interest_paid)) ? number_format($loans->total_interest_paid, 2) : 0 ,
                    'Penal Collect.' => (!empty($loans->total_penal_paid)) ? number_format($loans->total_penal_paid, 2) : 0 ,
                    'Collection Tenure' => $collectionTenure,
                    'Total O/s' => $totalos,
                    'Principal O/s' => $totalprncos,
                    'Interest O/s' => $totalintos,
                    'Penal O/s' => $totalpenalos,
                    'DPD' => !empty($loans->days_after_due) ? $loans->days_after_due : 0,
                    'Purpose Of Loan' => $lead->purpose_of_loan,
                    'Status' => $loanType === 'disbursed_loan' ? 'Active' : $loanType,
                    'CIBIL Score' => !empty($lead->loanApproval->cibil_score) ? $lead->loanApproval->cibil_score : '',
                    'Monthly Income' => !empty($lead->loanApproval->monthly_income) ? number_format($lead->loanApproval->monthly_income,2) : '',
                    'Employment Type' => !empty($lead->personalDetails->employment_type) ? $lead->personalDetails->employment_type : '',
                    'Organisation Name' => !empty($lead->employmentDetails->company_name) ? $lead->employmentDetails->company_name : '',
                    'Designation' => !empty($lead->employmentDetails->designation) ? $lead->employmentDetails->designation : '',
                    'City' => !empty($lead->addressDetails->city) ? $lead->addressDetails->city : '',
                    'PIN Code' => !empty( $lead->addressDetails->pincode) ? $lead->addressDetails->pincode : '',
                    'Address' => !empty($lead->addressDetails->house_no) ? $lead->addressDetails->house_no.', '.$lead->addressDetails->locality : '',
                ];
            }

            $loanTypeText = $loanType ?? 'all';
            $dateRangeText = $dateRange ?? 'alltime';
            $timestamp = now()->format('Ymd_His');

            $filename = "{$dateRangeText}_{$loanTypeText}_export_{$timestamp}.csv";

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

        return view('admin.osreport.index', compact('leads', 'usersWithKyc', 'userIdsWithKyc','totalAmount', 'totalRecords'));
    
    }

    public function cindex(Request $request)
    {
        $excludedUserIds = ['591','592','593','594','595','697','1003','601','1379','1680'];
        // Step 1: Fetch all loan_application_ids which have KYC details
        $usersWithKyc = DB::table('loan_kyc_details')
            ->pluck('loan_application_id');

        // Step 2: Get distinct user_ids from loan applications that have KYC details
        $userIdsWithKyc = LoanApplication::whereIn('id', $usersWithKyc)
            ->pluck('user_id')
            ->unique()
            ->toArray();

        // Step 3: Start building the loan applications query
        $query = LoanApplication::with(['user:id,firstname,lastname,mobile','loanApproval','loanDisbursal','collections' => function($q) {
                $q->select(
                    'loan_application_id',
                    DB::raw('SUM(collection_amt) as total_paid'),
                    DB::raw('SUM(principal) as total_principal_paid'),
                    DB::raw('SUM(interest) as total_interest_paid'),
                    DB::raw('SUM(penal) as total_penal_paid'),
                    DB::raw('MAX(created_at) as last_payment_date')
                )->groupBy('loan_application_id');
            }])
            ->withExists([
                'personalDetails',
                'employmentDetails',
                'loanDocument',
                'addressDetails',
                'bankDetails'
            ])->whereNotIn('user_id', $excludedUserIds)
            ->where('loan_disbursal_status', 'disbursed')
            ->whereHas('collections', function ($q) {
                $q->where('collection_amt', '>', 0);
            })
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

        if ($dateRange) {
            switch ($dateRange) {
                case 'today':
                    $query->whereHas('collections', function ($q) {
                        $q->whereDate('created_at', Carbon::today());
                    });
                    break;
                case 'yesterday':
                    $query->whereHas('collections', function ($q) {
                        $q->whereDate('collection_date', Carbon::yesterday());
                    });
                    break;
                case 'last_3_days':
                    $query->whereHas('collections', function ($q) {
                        $q->whereBetween('collection_date', [now()->subDays(3), now()]);
                    });
                    break;
                case 'last_7_days':
                    $query->whereHas('collections', function ($q) {
                        $q->whereBetween('collection_date', [now()->subDays(7), now()]);
                    });
                    break;
                case 'last_15_days':
                    $query->whereHas('collections', function ($q) {
                        $q->whereBetween('collection_date', [now()->subDays(15), now()]);
                    });
                    break;
                case 'current_month':
                    $query->whereHas('collections', function ($q) {
                        $q->whereBetween('collection_date', [
                            Carbon::now()->startOfMonth(),
                            Carbon::now()->endOfMonth()
                        ]);
                    });
                    break;
                case 'previous_month':
                    $query->whereHas('collections', function ($q) {
                        $q->whereBetween('collection_date', [
                            Carbon::now()->subMonth()->startOfMonth(),
                            Carbon::now()->subMonth()->endOfMonth()
                        ]);
                    });
                    break;
                case 'custom':
                    if ($request->from_date && $request->to_date) {
                        $query->whereHas('collections', function ($q) use ($request) {
                            $q->whereBetween('collection_date', [$request->from_date, $request->to_date]);
                        });
                    }
                    break;
            }
        }

        // Step 8: Handle CSV Export
        if ($request->has('export') && $request->export === 'csv') {
            $query->with(['user', 'loanApproval', 'loanDisbursal', 'collections']);
            $leads = $query->get();

            $csvData = [];

            foreach ($leads as $index => $lead) {

                    $today = Carbon::today()->toDateString();

                    $loans = DB::table('loan_applications as la')
                    ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
                    ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
                    ->leftJoin(DB::raw('(
                        SELECT 
                            loan_application_id,
                            SUM(collection_amt) as total_paid,
                            SUM(principal) as total_principal_paid,
                            SUM(interest) as total_interest_paid,
                            SUM(penal) as total_penal_paid,
                            MAX(created_at) as last_payment_date,
                            MAX(status) as ucstatus,
                            discount_principal as dspr,
                            discount_interest as dsit,
                            discount_penal as dspl
                        FROM utr_collections
                        GROUP BY loan_application_id
                    ) as uc'), 'uc.loan_application_id', '=', 'la.id')
                    ->select([
                        'la.loan_no',
                        'ld.loan_disbursal_number',
                        'lap.approval_amount',
                        'uc.total_paid',
                        'uc.total_principal_paid',
                        'uc.total_interest_paid',
                        'uc.total_penal_paid',
                        'uc.last_payment_date',
                        'uc.ucstatus',
                        'uc.dspr',
                        'uc.dsit',
                        'uc.dspl',
                        DB::raw("DATEDIFF('$today', ld.created_at) as days_since_disbursal"),
                        DB::raw("DATEDIFF('$today', lap.repay_date) as days_after_due"),
                        DB::raw('IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount) as remaining_principal'),

                        DB::raw("DATEDIFF('$today', IFNULL(uc.last_payment_date, ld.created_at)) as days_since_payment"),

                        DB::raw('(
                            (IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount) * lap.roi / 100)
                            * DATEDIFF("' . $today . '", IFNULL(uc.last_payment_date, ld.created_at))
                        ) as interest'),

                        DB::raw('
                            IF(DATEDIFF("' . $today . '", lap.repay_date) > 0,
                                (IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date),
                                0
                            ) as penal_interest'),

                        DB::raw('
                            (IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount))
                            + ((IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount) * lap.roi / 100) * DATEDIFF("' . $today . '", IFNULL(uc.last_payment_date, ld.created_at)))
                            + IF(DATEDIFF("' . $today . '", lap.repay_date) > 0,
                                (IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date),
                                0
                            ) as total_dues
                        ')
                    ])
                    ->where('la.id', $lead->id)
                    ->first();

                $loanType = isset($loanType) ? $loanType : 'Active';
                $totalos = (!empty($loans->total_dues) || !empty($loans->total_paid)) ? $loans->total_dues - $loans->total_paid : 0;
                $totalprncos = (!empty($loans->remaining_principal) || !empty($loans->total_principal_paid)) ? $loans->remaining_principal - $loans->total_principal_paid : 0;
                $totalintos = (!empty($loans->interest) || !empty($loans->total_interest_paid)) ? $loans->interest - $loans->total_interest_paid : 0;
                $totalpenalos = (!empty($loans->penal_interest) || !empty($loans->total_penal_paid)) ? $loans->penal_interest - $loans->total_penal_paid : 0;

                // Adjust negative O/s by adding back into dues
                if ($totalos < 0) {
                    $loans->total_dues += abs($totalos);
                    $totalos = 0;
                }
                if ($totalprncos < 0) {
                    $loans->remaining_principal += abs($totalprncos);
                    $totalprncos = 0;
                }
                if ($totalintos < 0) {
                    $loans->interest += abs($totalintos);
                    $totalintos = 0;
                }
                if ($totalpenalos < 0) {
                    $loans->penal_interest += abs($totalpenalos);
                    $totalpenalos = 0;
                }
                $daysAfterDue = max((int)($loans->days_after_due ?? 0), 0);
                
                if (!empty($lead->loanApproval->repay_date) && !empty($loans->last_payment_date)) {
                    $repayDate = \Carbon\Carbon::parse($lead->loanApproval->repay_date);
                    $lastPaymentDate = \Carbon\Carbon::parse($loans->last_payment_date);

                    $diffInDays = $repayDate->diffInDays($lastPaymentDate); // Always positive
                    // Or if you want signed difference (last_payment_date - repay_date)
                    $signedDiff = $lastPaymentDate->diffInDays($repayDate, false);
                    $collectionTenure = $signedDiff;
                } else {
                    $collectionTenure = 0;
                }

                $csvData[] = [
                    'Sr No' => $index + 1,
                    'Disb Month' => !empty($lead->loanDisbursal->disbursal_date) ? date('M-Y',strtotime($lead->loanDisbursal->disbursal_date)) : '',
                    'Collection Month' => !empty($loans->last_payment_date) ? date('M-Y',strtotime($loans->last_payment_date)) : '',
                    'Paid Month' => !empty($loans->last_payment_date) ? date('m-Y',strtotime($loans->last_payment_date)) : '',
                    'Appl. No.' => $lead->loan_no,
                    'Appl. ID' => $lead->id,
                    'Customer Name' => $lead->user->firstname . ' ' . $lead->user->lastname,
                    'Mobile Number' => "'" . $lead->user->mobile,
                    'Email Id' => $lead->user->email,
                    'Disbursed date' => optional($lead->loanDisbursal)->disbursal_date ?? 0,
                    'Loan Amount' => !empty($lead->loanApproval->approval_amount) ? number_format($lead->loanApproval->approval_amount, 2) : '',
                    'Process fee' => !empty($lead->loanApproval->processing_fee_amount) ? $lead->loanApproval->processing_fee_amount : '',
                    'GST' => !empty($lead->loanApproval->gst_amount) ? $lead->loanApproval->gst_amount : '',
                    'Disbursed amount' => !empty($lead->loanDisbursal->disbursal_amount) ? number_format($lead->loanDisbursal->disbursal_amount, 2) : '',
                    'Rate of Interest' => !empty($lead->loanApproval->roi) ? $lead->loanApproval->roi : '',
                    'Tenure' => !empty($lead->loanApproval->loan_tenure_days) ? $lead->loanApproval->loan_tenure_days : '',
                    'Repayment Due Date' => !empty($lead->loanApproval->repay_date) ? $lead->loanApproval->repay_date : '',
                    'Total Due Amount' => number_format(max($loans->total_dues, 0), 2),
                    'Principal Due'   => number_format(max($loans->remaining_principal, 0), 2),
                    'Interest Due'    => number_format(max($loans->interest, 0), 2),
                    'Penal Due'       => number_format(max($loans->penal_interest, 0), 2),
                    'Paid Date' => (!empty($loans->last_payment_date)) ? date('d-m-Y',strtotime($loans->last_payment_date)) : '',
                    'Payment Status' => (!empty($loans->ucstatus)) ? $loans->ucstatus : '',
                    'Total Amt Collected' => !empty($loans->total_paid) ? number_format($loans->total_paid, 2) : 0,
                    'Principal Collect.' => (!empty($loans->total_principal_paid)) ? number_format($loans->total_principal_paid, 2) : 0 ,
                    'Interest Collect.' => (!empty($loans->total_interest_paid)) ? number_format($loans->total_interest_paid, 2) : 0 ,
                    'Penal Collect.' => (!empty($loans->total_penal_paid)) ? number_format($loans->total_penal_paid, 2) : 0 ,
                    'Principal Discount' => (!empty($loans->dspr)) ? $loans->dspr : '',
                    'Interest Discount' => (!empty($loans->dsit)) ? $loans->dsit : '',
                    'Penal Discount' => (!empty($loans->dspl)) ? $loans->dspl : '',
                    'Collection Tenure' => $collectionTenure,
                    'Total O/s' => $totalos,
                    'Principal O/s' => $totalprncos,
                    'Interest O/s' => $totalintos,
                    'Penal O/s' => $totalpenalos,
                    'DPD' => $daysAfterDue,
                    'Purpose Of Loan' => $lead->purpose_of_loan,
                    'Status' => $loanType === 'disbursed_loan' ? 'Active' : $loanType,
                    'CIBIL Score' => !empty($lead->loanApproval->cibil_score) ? $lead->loanApproval->cibil_score : '',
                    'Monthly Income' => !empty($lead->loanApproval->monthly_income) ? number_format($lead->loanApproval->monthly_income,2) : '',
                    'Employment Type' => !empty($lead->personalDetails->employment_type) ? $lead->personalDetails->employment_type : '',
                    'Organisation Name' => !empty($lead->employmentDetails->company_name) ? $lead->employmentDetails->company_name : '',
                    'Designation' => !empty($lead->employmentDetails->designation) ? $lead->employmentDetails->designation : '',
                    'City' => !empty($lead->addressDetails->city) ? $lead->addressDetails->city : '',
                    'PIN Code' => !empty( $lead->addressDetails->pincode) ? $lead->addressDetails->pincode : '',
                    'Address' => !empty($lead->addressDetails->house_no) ? $lead->addressDetails->house_no.', '.$lead->addressDetails->locality : '',
                ];
            }

            $loanTypeText = $loanType ?? 'all';
            $dateRangeText = $dateRange ?? 'alltime';
            $timestamp = now()->format('Ymd_His');

            $filename = "{$dateRangeText}_{$loanTypeText}_export_{$timestamp}.csv";

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

        $totalRecordsQuery = clone $query;
        $totalRecords = $totalRecordsQuery->count();

        $leads = $query->paginate(25);

        return view('admin.osreport.cindex', compact('leads', 'usersWithKyc', 'userIdsWithKyc', 'totalRecords'));
    
    }

    public function findex(Request $request)
    {
        $excludedUserIds = ['591','592','593','594','595','697','1003','601','1379','1680'];

        // 📌 Step 1: handle shortcut date filter
        $dateRange = $request->get('date_range');
        $userType = $request->get('user_type', 'new'); // default new

        if ($dateRange) {
            switch ($dateRange) {
                case 'today':
                    $from = $to = Carbon::today()->toDateString();
                    break;

                case 'yesterday':
                    $from = $to = Carbon::yesterday()->toDateString();
                    break;

                case 'last_3_days':
                    $from = Carbon::now()->subDays(3)->toDateString();
                    $to   = Carbon::now()->toDateString();
                    break;

                case 'last_7_days':
                    $from = Carbon::now()->subDays(7)->toDateString();
                    $to   = Carbon::now()->toDateString();
                    break;

                case 'last_15_days':
                    $from = Carbon::now()->subDays(15)->toDateString();
                    $to   = Carbon::now()->toDateString();
                    break;

                case 'current_month':
                    $from = Carbon::now()->startOfMonth()->toDateString();
                    $to   = Carbon::now()->endOfMonth()->toDateString();
                    break;

                case 'previous_month':
                    $from = Carbon::now()->subMonth()->startOfMonth()->toDateString();
                    $to   = Carbon::now()->subMonth()->endOfMonth()->toDateString();
                    break;

                case 'custom':
                    $from = $request->from_date;
                    $to   = $request->to_date;
                    break;

                default:
                    $from = $to = date('Y-m-d');
            }
        } else {
            // Default if nothing selected
            $from = $request->from ?? date('Y-m-d');
            $to   = $request->to   ?? date('Y-m-d');
        }

        // get all users who applied before the selected date range
        $existingUsersSQL = "
            SELECT DISTINCT user_id 
            FROM finovel.loan_applications 
            WHERE created_at < '{$from} 00:00:00'
        ";

        if ($userType == 'new') {

            $userFilter = "AND user_id NOT IN ($existingUsersSQL)";
            $userFilterGeneric = "AND user_id NOT IN ($existingUsersSQL)";

        } elseif ($userType == 'existing') {

            $userFilter = "AND user_id IN ($existingUsersSQL)";
            $userFilterGeneric = "AND user_id IN ($existingUsersSQL)";

        } else {

            $userFilter = "";
            $userFilterGeneric = "";
        }

        $sql = "SELECT
            dates.creation_date,
            COALESCE(loan_applications_counts.loan_applications_count, 0) AS Leads,
            COALESCE(pan_counts.pan_count, 0) AS PAN,
            COALESCE(aadhaar_counts.aadhaar_count, 0) AS Aadhar,
            COALESCE(experian_counts.experian_count, 0) AS Bureau,
            COALESCE(loan_docs_counts.loan_documents_count, 0) AS Selfie,
            COALESCE(loan_bank_details_counts.loan_bank_details_count, 0) AS `Bank Statement`,
            COALESCE(loan_approvals_counts.loan_approvals_count, 0) AS Approvals,
            COALESCE(loan_disbursals_counts.loan_disbursals_count, 0) AS Disbursed
        FROM
        (
            SELECT DATE(la.created_at) AS creation_date
            FROM finovel.loan_applications la
            UNION
            SELECT DATE(p.created_at) AS creation_date FROM finovel.pan_data p
            UNION
            SELECT DATE(a.created_at) AS creation_date FROM finovel.aadhaar_data a
            UNION
            SELECT DATE(e.created_at) AS creation_date FROM finovel.experian_credit_reports e
            UNION
            SELECT DATE(d.created_at) AS creation_date FROM finovel.loan_documents d
            UNION
            SELECT DATE(b.created_at) AS creation_date FROM finovel.loan_bank_details b
            UNION
            SELECT DATE(ap.approval_date) AS creation_date FROM finovel.loan_approvals ap
            UNION
            SELECT DATE(ds.created_at) AS creation_date FROM finovel.loan_disbursals ds
        ) AS dates
        LEFT JOIN
        (
            SELECT DATE(created_at) AS creation_date, COUNT(*) AS loan_applications_count
            FROM finovel.loan_applications
            WHERE user_id NOT IN (" . implode(',', $excludedUserIds) . ")
            $userFilter
            GROUP BY creation_date
        ) AS loan_applications_counts ON dates.creation_date = loan_applications_counts.creation_date
        LEFT JOIN
        (
            SELECT DATE(pd.created_at) AS creation_date, COUNT(*) AS pan_count
            FROM finovel.pan_data pd
            WHERE pd.user_id NOT IN (" . implode(',', $excludedUserIds) . ")
            $userFilterGeneric
            GROUP BY creation_date
        ) AS pan_counts ON dates.creation_date = pan_counts.creation_date
        LEFT JOIN
        (
            SELECT DATE(ad.created_at) AS creation_date, COUNT(*) AS aadhaar_count
            FROM finovel.aadhaar_data ad
            WHERE ad.user_id NOT IN (" . implode(',', $excludedUserIds) . ")
            $userFilterGeneric
            GROUP BY creation_date
        ) AS aadhaar_counts ON dates.creation_date = aadhaar_counts.creation_date
        LEFT JOIN
        (
            SELECT DATE(ec.created_at) AS creation_date, COUNT(*) AS experian_count
            FROM finovel.experian_credit_reports ec
            WHERE ec.user_id NOT IN (" . implode(',', $excludedUserIds) . ")
            $userFilterGeneric
            GROUP BY creation_date
        ) AS experian_counts ON dates.creation_date = experian_counts.creation_date
        LEFT JOIN
        (
            SELECT DATE(ld.created_at) AS creation_date, COUNT(*) AS loan_documents_count
            FROM finovel.loan_documents ld
            WHERE ld.loan_application_id IN (SELECT id FROM finovel.loan_applications WHERE user_id NOT IN (" . implode(',', $excludedUserIds) . ")
            $userFilter )
            GROUP BY creation_date
        ) AS loan_docs_counts ON dates.creation_date = loan_docs_counts.creation_date
        LEFT JOIN
        (
            SELECT DATE(lbd.created_at) AS creation_date, COUNT(DISTINCT lbd.account_number) AS loan_bank_details_count FROM finovel.loan_bank_details lbd
            WHERE lbd.loan_application_id IN (SELECT id FROM finovel.loan_applications WHERE user_id NOT IN (" . implode(',', $excludedUserIds) . ")
            $userFilter ) AND TRIM(account_number) <> ''
            GROUP BY creation_date
        ) AS loan_bank_details_counts ON dates.creation_date = loan_bank_details_counts.creation_date
        LEFT JOIN
        (
            SELECT DATE(lap.approval_date) AS creation_date, COUNT(*) AS loan_approvals_count
            FROM finovel.loan_approvals lap
            WHERE lap.loan_application_id IN (SELECT id FROM finovel.loan_applications WHERE user_id NOT IN (" . implode(',', $excludedUserIds) . ")
            $userFilter AND admin_approval_status = 'approved') 
            GROUP BY creation_date
        ) AS loan_approvals_counts ON dates.creation_date = loan_approvals_counts.creation_date
        LEFT JOIN
        (
            SELECT DATE(ldis.created_at) AS creation_date, COUNT(*) AS loan_disbursals_count
            FROM finovel.loan_disbursals ldis
            WHERE ldis.loan_application_id IN (SELECT id FROM finovel.loan_applications WHERE user_id NOT IN (" . implode(',', $excludedUserIds) . ")
            $userFilter )
            GROUP BY creation_date
        ) AS loan_disbursals_counts ON dates.creation_date = loan_disbursals_counts.creation_date
        WHERE
            dates.creation_date BETWEEN ? AND ?
        ORDER BY
            dates.creation_date";

        // 🔍 Build final SQL with parameters for debugging
        $debugSql = Str::replaceArray('?', [$from, $to], $sql);

        // Log it to storage/logs/laravel.log
        Log::info('OS Report Final SQL', ['sql' => $debugSql]);

        $data = collect(DB::select($sql, [$from, $to]));

        //$data = DB::select($sql);

        // Export if clicked
        if ($request->export == "csv") {
            $filename = "report_" . date('YmdHis') . ".csv";
            $handle = fopen($filename, 'w');

            fputcsv($handle, array_keys((array)$data[0])); // header
            foreach ($data as $row) {
                fputcsv($handle, (array)$row);
            }
            fclose($handle);

            return response()->download($filename)->deleteFileAfterSend();
        }

        return view('admin.osreport.findex', compact('data', 'from', 'to'));
    
    }
}
