<div class="dashboard-sidebar" id="dashboard-sidebar">
    <button class="btn-close dash-sidebar-close d-xl-none"></button>
    <a href="{{ route('home') }}" class="logo"><img src="{{ getImage(getFilePath('logoIcon') . '/logo.png') }} " alt="images"></a>

    <div class="bg--lights">
        <div class="profile-info">
            <p class="fs--13px mb-1 fw-bold">@lang('ACCOUNT BALANCE')</p>
            <h4 class="usd-balance text--base mb-2 fs--30">{{ $general->cur_sym . showAmount(auth()->user()->balance) }}
                <sub class="top-0 fs--13px">{{ __($general->cur_text) }}</sub>
            </h4>

            <div class="mt-4 d-flex flex-wrap gap-2">
                <a href="{{ route('user.deposit.index') }}" class="btn btn--success btn--smd">@lang('Deposit')</a>
                <a href="{{ route('user.withdraw') }}" class="btn btn--dark btn--smd">@lang('Withdraw')</a>
            </div>
        </div>
    </div>

    <ul class="sidebar-menu">
        <li><a href="{{ route('user.home') }}" class="{{ menuActive('user.home') }}">
                <img src="{{ asset($activeTemplateTrue . '/images/icon/dashboard.png') }}">@lang('Dashboard')</a></li>

        <li><a href="{{ route('user.loan.plans') }}" class="{{ menuActive('user.loan.plans') }}">
                <img src="{{ asset($activeTemplateTrue . '/images/icon/balance-transfer.png') }}">@lang('Take Loan')
            </a></li>
        <li><a href="{{ route('user.loan.list') }}" class="{{ menuActive(['user.loan.list', 'user.loan.instalment.logs']) }}">
                <img src="{{ asset($activeTemplateTrue . '/images/icon/schedule.png') }}">@lang('My Loans')
            </a></li>

        <li><a href="{{ route('user.deposit.index') }}" class="{{ menuActive('user.deposit*') }}">
                <img src="{{ asset($activeTemplateTrue . '/images/icon/wallet.png') }}">@lang('Deposit')</a></li>
        <li><a href="{{ route('user.withdraw') }}" class="{{ menuActive('user.withdraw*') }}">
                <img src="{{ asset($activeTemplateTrue . '/images/icon/withdraw.png') }}">@lang('Withdraw')</a></li>

        <li><a href="{{ route('user.transactions') }}" class="{{ menuActive('user.transactions') }}">
                <img src="{{ asset($activeTemplateTrue . '/images/icon/transaction.png') }}">@lang('Transactions')</a></li>
        <li><a href="{{ route('ticket.index') }}" class="{{ menuActive(['ticket.index', 'ticket.view', 'ticket.open']) }}">
                <img src="{{ asset($activeTemplateTrue . '/images/icon/ticket.png') }}">@lang('Support Ticket')</a></li>
        <li><a href="{{ route('user.twofactor') }}" class="{{ menuActive('user.twofactor') }}">
                <img src="{{ asset($activeTemplateTrue . '/images/icon/2fa.png') }}">@lang('2FA Setting')</a></li>
        <li><a href="{{ route('user.profile.setting') }}" class="{{ menuActive('user.profile.setting') }}">
                <img src="{{ asset($activeTemplateTrue . '/images/icon/profile.png') }}">@lang('Profile')</a></li>
        <li><a href="{{ route('user.change.password') }}" class="{{ menuActive('user.change.password') }}">
                <img src="{{ asset($activeTemplateTrue . '/images/icon/password.png') }}">@lang('Change Password')</a></li>
        <li><a href="{{ route('user.logout') }}" class="{{ menuActive('user.logout') }}">
                <img src="{{ asset($activeTemplateTrue . '/images/icon/logout.png') }}">@lang('Logout')</a></li>
    </ul>
</div>
