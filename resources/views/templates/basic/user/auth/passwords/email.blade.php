@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <div class="section container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                <div class="card custom--card">
                    <div class="card-body">
                        <div class="mb-4">
                            <p>@lang('To recover your account please provide your email or username to find your account.')</p>
                        </div>
                        <form method="POST" action="{{ route('user.password.email') }}" class="verify-gcaptcha">
                            @csrf

                            <div class="auth-form__input-group">
                                <span class="auth-form__input-icon">
                                    <i class="las la-user"></i>
                                </span>
                                <input type="text" class="auth-form__input" name="value" value="{{ old('value') }}" placeholder="@lang('Username or Email')" required autofocus="off" />
                            </div>

                            <div class="mt-3">
                                <x-captcha />

                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn--base btn--xxl w-100 text-capitalize xl-text mt-3">@lang('Submit')</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .form--control {
            padding: 35px;
        }
    </style>
@endpush
