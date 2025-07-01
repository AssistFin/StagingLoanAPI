<div class="row g-4">
    <div class="col-lg-12">
        <ul class="nav nav-pills custom--tab" id="pills-tab" role="tablist">
            @foreach (@$categories as $category)
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if ($loop->first) active @endif"
                        id="pills-{{ slug($category->name) }}-tab" data-bs-toggle="pill"
                        data-bs-target="#pills-{{ slug($category->name) }}" type="button" role="tab"
                        aria-controls="pills-{{ slug($category->name) }}"
                        aria-selected="false">{{ __($category->name) }}
                    </button>
                </li>
            @endforeach
        </ul>
        <div class="tab-content" id="pills-tabContent">
            @foreach ($categories as $category)
                <div class="tab-pane fade @if ($loop->first) show active @endif"
                    id="pills-{{ slug($category->name) }}" role="tabpanel"
                    aria-labelledby="pills-{{ slug($category->name) }}-tab" tabindex="0">

                    <div class="row justify-content-center gy-4 gx-sm-2 gx-md-4">
                        @forelse($category->plans as $plan)
                            <div class="col-lg-4 col-sm-6 col-10">
                                <div class="plan-card rounded-3 h-100 d-flex flex-column justify-content-between">
                                    <div class="plan-card-header-body">
                                        <div class="plan-card__header">
                                            <div class="plan-name-tag">
                                                <h4 class="plan-name">{{ __($plan->name) }}</h4>
                                                <span class="plan-tagline">{{ __($plan->title) }}</span>
                                            </div>
                                            <div class="plan-price">
                                                <h4 class="title">{{ getAmount($plan->loanInterest()) }}%</h4>
                                                <span>@lang('Interest Rate')</span>
                                            </div>
                                        </div>

                                        <div class="plan-card__body text-center">
                                            <ul class="plan-feature-list">
                                                <li class="plan-feature-list-item">
                                                    <span class="plan-feature-list-title">@lang('Take Minimum')</span>
                                                    <span
                                                        class="plan-feature-list-amount ">{{ __($general->cur_sym) }}{{ showAmount($plan->minimum_amount) }}</span>
                                                </li>
                                                <li class="plan-feature-list-item">
                                                    <span class="plan-feature-list-title">@lang('Take Maximum')</span>
                                                    <span
                                                        class="plan-feature-list-amount ">{{ __($general->cur_sym) }}{{ showAmount($plan->maximum_amount) }}</span>
                                                </li>
                                                <li class="plan-feature-list-item">
                                                    <span class="plan-feature-list-title">@lang('Per Installment')</span>
                                                    <span
                                                        class="plan-feature-list-amount">{{ getAmount($plan->per_installment) }}%</span>
                                                </li>
                                                <li class="plan-feature-list-item">
                                                    <span class="plan-feature-list-title">@lang('Installment Interval')</span>
                                                    <span class="plan-feature-list-amount">
                                                        {{ $plan->installment_interval }}
                                                        @lang('Days')</span>
                                                </li>
                                                <li class="plan-feature-list-item">
                                                    <span class="plan-feature-list-title"> @lang('Total Installment')</span>
                                                    <span
                                                        class="plan-feature-list-amount">{{ $plan->total_installment }}</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="plan-card__footer text-center">
                                        <button type="button" data-id="{{ $plan->id }}"
                                            data-planname="{{ __($plan->name) }}"
                                            data-minimum="{{ $general->cur_sym }}{{ showAmount($plan->minimum_amount) }}"
                                            data-maximum="{{ $general->cur_sym }}{{ showAmount($plan->maximum_amount) }}"
                                            class="btn btn-md btn--xl xl-text w-100 btn--base loanBtn">@lang('Apply Now')
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>
            @endforeach
        </div>
    </div>
</div>

@push('modal')
    <div class="modal fade" id="loanModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="" method="post">
                    @auth
                        <div class="modal-header">
                            <h5 class="modal-title method-name" id="exampleModalLabel">@lang('Apply for ') <span
                                    class="loan-name"></span> </h5>
                            <span type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <i class="las la-times"></i>
                            </span>
                        </div>
                        @csrf
                        <div class="modal-body">
                            <div class="form-group">
                                <label>@lang('Amount')</label>
                                <div class="input-group">
                                    <input type="number" step="any" name="amount" class="form-control form--control"
                                        placeholder="@lang('Enter An Amount')" required>
                                    <span class="input-group-text"> {{ __($general->cur_text) }} </span>
                                </div>
                                <p class="mt-2"><small class="text--danger min-limit d-block"></small>
                                    <small class="text--danger max-limit"></small>
                                </p>
                            </div>
                            <button type="submit" class="btn btn--base w-100">@lang('Confirm')</button>
                        </div>
                    @else
                        <div class="modal-body">
                            <div class="text-center"><i class="la la-times-circle text--danger la-6x" aria-hidden="true"></i>
                            </div>
                            <h3 class="text-center mt-3">@lang('You are not logged in!')</h3>
                        </div>

                        <div class="modal-footer">
                            <a href="{{ route('user.login') }}" class="btn btn-sm btn--base">@lang('Login')</a>
                            <button type="button" class="btn btn-sm btn--dark" data-bs-dismiss="modal"
                                aria-label="Close">@lang('Close')</button>
                        </div>
                    @endauth
                </form>
            </div>
        </div>
    </div>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";
            $('.loanBtn').on('click', (e) => {
                var modal = $('#loanModal');
                let data = e.currentTarget.dataset;
                modal.find('.min-limit').text(`Minimum Amount ${data.minimum}`);
                modal.find('.max-limit').text(`Maximum Amount ${data.maximum}`);
                modal.find('.loan-name').text(`${data.planname}`);
                let form = modal.find('form')[0];
                form.action = `{{ route('user.loan.apply', '') }}/${data.id}`;
                modal.modal('show');
            });
        })(jQuery);
    </script>
@endpush
