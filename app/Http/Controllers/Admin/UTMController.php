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
        $pageTitle = 'UTM Tracking Data';
        
        $query = DB::table('utm_tracking')
            ->leftJoin('users', 'utm_tracking.user_id', '=', 'users.id')
            ->leftJoin('loan_applications', 'loan_applications.user_id', '=', 'users.id')
            ->leftJoin('loan_personal_details', 'loan_personal_details.loan_application_id', '=', 'loan_applications.id')
            ->select(
                'utm_tracking.*',
                'users.firstname',
                'users.lastname',
                'users.mobile',
                'users.email',
                'loan_applications.id',
                'loan_applications.loan_no',
                'loan_personal_details.employment_type',
                'loan_personal_details.monthly_income',
                'loan_personal_details.income_received_in'
            )->orderByRaw('utm_tracking.created_at DESC');
        
        $searchTerm = $request->get('search');
        $dateRange = $request->get('date_range');
        $utm_records = $request->get('utm_records');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        // Filter by UTM Records
        if ($utm_records) {
            if ($utm_records === 'tusr') {
                $query->where('utm_tracking.user_id' , '!=', NULL);
            }
        }

        // Filter by Date Range
        if ($dateRange) {
            if ($dateRange === 'today') {
                $query->whereDate('utm_tracking.created_at', now()->today());
            } elseif ($dateRange === 'yesterday') {
                $query->whereDate('utm_tracking.created_at', now()->yesterday());
            } elseif ($dateRange === 'last_3_days') {
                $query->whereBetween('utm_tracking.created_at', [now()->subDays(3), now()]);
            } elseif ($dateRange === 'last_7_days') {
                $query->whereBetween('utm_tracking.created_at', [now()->subDays(7), now()]);
            } elseif ($dateRange === 'last_15_days') {
                $query->whereBetween('utm_tracking.created_at', [now()->subDays(15), now()]);
            } elseif ($dateRange === 'current_month') {
                $query->whereBetween('utm_tracking.created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
            } elseif ($dateRange === 'previous_month') {
                $query->whereBetween('utm_tracking.created_at', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()]);
            } elseif ($dateRange === 'custom' && $fromDate && $toDate) {
                $query->whereBetween('utm_tracking.created_at', [$fromDate, $toDate]);
            }
        }

        // Search functionality
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('users.mobile', 'like', "%{$searchTerm}%");
            });
        }

        // Clone the query to get the total count before pagination
        $totalRecordsQuery = clone $query;
        $totalRecords = $totalRecordsQuery->count();

       if ($request->has('export') && $request->export === 'csv') {
            $utmRecords = $query->get();

            $csvData = [];
            //echo '<pre>';
            //print_r($utmRecords);
            //dd();
            foreach ($utmRecords as $utmRecord) {
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

                ];
            }

            $missingInfoText = $utm_records ?? 'all';
            $dateRangeText = $dateRange ?? 'alltime';
            $timestamp = now()->format('Ymd_His');

            $filename = "{$dateRangeText}_{$missingInfoText}_utm_tracking_export_{$timestamp}.csv";

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

        $utmRecords = $query->paginate(25);

        return view('admin.leads.utm-details', compact('pageTitle', 'utmRecords', 'totalRecords'));
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
}