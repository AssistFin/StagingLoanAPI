@extends('admin.layouts.app')
@section('panel')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card b-radius--10">
                <div class="card-body">
                    <h4 class="card-title mb-4">@lang('Bank/UPI Details')</h4>

                    @if($user->bank_account_number || $user->upi_id)
                        <ul class="list-group">
                            @if($user->bank_account_number)
                                <li class="list-group-item">
                                    <strong>@lang('Bank Account Number:')</strong> {{ $user->bank_account_number }}
                                </li>
                                <li class="list-group-item">
                                    <strong>@lang('Bank Account Holder:')</strong> {{ $user->bank_account_holder_name }}
                                </li>
                                <li class="list-group-item">
                                    <strong>@lang('Bank Name:')</strong> {{ $user->bank_name }}
                                </li>
                                <li class="list-group-item">
                                    <strong>@lang('IFSC Code:')</strong> {{ $user->bank_ifsc_code }}
                                </li>
                                <li class="list-group-item">
                                    <strong>@lang('Account Type:')</strong> {{ucfirst($user->bank_account_type) }}
                                </li>
                            @endif
                            @if($user->upi_id)
                                <li class="list-group-item">
                                    <strong>@lang('UPI ID:')</strong> {{ $user->upi_id }}
                                </li>
                            @endif
                        </ul>
                    @else
                        <h5 class="text-center">@lang('No Bank or UPI Data Available')</h5>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
