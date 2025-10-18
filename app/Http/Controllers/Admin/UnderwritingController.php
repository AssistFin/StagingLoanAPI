<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnderwritingConfig;
use App\Models\UnderwritingConfigChangeLog;
use App\Models\Role;
use App\Models\Menu;
use App\Models\Submenu;
use Illuminate\Http\Request;

class UnderwritingController extends Controller
{
    public function index()
    {
        $uwclogs = UnderwritingConfigChangeLog::with('admin')->get();
        return view('admin.underwriting.index', compact('uwclogs'));
    }

    public function create()
    {
        $existingData = UnderwritingConfig::first();
        return view('admin.underwriting.create', compact('existingData') );
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required',
            'average_salary' => 'required',
            'min_balance' => 'required',
            'avg_balance' => 'required',
            'bank_score' => 'required',
            'bounce_1_month' => 'required',
            'bounce_3_month' => 'required',
            'bureau_score' => 'required',
            'dpd_30' => 'required',
            'dpd_30_amt' => 'required',
            'dpd_90' => 'required',
            'dpd_90_amt' => 'required',
            'experience_unsecured' => 'required',
            'leverage' => 'required',
            'exposure_on_salary' => 'required',
            'maxLoanAmt' => 'required',
            'remark' => 'required',
        ]);

        $data = ([
            'avgSalary' => $request->average_salary,
            'minBalance' => $request->min_balance,
            'avgBalance' => $request->avg_balance,
            'bankScore' => $request->bank_score,
            'bounceLast1Month' => $request->bounce_1_month,
            'bounceLast3Month' => $request->bounce_3_month,
            'bureauScore' => $request->bureau_score,
            'dpdLast30Days' => $request->dpd_30,
            'dpdamtLast30Days' => $request->dpd_30_amt,
            'dpdLast90Days' => $request->dpd_90,
            'dpdamtLast90Days' => $request->dpd_90_amt,
            'expUnsecureLoan' => $request->experience_unsecured,
            'leverage' => $request->leverage,
            'exposureOnSalary' => $request->exposure_on_salary,
            'maxLoanAmt' => $request->maxLoanAmt,
        ]);

        $existing = UnderwritingConfig::first();

        if ($existing) {
            // Store old data for log
            $oldData = $existing->toArray();

            // Update the existing record
            $existing->update($data);

            // Save change log
            UnderwritingConfigChangeLog::create([
                'admin_id' => $request->client_id,
                'old_value' => json_encode($oldData),
                'new_value' => json_encode($data),
                'remark' => $request->remark,
            ]);
        } else {
            // If no record exists, create one
            $created = UnderwritingConfig::create($data);

            // Log initial creation (optional)
            UnderwritingConfigChangeLog::create([
                'admin_id' => $request->client_id,
                'old_value' => json_encode([]),
                'new_value' => json_encode($data),
                'remark' => 'Initial configuration created',
            ]);
        }

        return redirect()
            ->route('admin.underwriting.index')
            ->with('success', 'Underwriting configuration saved successfully!');
    }
}