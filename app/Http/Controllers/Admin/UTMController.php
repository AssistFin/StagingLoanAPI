<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;

class UTMController extends Controller
{

    public function index(Request $request)
    {
        ini_set('memory_limit', '2048M');
        $pageTitle = 'UTM Tracking Data';

        $searchTerm = $request->get('search');
        $dateRange = $request->get('date_range');
        $utm_records = $request->get('utm_records');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $source = $request->get('source');
        $campaignId = $request->get('campaign_id');

        // Subquery: get the latest utm_tracking record per user
        $latestUtm = DB::table('utm_tracking as ut1')
            ->select('ut1.*')
            ->whereRaw('ut1.id = (SELECT MAX(ut2.id) FROM utm_tracking ut2 WHERE ut2.user_id = ut1.user_id)');

        // Main query starts with reduced utm_tracking
        $query = DB::table(DB::raw('(' . $latestUtm->toSql() . ') as utm_tracking'))
            ->mergeBindings($latestUtm)
            ->leftJoin('users', 'utm_tracking.user_id', '=', 'users.id')
            ->leftJoin('loan_applications', 'loan_applications.user_id', '=', 'users.id')
            ->leftJoin('loan_personal_details', 'loan_personal_details.loan_application_id', '=', 'loan_applications.id')
            ->leftJoin('loan_disbursals', 'loan_disbursals.loan_application_id', '=', 'loan_applications.id')
            ->leftJoin('loan_approvals', 'loan_approvals.loan_application_id', '=', 'loan_applications.id')
            ->leftJoin('loan_bank_details', 'loan_bank_details.loan_application_id', '=', 'loan_applications.id')
            ->select([
                'utm_tracking.id as utm_id',
                'utm_tracking.user_id as utm_user_id',
                'utm_tracking.utm_source',
                'utm_tracking.utm_medium',
                'utm_tracking.utm_campaign',
                'utm_tracking.landing_page',
                'utm_tracking.ip_address',
                'utm_tracking.user_agent',
                'utm_tracking.created_at as utm_created_at',

                'users.id as user_id',
                'users.firstname',
                'users.lastname',
                'users.mobile',
                'users.email',

                'loan_applications.id as loan_app_id',
                'loan_applications.loan_no',
                'loan_applications.created_at as loan_app_created_at',
                'loan_applications.current_step',

                'loan_personal_details.employment_type',
                'loan_personal_details.monthly_income',
                'loan_personal_details.income_received_in',

                'loan_approvals.cibil_score',
                'loan_approvals.final_remark',
                'loan_approvals.status as approval_status',
                'loan_approvals.approval_amount'
            ])
            ->whereRaw('loan_applications.id = (SELECT MIN(id) FROM loan_applications WHERE loan_applications.user_id = users.id)')
            ->orderBy('utm_tracking.created_at', 'desc');

        // ================= Date Filter =================
        if ($dateRange) {
            $dateColumn = ($utm_records && $utm_records === 'tca') ? 'loan_bank_details.created_at' : 'loan_applications.created_at';

            if ($dateRange === 'today') {
                $query->whereDate($dateColumn, now()->today());
            } elseif ($dateRange === 'yesterday') {
                $query->whereDate($dateColumn, now()->yesterday());
            } elseif ($dateRange === 'last_3_days') {
                $query->whereBetween($dateColumn, [now()->subDays(2)->startOfDay(), now()->endOfDay()]);
            } elseif ($dateRange === 'last_7_days') {
                $query->whereBetween($dateColumn, [now()->subDays(6)->startOfDay(), now()->endOfDay()]);
            } elseif ($dateRange === 'last_15_days') {
                $query->whereBetween($dateColumn, [now()->subDays(14)->startOfDay(), now()->endOfDay()]);
            } elseif ($dateRange === 'current_month') {
                $query->whereBetween($dateColumn, [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
            } elseif ($dateRange === 'previous_month') {
                $query->whereBetween($dateColumn, [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()]);
            } elseif ($dateRange === 'custom' && $fromDate && $toDate) {
                $query->whereBetween($dateColumn, [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()]);
            }
        }

        // ================= Filters =================
        if ($source) {
            $query->where('utm_tracking.utm_source', 'like', "%{$source}%");
        }

        if ($campaignId) {
            $query->where('utm_tracking.utm_campaign', $campaignId);
        }

        if ($searchTerm) {
            $query->where('users.mobile', 'like', "%{$searchTerm}%");
        }

        // ================= Record Types =================
        if ($utm_records) {
            if ($utm_records === 'tusr') {
                $query->whereNotNull('utm_tracking.user_id');
            } elseif ($utm_records === 'tca') {
                $state = ['loanstatus','noteligible','viewloan','loandisbursal'];
                $query->whereIn('loan_applications.current_step', $state);
            } elseif ($utm_records === 'taa') {
                $query->where('loan_approvals.status', 1);
            } elseif ($utm_records === 'tra') {
                $query->where('loan_approvals.status', 2);
            } elseif ($utm_records === 'tda') {
                $query->whereNotNull('loan_disbursals.loan_application_id');
            }
        }

        // ================= Count Total =================
        //$totalRecords = DB::table('utm_tracking')->select(DB::raw('COUNT(DISTINCT user_id) as total'))->first()->total;

        // ================= Export =================
        if ($request->has('export') && $request->export === 'csv') {
            $utmRecords = $query->get();

            $csvData = [];
            foreach ($utmRecords as $utmRecord) {
                if($utm_records && $utm_records === 'taa'){
                    $csvData[] = [
                        'Customer Name' => $utmRecord->firstname . ' ' . $utmRecord->lastname,
                        'Customer Mobile' => "'" . $utmRecord->mobile,
                        'Loan Application No' => $utmRecord->loan_no ?? '',
                        'Employment Type' => $utmRecord->employment_type ?? '',
                        'Monthly Income' => $utmRecord->monthly_income ?? '',
                        'Income Received In' => $utmRecord->income_received_in ?? '',
                        'Date' => Carbon::parse($utmRecord->loan_app_created_at)->timezone('Asia/Kolkata'),
                        'Source' => $utmRecord->utm_source ?? '',
                        'Medium' => $utmRecord->utm_medium ?? '',
                        'Campaign' => $utmRecord->utm_campaign ?? '',
                        'IP Address' => $utmRecord->ip_address ?? '',
                        'Cibil Score' => $utmRecord->cibil_score ?? '',
                        'Approval Remark' => $utmRecord->final_remark ?? '',
                    ];
                } elseif($utm_records && $utm_records === 'tra'){
                    $csvData[] = [
                        'Customer Name' => $utmRecord->firstname . ' ' . $utmRecord->lastname,
                        'Customer Mobile' => "'" . $utmRecord->mobile,
                        'Loan Application No' => $utmRecord->loan_no ?? '',
                        'Employment Type' => $utmRecord->employment_type ?? '',
                        'Monthly Income' => $utmRecord->monthly_income ?? '',
                        'Income Received In' => $utmRecord->income_received_in ?? '',
                        'Date' => Carbon::parse($utmRecord->loan_app_created_at)->timezone('Asia/Kolkata'),
                        'Source' => $utmRecord->utm_source ?? '',
                        'Medium' => $utmRecord->utm_medium ?? '',
                        'Campaign' => $utmRecord->utm_campaign ?? '',
                        'IP Address' => $utmRecord->ip_address ?? '',
                        'Rejection Remark' => $utmRecord->final_remark ?? '',
                    ];
                } else {
                    $csvData[] = [
                        'Customer Name' => $utmRecord->firstname . ' ' . $utmRecord->lastname,
                        'Customer Mobile' => "'" . $utmRecord->mobile,
                        'Loan Application No' => $utmRecord->loan_no ?? '',
                        'Loan Amount' => $utmRecord->approval_amount ?? '',
                        'Employment Type' => $utmRecord->employment_type ?? '',
                        'Monthly Income' => $utmRecord->monthly_income ?? '',
                        'Income Received In' => $utmRecord->income_received_in ?? '',
                        'Date' => Carbon::parse($utmRecord->loan_app_created_at),
                        'Source' => $utmRecord->utm_source ?? '',
                        'Medium' => $utmRecord->utm_medium ?? '',
                        'Campaign' => $utmRecord->utm_campaign ?? '',
                        'IP Address' => $utmRecord->ip_address ?? '',
                    ];
                }
            }

            $missingInfoText = $utm_records ?? 'all';
            $sourceText = $source ?? 'all';
            $dateRangeText = $dateRange ?? 'alltime';
            $timestamp = now()->format('Ymd_His');

            $filename = "{$dateRangeText}_{$sourceText}_{$missingInfoText}_utm_tracking_export_{$timestamp}.csv";

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

        // ================= Campaign IDs =================
        $campaignIdsQuery = (clone $query)->whereNotNull('utm_tracking.utm_campaign');
        if (!empty($source)) {
            $campaignIdsQuery->where('utm_tracking.utm_source', $source);
        }
        $campaignIds = $campaignIdsQuery->pluck('utm_tracking.utm_campaign')->unique()->values()->toArray();

        // ================= Pagination =================
        $utmRecords = $query->paginate(25);

        // ================= Count Total =================
        $totalRecords = (clone $query)->distinct('users.id')->count('users.id');

        return view('admin.leads.utm-details', compact('pageTitle', 'utmRecords', 'campaignIds', 'totalRecords'));
    }


    // Store UTM data for anonymous users
    public function store(Request $request)
    {
        $request->validate([
            'utm_source' => 'nullable|string|max:255',
            'utm_medium' => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
            'utm_term' => 'nullable|string|max:255',
            'utm_content' => 'nullable|string|max:255',
        ]);

        try {
            DB::table('utm_tracking')->insert([
                'session_id' => session()->getId(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'utm_source' => $request->utm_source,
                'utm_medium' => $request->utm_medium,
                'utm_campaign' => $request->utm_campaign,
                'utm_term' => $request->utm_term,
                'utm_content' => $request->utm_content,
                'landing_page' => $request->header('referer', 'direct'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('UTM tracking error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    // Link UTM data to authenticated user
    public function linkUser(Request $request)
    {
        $request->validate([
            'utm_source' => 'nullable|string|max:255',
            'utm_medium' => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
            'utm_term' => 'nullable|string|max:255',
            'utm_content' => 'nullable|string|max:255',
        ]);

        try {
            $user = Auth::user();
            $sessionId = session()->getId();

            // Check if we have existing UTM data for this session
            $existingRecord = DB::table('utm_tracking')
                ->where('session_id', $sessionId)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($existingRecord) {
                // Update existing record with user ID
                DB::table('utm_tracking')
                    ->where('id', $existingRecord->id)
                    ->update([
                        'user_id' => $user->id,
                        'updated_at' => now()
                    ]);
            } else {
                // Create new record with user ID (if UTM params exist)
                if ($request->hasAny(['utm_source', 'utm_medium', 'utm_campaign'])) {
                    DB::table('utm_tracking')->insert([
                        'session_id' => $sessionId,
                        'user_id' => $user->id,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'utm_source' => $request->utm_source,
                        'utm_medium' => $request->utm_medium,
                        'utm_campaign' => $request->utm_campaign,
                        'utm_term' => $request->utm_term,
                        'utm_content' => $request->utm_content,
                        'landing_page' => $request->header('referer', 'direct'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('UTM user linking error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    public function getCampaignIds(Request $request)
    {
        $source = $request->get('source');

        $campaignIdsQuery = DB::table('utm_tracking')
            ->whereNotNull('utm_campaign');

        if (!empty($source)) {
            $campaignIdsQuery->where('utm_source', $source);
        }

        $campaignIds = $campaignIdsQuery
            ->distinct()
            ->pluck('utm_campaign')
            ->values();

        return response()->json($campaignIds);
    }
}