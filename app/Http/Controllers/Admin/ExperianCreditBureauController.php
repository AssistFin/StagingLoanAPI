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
        $today = now()->format('Y-m-d');

        $query = LoanApplication::query()
            ->with([
                'user:id,firstname,lastname,mobile,email,username',
                'personalDetails',
                'employmentDetails',
                'kycDetails',
                'loanDocument',
                'bankDetails',
            ])
            ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'loan_applications.id')
            ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'loan_applications.id')
            ->join('pan_data', 'pan_data.user_id', '=', 'loan_applications.user_id')
            ->join('loan_address_details', 'loan_address_details.loan_application_id', '=', 'loan_applications.id')
            ->join('aadhaar_data', 'aadhaar_data.user_id', '=', 'loan_applications.user_id')
            ->leftJoin(DB::raw('(
                SELECT loan_application_id, SUM(collection_amt) as total_paid
                FROM utr_collections
                GROUP BY loan_application_id
            ) as uc'), 'uc.loan_application_id', '=', 'loan_applications.id')
            ->whereNotIn('loan_applications.user_id', $excludedUserIds)
            ->where('loan_applications.loan_closed_status', 'pending')
            ->orderByDesc('loan_applications.user_id')
            ->select(
                'loan_applications.*',
                'lap.approval_amount as approval_amount',
                'ld.loan_disbursal_number',
                'pan_data.pan as pan',
                'pan_data.date_of_birth as date_of_birth',
                'aadhaar_data.aadhaar_number as aadhaar_number',
                'uc.total_paid as total_paid',
                'loan_address_details.house_no as house_no',
                'loan_address_details.city as city',
                'loan_address_details.state as state',
                'loan_address_details.pincode as pincode',
                'loan_address_details.address_type as address_type',

            );
        
        if ($request->has('export') && $request->export === 'csv') {
            $userRecords = $query->get();
            $csvData = [];

            foreach ($userRecords as $lead) {
                $todayDate = $today;
                //dd($lead->addressDetails);
                $loan = DB::table('loan_applications as la')
                    ->join('loan_disbursals as ld', 'ld.loan_application_id', '=', 'la.id')
                    ->join('loan_approvals as lap', 'lap.loan_application_id', '=', 'la.id')
                    ->leftJoin(DB::raw('(
                        SELECT loan_application_id, SUM(collection_amt) as total_paid
                        FROM utr_collections
                        GROUP BY loan_application_id
                    ) as uc'), 'uc.loan_application_id', '=', 'la.id')
                    ->select([
                        DB::raw("DATEDIFF('$todayDate', ld.created_at) as days_since_disbursal"),
                        DB::raw("DATEDIFF('$todayDate', lap.repay_date) as days_after_due"),
                        DB::raw('IFNULL(lap.approval_amount - uc.total_paid, lap.approval_amount) as remaining_principal'),
                        DB::raw('
                            (lap.approval_amount * lap.roi / 100 ) * DATEDIFF("' . $todayDate . '", ld.created_at) as interest'),
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

                $daysAfterDue = is_numeric($loan->days_after_due ?? null) && $loan->days_after_due > 0 ? $loan->days_after_due : 0;
                $totalDues = $loan->total_dues ?? 0;

                $full_address = $lead->house_no ? $lead->house_no.', '.$lead->city : '';
                $state = $lead->state ? $lead->state : '';
                $pin_code = $lead->pincode ? $lead->pincode : '';
                $address_type = $lead->address_type ? $lead->address_type : '';

                $asset_classification = '';
                if ($daysAfterDue <= 30) $asset_classification = 'Standard';
                elseif ($daysAfterDue <= 89) $asset_classification = 'Sub Standard';
                elseif ($daysAfterDue > 90) $asset_classification = 'NPA';

                $csvData[] = [
                    'Consumer Name' => $lead->user->firstname.' '.$lead->user->lastname,
                    'Date of Birth' => '="'.$lead->date_of_birth.'"',
                    'Gender' => $lead->gender,
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
                    'Telephone No.Residence' => '',
                    'Telephone No.Office' => '',
                    'Extension Office' => '',
                    'Telephone No.Other' => '',
                    'Extension Other' => '',
                    'Email ID 1' => $lead->user->email,
                    'Email ID 2' => '',
                    'Address 1' => $full_address,
                    'State Code 1' => $state,
                    'PIN Code 1' => '="'.$pin_code.'"',
                    'Address Category 1' =>$address_type,
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
                    'Date Opened/Disbursed' => $lead->loan_disbursal_date ? date('Y-m-d', strtotime($lead->loan_disbursal_date)) : '',
                    'Date of Last Payment' => !empty($lead->collection_date) ? date('Y-m-d', strtotime($lead->collection_date)) : '',
                    'Date Closed' => !empty($lead->loan_closed_date) ? date('Y-m-d', strtotime($lead->loan_closed_date)) : '',
                    'Date Reported' => $todayDate,
                    'High Credit/Sanctioned Amt' => $lead->approval_amount,
                    'Current Balance' => number_format($loan->remaining_principal ?? 0, 2),
                    'Amt Overdue' => $totalDues,
                    'No of Days Past Due' => $daysAfterDue,
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
                    'EMI Amount' => !empty($lead->loanApproval->repayment_amount && $lead->loanApproval->repayment_amount != 0.00) ? $lead->loanApproval->repayment_amount : 0,
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
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM for Excel UTF-8
                fputcsv($file, array_keys($csvData[0]));
                foreach ($csvData as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            };

            return Response::stream($callback, 200, $headers);
        }

        $totalRecords = $query->count();
        $userRecords = $query->paginate(25);

        return view('admin.experiancreditbureau.experian-credit-bureau', compact('pageTitle', 'userRecords', 'totalRecords'));
    }

}
