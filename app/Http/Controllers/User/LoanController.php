<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Models\AdminNotification;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanPlan;
use App\Models\Transaction;
use Illuminate\Http\Request;

class LoanController extends Controller {
    public function list() {
        $pageTitle = 'My Loans';
        $loans     = Loan::where('user_id', auth()->id())->with('nextInstallment')->with('plan')->searchable(['loan_number'])->filter(['status'])->orderBy('id', 'desc')->paginate(getPaginate());
        return view($this->activeTemplate . 'user.loan.list', compact('pageTitle', 'loans'));
    }

    public function plans() {
        $pageTitle = 'Loan Plans';
        $categories = Category::where('Status', Status::ENABLE)->with('plans')->whereHas('plans', function ($query) {
            $query->where('status', Status::ENABLE);
        })->latest()->get();
        return view($this->activeTemplate . 'user.loan.plans', compact('pageTitle', 'categories'));
    }

    public function applyLoan(Request $request, $id) {
        $plan = LoanPlan::active()->findOrFail($id);
        $request->validate(['amount' => "required|numeric|min:$plan->minimum_amount|max:$plan->maximum_amount"]);
        session()->put('loan', ['plan' => $plan, 'amount' => $request->amount]);
        return to_route('user.loan.apply.form');
    }

    public function loanPreview() {
        $loan = session('loan');
        if (!$loan) {
            return to_route('user.loan.plans');
        }
        $plan      = $loan['plan'];
        $amount    = $loan['amount'];
        $pageTitle = 'Apply For Loan';
        return view($this->activeTemplate . 'user.loan.form', compact('pageTitle', 'plan', 'amount'));
    }

    public function confirm(Request $request) {
        $loan = session('loan');
        if (!$loan) {
            return to_route('user.loan.plans');
        }
        $plan   = $loan['plan'];
        $amount = $loan['amount'];
        $user            = auth()->user();

        $percentCharge = $amount * $plan->application_percent_charge / 100;
        $applicationFee = $plan->application_fixed_charge + $percentCharge;

        if ($applicationFee > $user->balance) {
            $notify[] = ['error', 'Insufficient balance. You have to pay the application fee.'];
            return back()->withNotify($notify)->withInput($request->all());
        }

        $plan   = LoanPlan::active()->with('category')->where('id', $plan->id)->firstOrFail();

        $formData       = $plan->form->form_data;
        $formProcessor  = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $request->validate($validationRule);
        $applicationForm = $formProcessor->processFormData($request, $formData);


        $perInstallment = $amount * $plan->per_installment / 100;

        $percentCharge = $plan->per_installment * $plan->percent_charge / 100;
        $charge        = $plan->fixed_charge + $percentCharge;

        $user->balance -=  $applicationFee;
        $user->save();

        $applicationTrx = getTrx();

        $loan                         = new Loan();
        $loan->loan_number            =  $applicationTrx;
        $loan->user_id                = $user->id;
        $loan->plan_id                = $plan->id;
        $loan->amount                 = $amount;
        $loan->per_installment        = $perInstallment;
        $loan->installment_interval   = $plan->installment_interval;
        $loan->delay_value            = $plan->delay_value;
        $loan->charge_per_installment = $charge;
        $loan->total_installment      = $plan->total_installment;
        $loan->application_form       = $applicationForm;
        $loan->save();

        //transaction
        $general = gs();
        $transaction = new Transaction();
        $transaction->user_id      = $user->id;
        $transaction->amount       =  $applicationFee;
        $transaction->post_balance = $user->balance;
        $transaction->charge       = 0;
        $transaction->trx_type     = '-';
        $transaction->details      = $general->cur_sym . showAmount($amount) . ' '   . 'Charged for application fee ' . $plan->name;
        $transaction->trx          = $applicationTrx;
        $transaction->remark       = 'application_fee';
        $transaction->save();

        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = $user->id;
        $adminNotification->title     = 'New loan request';
        $adminNotification->click_url = urlPath('admin.loan.index') . '?search=' . $loan->loan_number;
        $adminNotification->save();

        session()->forget('loan');

        $notify[] = ['success', 'Loan application submitted successfully'];
        return to_route('user.loan.list')->withNotify($notify);
    }

    public function installments($loanNumber) {
        $loan         = Loan::where('loan_number', $loanNumber)->where('user_id', auth()->id())->firstOrFail();
        $installments = $loan->installments()->paginate(getPaginate());
        $pageTitle    = 'Loan Installments';
        return view($this->activeTemplate . 'user.loan.installments', compact('pageTitle', 'installments', 'loan'));
    }
}
