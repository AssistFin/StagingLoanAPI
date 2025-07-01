            @extends($activeTemplate . 'layouts.master')
            @section('content')
                <div class="dashboard-inner">
                    <div class="row gy-4">
                        @php
                            $userBalance = auth()->user()->balance;
                            $percentCharge = ($amount * $plan->application_percent_charge) / 100;
                            $applicationFee = $plan->application_fixed_charge + $percentCharge;
                        @endphp

                        <div class="col-lg-5">
                            <div class="card custom--card">
                                <div class="card-body">
                                    <h5 class="text-center">
                                        @lang('You are aplying to take loan')
                                    </h5>
                                    <p class="text-center text--danger">(@lang('Be Sure Before Confirm'))</p>

                                    <ul class="caption-list-two">
                                        <li>
                                            <span class="caption">@lang('Plan Name')</span>
                                            <span class="value">{{ __($plan->name) }}</span>
                                        </li>

                                        <li>
                                            <span class="caption">@lang('Loan Amount')</span>
                                            <span class="value">{{ $general->cur_sym . showAmount($amount) }}</span>
                                        </li>

                                        <li>
                                            <span class="caption">@lang('Total Installment')</span>
                                            <span class="value">{{ $plan->total_installment }}</span>
                                        </li>

                                        @php $perIntallment = $amount * $plan->per_installment / 100; @endphp

                                        <li>
                                            <span class="caption">@lang('Per Installment')</span>
                                            <span class="value">{{ $general->cur_sym . showAmount($perIntallment) }}</span>
                                        </li>

                                        <li class="fw-bold text--danger">
                                            <span class="caption">@lang('You\'ll Need To Pay')</span>
                                            <span class="value">{{ $general->cur_sym . showAmount($perIntallment * $plan->total_installment) }}</span>
                                        </li>
                                    </ul>

                                    <p class="px-2">
                                        @if ($plan->delay_value && getAmount($plan->delay_charge))
                                            <small class="text--danger d-block mb-3 mt-2">*
                                                @lang('If an installment is delayed for')
                                                <span class="fw-bold">{{ $plan->delay_value }}</span> @lang('or more days then, an amount of'),
                                                <span class="fw-bold">{{ $general->cur_sym . showAmount($plan->delay_charge) }}</span>
                                                @lang('will be applied for each day.')
                                            </small>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="card custom--card mt-3">
                                <div class="card-body">
                                    <h4>@lang('Application Fee'): {{ $general->cur_sym . showAmount($applicationFee) }}</h4>
                                    <p class="mt-2">@lang('The application fee will be deducted from your balance now. You should have sufficient balance on your account for applying.')</p>
                                </div>
                            </div>

                        </div>
                        <div class="col-lg-7">
                            <div class="card custom--card">
                                <div class="card-header">
                                    <h5 class="card-title">@lang('Application Form')</h5>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('user.loan.apply.confirm') }}" method="post" enctype="multipart/form-data">
                                        @csrf
                                        @if ($plan->instruction)
                                            <div class="form-group">
                                                <p class="caption-list-two p-3 bg--light">
                                                    @php echo $plan->instruction @endphp
                                                </p>
                                            </div>
                                        @endif

                                        <x-viser-form identifier="id" identifierValue="{{ $plan->form_id }}" />

                                        <button type="submit" class="btn btn--base w-100" @disabled($applicationFee > $userBalance)><i class="las la-check-circle"></i> @lang('Apply')</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endsection
