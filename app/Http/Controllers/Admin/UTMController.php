<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class UTMController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'UTM Tracking Data';
        
        $utmRecords = DB::table('utm_tracking')
            ->leftJoin('users', 'utm_tracking.user_id', '=', 'users.id')
            ->select(
                'utm_tracking.*',
                'users.firstname',
                'users.lastname',
                'users.mobile',
                'users.email'
            )
            ->when($request->search, function($query) use ($request) {
                $query->where(function($q) use ($request) {
                    $q->where('utm_source', 'like', "%{$request->search}%")
                    ->orWhere('utm_medium', 'like', "%{$request->search}%")
                    ->orWhere('utm_campaign', 'like', "%{$request->search}%")
                    ->orWhere('users.mobile', 'like', "%{$request->search}%")
                    ->orWhere('users.email', 'like', "%{$request->search}%");
                });
            })
            ->orderBy('utm_tracking.created_at', 'desc')
            ->paginate(getPaginate());

        return view('admin.leads.utm-details', compact('pageTitle', 'utmRecords'));
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