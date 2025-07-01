@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-inner">
        @if ($user->loans->where('status', Status::LOAN_RUNNING)->count() == 1)
            <div class="alert border border--warning" role="alert">
                <div class="alert__icon d-flex align-items-center text--success"><i class="fas fa-check"></i></div>
                <p class="alert__message">
                    <span class="fw-bold">@lang('First Loan')</span><br>
                    <small><i><span class="fw-bold">@lang('Congratulations!')</span> @lang('You\'ve made your first loan successfully. Go to') <a
                                href="{{ route('user.loan.plans') }}" class="link-color">@lang('Loan List')</a>
                            @lang('for see your next installment date at near.')</i></small>
                </p>
            </div>
        @endif

        @if ($pendingWithdrawals)
            <div class="alert border border--primary" role="alert">
                <div class="alert__icon d-flex align-items-center text--primary"><i class="fas fa-spinner"></i></div>
                <p class="alert__message">
                    <span class="fw-bold">@lang('Withdrawal Pending')</span><br>
                    <small><i>@lang('Total') {{ showAmount($pendingWithdrawals) }} {{ $general->cur_text }}
                            @lang('withdrawal request is pending. Please wait for admin approval. The amount will send to the account which you\'ve provided. See') <a href="{{ route('user.withdraw.history') }}"
                                class="link-color">@lang('withdrawal history')</a></i></small>
                </p>
            </div>
        @endif

        @if ($pendingDeposits)
            <div class="alert border border--primary" role="alert">
                <div class="alert__icon d-flex align-items-center text--primary"><i class="fas fa-spinner"></i></div>
                <p class="alert__message">
                    <span class="fw-bold">@lang('Deposit Pending')</span><br>
                    <small><i>@lang('Total') {{ showAmount($pendingDeposits) }} {{ $general->cur_text }}
                            @lang('deposit request is pending. Please wait for admin approval. See') <a href="{{ route('user.deposit.history') }}"
                                class="link-color">@lang('deposit history')</a></i></small>
                </p>
            </div>
        @endif

        @if (!$user->ts)
            <div class="alert border border--warning" role="alert">
                <div class="alert__icon d-flex align-items-center text--warning"><i class="fas fa-user-lock"></i></div>
                <p class="alert__message">
                    <span class="fw-bold">@lang('2FA Authentication')</span><br>
                    <small><i>@lang('To keep safe your account, Please enable') <a href="{{ route('user.twofactor') }}"
                                class="link-color">@lang('2FA')</a> @lang('security').</i> @lang('It will make secure your account and balance.')</small>
                </p>
            </div>
        @endif

        @if ($user->kv == 0)
            <div class="alert border border--info" role="alert">
                <div class="alert__icon d-flex align-items-center text--info"><i class="fas fa-file-signature"></i></div>
                <p class="alert__message">
                    <span class="fw-bold">@lang('KYC Verification Required')</span><br>
                    <small><i>@lang('Please submit the required KYC information to verify yourself. Otherwise, you couldn\'t make any withdrawal requests to the system.') <a href="{{ route('user.kyc.form') }}"
                                class="link-color">@lang('Click here')</a> @lang('to submit KYC information').</i></small>
                </p>
            </div>
        @elseif($user->kv == 2)
            <div class="alert border border--warning" role="alert">
                <div class="alert__icon d-flex align-items-center text--warning"><i class="fas fa-user-check"></i></div>
                <p class="alert__message">
                    <span class="fw-bold">@lang('KYC Verification Pending')</span><br>
                    <small><i>@lang('Your submitted KYC information is pending for admin approval. Please wait till that.') <a href="{{ route('user.kyc.data') }}"
                                class="link-color">@lang('Click here')</a> @lang('to see your submitted information')</i></small>
                </p>
            </div>
        @endif

        <div class="row g-3 mt-4">
            <div class="col-lg-4">
                <div class="dashboard-widget">
                    <div class="d-flex justify-content-between">
                        <h5 class="text-secondary">@lang('Successful Deposits')</h5>
                    </div>
                    <h3 class="text--secondary my-4">{{ showAmount($successfulDeposits) }} {{ $general->cur_text }}</h3>
                    <div class="widget-lists">
                        <div class="row">
                            <div class="col-4">
                                <p class="fw-bold">@lang('Submitted')</p>
                                <small>{{ $general->cur_sym }}{{ showAmount($submittedDeposits) }}</small>
                            </div>
                            <div class="col-4">
                                <p class="fw-bold">@lang('Pending')</p>
                                <small>{{ $general->cur_sym }}{{ showAmount($pendingDeposits) }}</small>
                            </div>
                            <div class="col-4">
                                <p class="fw-bold">@lang('Rejected')</p>
                                <small>{{ $general->cur_sym }}{{ showAmount($rejectedDeposits) }}</small>
                            </div>
                        </div>
                        <hr>
                        <p><small><i>@lang('You\'ve requested to deposit') {{ $general->cur_sym }}{{ showAmount($requestedDeposits) }}.
                                    @lang('Where') {{ $general->cur_sym }}{{ showAmount($initiatedDeposits) }}
                                    @lang('is just initiated but not submitted.')</i></small></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="dashboard-widget">
                    <div class="d-flex justify-content-between">
                        <h5 class="text-secondary">@lang('Successful Withdrawals')</h5>
                    </div>
                    <h3 class="text--secondary my-4">{{ showAmount($successfulWithdrawals) }} {{ $general->cur_text }}
                    </h3>
                    <div class="widget-lists">
                        <div class="row">
                            <div class="col-4">
                                <p class="fw-bold">@lang('Submitted')</p>
                                <small>{{ $general->cur_sym }}{{ showAmount($submittedWithdrawals) }}</small>
                            </div>
                            <div class="col-4">
                                <p class="fw-bold">@lang('Pending')</p>
                                <small>{{ $general->cur_sym }}{{ showAmount($pendingWithdrawals) }}</small>
                            </div>
                            <div class="col-4">
                                <p class="fw-bold">@lang('Rejected')</p>
                                <small>{{ $general->cur_sym }}{{ showAmount($rejectedWithdrawals) }}</small>
                            </div>
                        </div>
                        <hr>
                        <p><small><i>@lang('You\'ve requested to withdraw') {{ $general->cur_sym }}{{ showAmount($requestedWithdrawals) }}.
                                    @lang('Where') {{ $general->cur_sym }}{{ showAmount($initiatedWithdrawals) }}
                                    @lang('is just initiated but not submitted.')</i></small></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="dashboard-widget">
                    <div class="d-flex justify-content-between">
                        <h5 class="text-secondary">@lang('Total Loan')</h5>
                    </div>
                    <h3 class="text--secondary my-4">{{ showAmount($totalLoan) }} {{ $general->cur_text }}</h3>
                    <div class="widget-lists">
                        <div class="row">
                            <div class="col-3">
                                <p class="fw-bold">@lang('Pending')</p>
                                <small>{{ $general->cur_sym }}{{ showAmount($pendingLoans) }}</small>
                            </div>
                            <div class="col-3">
                                <p class="fw-bold">@lang('Running')</p>
                                <small>{{ $general->cur_sym }}{{ showAmount($runningLoans) }}</small>
                            </div>

                            <div class="col-3">
                                <p class="fw-bold">@lang('Completed')</p>
                                <small>{{ $general->cur_sym }}{{ showAmount($paidLoans) }}</small>
                            </div>
                            <div class="col-3">
                                <p class="fw-bold">@lang('Rejected')</p>
                                <small>{{ $general->cur_sym }}{{ showAmount($rejectedLoans) }}</small>
                            </div>
                        </div>

                        <hr>
                        <p><small><i>@lang('You\'ve') {{ getAmount($totalLoans) }} @lang('Loans').
                                    @lang('Which is') {{ getAmount($totalRunningLoans) }} @lang('Running') &
                                    {{ getAmount($totalPendingLoans) }}
                                    @lang('is Pending') & {{ getAmount($totalRejectedLoans) }} @lang('is Rejected') &
                                    {{ getAmount($totalPaidLoans) }} @lang('is Completed')</i></small>.</p>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="my-4"> @lang('My Running Loans')</h3>
        <div class="card custom--card ">
            <div class="table-responsive">
                <table class="table table--responsive--md">
                    <thead>
                        <tr>
                            <th>@lang('Loan Number')</th>
                            <th>@lang('Plan Name')</th>
                            <th>@lang('Loan Amount')</th>
                            <th>@lang('Installment')</th>
                            <th>@lang('Installment Amount')</th>
                            <th>@lang('Next Installment')</th>
                        </tr>
                    </thead>
                    <tbody>

                        @forelse ($userRunningLoans as $loan)
                            <tr>
                                <td>
                                    <span class="text--primary"> {{ __($loan->loan_number) }}</span>
                                </td>
                                <td>
                                    {{ __($loan->plan->name) }}
                                </td>
                                <td>
                                    <p>
                                        <b>{{ showAmount($loan->amount) }} {{ $general->cur_text }}</b><br>
                                        <small class="text--base">
                                            {{ $general->cur_sym . showAmount($loan->payable_amount) }}
                                            <small>(@lang('Need to pay'))</small>
                                        </small>
                                    </p>
                                </td>
                                <td>
                                    <span> @lang('Total') : {{ __($loan->total_installment) }}</span>
                                    <br>
                                    <small class="text--base">
                                        @lang('Given') : {{ __($loan->given_installment) }}
                                    </small>
                                </td>
                                <td>
                                    <span>{{ $general->cur_sym . showAmount($loan->per_installment) }}</span>
                                    <br>
                                    <small class="text--base">
                                        @lang('In Every') {{ __($loan->installment_interval) }}
                                        @lang('Days')
                                    </small>
                                </td>
                                <td>
                                    @if ($loan->nextInstallment)
                                        <b> {{ showDateTime($loan->nextInstallment->installment_date, 'd M, Y') }}</b>
                                    @endif
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="100%" class="text-center">{{ __($emptyMessage) }}</td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>
        </div>
        @if ($totalRunningLoans > 5)
            <span class="d-flex justify-content-center">
                <a href="{{ route('user.loan.list') }}?status={{ Status::LOAN_RUNNING }}" class="btn btn--base my-2">
                    @lang('See All')</a>
            </span>
        @endif

    </div>
@endsection
