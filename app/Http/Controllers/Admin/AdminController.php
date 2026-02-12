<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\CurlRequest;
use App\Models\AdminNotification;
use App\Models\Deposit;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserLogin;
use App\Models\Withdrawal;
use App\Rules\FileTypeValidate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\Menu;
use App\Models\Submenu;
use App\Models\Admin;
use App\Models\LoanApplication;

class AdminController extends Controller
{

    public function dashboard(Request $request)
    {
        $pageTitle = 'Dashboard';

        $fromDate = null;
        $toDate   = null;

        if ($request->filter) {

            switch ($request->filter) {

                case 'today':
                    $fromDate = Carbon::today();
                    $toDate   = Carbon::today()->endOfDay();
                    break;

                case 'yesterday':
                    $fromDate = Carbon::yesterday();
                    $toDate   = Carbon::yesterday()->endOfDay();
                    break;

                case 'last3days':
                    $fromDate = Carbon::now()->subDays(2)->startOfDay();
                    $toDate   = Carbon::now();
                    break;

                case 'last7days':
                    $fromDate = Carbon::now()->subDays(6)->startOfDay();
                    $toDate   = Carbon::now();
                    break;

                case 'current_month':
                    $fromDate = Carbon::now()->startOfMonth();
                    $toDate   = Carbon::now()->endOfMonth();
                    break;

                case 'previous_month':
                    $fromDate = Carbon::now()->subMonth()->startOfMonth();
                    $toDate   = Carbon::now()->subMonth()->endOfMonth();
                    break;
            }

        }

        // Custom Range Override
        if ($request->from_date && $request->to_date) {
            $fromDate = Carbon::parse($request->from_date)->startOfDay();
            $toDate   = Carbon::parse($request->to_date)->endOfDay();
        }

        // User Info
        $widget['total_users'] = LoanApplication::whereHas('bankDetails', function ($q) use ($fromDate,$toDate) {
                                        $q->whereBetween('created_at', [$fromDate,$toDate])
                                        ->whereNotNull('account_number')
                                        ->where('account_number', '!=', '');
                                    })->count();

        $widget['active_users'] = LoanApplication::where('loan_disbursal_status', 'disbursed')
                                    ->whereHas('loanDisbursal', function ($q) use ($fromDate,$toDate) {
                                        $q->whereBetween('created_at', [$fromDate,$toDate]);
                                    })->count();

        $widget['active_loans'] = LoanApplication::where('loan_closed_status', 'pending')
                                    ->whereHas('loanDisbursal', function ($q) use ($fromDate,$toDate) {
                                        $q->whereBetween('created_at', [$fromDate,$toDate]);
                                    })->count();

        $widget['closed_loans'] = LoanApplication::where('loan_closed_status', 'closed')
                                    ->whereBetween('loan_closed_date', [$fromDate,$toDate])->count();

        $widget['total_pending_loan'] = Loan::pending()->count();
        $widget['total_due_loan']     = Loan::due()->count();
        $widget['total_running_loan'] = Loan::running()->count();
        $widget['total_paid_loan']    = Loan::paid()->count();


        // user Browsing, Country, Operating Log
        $userLoginData = UserLogin::where('created_at', '>=', Carbon::now()->subDay(30))->get(['browser', 'os', 'country']);

        $chart['user_browser_counter'] = $userLoginData->groupBy('browser')->map(function ($item, $key) {
            return collect($item)->count();
        });
        $chart['user_os_counter'] = $userLoginData->groupBy('os')->map(function ($item, $key) {
            return collect($item)->count();
        });
        $chart['user_country_counter'] = $userLoginData->groupBy('country')->map(function ($item, $key) {
            return collect($item)->count();
        })->sort()->reverse()->take(5);


        $deposit['total_deposit_amount']        = Deposit::successful()->sum('amount');
        $deposit['total_deposit_pending']       = Deposit::pending()->count();
        $deposit['total_deposit_rejected']      = Deposit::rejected()->count();
        $deposit['total_deposit_charge']        = Deposit::successful()->sum('charge');

        $withdrawals['total_withdraw_amount']   = Withdrawal::approved()->sum('amount');
        $withdrawals['total_withdraw_pending']  = Withdrawal::pending()->count();
        $withdrawals['total_withdraw_rejected'] = Withdrawal::rejected()->count();
        $withdrawals['total_withdraw_charge']   = Withdrawal::approved()->sum('charge');


        $trxReport['date'] = collect([]);
        $plusTrx = Transaction::where('trx_type','+')->where('created_at', '>=', Carbon::now()->subDays(30))
                                       ->selectRaw("SUM(amount) as amount, DATE_FORMAT(created_at,'%Y-%m-%d') as date")
                                       ->orderBy('created_at')
                                       ->groupBy('date')
                                       ->get();

        $plusTrx->map(function ($trxData) use ($trxReport) {
            $trxReport['date']->push($trxData->date);
        });

        $minusTrx = Transaction::where('trx_type','-')->where('created_at', '>=', Carbon::now()->subDays(30))
                                       ->selectRaw("SUM(amount) as amount, DATE_FORMAT(created_at,'%Y-%m-%d') as date")
                                       ->orderBy('created_at')
                                       ->groupBy('date')
                                       ->get();

        $minusTrx->map(function ($trxData) use ($trxReport) {
            $trxReport['date']->push($trxData->date);
        });

        $trxReport['date'] = dateSorting($trxReport['date']->unique()->toArray());


        // Monthly Deposit & Withdraw Report Graph
        $report['months'] = collect([]);
        $report['deposit_month_amount'] = collect([]);
        $report['withdraw_month_amount'] = collect([]);

        $depositsMonth = Deposit::where('created_at', '>=', Carbon::now()->subYear())
            ->where('status', Status::PAYMENT_SUCCESS)
            ->selectRaw("SUM( CASE WHEN status = ".Status::PAYMENT_SUCCESS." THEN amount END) as depositAmount")
            ->selectRaw("DATE_FORMAT(created_at,'%M-%Y') as months")
            ->orderBy('created_at')
            ->groupBy('months')->get();

        $depositsMonth->map(function ($depositData) use ($report) {
            $report['months']->push($depositData->months);
            $report['deposit_month_amount']->push(getAmount($depositData->depositAmount));
        });
        $withdrawalMonth = Withdrawal::where('created_at', '>=', Carbon::now()->subYear())->where('status', Status::PAYMENT_SUCCESS)
            ->selectRaw("SUM( CASE WHEN status = ".Status::PAYMENT_SUCCESS." THEN amount END) as withdrawAmount")
            ->selectRaw("DATE_FORMAT(created_at,'%M-%Y') as months")
            ->orderBy('created_at')
            ->groupBy('months')->get();
        $withdrawalMonth->map(function ($withdrawData) use ($report){
            if (!in_array($withdrawData->months,$report['months']->toArray())) {
                $report['months']->push($withdrawData->months);
            }
            $report['withdraw_month_amount']->push(getAmount($withdrawData->withdrawAmount));
        });

        $months = $report['months'];

        for($i = 0; $i < $months->count(); ++$i) {
            $monthVal      = Carbon::parse($months[$i]);
            if(isset($months[$i+1])){
                $monthValNext = Carbon::parse($months[$i+1]);
                if($monthValNext < $monthVal){
                    $temp = $months[$i];
                    $months[$i]   = Carbon::parse($months[$i+1])->format('F-Y');
                    $months[$i+1] = Carbon::parse($temp)->format('F-Y');
                }else{
                    $months[$i]   = Carbon::parse($months[$i])->format('F-Y');
                }
            }
        }
        return view('admin.dashboard', compact('pageTitle', 'widget', 'chart','deposit','withdrawals','depositsMonth','withdrawalMonth','months','trxReport','plusTrx','minusTrx'));
    }

    public function profile()
    {
        $pageTitle = 'Profile';
        $admin = auth('admin')->user();
        return view('admin.profile', compact('pageTitle', 'admin'));
    }

    public function profileUpdate(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email',
            'image' => ['nullable','image',new FileTypeValidate(['jpg','jpeg','png'])]
        ]);
        $user = auth('admin')->user();

        if ($request->hasFile('image')) {
            try {
                $old = $user->image;
                $user->image = fileUploader($request->image, getFilePath('adminProfile'), getFileSize('adminProfile'), $old);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload your image'];
                return back()->withNotify($notify);
            }
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();
        $notify[] = ['success', 'Profile updated successfully'];
        return to_route('admin.profile')->withNotify($notify);
    }

    public function password()
    {
        $pageTitle = 'Password Setting';
        $admin = auth('admin')->user();
        return view('admin.password', compact('pageTitle', 'admin'));
    }

    public function passwordUpdate(Request $request)
    {
        $this->validate($request, [
            'old_password' => 'required',
            'password' => 'required|min:5|confirmed',
        ]);

        $user = auth('admin')->user();
        if (!Hash::check($request->old_password, $user->password)) {
            $notify[] = ['error', 'Password doesn\'t match!!'];
            return back()->withNotify($notify);
        }
        $user->password = bcrypt($request->password);
        $user->save();
        $notify[] = ['success', 'Password changed successfully.'];
        return to_route('admin.password')->withNotify($notify);
    }

    public function notifications(){
        $notifications = AdminNotification::orderBy('id','desc')->with('user')->paginate(getPaginate());
        $pageTitle = 'Notifications';
        return view('admin.notifications',compact('pageTitle','notifications'));
    }


    public function notificationRead($id){
        $notification = AdminNotification::findOrFail($id);
        $notification->is_read = Status::YES;
        $notification->save();
        $url = $notification->click_url;
        if ($url == '#') {
            $url = url()->previous();
        }
        return redirect($url);
    }


    public function readAll(){
        AdminNotification::where('is_read',Status::NO)->update([
            'is_read'=>Status::YES
        ]);
        $notify[] = ['success','Notifications read successfully'];
        return back()->withNotify($notify);
    }

    public function downloadAttachment($fileHash)
    {
        $filePath = decrypt($fileHash);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $title = slug(gs('site_name')).'- attachments.'.$extension;
        $mimetype = mime_content_type($filePath);
        header('Content-Disposition: attachment; filename="' . $title);
        header("Content-Type: " . $mimetype);
        return readfile($filePath);
    }

    public function index()
    {
        $admins = Admin::with('roles')->get();
        return view('admin.admins.index', compact('admins'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('admin.admins.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:admins,email',
            'mobile' => 'required',
            'username' => 'required|unique:admins,username',
            'password' => 'required|confirmed',
            'roles' => 'required|array',
        ]);

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'username' => $request->username,
            'password' => Hash::make($request->password),
        ]);

        $admin->roles()->sync($request->roles);

        return redirect()->route('admin.admins.index')->with('success', 'Admin user created successfully.');
    }

    public function edit(Admin $admin)
    {
        $roles = Role::all();
        return view('admin.admins.edit', compact('admin', 'roles'));
    }

    public function update(Request $request, Admin $admin)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:admins,email,' . $admin->id,
            'mobile' => 'required',
            'username' => 'required|unique:admins,username,' . $admin->id,
            'roles' => 'required|array',
        ]);

        $admin->update([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'username' => $request->username,
        ]);

        if ($request->password) {
            $admin->password = Hash::make($request->password);
            $admin->save();
        }

        $admin->roles()->sync($request->roles);

        return redirect()->route('admin.admins.index')->with('success', 'Admin user updated successfully.');
    }
}
