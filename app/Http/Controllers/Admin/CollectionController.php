<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\LoanApplication;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;

class CollectionController extends Controller
{
    public function collectionPredue(Request $request)
    {
        ini_set('memory_limit', '2048M');
        $today = now()->toDateString();

        // 1️⃣ Base query
        $query = LoanApplication::whereHas('loanDisbursal')
            ->whereHas('loanApproval', function ($query) use ($today) {
                $query->whereDate('repay_date', '>=', $today);
            })
            ->with([
                'user',
                'personalDetails',
                'employmentDetails',
                'kycDetails',
                'loanDocument',
                'addressDetails',
                'bankDetails',
                'loanDisbursal',
                'loanApproval',
                'experianCreditReport',
            ])->where('loan_applications.loan_closed_status', '!=', 'closed')
            ->orderByRaw('created_at DESC');

        $searchTerm = $request->get('search');
        $dateRange = $request->get('date_range');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        if ($dateRange) {
            if ($dateRange === 'custom' && $fromDate && $toDate) {
                $query->whereHas('loanApproval', function ($q) use($fromDate, $toDate) {
                $q->whereBetween('repay_date', [$fromDate, $toDate]);
                });
            }
        }

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('user', function ($userQuery) use ($searchTerm) {
                    $userQuery->where('firstname', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhere('mobile', 'like', "%{$searchTerm}%");
                })->orWhere('loan_no', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->has('export') && $request->export === 'csv') {
            $leads = $query->get();

            $csvData = [];

            foreach ($leads as $lead) {
                // Run the same sub-query to get your dynamic values:
                $loans = DB::table('loan_applications as la')
                    ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
                    ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
                    ->leftJoin(DB::raw('(SELECT loan_application_id, SUM(collection_amt) as total_paid FROM utr_collections GROUP BY loan_application_id) as uc'), 'uc.loan_application_id', '=', 'la.id')
                    ->select([
                        'lap.repay_date','lap.approval_amount','lap.repayment_amount',
                        DB::raw("DATEDIFF('$today', lap.repay_date) as days_after_due"),
                        DB::raw('
                            (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) +
                            ((lap.approval_amount * lap.roi / 100 ) * DATEDIFF("' . $today . '", ld.created_at)) +
                            IF(DATEDIFF("' . $today . '", lap.repay_date) > 0,
                                (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date),
                                0
                            ) as total_dues')
                    ])
                    ->where('la.id', $lead->id)
                    ->where('la.loan_closed_status', 'pending')
                    ->first();

                $totalDues = !empty($loans->total_dues) ? (int)$loans->total_dues : 0;
                $repayDate = !empty($loans->repay_date) ? $loans->repay_date : '';
                $paymentLink = config('services.docs.app_url') . '/api/pay/'.base64_encode($lead->id);

                $experian = null;

                if (!empty($lead->experianCreditReport) && !empty($lead->experianCreditReport->response_data)) {
                    $experian = json_decode($lead->experianCreditReport->response_data, true);
                }
                $telephone = $mobile = $emailid = [];
                if(!empty($experian['CAIS_Account']['CAIS_Account_DETAILS']))
                {
                    $accountDetails = $experian['CAIS_Account']['CAIS_Account_DETAILS'] ?? [];
                    foreach($accountDetails as $account)
                    {
                        $phones = $account['CAIS_Holder_Phone_Details'] ?? [];
                        if(!empty($phones))
                        {
                            foreach($phones as $index => $ph)
                            {
                                $tel   = $ph['Telephone_Number'] ?? null;
                                $mob   = $ph['Mobile_Telephone_Number'] ?? null;
                                $email = $ph['EMailId'] ?? null;

                                // Convert arrays to comma-separated strings
                                $tel   = is_array($tel)   ? implode(', ', $tel)   : $tel;
                                $mob   = is_array($mob)   ? implode(', ', $mob)   : $mob;
                                $email = is_array($email) ? implode(', ', $email) : $email;

                                if ($tel)   $telephone[] = $tel;
                                if ($mob)   $mobile[]    = $mob;
                                if ($email) $emailid[]   = $email;
                            }
                        }
                    }
                }

                $telephone = array_unique($telephone);
                $mobile    = array_unique($mobile);
                $emailid   = array_unique($emailid);

                $csvData[] = [
                    'Customer Name' => $lead->user->firstname . ' ' . $lead->user->lastname,
                    'Customer Mobile' => '="'. substr($lead->user->mobile, 2, 12).'"',
                    'Loan Application No' => $lead->loan_no,
                    'Loan Amount' => number_format($loans->approval_amount ?? 0, 0),
                    'Total Due' => number_format($totalDues, 0),
                    'Repayment Amount' => number_format($loans->repayment_amount ?? 0, 0),
                    'Repayment date' => $repayDate,
                    'Payment Link' => $paymentLink,
                    'Telephone' => implode(', ', $telephone),
                    'Mobile No' => implode(', ', $mobile),
                    'Email Id'  => implode(', ', $emailid),
                ];
            }

            $dateRangeText = $dateRange ?? 'alltime';
            $timestamp = now()->format('Ymd_His');

            $filename = "{$dateRangeText}_COD_export_{$timestamp}.csv";

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

        $today = now()->toDateString();
        $baseQuery = (clone $query)->getQuery();

        $totals = DB::table(DB::raw("({$baseQuery->toSql()}) as base"))
        ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'base.id')
        ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'base.id')
        ->leftJoin(DB::raw('(SELECT loan_application_id, SUM(collection_amt) as total_paid FROM utr_collections GROUP BY loan_application_id) as uc'), 'uc.loan_application_id', '=', 'base.id')
        ->selectRaw('
            SUM(
                (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) +
                ((lap.approval_amount * lap.roi / 100) * DATEDIFF(?, ld.created_at)) +
                IF(DATEDIFF(?, lap.repay_date) > 0,
                    (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) * 0.0025 * DATEDIFF(?, lap.repay_date),
                    0
                )
            ) as total_dues_sum,
            SUM(lap.approval_amount) as total_approval_amount
        ', [$today, $today, $today])
        ->mergeBindings($baseQuery)
        ->first();

        $totalDuesSum = (int) $totals->total_dues_sum;
        $totalApprovalAmount = (int) $totals->total_approval_amount;

        // 2️⃣ Paginate first
        $leads = $query->paginate(25);

        // 3️⃣ For each lead, add your computed data
        $leads->getCollection()->transform(function ($lead) use ($today) {

            // Your specific raw query
            $loans = DB::table('loan_applications as la')
                ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
                ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
                ->leftJoin(DB::raw('(SELECT loan_application_id, SUM(collection_amt) as total_paid FROM utr_collections GROUP BY loan_application_id) as uc'), 'uc.loan_application_id', '=', 'la.id')
                ->select([
                    'la.id',
                    'la.loan_no',
                    'ld.loan_disbursal_number',
                    'lap.approval_amount',
                    DB::raw("DATEDIFF('$today', ld.created_at) as days_since_disbursal"),
                    DB::raw("DATEDIFF('$today', lap.repay_date) as days_after_due"),
                    DB::raw('IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount) as remaining_principal'),
                    DB::raw('(lap.approval_amount * lap.roi / 100 ) * DATEDIFF("' . $today . '", ld.created_at) as interest'),
                    DB::raw('
                        IF(DATEDIFF("' . $today . '", lap.repay_date) > 0,
                            (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date),
                            0
                        ) as penal_interest'),
                    DB::raw('
                        (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) +
                        ((lap.approval_amount * lap.roi / 100 ) * DATEDIFF("' . $today . '", ld.created_at)) +
                        IF(DATEDIFF("' . $today . '", lap.repay_date) > 0,
                            (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date),
                            0
                        ) as total_dues')
                ])
                ->where('la.id', $lead->id)
                ->where('la.loan_closed_status', 'pending')
                ->first();

            $totalDues = !empty($loans->total_dues) ? (int)$loans->total_dues : 0;

            // Add to the lead
            $lead->total_dues = $totalDues;
            $paymentLink = config('services.docs.app_url') . '/api/pay/'.base64_encode($loans->id);
            $lead->paymentLink = $paymentLink;

            return $lead;
        });

        return view('admin.collection.collection-predue', compact('leads','totalRecords','totalDuesSum','totalApprovalAmount'));
    }

    public function collectionOverdue(Request $request)
    {
        ini_set('memory_limit', '4096M');
        $today = now()->toDateString();

        // 1️⃣ Base query
        $query = LoanApplication::whereHas('loanDisbursal')
            ->whereHas('loanApproval', function ($query) use ($today) {
                $query->whereDate('repay_date', '<', $today);
            })
            ->whereDoesntHave('collections', function ($q) {
                $q->whereIn('status', ['Closed', 'Settlement']);
            })
            ->with([
                'user',
                'personalDetails',
                'employmentDetails',
                'kycDetails',
                'loanDocument',
                'addressDetails',
                'bankDetails',
                'loanDisbursal',
                'loanApproval',
                'experianCreditReport',
            ])->where('loan_applications.loan_closed_status', '!=', 'closed')
            ->orderByRaw('created_at DESC');

        $searchTerm = $request->get('search');
        $dateRange = $request->get('date_range');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        if ($dateRange) {
            if ($dateRange === 'custom' && $fromDate && $toDate) {
                $query->whereHas('loanApproval', function ($q) use($fromDate, $toDate) {
                $q->whereBetween('repay_date', [$fromDate, $toDate]);
                });
            }
        }

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('user', function ($userQuery) use ($searchTerm) {
                    $userQuery->where('firstname', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhere('mobile', 'like', "%{$searchTerm}%");
                })->orWhere('loan_no', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->has('export') && $request->export === 'csv') {
            $leads = $query->get();

            $csvData = [];

            foreach ($leads as $lead) {
                // Run the same sub-query to get your dynamic values:
                $loans = DB::table('loan_applications as la')
                    ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
                    ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
                    ->leftJoin(DB::raw('(SELECT loan_application_id, SUM(collection_amt) as total_paid,
                    SUM(principal) as total_principal_paid, SUM(interest) as total_interest_paid FROM utr_collections GROUP BY loan_application_id) as uc'), 'uc.loan_application_id', '=', 'la.id')
                    ->select([
                        'lap.repay_date','lap.approval_amount','lap.loan_tenure_days','lap.repayment_amount','lap.roi','lap.cibil_score','ld.loan_disbursal_number','ld.disbursal_date',
                        DB::raw("DATEDIFF('$today', lap.repay_date) as days_after_due"),
                        DB::raw('
                        (IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount))
                        + ((IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount) * lap.roi / 100) * DATEDIFF("' . $today . '", ld.created_at) - IFNULL(uc.total_interest_paid, 0))
                        + IF(DATEDIFF("' . $today . '", lap.repay_date) > 0, (IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date), 0 ) as total_dues')
                    ])
                    ->where('la.id', $lead->id)
                    ->where('la.loan_closed_status', 'pending')
                    ->first();

                $totalDues = !empty($loans->total_dues) ? (int)$loans->total_dues : 0;
                $daysAfterDue = !empty($loans->days_after_due) ? (int)$loans->days_after_due : 0;
                $repayDate = !empty($loans->repay_date) ? $loans->repay_date : '';
                
                $userAddress = DB::table('aadhaar_data')->where('user_id', $lead->user->id)->first();
                $paymentLink = config('services.docs.app_url') . '/api/pay/'.base64_encode($lead->id);

                $experian = null;

                if (!empty($lead->experianCreditReport) && !empty($lead->experianCreditReport->response_data)) {
                    $experian = json_decode($lead->experianCreditReport->response_data, true);
                }
                $telephone = $mobile = $emailid = [];
                if(!empty($experian['CAIS_Account']['CAIS_Account_DETAILS']))
                {
                    $accountDetails = $experian['CAIS_Account']['CAIS_Account_DETAILS'] ?? [];
                    foreach($accountDetails as $account)
                    {
                        $phones = $account['CAIS_Holder_Phone_Details'] ?? [];
                        if(!empty($phones))
                        {
                            foreach($phones as $index => $ph)
                            {
                                $tel   = $ph['Telephone_Number'] ?? null;
                                $mob   = $ph['Mobile_Telephone_Number'] ?? null;
                                $email = $ph['EMailId'] ?? null;

                                // Convert arrays to comma-separated strings
                                $tel   = is_array($tel)   ? implode(', ', $tel)   : $tel;
                                $mob   = is_array($mob)   ? implode(', ', $mob)   : $mob;
                                $email = is_array($email) ? implode(', ', $email) : $email;

                                if ($tel)   $telephone[] = $tel;
                                if ($mob)   $mobile[]    = $mob;
                                if ($email) $emailid[]   = $email;
                            }
                        }
                    }
                }

                $telephone = array_unique($telephone);
                $mobile    = array_unique($mobile);
                $emailid   = array_unique($emailid);

                $loanCount = $lead->user->loanApplications()->count();
                $customerType = $loanCount > 1 ? 'Existing' : 'New';

                $csvData[] = [
                    'Customer Name' => $lead->user->firstname . ' ' . $lead->user->lastname,
                    'Customer Mobile' => '="'. substr($lead->user->mobile, 2, 12).'"',
                    'Loan Application No' => $lead->loan_no,
                    'Loan Account No' => $loans->loan_disbursal_number ?? '',
                    'Loan Amount' => number_format($loans->approval_amount ?? 0, 0),
                    'Total Due' => number_format($totalDues, 0),
                    'Repayment Amount' => number_format($loans->repayment_amount ?? 0, 0),
                    'Repayment date' => $repayDate,
                    'Loan Disbursal Date' => $loans->disbursal_date ?? '',
                    'Intrest Rate (%)' => $loans->roi ?? 0,
                    'Cibil Score' => $loans->cibil_score ?? 0,
                    'Customer Type' => $customerType,
                    'Intrest Calculated upto' => $today,
                    'Payment Link' => $paymentLink,
                    'DPD' => $daysAfterDue,
                    'Email' => $lead->user->email,
                    'Loan Tenure' => $loans->loan_tenure_days ?? 0,
                    'Full Address' => isset($userAddress) ? $userAddress->full_address : '',
                    'Telephone' => implode(', ', $telephone),
                    'Mobile No' => implode(', ', $mobile),
                    'Email Id'  => implode(', ', $emailid),
                ];
            }

            $dateRangeText = $dateRange ?? 'alltime';
            $timestamp = now()->format('Ymd_His');

            $filename = "{$dateRangeText}_COD_export_{$timestamp}.csv";

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

        $today = now()->toDateString();
        $baseQuery = (clone $query)->getQuery();

        $totals = DB::table(DB::raw("({$baseQuery->toSql()}) as base"))
        ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'base.id')
        ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'base.id')
        ->leftJoin(DB::raw('(SELECT loan_application_id, SUM(collection_amt) as total_paid FROM utr_collections GROUP BY loan_application_id) as uc'), 'uc.loan_application_id', '=', 'base.id')
        ->selectRaw('
            SUM(
                (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) +
                ((lap.approval_amount * lap.roi / 100) * DATEDIFF(?, ld.created_at)) +
                IF(DATEDIFF(?, lap.repay_date) > 0,
                    (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) * 0.0025 * DATEDIFF(?, lap.repay_date),
                    0
                )
            ) as total_dues_sum,
            SUM(lap.approval_amount) as total_approval_amount
        ', [$today, $today, $today])
        ->mergeBindings($baseQuery)
        ->first();

        $totalDuesSum = (int) $totals->total_dues_sum;
        $totalApprovalAmount = (int) $totals->total_approval_amount;
        // 2️⃣ Paginate first
        $leads = $query->paginate(25);

        // 3️⃣ For each lead, add your computed data
        $leads->getCollection()->transform(function ($lead) use ($today) {

            // Your specific raw query
            $loans = DB::table('loan_applications as la')
                ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
                ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
                ->leftJoin(DB::raw('(SELECT loan_application_id, SUM(collection_amt) as total_paid,
                    SUM(principal) as total_principal_paid, SUM(interest) as total_interest_paid FROM utr_collections GROUP BY loan_application_id) as uc'), 'uc.loan_application_id', '=', 'la.id')
                ->select([
                    'la.id',
                    'la.loan_no',
                    'ld.loan_disbursal_number',
                    'lap.approval_amount',
                    DB::raw("DATEDIFF('$today', ld.created_at) as days_since_disbursal"),
                    DB::raw("DATEDIFF('$today', lap.repay_date) as days_after_due"),
                    DB::raw('IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount) as remaining_principal'),
                    DB::raw('((IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount) * lap.roi / 100) * DATEDIFF("' . $today . '", ld.created_at) - IFNULL(uc.total_interest_paid, 0)) as interest'),
                    DB::raw('
                    IF(DATEDIFF("' . $today . '", lap.repay_date) > 0,
                        (IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date),
                        0
                    ) as penal_interest'),
                    DB::raw('
                    (IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount))
                    + ((IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount) * lap.roi / 100) * DATEDIFF("' . $today . '", ld.created_at) - IFNULL(uc.total_interest_paid, 0))
                    + IF(DATEDIFF("' . $today . '", lap.repay_date) > 0, (IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date), 0 ) as total_dues')
                ])
                ->where('la.id', $lead->id)
                ->where('la.loan_closed_status', 'pending')
                ->first();

            $totalDues = !empty($loans->total_dues) ? (int)$loans->total_dues : 0;
            $days_after_due = !empty($loans->days_after_due) ? (int)$loans->days_after_due : 0;

            // Add to the lead
            $lead->total_dues = $totalDues;
            $lead->dpd = $days_after_due;
            $paymentLink = config('services.docs.app_url') . '/api/pay/'.base64_encode($loans->id);
            $lead->paymentLink = $paymentLink;

            return $lead;
        });

        return view('admin.collection.collection-overdue', compact('leads','totalRecords','totalDuesSum','totalApprovalAmount'));
    }

    public function collectionAll(Request $request)
    {
        ini_set('memory_limit', '2048M');
        $today = now()->toDateString();

        // 1️⃣ Base query
        $query = LoanApplication::whereHas('loanDisbursal')
            ->with([
                'user',
                'personalDetails',
                'employmentDetails',
                'kycDetails',
                'loanDocument',
                'addressDetails',
                'bankDetails',
                'loanDisbursal',
                'loanApproval',
            ])
            ->orderByRaw('created_at DESC');

        $searchTerm = $request->get('search');
        $dateRange = $request->get('date_range');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        if ($dateRange) {
            if ($dateRange === 'custom' && $fromDate && $toDate) {
                $query->whereHas('loanApproval', function ($q) use($fromDate, $toDate) {
                $q->whereBetween('repay_date', [$fromDate, $toDate]);
                });
            }
        }

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('user', function ($userQuery) use ($searchTerm) {
                    $userQuery->where('firstname', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhere('mobile', 'like', "%{$searchTerm}%");
                })->orWhere('loan_no', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->has('export') && $request->export === 'csv') {
            $leads = $query->get();

            $csvData = [];

            foreach ($leads as $lead) {
                // Run the same sub-query to get your dynamic values:
                $loans = DB::table('loan_applications as la')
                    ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
                    ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
                    ->leftJoin(DB::raw('(SELECT loan_application_id, SUM(collection_amt) as total_paid,
                    SUM(principal) as total_principal_paid, SUM(interest) as total_interest_paid, SUM(penal) as total_penal_paid,
                    MAX(collection_date) as last_collection_date, MAX(created_at) as last_payment_date FROM utr_collections GROUP BY loan_application_id) as uc'), 'uc.loan_application_id', '=', 'la.id')
                    ->select([
                        'lap.repay_date','lap.approval_amount','lap.loan_tenure_days','lap.repayment_amount', 'uc.total_principal_paid', 'uc.total_interest_paid', 'uc.total_penal_paid', 'uc.last_collection_date', 'uc.last_payment_date', 'uc.total_paid',
                        DB::raw("DATEDIFF('$today', lap.repay_date) as days_after_due"),
                        DB::raw('
                        (IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount))
                        + ((IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount) * lap.roi / 100) * DATEDIFF("' . $today . '", ld.created_at) - IFNULL(uc.total_interest_paid, 0))
                        + IF(DATEDIFF("' . $today . '", lap.repay_date) > 0, (IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date), 0 ) as total_dues')
                    ])
                    ->where('la.id', $lead->id)
                    ->first();

                $totalDues = max((int)($loans->total_dues ?? 0), 0);
                $daysAfterDue = max((int)($loans->days_after_due ?? 0), 0);
                $repayDate = !empty($loans->repay_date) ? $loans->repay_date : '';
                $loanStatus = $totalDues == 0 ? 'Paid' : 'Unpaid';

                $csvData[] = [
                        'Customer Name'      => $lead->user->firstname . ' ' . $lead->user->lastname,
                        'Customer Mobile'    => '="'. substr($lead->user->mobile, 2, 12).'"',
                        'Loan Application No'=> $lead->loan_no,
                        'Loan Amount'        => number_format($loans->approval_amount ?? 0, 0),
                        'Total Due'          => number_format($totalDues, 0),
                        'Repayment Amount'   => number_format($loans->repayment_amount ?? 0, 0),
                        'Repayment date'     => $repayDate,
                        'Collection Date'    => $loans->last_collection_date ?? '',
                        'Collection Amount'    => number_format($loans->total_paid ?? 0, 0),
                        'DPD'                => $daysAfterDue,
                        'Email'              => $lead->user->email,
                        'Loan Tenure'        => $loans->loan_tenure_days ?? 0,
                        'Status'             => $loanStatus,
                ];
            }

            $dateRangeText = $dateRange ?? 'alltime';
            $timestamp = now()->format('Ymd_His');

            $filename = "{$dateRangeText}_collection_export_{$timestamp}.csv";

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

        $today = now()->toDateString();
        $baseQuery = (clone $query)->getQuery();

        $totals = DB::table(DB::raw("({$baseQuery->toSql()}) as base"))
        ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'base.id')
        ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'base.id')
        ->leftJoin(DB::raw('(SELECT loan_application_id, SUM(collection_amt) as total_paid FROM utr_collections GROUP BY loan_application_id) as uc'), 'uc.loan_application_id', '=', 'base.id')
        ->selectRaw('
            SUM(
                (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) +
                ((lap.approval_amount * lap.roi / 100) * DATEDIFF(?, ld.created_at)) +
                IF(DATEDIFF(?, lap.repay_date) > 0,
                    (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) * 0.0025 * DATEDIFF(?, lap.repay_date),
                    0
                )
            ) as total_dues_sum,
            SUM(lap.approval_amount) as total_approval_amount
        ', [$today, $today, $today])
        ->mergeBindings($baseQuery)
        ->first();

        $totalDuesSum = (int) $totals->total_dues_sum;
        $totalApprovalAmount = (int) $totals->total_approval_amount;
        // 2️⃣ Paginate first
        $leads = $query->paginate(25);

        // 3️⃣ For each lead, add your computed data
        $leads->getCollection()->transform(function ($lead) use ($today) {

            // Your specific raw query
            $loans = DB::table('loan_applications as la')
                ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
                ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
                ->leftJoin(DB::raw('(SELECT loan_application_id, SUM(collection_amt) as total_paid,
                    SUM(principal) as total_principal_paid, SUM(interest) as total_interest_paid FROM utr_collections GROUP BY loan_application_id) as uc'), 'uc.loan_application_id', '=', 'la.id')
                ->select([
                    'la.id',
                    'la.loan_no',
                    'ld.loan_disbursal_number',
                    'lap.approval_amount',
                    DB::raw("DATEDIFF('$today', ld.created_at) as days_since_disbursal"),
                    DB::raw("DATEDIFF('$today', lap.repay_date) as days_after_due"),
                    DB::raw('IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount) as remaining_principal'),
                    DB::raw('((IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount) * lap.roi / 100) * DATEDIFF("' . $today . '", ld.created_at) - IFNULL(uc.total_interest_paid, 0)) as interest'),
                    DB::raw('
                    IF(DATEDIFF("' . $today . '", lap.repay_date) > 0,
                        (IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date),
                        0
                    ) as penal_interest'),
                    DB::raw('
                    (IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount))
                    + ((IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount) * lap.roi / 100) * DATEDIFF("' . $today . '", ld.created_at) - IFNULL(uc.total_interest_paid, 0))
                    + IF(DATEDIFF("' . $today . '", lap.repay_date) > 0, (IFNULL(lap.approval_amount - uc.total_principal_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date), 0 ) as total_dues')
                ])
                ->where('la.id', $lead->id)
                ->where('la.loan_closed_status', 'pending')
                ->first();

            $totalDues = !empty($loans->total_dues) ? (int)$loans->total_dues : 0;
            $days_after_due = !empty($loans->days_after_due) ? (int)$loans->days_after_due : 0;

            // Add to the lead
            $lead->total_dues = $totalDues;
            $lead->dpd = $days_after_due;

            return $lead;
        });

        return view('admin.collection.collection-all', compact('leads','totalRecords','totalDuesSum','totalApprovalAmount'));
    }
}
