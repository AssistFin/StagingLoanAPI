<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserPersonalDetails;
use Illuminate\Http\Request;

class CreditAssessmentController extends Controller
{



private function calculateEligibility($cibilScore) {
        if ($cibilScore >= 820) {
            return 10000; // Returning integer value
        } elseif ($cibilScore >= 778) {
            return 5000;
        } elseif ($cibilScore >= 765) {
            return 4000;
        } elseif ($cibilScore >= 748) {
            return 3000;
        } elseif ($cibilScore >= 723) {
            return 2000;
        } elseif ($cibilScore >= 681) {
            return 1000;
        } elseif ($cibilScore >= 650) {
            return 500;
        } else {
            return 'Rejected';
        }
    }


    public function newAssessments(Request $request)
    {
        $pageTitle = 'New Credit Assessments';
        $personalDetails = UserPersonalDetails::latest()->paginate(getPaginate());
        foreach ($personalDetails as $detail) {
            $eligibilityAmount = $this->calculateEligibility($detail->cibilscore);
            $detail->eligibilityStatus = is_numeric($eligibilityAmount) ? "Approved" : $eligibilityAmount;
        }
        return view('admin.credit_assessment.index', compact('pageTitle', 'personalDetails'));
    }

    public function completed(Request $request)
    {
        $pageTitle = 'Completed Credit Assessments';
        $assessments = UserPersonalDetails::latest()->paginate(getPaginate());
        return view('admin.credit_assessment.completed', compact('pageTitle', 'assessments'));
    }

    public function deleteDetail($id)
{
    $detail = UserPersonalDetails::find($id);
    if ($detail) {
        $detail->delete();
        return back()->with('success', 'Detail deleted successfully.');
    }
    return back()->with('error', 'Detail not found.');
}

    // Add more methods as needed for other operations
}
