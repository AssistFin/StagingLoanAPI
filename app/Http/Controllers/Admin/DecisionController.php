<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\LoanApplication;
use App\Http\Controllers\Controller;

class DecisionController extends Controller
{
    public function decisionApproved(Request $request)
    {
        $query = LoanApplication::with([
            'user',
            'personalDetails', 
            'employmentDetails', 
            'kycDetails', 
            'loanDocument',
            'addressDetails', 
            'bankDetails'
        ])->where('admin_approval_status', 'approved')
          ->where('loan_disbursal_status', '!=', 'disbursed')
          ->where('loan_closed_status', '!=', 'closed')
          ->orderByRaw('created_at DESC');

          $searchTerm = $request->get('search');
            
            if ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereHas('user', function ($userQuery) use ($searchTerm) {
                        $userQuery->where('firstname', 'like', "%{$searchTerm}%")
                            ->orWhere('email', 'like', "%{$searchTerm}%")
                            ->orWhere('mobile', 'like', "%{$searchTerm}%");
                    })
                    ->orWhere('loan_no', 'like', "%{$searchTerm}%");
                });
            }
        $leads = $query->paginate(25);

        return view('admin.decision.decision-approved', compact('leads'));
    }

    public function decisionDisbursed(Request $request)
    {
        $query = LoanApplication::with([
            'user',
            'personalDetails', 
            'employmentDetails', 
            'kycDetails', 
            'loanDocument',
            'addressDetails', 
            'bankDetails'
        ])->where('loan_disbursal_status', 'disbursed')
          ->orderByRaw('created_at DESC');

        $searchTerm = $request->get('search');
            
            if ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereHas('user', function ($userQuery) use ($searchTerm) {
                        $userQuery->where('firstname', 'like', "%{$searchTerm}%")
                            ->orWhere('email', 'like', "%{$searchTerm}%")
                            ->orWhere('mobile', 'like', "%{$searchTerm}%");
                    })
                    ->orWhere('loan_no', 'like', "%{$searchTerm}%");
                });
            }
        $leads = $query->paginate(25);

        return view('admin.decision.decision-disbursed', compact('leads'));
    }

    public function decisionRejected(Request $request)
    {
        $query = LoanApplication::with([
            'user',
            'personalDetails', 
            'employmentDetails', 
            'kycDetails', 
            'loanDocument',
            'addressDetails', 
            'bankDetails'
        ])->where('admin_approval_status', 'rejected')
          ->orderByRaw('created_at DESC');

        $searchTerm = $request->get('search');
            
            if ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereHas('user', function ($userQuery) use ($searchTerm) {
                        $userQuery->where('firstname', 'like', "%{$searchTerm}%")
                            ->orWhere('email', 'like', "%{$searchTerm}%")
                            ->orWhere('mobile', 'like', "%{$searchTerm}%");
                    })
                    ->orWhere('loan_no', 'like', "%{$searchTerm}%");
                });
            }
        $leads = $query->paginate(25);

        return view('admin.decision.decision-rejected', compact('leads'));
    }

    public function decisionClosed(Request $request)
    {
        $query = LoanApplication::with([
            'user',
            'personalDetails', 
            'employmentDetails', 
            'kycDetails', 
            'loanDocument',
            'addressDetails', 
            'bankDetails'
        ])->where('loan_closed_status', 'closed')
          ->orderByRaw('created_at DESC');

        $searchTerm = $request->get('search');
            
            if ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereHas('user', function ($userQuery) use ($searchTerm) {
                        $userQuery->where('firstname', 'like', "%{$searchTerm}%")
                            ->orWhere('email', 'like', "%{$searchTerm}%")
                            ->orWhere('mobile', 'like', "%{$searchTerm}%");
                    })
                    ->orWhere('loan_no', 'like', "%{$searchTerm}%");
                });
            }
        $leads = $query->paginate(25);

        return view('admin.decision.decision-closed', compact('leads'));
    }
}
