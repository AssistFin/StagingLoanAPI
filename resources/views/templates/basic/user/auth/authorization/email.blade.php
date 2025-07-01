@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <div class="section container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7 col-xl-5">
                <div class="d-flex justify-content-center">
                    <div class="verification-code-wrapper">
                        <div class="verification-area">
                            <form action="{{ route('user.verify.email') }}" method="POST" class="submit-form">
                                @csrf
                                <p class="verification-text">@lang('A 6 digit verification code sent to your email address'):
                                    {{ showEmailAddress(auth()->user()->email) }}</p>

                                @include($activeTemplate . 'partials.verification_code')

                                <div class="col-12">
                                    <button type="submit" class="btn btn--base btn--xxl w-100 text-capitalize xl-text">
                                        @lang('Submit')
                                    </button>
                                </div>

                                <div class="mb-3">
                                    <p>
                                        @lang('If you don\'t get any code'), <a href="{{ route('user.send.verify.code', 'email') }}">
                                            @lang('Try again')</a>
                                    </p>

                                    @if ($errors->has('resend'))
                                        <small class="text--danger d-block">{{ $errors->first('resend') }}</small>
                                    @endif
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


