<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminEventLog;
use App\Models\Menu;
use App\Models\Submenu;
use Illuminate\Http\Request;

class EventLogController extends Controller
{
    public function index()
    {
        $userRecords = AdminEventLog::with(['admin', 'user'])->latest()->paginate(25);
        return view('admin.eventlog.index', compact('userRecords'));
    }
}