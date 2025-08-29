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
        
        $query = DB::table('utm_tracking')
            ->leftJoin('users', 'utm_tracking.user_id', '=', 'users.id')
            ->leftJoin('loan_applications', 'loan_applications.user_id', '=', 'users.id')
            ->leftJoin('loan_personal_details', 'loan_personal_details.loan_application_id', '=', 'loan_applications.id')
            ->leftJoin('loan_disbursals', 'loan_disbursals.loan_application_id', '=', 'loan_applications.id')
            ->leftJoin('loan_approvals', 'loan_approvals.loan_application_id', '=', 'loan_applications.id')
            ->leftJoin('loan_bank_details', 'loan_bank_details.loan_application_id', '=', 'loan_applications.id')
            ->select([
                DB::raw('ANY_VALUE(utm_tracking.id) as utm_id'),
                'utm_tracking.user_id as utm_user_id',
                DB::raw('ANY_VALUE(utm_tracking.utm_source) as utm_source'),
                DB::raw('ANY_VALUE(utm_tracking.utm_medium) as utm_medium'),
                DB::raw('ANY_VALUE(utm_tracking.utm_campaign) as utm_campaign'),
                DB::raw('ANY_VALUE(utm_tracking.landing_page) as landing_page'),
                DB::raw('ANY_VALUE(utm_tracking.ip_address) as ip_address'),
                DB::raw('ANY_VALUE(utm_tracking.user_agent) as user_agent'),
                DB::raw('ANY_VALUE(utm_tracking.created_at) as utm_created_at'),

                DB::raw('ANY_VALUE(users.id) as user_id'),
                DB::raw('ANY_VALUE(users.firstname) as firstname'),
                DB::raw('ANY_VALUE(users.lastname) as lastname'),
                DB::raw('ANY_VALUE(users.mobile) as mobile'),
                DB::raw('ANY_VALUE(users.email) as email'),

                DB::raw('ANY_VALUE(loan_applications.id) as loan_app_id'),
                DB::raw('ANY_VALUE(loan_applications.loan_no) as loan_no'),

                DB::raw('ANY_VALUE(loan_personal_details.employment_type) as employment_type'),
                DB::raw('ANY_VALUE(loan_personal_details.monthly_income) as monthly_income'),
                DB::raw('ANY_VALUE(loan_personal_details.income_received_in) as income_received_in'),

                DB::raw('ANY_VALUE(loan_approvals.cibil_score) as cibil_score'),
                DB::raw('ANY_VALUE(loan_approvals.final_remark) as final_remark'),
            ])
            ->groupBy('utm_tracking.user_id')
            ->orderBy(DB::raw('MAX(utm_tracking.created_at)'), 'desc');
        
        $searchTerm = $request->get('search');
        $dateRange = $request->get('date_range');
        $utm_records = $request->get('utm_records');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $source = $request->get('source');
        $campaignId = $request->get('campaign_id');

        // Filter by Date Range
        if ($dateRange) {
            if($utm_records && $utm_records === 'tca'){
                if ($dateRange === 'today') {
                $query->whereDate('loan_bank_details.created_at', now()->today());
                } elseif ($dateRange === 'yesterday') {
                    $query->whereDate('loan_bank_details.created_at', now()->yesterday());
                } elseif ($dateRange === 'last_3_days') {
                    $query->whereBetween('loan_bank_details.created_at', [
                        now()->subDays(2)->startOfDay(),
                        now()->endOfDay()
                    ]);
                } elseif ($dateRange === 'last_7_days') {
                    $query->whereBetween('loan_bank_details.created_at', [
                        now()->subDays(6)->startOfDay(),
                        now()->endOfDay()
                    ]);
                } elseif ($dateRange === 'last_15_days') {
                    $query->whereBetween('loan_bank_details.created_at', [
                        now()->subDays(14)->startOfDay(),
                        now()->endOfDay()
                    ]);
                } elseif ($dateRange === 'current_month') {
                    $query->whereBetween('loan_bank_details.created_at', [
                        Carbon::now()->startOfMonth(),
                        Carbon::now()->endOfMonth()
                    ]);
                } elseif ($dateRange === 'previous_month') {
                    $query->whereBetween('loan_bank_details.created_at', [
                        Carbon::now()->subMonth()->startOfMonth(),
                        Carbon::now()->subMonth()->endOfMonth()
                    ]);
                } elseif ($dateRange === 'custom' && $fromDate && $toDate) {
                    $query->whereBetween('loan_bank_details.created_at', [
                        Carbon::parse($fromDate)->startOfDay(),
                        Carbon::parse($toDate)->endOfDay()
                    ]);
                }
            }else{
                if ($dateRange === 'today') {
                    $query->whereDate('utm_tracking.created_at', now()->today());
                } elseif ($dateRange === 'yesterday') {
                    $query->whereDate('utm_tracking.created_at', now()->yesterday());
                } elseif ($dateRange === 'last_3_days') {
                    $query->whereBetween('utm_tracking.created_at', [
                        now()->subDays(2)->startOfDay(),
                        now()->endOfDay()
                    ]);
                } elseif ($dateRange === 'last_7_days') {
                    $query->whereBetween('utm_tracking.created_at', [
                        now()->subDays(6)->startOfDay(),
                        now()->endOfDay()
                    ]);
                } elseif ($dateRange === 'last_15_days') {
                    $query->whereBetween('utm_tracking.created_at', [
                        now()->subDays(14)->startOfDay(),
                        now()->endOfDay()
                    ]);
                } elseif ($dateRange === 'current_month') {
                    $query->whereBetween('utm_tracking.created_at', [
                        Carbon::now()->startOfMonth(),
                        Carbon::now()->endOfMonth()
                    ]);
                } elseif ($dateRange === 'previous_month') {
                    $query->whereBetween('utm_tracking.created_at', [
                        Carbon::now()->subMonth()->startOfMonth(),
                        Carbon::now()->subMonth()->endOfMonth()
                    ]);
                } elseif ($dateRange === 'custom' && $fromDate && $toDate) {
                    $query->whereBetween('utm_tracking.created_at', [
                        Carbon::parse($fromDate)->startOfDay(),
                        Carbon::parse($toDate)->endOfDay()
                    ]);
                }
            }

        }

        if ($source) {
            $query->where(function ($q) use ($source) {
                $q->where('utm_tracking.utm_source', 'like', "%{$source}%");
            });
        }

        if ($campaignId) {
            $query->where('utm_tracking.utm_campaign', $campaignId);
        }

        // Search functionality
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('users.mobile', 'like', "%{$searchTerm}%");
            });
        }
        
        //echo 'test 1 - '.$totalRecords;
        if ($utm_records) {
            $loanAppIds = (clone $query)->pluck('loan_app_id');
            $loanAppIds = array_filter($loanAppIds->toArray(), function ($id) {
                return !is_null($id);
            });
            $loanAppIds = array_values($loanAppIds);

            if ($utm_records === 'tusr') {
                $query->where('utm_tracking.user_id' , '!=', NULL);

            }else if ($utm_records === 'tca') {
                
                $state = ['loanstatus','noteligible','viewloan','loandisbursal'];
                $query->whereIn('loan_applications.id', $loanAppIds)->whereIn('loan_applications.current_step', $state);

            }else if ($utm_records === 'taa') {
                
                $query->whereIn('loan_approvals.loan_application_id', $loanAppIds)->where('loan_approvals.status', 1); 

            }else if ($utm_records === 'tra') {
                
                $query->whereIn('loan_approvals.loan_application_id', $loanAppIds)->where('loan_approvals.status', 2);

            }else if ($utm_records === 'tda') {
                
                $query->whereIn('loan_disbursals.loan_application_id', $loanAppIds); 
            }
        }

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
                        'Date' => \Carbon\Carbon::parse($utmRecord->created_at)->timezone('Asia/Kolkata'),
                        'Source' => $utmRecord->utm_source ?? '',
                        'Medium' => $utmRecord->utm_medium ?? '',
                        'Campaign' => $utmRecord->utm_campaign ?? '',
                        'Term' => $utmRecord->utm_term ?? '',
                        'Content' => $utmRecord->utm_content ?? '',
                        'IP Address' => $utmRecord->ip_address ?? '',
                        'Cibil Score' => $utmRecord->cibil_score ?? '',
                        'Approval Remark' => $utmRecord->final_remark ?? '',
                    ];
                }else if($utm_records && $utm_records === 'tra'){
                    $csvData[] = [
                        'Customer Name' => $utmRecord->firstname . ' ' . $utmRecord->lastname,
                        'Customer Mobile' => "'" . $utmRecord->mobile,
                        'Loan Application No' => $utmRecord->loan_no ?? '',
                        'Employment Type' => $utmRecord->employment_type ?? '',
                        'Monthly Income' => $utmRecord->monthly_income ?? '',
                        'Income Received In' => $utmRecord->income_received_in ?? '',
                        'Date' => \Carbon\Carbon::parse($utmRecord->created_at)->timezone('Asia/Kolkata'),
                        'Source' => $utmRecord->utm_source ?? '',
                        'Medium' => $utmRecord->utm_medium ?? '',
                        'Campaign' => $utmRecord->utm_campaign ?? '',
                        'Term' => $utmRecord->utm_term ?? '',
                        'Content' => $utmRecord->utm_content ?? '',
                        'IP Address' => $utmRecord->ip_address ?? '',
                        'Rejection Remark' => $utmRecord->final_remark ?? '',
                    ];
                }else{
                    $csvData[] = [
                        'Customer Name' => $utmRecord->firstname . ' ' . $utmRecord->lastname,
                        'Customer Mobile' => "'" . $utmRecord->mobile,
                        'Loan Application No' => $utmRecord->loan_no ?? '',
                        'Employment Type' => $utmRecord->employment_type ?? '',
                        'Monthly Income' => $utmRecord->monthly_income ?? '',
                        'Income Received In' => $utmRecord->income_received_in ?? '',
                        'Date' => \Carbon\Carbon::parse($utmRecord->utm_created_at),
                        'Source' => $utmRecord->utm_source ?? '',
                        'Medium' => $utmRecord->utm_medium ?? '',
                        'Campaign' => $utmRecord->utm_campaign ?? '',
                        'Term' => $utmRecord->utm_term ?? '',
                        'Content' => $utmRecord->utm_content ?? '',
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
        //echo 'test 3 - '.$totalRecords;
        $campaignIdsQuery = (clone $query)
            ->whereNotNull('utm_tracking.utm_campaign');

        if (!empty($source)) {
            // If source is selected, filter by it
            $campaignIdsQuery->where('utm_tracking.utm_source', $source);
        }

        $campaignIds = $campaignIdsQuery
            ->pluck('utm_tracking.utm_campaign')
            ->unique()
            ->values()
            ->toArray();
        $utmRecords = $query->paginate(25);

        return view('admin.leads.utm-details', compact('pageTitle', 'utmRecords','campaignIds'));
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