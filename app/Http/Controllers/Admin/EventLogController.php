<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminEventLog;
use App\Models\Menu;
use App\Models\Admin;
use App\Models\User;
use App\Models\Submenu;
use Illuminate\Http\Request;

class EventLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminEventLog::with(['admin', 'user'])
            ->orderBy('id','desc');

        $dateRange = $request->get('date_range');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $employee = $request->get('employee');
        $customer = $request->get('customer');
        $event = $request->get('event');

        if ($dateRange) {
            if ($dateRange === 'today') {
            $query->whereDate('created_at', now()->today());
            } elseif ($dateRange === 'yesterday') {
                $query->whereDate('created_at', now()->yesterday());
            } elseif ($dateRange === 'last_3_days') {
                $query->whereBetween('created_at', [
                    now()->subDays(2)->startOfDay(),
                    now()->endOfDay()
                ]);
            } elseif ($dateRange === 'last_7_days') {
                $query->whereBetween('created_at', [
                    now()->subDays(6)->startOfDay(),
                    now()->endOfDay()
                ]);
            } elseif ($dateRange === 'last_15_days') {
                $query->whereBetween('created_at', [
                    now()->subDays(14)->startOfDay(),
                    now()->endOfDay()
                ]);
            } elseif ($dateRange === 'current_month') {
                $query->whereBetween('created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ]);
            } elseif ($dateRange === 'previous_month') {
                $query->whereBetween('created_at', [
                    Carbon::now()->subMonth()->startOfMonth(),
                    Carbon::now()->subMonth()->endOfMonth()
                ]);
            } elseif ($dateRange === 'custom' && $fromDate && $toDate) {
                $query->whereBetween('created_at', [
                    Carbon::parse($fromDate)->startOfDay(),
                    Carbon::parse($toDate)->endOfDay()
                ]);
            }
        }

        if ($employee) {
            $query->where('admin_id', $employee);
        }

        if ($customer) {
            $query->where('user_id', $customer);
        }

        if ($event) {
            $query->where(function ($q) use ($event) {
                $q->where('event', 'like', "%{$event}%");
            });
        }
        
        $eventRecords = $query->paginate(25);

        $adminData = Admin::where('name', '!=', '')->get();
        $userData = User::where('firstname', '!=', '')->get();

        return view('admin.eventlog.index', compact('eventRecords','adminData','userData'));
    }
}