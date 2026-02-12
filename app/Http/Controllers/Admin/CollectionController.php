<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\LoanApplication;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use App\Models\CollectionConfiguration;
use App\Http\Controllers\Api\LoanPaymentController;

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

                     ->leftJoin('cashfree_enach_request_response_data as cerd', function($join) {
                        $join->on('cerd.subscription_id', '=', 'la.loan_no')
                            ->where('cerd.status', '=', 'ACTIVE');
                    })

                    ->select([
                        'lap.repay_date','lap.approval_amount','lap.repayment_amount','lap.salary_date',
                        DB::raw("DATEDIFF('$today', lap.repay_date) as days_after_due"),
                        DB::raw('
                            (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) +
                            ((lap.approval_amount * lap.roi / 100 ) * DATEDIFF("' . $today . '", ld.created_at)) +
                            IF(DATEDIFF("' . $today . '", lap.repay_date) > 0,
                                (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $today . '", lap.repay_date),
                                0
                            ) as total_dues'),
                        'cerd.reference_id'
                    ])
                    ->where('la.id', $lead->id)
                    ->where('la.loan_closed_status', 'pending')
                    ->first();

                $totalDues = !empty($loans->total_dues) ? (int)$loans->total_dues : 0;
                $repayDate = !empty($loans->repay_date) ? $loans->repay_date : '';
                $paymentLink = config('services.docs.app_url') . '/api/pay/'.base64_encode($lead->id);

                $loanCount = $lead->user->loanApplications()->count();
                $customerType = $loanCount > 1 ? 'Existing' : 'New';

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
                    'Salary Date' => $loans->salary_date ?? '',
                    'City' => $lead->addressDetails->city ?? '',
                    'State' => $lead->addressDetails->state ?? '',
                    'Customer Type' => $customerType,
                    'Cashfree Reference No' => $loans->reference_id ?? '',
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

        if ($request->has('export') && $request->export === 'user_contacts') {

            $leads = $query->get();

            $csvData = [];
            $usedNumbers = []; // prevent duplicates

            foreach ($leads as $lead) {

                $customerName = $lead->user->firstname . ' ' . $lead->user->lastname;
                $loan_no = $lead->loan_no;

                // ---------- PRIMARY MOBILE ----------

                if (!empty($lead->user->mobile)) {

                    $mobile = $this->cleanIndianMobile($lead->user->mobile);

                    if ($mobile && !in_array($mobile, $usedNumbers)) {

                        $csvData[] = [
                            'Customer Name' => $customerName,
                            'Loan App Id'   => $loan_no,
                            'Phone Number' => $mobile
                        ];

                        $usedNumbers[] = $mobile;
                    }
                }

                // ---------- EXPERIAN NUMBERS ----------

                if (!empty($lead->experianCreditReport->response_data)) {

                    $experian = json_decode($lead->experianCreditReport->response_data, true);

                    if (!empty($experian['CAIS_Account']['CAIS_Account_DETAILS'])) {

                        foreach ($experian['CAIS_Account']['CAIS_Account_DETAILS'] as $account) {

                            $phones = $account['CAIS_Holder_Phone_Details'] ?? [];

                            foreach ($phones as $ph) {

                                $numbers = array_merge(
                                    (array)($ph['Mobile_Telephone_Number'] ?? []),
                                    (array)($ph['Telephone_Number'] ?? [])
                                );

                                foreach ($numbers as $raw) {

                                    $mobile = $this->cleanIndianMobile($raw);

                                    if ($mobile && !in_array($mobile, $usedNumbers)) {

                                        $csvData[] = [
                                            'Customer Name' => $customerName,
                                            'Loan App Id'   => $loan_no,
                                            'Phone Number' => $mobile
                                        ];

                                        $usedNumbers[] = $mobile;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // ---------- CSV DOWNLOAD ----------

            $filename = "COD_User_Contacts_RowWise_" . now()->format('Ymd_His') . ".csv";

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function () use ($csvData) {

                $file = fopen('php://output', 'w');

                fputcsv($file, ['Customer Name', 'Loan App Id', 'Phone Number']);

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

            $collections = DB::table('utr_collections')
            ->select(
                'loan_application_id',
                'collection_amt',
                'payment_id'
            )
            ->get()
            ->groupBy('loan_application_id');

            $csvData = [];

            foreach ($leads as $lead) {
                // Run the same sub-query to get your dynamic values:
                $loans = DB::table('loan_applications as la')
                    ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
                    ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
                    ->leftJoin('admins as a', 'a.id', '=', 'lap.credited_by')
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
                    ->leftJoin('cashfree_enach_request_response_data as cerd', function($join) {
                        $join->on('cerd.subscription_id', '=', 'la.loan_no')
                            ->where('cerd.status', '=', 'ACTIVE');
                    })
                    ->select([
                        'lap.repay_date','lap.approval_amount','lap.loan_tenure_days','lap.repayment_amount','lap.roi','lap.cibil_score','lap.salary_date','ld.loan_disbursal_number','ld.disbursal_date','a.name as credited_by_name',
                        DB::raw("DATEDIFF('$today', lap.repay_date) as days_after_due"),
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
                        ) as total_dues'),
                        'cerd.reference_id'
                    ])
                    ->where('la.id', $lead->id)
                    ->where('la.loan_closed_status', 'pending')
                    ->first();

                $totalDues = !empty($loans->total_dues) ? (int)$loans->total_dues : 0;
                $daysAfterDue = !empty($loans->days_after_due) ? (int)$loans->days_after_due : 0;
                $repayDate = !empty($loans->repay_date) ? $loans->repay_date : '';
                
                $userAddress = DB::table('aadhaar_data')->where('user_id', $lead->user->id)->first();
                $paymentLink = config('services.docs.app_url') . '/api/pay/'.base64_encode($lead->id);
                $settlementpaymentLink = config('services.docs.app_url') . '/api/settlementpay/'.base64_encode($lead->id);
                $partpaymentLink = config('services.docs.app_url') . '/api/partpay/'.base64_encode($lead->id);

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

                $paymentAmounts = [];
                $paymentIds     = [];
                $totalPaid      = 0;

                if (isset($collections[$lead->id])) {

                    foreach ($collections[$lead->id] as $row) {

                        if (!empty($row->collection_amt)) {

                            $paymentAmounts[] = number_format($row->collection_amt, 0);
                            $totalPaid += $row->collection_amt;
                        }

                        if (!empty($row->payment_id)) {
                            $paymentIds[] = $row->payment_id;
                        }
                    }
                }

                $paymentAmountText = implode(', ', $paymentAmounts);
                $paymentIdText     = implode(', ', $paymentIds);

                $csvData[] = [
                    'Customer Name' => $lead->user->firstname . ' ' . $lead->user->lastname,
                    'Customer Mobile' => '="'. substr($lead->user->mobile, 2, 12).'"',
                    'Loan Application No' => $lead->loan_no,
                    'Loan Account No' => $loans->loan_disbursal_number ?? '',
                    'Loan Amount' => number_format($loans->approval_amount ?? 0, 0),
                    'Total Due' => number_format($totalDues, 0),
                    'Cashfree Reference No' => $loans->reference_id ?? '',
                    'Repayment Amount' => number_format($loans->repayment_amount ?? 0, 0),
                    'Repayment date' => $repayDate,
                    'Loan Disbursal Date' => $loans->disbursal_date ?? '',
                    'Intrest Rate (%)' => $loans->roi ?? 0,
                    'Salary Date' => $loans->salary_date ?? '',
                    'City' => $lead->addressDetails->city ?? '',
                    'State' => $lead->addressDetails->state ?? '',
                    'Cibil Score' => $loans->cibil_score ?? 0,
                    'Customer Type' => $customerType,
                    'Part Payment Amounts' => $paymentAmountText,
                    'Payment IDs' => $paymentIdText,
                    'Total Part Paid' => number_format($totalPaid, 0),
                    'Intrest Calculated upto' => $today,
                    'Payment Link' => $paymentLink,
                    'Settlement Payment Link' => $settlementpaymentLink,
                    'Part Payment Link' => $partpaymentLink,
                    'DPD' => $daysAfterDue,
                    'Email' => $lead->user->email,
                    'Relative Name' => $lead->addressDetails->relative_name,
                    'Relation' => $lead->addressDetails->relation,
                    'Relative Contact No' => $lead->addressDetails->contact_number,
                    'Loan Tenure' => $loans->loan_tenure_days ?? 0,
                    'CPA' => $loans->credited_by_name ?? '',
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

        if ($request->has('export') && $request->export === 'user_contacts') {

            $leads = $query->get();

            $csvData = [];
            $usedNumbers = []; // prevent duplicates

            foreach ($leads as $lead) {

                $customerName = $lead->user->firstname . ' ' . $lead->user->lastname;
                $loan_no = $lead->loan_no;

                // ---------- PRIMARY MOBILE ----------

                if (!empty($lead->user->mobile)) {

                    $mobile = $this->cleanIndianMobile($lead->user->mobile);

                    if ($mobile && !in_array($mobile, $usedNumbers)) {

                        $csvData[] = [
                            'Customer Name' => $customerName,
                            'Loan App Id'   => $loan_no,
                            'Phone Number' => $mobile
                        ];

                        $usedNumbers[] = $mobile;
                    }
                }

                // ---------- EXPERIAN NUMBERS ----------

                if (!empty($lead->experianCreditReport->response_data)) {

                    $experian = json_decode($lead->experianCreditReport->response_data, true);

                    if (!empty($experian['CAIS_Account']['CAIS_Account_DETAILS'])) {

                        foreach ($experian['CAIS_Account']['CAIS_Account_DETAILS'] as $account) {

                            $phones = $account['CAIS_Holder_Phone_Details'] ?? [];

                            foreach ($phones as $ph) {

                                $numbers = array_merge(
                                    (array)($ph['Mobile_Telephone_Number'] ?? []),
                                    (array)($ph['Telephone_Number'] ?? [])
                                );

                                foreach ($numbers as $raw) {

                                    $mobile = $this->cleanIndianMobile($raw);

                                    if ($mobile && !in_array($mobile, $usedNumbers)) {

                                        $csvData[] = [
                                            'Customer Name' => $customerName,
                                            'Loan App Id'   => $loan_no,
                                            'Phone Number' => $mobile
                                        ];

                                        $usedNumbers[] = $mobile;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // ---------- CSV DOWNLOAD ----------

            $filename = "COD_User_Contacts_RowWise_" . now()->format('Ymd_His') . ".csv";

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function () use ($csvData) {

                $file = fopen('php://output', 'w');

                fputcsv($file, ['Customer Name', 'Loan App Id', 'Phone Number']);

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
                        'la.id',
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
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $today = now()->toDateString();

        $query = LoanApplication::whereHas('loanDisbursal')

            // JOIN FIRST (so aliases exist)
            // Join latest approval per loan
            ->join(DB::raw("
                (
                    SELECT la1.*
                    FROM loan_approvals la1
                    INNER JOIN (
                        SELECT loan_application_id, MAX(id) as max_id
                        FROM loan_approvals
                        GROUP BY loan_application_id
                    ) la2
                    ON la1.id = la2.max_id
                ) as lap
            "), 'lap.loan_application_id', '=', 'loan_applications.id')

            // Join latest disbursal per loan
            ->join(DB::raw("
                (
                    SELECT ld1.*
                    FROM loan_disbursals ld1
                    INNER JOIN (
                        SELECT loan_application_id, MAX(id) as max_id
                        FROM loan_disbursals
                        GROUP BY loan_application_id
                    ) ld2
                    ON ld1.id = ld2.max_id
                ) as ld
            "), 'ld.loan_application_id', '=', 'loan_applications.id')

            // SELECT base + calculated column
            ->select([
                'loan_applications.*'
            ])->distinct()

            // NOW ADD account type count
            ->addSelect([
                'account_type_count' => LoanApplication::from('loan_applications as la2')
                    ->selectRaw('COUNT(*)')
                    ->join('loan_disbursals as ld2', 'ld2.loan_application_id', '=', 'la2.id')
                    ->whereColumn('la2.user_id', 'loan_applications.user_id')
                    ->where(function ($q) {
                        $q->whereColumn('ld2.disbursal_date', '<', 'ld.disbursal_date')
                        ->orWhere(function ($q2) {
                            $q2->whereColumn('ld2.disbursal_date', '=', 'ld.disbursal_date')
                                ->whereColumn('la2.id', '<=', 'loan_applications.id');
                        });
                    })
            ])

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

            ->orderBy('lap.repay_date', 'DESC');

        $searchTerm = $request->get('search');
        $dateRange = $request->get('date_range');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        if ($dateRange) {

            if ($dateRange === 'today') {
                $query->whereDate('lap.repay_date', now()->today());
            }

            elseif ($dateRange === 'yesterday') {
                $query->whereDate('lap.repay_date', now()->yesterday());
            }

            elseif ($dateRange === 'last_3_days') {
                $query->whereBetween('lap.repay_date', [now()->subDays(3), now()]);
            }

            elseif ($dateRange === 'last_7_days') {
                $query->whereBetween('lap.repay_date', [now()->subDays(7), now()]);
            }

            elseif ($dateRange === 'last_15_days') {
                $query->whereBetween('lap.repay_date', [now()->subDays(15), now()]);
            }

            elseif ($dateRange === 'current_month') {
                $query->whereBetween('lap.repay_date', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ]);
            }

            elseif ($dateRange === 'previous_month') {
                $query->whereBetween('lap.repay_date', [
                    now()->subMonth()->startOfMonth(),
                    now()->subMonth()->endOfMonth()
                ]);
            }

            elseif ($dateRange === 'custom' && $fromDate && $toDate) {
                $query->whereBetween('lap.repay_date', [$fromDate, $toDate]);
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

    set_time_limit(0);
    ini_set('output_buffering','off');
    ini_set('zlib.output_compression', false);
    while (ob_get_level()) ob_end_clean();

    $filename = 'collection_export_' . now()->format('Ymd_His') . '.csv';

    return response()->streamDownload(function () use ($query, $today) {

        $file = fopen('php://output', 'w');

        fputcsv($file, [
            'Customer Name','Customer Mobile','Loan Application No',
            'Loan Amount','Total Due','Repayment Amount',
            'Disbursement date','Repayment date',
            'Principal Coll.','Interest Coll.','Penal Coll.',
            'Collection Date','Collection Amount',
            'DPD','Bucket','Email',
            'Loan Tenure','Status','Salary Date',
            'Account Type','Account Type Count',
            'CIBIL Score','Monthly Income','Employment Type',
            'Organisation Name','Designation',
            'City','PIN Code','Full Address'
        ]);

        // ✅ CLONE MAIN QUERY (DON'T MODIFY ORIGINAL)
        $exportQuery = clone $query;

        // ✅ ONLY ADD COLLECTION + AADHAAR JOIN
        $exportQuery
            ->leftJoin(DB::raw("
                (
                    SELECT loan_application_id,
                        SUM(collection_amt) as total_paid,
                        SUM(principal) as total_principal_paid,
                        SUM(interest) as total_interest_paid,
                        SUM(penal) as total_penal_paid,
                        MAX(collection_date) as last_collection_date
                    FROM utr_collections
                    GROUP BY loan_application_id
                ) as uc
            "), 'uc.loan_application_id','=','loan_applications.id')

            ->leftJoin('aadhaar_data as aad','aad.user_id','=','loan_applications.user_id')

            ->addSelect([
                'lap.approval_amount',
                'lap.loan_tenure_days',
                'lap.repayment_amount',
                'lap.repay_date',
                'lap.salary_date',
                'lap.cibil_score',
                'lap.monthly_income',
                'lap.roi',
                'ld.disbursal_date',
                'ld.created_at as disbursal_created_at',
                'uc.total_principal_paid',
                'uc.total_interest_paid',
                'uc.total_penal_paid',
                'uc.total_paid',
                'uc.last_collection_date',
                'aad.full_address',

                DB::raw("DATEDIFF('$today', lap.repay_date) as days_after_due"),

                DB::raw("
                    (IFNULL(lap.approval_amount - IFNULL(uc.total_principal_paid,0), lap.approval_amount))
                    + ((IFNULL(lap.approval_amount - IFNULL(uc.total_principal_paid,0), lap.approval_amount) * lap.roi / 100)
                    * DATEDIFF('$today', ld.created_at) - IFNULL(uc.total_interest_paid,0))
                    + IF(DATEDIFF('$today', lap.repay_date) > 0,
                        (IFNULL(lap.approval_amount - IFNULL(uc.total_principal_paid,0), lap.approval_amount))
                        * 0.0025 * DATEDIFF('$today', lap.repay_date),0)
                    as total_dues
                ")
            ])

            // 🔥 THIS IS THE REAL FIX
            ->groupBy('loan_applications.id')

            ->orderBy('loan_applications.id');

        $exportQuery->chunk(200, function ($rows) use ($file) {

            foreach ($rows as $lead) {

                $totalDues = max((int)$lead->total_dues,0);
                $dpd = max((int)$lead->days_after_due,0);
                $loanStatus = $totalDues == 0 ? 'Paid' : 'Unpaid';

                fputcsv($file, [

                    $lead->user->firstname.' '.$lead->user->lastname,
                    '="'.$lead->user->mobile.'"',
                    $lead->loan_no,

                    number_format($lead->approval_amount,0),
                    number_format($totalDues,0),
                    number_format($lead->repayment_amount,0),

                    $lead->disbursal_date,
                    $lead->repay_date,

                    $lead->total_principal_paid ?? 0,
                    $lead->total_interest_paid ?? 0,
                    $lead->total_penal_paid ?? 0,

                    $lead->last_collection_date ?? '',
                    number_format($lead->total_paid ?? 0,0),

                    $dpd,
                    '="'.$this->getDpDBucket($dpd).'"',

                    $lead->user->email,
                    $lead->loan_tenure_days,
                    $loanStatus,
                    $lead->salary_date,

                    $lead->account_type_count == 1 ? 'New' : 'Existing',
                    $lead->account_type_count,

                    $lead->loanApproval->cibil_score ?? '',
                    number_format($lead->loanApproval->monthly_income ?? 0,2),

                    $lead->personalDetails->employment_type ?? '',
                    $lead->employmentDetails->company_name ?? '',
                    $lead->employmentDetails->designation ?? '',

                    $lead->addressDetails->city ?? '',
                    $lead->addressDetails->pincode ?? '',

                    $lead->full_address ?? ''
                ]);
            }
        });

        fclose($file);

    }, $filename, ['Content-Type'=>'text/csv']);
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

	public function config(Request $request)
	{
		$uwclogs = CollectionConfiguration::get();
        return view('admin.collection.config', compact('uwclogs'));
	}
	
	public function createconfig(Request $request)
	{
		$existingData = CollectionConfiguration::first();
        return view('admin.collection.createconfig', compact('existingData') );
	}
	
	public function store(Request $request)
	{
		$request->validate([
            'pr_off' => 'required',
            'in_off' => 'required',
            'pe_off' => 'required',
        ]);

        $data = ([
            'pr_off' => $request->pr_off,
            'in_off' => $request->in_off,
            'pe_off' => $request->pe_off,
        ]);

        $existing = CollectionConfiguration::where($data)->first();

        if ($existing) {
            // Store old data for log
            $oldData = $existing->toArray();
			return redirect()
            ->route('admin.collection.config')
            ->with('Error', 'Alreay Exists!');
            
        } else {
            // If no record exists, create one
            $created = CollectionConfiguration::create($data);
        }

        return redirect()
            ->route('admin.collection.config')
            ->with('success', 'Collection configuration saved successfully!');
	}

    public function editconfig(Request $request)
	{
		$existingData = CollectionConfiguration::where('id', $request->id)->first();
        return view('admin.collection.editconfig', compact('existingData') );
	}

    public function updateconfig(Request $request)
	{
		$request->validate([
            'pr_off' => 'required',
            'in_off' => 'required',
            'pe_off' => 'required',
        ]);

        $data = ([
            'pr_off' => $request->pr_off,
            'in_off' => $request->in_off,
            'pe_off' => $request->pe_off
        ]);

        $existing = CollectionConfiguration::where('id', $request->id)->first();

        if ($existing) {
            $existing->update($data);
        }

        return redirect()
            ->route('admin.collection.config')
            ->with('success', 'Collection configuration updated successfully!');
	}

    public function activate(Request $request, $id)
    {
        // Deactivate all
        CollectionConfiguration::query()->update(['status' => 0]);

        // Activate selected
        $collConfig = CollectionConfiguration::findOrFail($id);
        $collConfig->status = 1;
        $collConfig->save();

        return response()->json([
            'success' => true,
            'active_id' => $collConfig->id,
            'message' => 'Template activated successfully!',
        ]);
    }

    public function showPartPaymentPage($token)
    {
        $leadId = base64_decode($token);
        $today = now()->toDateString();
        $lead = DB::table('loan_applications as la')
                    ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
                    ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
                    ->leftJoin('admins as a', 'a.id', '=', 'lap.credited_by')
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
                    ->leftJoin('cashfree_enach_request_response_data as cerd', function($join) {
                        $join->on('cerd.subscription_id', '=', 'la.loan_no')
                            ->where('cerd.status', '=', 'ACTIVE');
                    })
                    ->select([
                        'lap.repay_date','lap.approval_amount','lap.loan_tenure_days','lap.repayment_amount','lap.roi','lap.cibil_score','lap.salary_date','ld.loan_disbursal_number','ld.disbursal_date','a.name as credited_by_name', 'la.id',
                        DB::raw("DATEDIFF('$today', lap.repay_date) as days_after_due"),
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
                        ) as total_dues'),
                        'cerd.reference_id'
                    ])
                    ->where('la.id', $leadId)
                    ->where('la.loan_closed_status', 'pending')
                    ->first();

        if (!$lead) {
            abort(404, 'Invalid payment link');
        }

        return view('admin.payments.part-payment', compact('lead'));
    }

    public function initiatePartPayment(Request $request)
    {
        $request->validate([
            'lead_id' => 'required',
            'amount'  => 'required|numeric|min:1',
        ]);

        $lead = LoanApplication::find($request->lead_id);
        
        if (empty($lead)) {
            return back()->with('error', 'Lead not found');
        }
        
        return app(LoanPaymentController::class)
        ->generatePartPaymentLink($request->lead_id, (int)$request->amount);
    }

    public function cleanIndianMobile($number)
    {
        $num = preg_replace('/[^0-9]/', '', $number);

        // Remove +91
        if (strlen($num) == 12 && substr($num, 0, 2) == '91') {
            $num = substr($num, 2);
        }

        // Remove leading 0
        if (strlen($num) == 11 && substr($num, 0, 1) == '0') {
            $num = substr($num, 1);
        }

        // Reject PBX / gateway junk
        if (preg_match('/^(0000|0001|0002)/', $num)) {
            return null;
        }

        // Accept ONLY real Indian mobile
        if (preg_match('/^[6-9][0-9]{9}$/', $num)) {
            return $num;
        }

        return null;
    }

    public function getDpDBucket($dpd)
    {
        if ($dpd == 0) {
            return '0';
        } elseif ($dpd >= 1 && $dpd <= 30) {
            return '1-30';
        } elseif ($dpd >= 31 && $dpd <= 60) {
            return '31-60';
        } elseif ($dpd >= 61 && $dpd <= 90) {
            return '61-90';
        } elseif ($dpd >= 91 && $dpd <= 120) {
            return '91-120';
        } elseif ($dpd >= 121 && $dpd <= 150) {
            return '121-150';
        } elseif ($dpd >= 151 && $dpd <= 180) {
            return '151-180';
        } else {
            return '>180';
        }
    }
}
