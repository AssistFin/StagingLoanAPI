@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-inner">
        <div class="mb-4">
            <p>@lang('Loan')</p>
            <div class="d-flex justify-content-between">
                <h3>{{ __($pageTitle) }}</h3>
                <a href="{{ route('user.loan.list') }}" class="btn btn--base btn--sm"><i class="las la-list-alt"></i>
                    @lang('My Loan List')</a>
            </div>
            <p>@lang('Empowering dreams, one installment at a time.')</p>
        </div>

        <div class="row gy-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center">
                            <div class="mb-2">
                                <h4 class="text--base value"><sup class="top-0 fw-light me-1">{{ $loan->loan_number }}
                                </h4>
                                <p class="fw-bold caption">@lang('Loan Number')</p>
                            </div>
                            <div class="mb-2">
                                <h4 class="text--base value"><sup class="top-0 fw-light me-1">{{ $loan->plan->name }}</h4>
                                <p class="fw-bold caption">@lang('Plan')</p>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-center">
                            <div class="mb-2">
                                <h4 class="text--base value"><sup
                                        class="top-0 fw-light me-1">{{ $general->cur_sym .showAmount($loan->amount) }}
                                        {{ __($general->cur_text) }}
                                </h4>
                                <p class="fw-bold caption">@lang('Loan Amount')</p>
                            </div>
                            <div class="mb-2">
                                <h4 class="text--base value"><sup
                                        class="top-0 fw-light me-1">{{ $general->cur_sym .showAmount($loan->per_installment) }}
                                        {{ __($general->cur_text) }}
                                </h4>
                                <p class="fw-bold caption">@lang('Per Installment')</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center">
                            <div class="mb-2">
                                <h4 class="text--base value"><sup
                                        class="top-0 fw-light me-1">{{ $general->cur_sym . showAmount($loan->payable_amount) }}
                                        {{ __($general->cur_text) }}
                                </h4>
                                <p class="fw-bold caption">@lang('Needs to Pay')</p>
                            </div>
                            <div class="mb-2">
                                <h4 class="text--base value"><sup
                                        class="top-0 fw-light me-1">{{ $loan->total_installment }}</h4>
                                <p class="fw-bold caption">@lang('Total Installment')</p>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-center">
                            @if (getAmount($loan->charge_per_installment))
                                <div class="mb-2">
                                    <h4 class="text--base value"><sup
                                            class="top-0 fw-light me-1">{{ $general->cur_sym . showAmount($loan->charge_per_installment) }}
                                            {{ __($general->cur_text) }} / {{ $loan->delay_value }} @lang('Day')
                                    </h4>
                                    <p class="fw-bold caption">@lang('Delay Charge') <i class="las la-info-circle text--danger"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="@lang('Charge will be applied if an installment delayed for') {{ $loan->delay_value }}
                                    @lang(' or more days')"></i>
                                    </p>

                                </div>
                            @endif
                            <div class="mb-2">
                                <h4 class="text--base value"><sup
                                        class="top-0 fw-light me-1">{{ $loan->given_installment }}
                                </h4>
                                <p class="fw-bold caption">@lang('Given Installment')</p>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-4">
            @include($activeTemplate . 'partials.installment_table')
        </div>
    </div>
@endsection
