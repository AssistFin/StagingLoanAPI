@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <div class="section container">
        <div class="row d-flex justify-content-center">
            <div class="col-lg-6">
                <div class="card custom--card">
                    <div class="card-body">
                        <div class="mb-4">
                            <p>@lang('Your account is verified successfully. Now you can change your password. Please enter a strong password and don\'t share it with anyone.')</p>
                        </div>

                        <form method="POST" action="{{ route('user.password.update') }}" class="row g-4">
                            @csrf
                            <input type="hidden" name="email" value="{{ $email }}">
                            <input type="hidden" name="token" value="{{ $token }}">

                            <div class="col-12">
                                <div class="auth-form__input-group form-group">
                                    <span class="auth-form__input-icon">
                                        <i class="las la-lock"></i>
                                    </span>
                                    <input type="password" name="password" class="auth-form__input @if ($general->secure_password) secure-password @endif" placeholder="@lang('Your password')" required />

                                    <span class="auth-form__input-icon auth-form__toggle-pass">
                                        <i class="bx bxs-hide"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="auth-form__input-group">
                                    <span class="auth-form__input-icon">
                                        <i class="bx bx-lock"></i>
                                    </span>
                                    <input type="password" name="password_confirmation" class="auth-form__input" placeholder="@lang('Confirm password')" required />
                                    <span class="auth-form__input-icon auth-form__toggle-pass">
                                        <i class="bx bxs-hide"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn--base btn--xxl w-100 text-capitalize xl-text">
                                    @lang('Submit')
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@if ($general->secure_password)
    @push('script-lib')
        <script src="{{ asset('assets/global/js/secure_password.js') }}"></script>
    @endpush
@endif
