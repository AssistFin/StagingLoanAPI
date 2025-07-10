<?php

namespace App\Http\Controllers\admin;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Models\LoanApplication;
use Illuminate\Support\Facades\DB;

class ExperianCreditBureauController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Experian Credit Bureau Report';
        $excludedUserIds = ['591','592','593','594','595','601','697','1003','1379','1680'];
        $query = LoanApplication::with(['user:id,firstname,lastname,mobile,email,username','personalDetails','employmentDetails', 'kycDetails', 'loanDocument', 'addressDetails', 'bankDetails','loanApproval','loanDisbursal'])
        ->join('pan_data', 'pan_data.user_id', '=', 'loan_applications.user_id')
        ->join('aadhaar_data', 'aadhaar_data.user_id', '=', 'loan_applications.user_id')
        ->whereNotIn('loan_applications.user_id', $excludedUserIds)
        ->whereHas('loanDisbursal')
        ->orderByDesc('loan_applications.user_id');
        //Only disbursed loan data will be show and export

        if ($request->has('export') && $request->export === 'csv') {
            $userRecords = $query->get();

            $csvData = [];

            foreach ($userRecords as $lead) {
                // Default amount value
                $today = now()->format('Y-m-d');
                //$current_balance = remaining principal due from history if closed then 0
                //$amt_overdue = total due from history if closed then 0
                //$actual_payment_amt = total due from history if closed then 0
                //$asset_classification = if DPD <= 30 days then Standard, DPD > 30 && DPD <= 89 ten Sub Standard, DPD <= 90 then NPA (Day Pass Due)
                //Total Collection Amount = $settlement_amt
                $todayDate = Carbon::today()->toDateString();

                $loans = DB::table('loan_applications as la')
                ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
                ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
                ->leftJoin(DB::raw('(SELECT loan_application_id, SUM(collection_amt) as total_paid FROM utr_collections GROUP BY loan_application_id) as uc'), 'uc.loan_application_id', '=', 'la.id')
                ->select([
                    'la.loan_no',
                    'ld.loan_disbursal_number',
                    'lap.approval_amount',
                    DB::raw("DATEDIFF('$todayDate', ld.created_at) as days_since_disbursal"),
                    DB::raw("DATEDIFF('$todayDate', lap.repay_date) as days_after_due"),
                    DB::raw('IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount) as remaining_principal'),
                    DB::raw('(lap.approval_amount * lap.roi / 100 ) * DATEDIFF("' . $todayDate . '", ld.created_at) as interest'),
                    DB::raw('
                        IF(DATEDIFF("' . $todayDate . '", lap.repay_date) > 0,
                            (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $todayDate . '", lap.repay_date),
                            0
                        ) as penal_interest'),
                    DB::raw('
                        (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) +
                        ((lap.approval_amount * lap.roi / 100 ) * DATEDIFF("' . $todayDate . '", ld.created_at)) +
                        IF(DATEDIFF("' . $todayDate . '", lap.repay_date) > 0,
                            (IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount)) * 0.0025 * DATEDIFF("' . $todayDate . '", lap.repay_date),
                            0
                        ) as total_dues')
                ])
                ->where('la.id', $lead->id)
                ->where('la.loan_closed_status', 'pending')
                ->first();

                $totalDues = (!empty($loans->total_dues)) ? (int)$loans->total_dues : 0;
                $totalDaysAfterDue = !empty($loans->days_after_due) ? $loans->days_after_due : 0;
                
                if(!empty($totalDaysAfterDue) && $totalDaysAfterDue <= 30){ 
                    $asset_classification = 'Standard'; 
                }elseif(!empty($totalDaysAfterDue) && ($totalDaysAfterDue > 30 && $totalDaysAfterDue <= 89)){ 
                    $asset_classification = 'Sub Standard'; 
                }elseif(!empty($totalDaysAfterDue) && $totalDaysAfterDue <= 90){
                    $asset_classification = 'NPA'; 
                }else{ $asset_classification = '';}

                $csvData[] = [
                    'Consumer Name' =>  $lead->user->firstname.' '.$lead->user->lastname,
                    'Date of Birth' =>  '="'.$lead->date_of_birth.'"',
                    'Gender' =>         $lead->gender,
                    'Income Tax ID Number' => '="'.$lead->pan.'"',
                    'Passport Number' => '',
                    'Passport Issue Date' => '',
                    'Passport Expiry Date' => '',
                    'Voter ID Number' => '',
                    'Driving License Number' => '',
                    'Driving License Issue Date' => '',
                    'Driving License Expiry Date' => '',
                    'Ration Card Number' => '',
                    'Universal ID Number' => '="'.$lead->aadhaar_number.'"',
                    'Additional ID #1' => '',
                    'Additional ID #2' => '',
                    'Telephone No.Mobile' => '="'.$lead->user->mobile.'"',
                    'Telephone No.Residence' => '="'.$lead->contact_number.'"',
                    'Telephone No.Office' => '',
                    'Extension Office' => '',
                    'Telephone No.Other' => '',
                    'Extension Other' => '',
                    'Email ID 1' => $lead->user->email,
                    'Email ID 2' => '',
                    'Address 1' => $lead->full_address,
                    'State Code 1' => $lead->state,
                    'PIN Code 1' => '="'.$lead->pin_code.'"',
                    'Address Category 1' =>$lead->address_type,
                    'Residence Code 1' => '',
                    'Address 2' => '',
                    'State Code 2' => '',
                    'PIN Code 2' => '',
                    'Address Category 2' => '',
                    'Residence Code 2' => '',
                    'Current/New Member Code' => $lead->user_id,
                    'Current/New Member Short Name' => $lead->user->username,
                    'Curr/New Account No' => $lead->loan_no,
                    'Account Type' => 'STPL',
                    'Ownership Indicator' => '',
                    'Date Opened/Disbursed' => !empty($lead->loan_disbursal_date) ? date('Y-m-d', strtotime($lead->loan_disbursal_date)) : '',
                    'Date of Last Payment' => !empty($lead->collection_date) ? date('Y-m-d', strtotime($lead->collection_date)) : '',
                    'Date Closed' => !empty($lead->loan_closed_date) ? date('Y-m-d', strtotime($lead->loan_closed_date)) : '',
                    'Date Reported' => $today,
                    'High Credit/Sanctioned Amt' => $lead->approval_amount,
                    'Current Balance' => !empty($loans->remaining_principal) ? number_format($loans->remaining_principal, 2) : 0,
                    'Amt Overdue' => $totalDues,
                    'No of Days Past Due' => $totalDaysAfterDue,
                    'Old Mbr Code' => '',
                    'Old Mbr Short Name' => '',
                    'Old Acc No' => '',
                    'Old Acc Type' => '',
                    'Old Ownership Indicator' => '',
                    'Suit Filed / Wilful Default' => '',
                    'Credit Facility Status' => '',
                    'Asset Classification' => $asset_classification,
                    'Value of Collateral' => '',
                    'Type of Collateral' => '',
                    'Credit Limit' => $lead->approval_amount,
                    'Cash Limit' => $lead->approval_amount,
                    'Rate of Interest' => '1%',
                    'RepaymentTenure' => !empty($lead->loanApproval->loan_tenure_days) ? $lead->loanApproval->loan_tenure_days : 0,
                    'EMI Amount' => !empty($lead->loanApproval->repayment_amount) ? $lead->loanApproval->repayment_amount : 0,
                    'Written- off Amount (Total)' => '',
                    'Written- off Principal Amount' => '',
                    'Settlement Amt' => !empty($loans->total_paid) ? $loans->total_paid : 0,
                    'Payment Frequency' => 'Monthly',
                    'Actual Payment Amt' => $totalDues,
                    'Occupation Code' => !empty($lead->personalDetails->employment_type) ? $lead->personalDetails->employment_type : 0,
                    'Income' => !empty($lead->loanApproval->monthly_income) ? number_format($lead->loanApproval->monthly_income, 0) : 0,
                    'Net/Gross Income Indicator' => !empty($lead->loanApproval->monthly_income) ? number_format($lead->loanApproval->monthly_income, 0) : 0,
                    'Monthly/Annual Income Indicator' => !empty($lead->loanApproval->monthly_income) ? number_format($lead->loanApproval->monthly_income, 0) : 0,
                    'CKYC' => '',
                    'NREGA Card Number' => ''
                ];
            }

            $timestamp = now()->format('Ymd_His');

            $filename = "experian_cb_report_export_{$timestamp}.csv";

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function () use ($csvData) {
                $file = fopen('php://output', 'w');

                // Add UTF-8 BOM
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

                // Header
                fputcsv($file, array_keys($csvData[0]), ','); // or ';'

                foreach ($csvData as $row) {
                    fputcsv($file, $row, ','); // or ';'
                }

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);
        }
        $userRecords = $query->paginate(25);

        return view('admin.experiancreditbureau.experian-credit-bureau', compact('pageTitle', 'userRecords'));
    }
}
