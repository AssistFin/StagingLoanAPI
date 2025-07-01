@php
    $loginContent = getContent('login.content', true);
@endphp
@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <div class="section container">
        <div class="row g-lg-0">
            <div class="col-lg-6 d-none d-lg-block">
                <div class="h-100 auth-form__bg" style="background-image: url({{ getImage('assets/images/frontend/login/' . @$loginContent->data_values->image, '800x1100') }});">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="auth-form__content section">
                    <h3 class="text-capitalize text-center mt-0 mb-4">
                        {{ __($loginContent->data_values->heading) }}
                    </h3>
                    <form method="POST" action="{{ route('user.login') }}" class="row g-4 verify-gcaptcha">
                        @csrf
                        <div class="col-12">
                            <div class="auth-form__input-group">
                                <span class="auth-form__input-icon">
                                    <i class="la la-user"></i>
                                </span>
                                <input type="text" name="username" class="auth-form__input" value="{{ old('username') }}" placeholder="@lang('Username or Email')" required />
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="auth-form__input-group">
                                <span class="auth-form__input-icon">
                                    <i class="la la-lock"></i>
                                </span>
                                <input type="password" name="password" class="auth-form__input" placeholder="@lang('Your password')" required />
                                <span class="auth-form__input-icon auth-form__toggle-pass">
                                    <i class="bx bxs-hide"></i>
                                </span>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex flex-wrap justify-content-between my-4">
                                <div class="form-group form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="remember">
                                        @lang('Remember Me')
                                    </label>
                                </div>
                                <a class="forgot-pass text-decoration-none" href="{{ route('user.password.request') }}">
                                    @lang('Forgot password?')
                                </a>
                            </div>
                        </div>

                        <x-captcha />

                        <div class="col-12">
                            <button type="submit" class="btn btn--base btn--xxl w-100 text-capitalize xl-text">
                                @lang('Login')
                            </button>
                        </div>

                        <div class="col-12">
                            <p class="mb-0 text-capitalize text-center">
                                @lang('Already have an account yet')?
                                <a href="{{ route('user.register') }}" class="text-decoration-none">
                                    @lang('Register Now')
                                </a>
                            </p>
                        </div>

                        @php
                            $credentials = $general->socialite_credentials;
                        @endphp
                        @if ($credentials->google->status == Status::ENABLE || $credentials->facebook->status == Status::ENABLE || $credentials->linkedin->status == Status::ENABLE)
                            <div class="col-12">
                                <div class="auth-form__divider">
                                    <span class="d-block text-center text-capitalize auth-form__divider-text">
                                        @lang(' or')
                                    </span>
                                </div>
                            </div>
                            <div class="col-12">
                                <p class="mb-0 text-capitalize text-center">
                                    @lang('Continue with social media')
                                </p>
                            </div>

                            <div class="col-12">
                                <ul class="list list--row justify-content-center">
                                    @if ($credentials->facebook->status == Status::ENABLE)
                                        <li class="list--row__item">
                                            <a href="{{ route('user.social.login', 'facebook') }}" class="t-link icon icon--circle icon--md bg--primary t-text-white t-link--light">
                                                <i class="bx bxl-facebook"></i>
                                            </a>
                                        </li>
                                    @endif
                                    @if ($credentials->google->status == Status::ENABLE)
                                        <li class="list--row__item">
                                            <a href="{{ route('user.social.login', 'google') }}" class="t-link icon icon--circle icon--md bg--danger t-text-white t-link--light">
                                                <i class="bx bxl-google"></i>
                                            </a>
                                        </li>
                                    @endif
                                    @if ($credentials->linkedin->status == Status::ENABLE)
                                        <li class="list--row__item">
                                            <a href="{{ route('user.social.login', 'linkedin') }}" class="t-link icon icon--circle icon--md bg--info t-text-white t-link--light">
                                                <i class="bx bxl-linkedin"></i>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
