<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\LoanApplication;
use App\Http\Controllers\Controller;

class CollectionController extends Controller
{
    public function collectionPredue(Request $request)
    {
        $today = now();

        $query = LoanApplication::whereHas('loanDisbursal') 
            ->where('status', '!=', 'closed')           
            ->whereHas('loanApproval', function ($query) use ($today) {
                $query->whereDate('repay_date', '>', $today); 
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
            ])->orderByRaw('created_at DESC');

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

        return view('admin.collection.collection-predue', compact('leads'));
    }

    public function collectionOverdue(Request $request)
    {
        $today = now();

        $query = LoanApplication::whereHas('loanDisbursal') 
            ->where('status', '!=', 'closed')           
            ->whereHas('loanApproval', function ($query) use ($today) {
                $query->whereDate('repay_date', '<', $today); 
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
            ])->orderByRaw('created_at DESC');

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

        return view('admin.collection.collection-overdue', compact('leads'));
    }

}
