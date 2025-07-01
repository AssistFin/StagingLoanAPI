@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-inner">
        <div class="mb-4">
            <p>@lang('Loan')</p>
            <div class="d-flex justify-content-between">
                <h3>{{ __($pageTitle) }}</h3>
                <a href="{{ route('user.loan.plans') }}" class="btn btn--base btn--sm"><i class="las la-hand-holding-usd"></i>
                    @lang('Take Loan')</a>
            </div>
        </div>
        <div class="row justify-content-center gy-4">
            <div class="col-lg-12">
                <div class="custom--card">

                    <div class="filter-area mb-3">
                        <div class="d-flex flex-wrap gap-4">
                            <div class="flex-grow-1">
                                <form action="">
                                    <div class="custom-input-box trx-search">
                                        <label>@lang('Loan Number')</label>
                                        <input type="text" name="search" value="{{ request()->search }}"
                                            placeholder="@lang('Search By Loan Number')">
                                        <button type="submit" class="icon-area">
                                            <i class="las la-search"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="flex-grow-1">
                                <div class="custom-input-box">
                                    <label>@lang('Loan Status')</label>
                                    <select name="status" onChange="window.location.href=this.value">
                                        <option value={{ queryLoanBuild('status', '') }}>@lang('All')
                                        </option>
                                        <option value="{{ queryLoanBuild('status', 0) }}" @selected(request()->status != null && request()->status == 0)>
                                            @lang('Pending')</option>
                                        <option value="{{ queryLoanBuild('status', 1) }}" @selected(request()->status == 1)>
                                            @lang('Running')</option>
                                        <option value="{{ queryLoanBuild('status', 2) }}" @selected(request()->status == 2)>
                                            @lang('Paid')</option>
                                        <option value="{{ queryLoanBuild('status', 3) }}" @selected(request()->status == 3)>
                                            @lang('Rejected')</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion table--acordion" id="transactionAccordion">
                        @forelse($loans as $loan)
                            <div class="accordion-item transaction-item" title="@lang('Click For Details')">
                                <h2 class="accordion-header" id="h-{{ $loop->iteration }}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#c-{{ $loop->iteration }}">
                                        <div class="col-lg-3 col-sm-5 col-8 order-1 icon-wrapper">
                                            <div class="left">
                                                @if ($loan->status == Status::LOAN_PENDING)
                                                    <div class="icon icon-dark">
                                                        <i class="las la-sync"></i>
                                                    </div>
                                                @elseif($loan->status == Status::LOAN_RUNNING)
                                                    <div class="icon icon-primary">
                                                        <i class="las la-spinner fa-spin"></i>
                                                    </div>
                                                @elseif($loan->status == Status::LOAN_PAID)
                                                    <div class="icon icon-success">
                                                        <i class="las la-check-circle"></i>
                                                    </div>
                                                @else
                                                    <div class="icon icon-danger">
                                                        <i class="las la-ban"></i>
                                                    </div>
                                                @endif

                                                <div class="content">
                                                    <h6 class="trans-title" title="@lang('Loan Plan')">
                                                        {{ __($loan->plan->name) }}</h6>
                                                    <span
                                                        class="text-muted font-size--14px mt-2">{{ showDateTime($loan->created_at, 'M d Y @g:i:a') }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-3 col-sm-4 col-12 order-sm-2 order-2 content-wrapper mt-sm-0 mt-3"
                                            title="@lang('Loan Number')">
                                            <p class="text-muted font-size--14px"><b>{{ $loan->loan_number }}</b></p>
                                        </div>
                                        <div class="col-lg-3 col-sm-4 col-12 order-sm-2 order-3 content-wrapper mt-sm-0 mt-3"
                                            title="@lang('Status')">
                                            <p class="text-muted font-size--14px">
                                                @php echo $loan->statusBadge; @endphp
                                            </p>
                                        </div>

                                        <div class="col-lg-3 col-sm-3 col-4 order-sm-3 order-4 text-end amount-wrapper">
                                            <p>
                                                <b>{{ showAmount($loan->amount) }} {{ $general->cur_text }}</b><br>
                                                <small class="text--base">
                                                    {{ $general->cur_sym . showAmount($loan->payable_amount) }}
                                                    <small>(@lang('Need to pay'))</small>
                                                </small>
                                            </p>

                                        </div>
                                    </button>
                                </h2>
                                <div id="c-{{ $loop->iteration }}" class="accordion-collapse collapse"
                                    aria-labelledby="h-1" data-bs-parent="#transactionAccordion">
                                    <div class="accordion-body">
                                        <ul class="caption-list">
                                            <li>
                                                <span class="caption">@lang('Installment Amount')</span>
                                                <div class="value">
                                                    <span>{{ $general->cur_sym . showAmount($loan->per_installment) }}</span>
                                                    <br>
                                                    <small class="text--base">
                                                        @lang('In Every') {{ __($loan->installment_interval) }}
                                                        @lang('Days')
                                                    </small>
                                                </div>
                                            </li>
                                            <li>
                                                <span class="caption">@lang('Installment')</span>
                                                <div class="value">
                                                    <span> @lang('Total') : {{ __($loan->total_installment) }}</span>
                                                    <br>
                                                    <small class="text--base">
                                                        @lang('Given') : {{ __($loan->given_installment) }}
                                                    </small>
                                                </div>
                                            </li>
                                            <li>
                                                <span class="caption">@lang('Next Installment')</span>
                                                <div class="value">
                                                    @if ($loan->nextInstallment)
                                                        {{ showDateTime($loan->nextInstallment->installment_date, 'd M, Y') }}
                                                    @endif
                                                </div>
                                            </li>
                                            <li>
                                                <span class="caption">@lang('Paid')</span>
                                                <span class="value">
                                                    <span>{{ $general->cur_sym . showAmount($loan->paid_amount) }}</span>
                                                    <br>
                                                    <span class="text--warning">
                                                        @php $remainingAmount = $loan->payableAmount - $loan->paid_amount;  @endphp
                                                        <small> {{ $general->cur_sym . showAmount($remainingAmount) }}
                                                            <small> @lang('Remaining')</small></small>
                                                    </span>
                                                </span>
                                            </li>
                                            <li>
                                                <span class="caption">@lang('Status')</span>
                                                <div class="value">
                                                    @php echo $loan->statusBadge; @endphp
                                                    @if ($loan->status == Status::LOAN_REJECTED)
                                                        <span class="admin-feedback"
                                                            data-feedback="{{ __($loan->admin_feedback) }}">
                                                            <i class="las la-info-circle"></i>
                                                        </span>
                                                    @endif
                                                </div>
                                            </li>
                                            <li>
                                                <span class="caption">@lang('Action')</span>
                                                <div class="value">
                                                    <a class="btn btn--outline-primary btn--sm @disabled(!$loan->nextInstallment)"
                                                        href="{{ route('user.loan.instalment.logs', $loan->loan_number) }}">
                                                        <i class="las la-wallet"></i> @lang('Installments')
                                                    </a>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div><!-- transaction-item end -->
                        @empty
                            <div class="accordion-body text-center">
                                <h4 class="text--muted">{{ __($emptyMessage) }}</h4>
                            </div>
                        @endforelse
                    </div>
                    @if ($loans->hasPages())
                        <div class="card-footer py-2">
                            {{ paginateLinks($loans) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    {{-- //Feedback-Modal --}}
    <div class="modal fade" id="adminFeedback">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Reason of Rejection')!</h5>
                </div>
                <div class="modal-body">

                </div>
                <div class="modal-footer">
                    <button class="btn btn-sm btn--dark" data-bs-dismiss="modal"
                        type="button">@lang('Close')</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .trx-search {
            position: relative;
        }

        .trx-search .icon-area {
            position: absolute;
            top: 10px;
            right: 8px;
            font-size: 20px;
            background: transparent;
            border: none;
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";
            $('.admin-feedback').on('click', function() {
                var modal = $('#adminFeedback');
                modal.find('.modal-body').text($(this).data('feedback'));
                modal.modal('show');
            });

        })(jQuery);
    </script>
@endpush
