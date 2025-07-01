@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-inner">
        <div class="mb-4">
            <p>@lang('Loan')</p>
            <div class="d-flex justify-content-between">
                <h3>{{ __($pageTitle) }}</h3>
                <a href="{{ route('user.loan.list') }}" class="btn btn--base btn--sm"><i class="las la-list-alt"></i>
                    @lang('My Loans')</a>
            </div>
            <p>@lang('Take Control of Your Financial Future with Our Top Loan Recommendations.')</p>
        </div>

        @include($activeTemplate . 'partials.loan_plans')
    </div>
@endsection
