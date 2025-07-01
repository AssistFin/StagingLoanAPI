@php
    $contactContent = getContent('contact.content', true);
@endphp
@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <div class="container section">
        <div class="row gy-5 gy-md-0 gx-xl-5 align-items-center flex-wrap-reverse">
            <div class="col-lg-6 col-xl-5">
                <ul class="list list--column">
                    <li class="list--column__item-xl">
                        <div class="d-flex align-items-center bg--light-1 p-3 p-lg-4">
                            <div class="flex-shrink-0 contact-icon-box--primary">
                                <i class="las la-street-view"></i>
                            </div>
                            <div class="ms-5">
                                <p class="mb-2 fw-bold text-capitalize t-text-heading xl-text">
                                    @lang('Office Address')
                                </p>
                                <p class="mb-0">{{ __($contactContent->data_values->location) }}</p>
                            </div>
                        </div>
                    </li>
                    <li class="list--column__item-xl">
                        <div class="d-flex align-items-center bg--light-1 p-3 p-lg-4">
                            <div class="flex-shrink-0 contact-icon-box--primary">
                                <i class="las la-envelope-open-text"></i>
                            </div>
                            <div class="ms-5">
                                <p class="mb-2 fw-bold text-capitalize t-text-heading xl-text">
                                    @lang('Email Address')
                                </p>
                                <p class="mb-0">{{ __($contactContent->data_values->email) }}</p>
                            </div>
                        </div>
                    </li>
                    <li class="list--column__item-xl">
                        <div class="d-flex align-items-center bg--light-1 p-3 p-lg-4">
                            <div class="flex-shrink-0 contact-icon-box--primary">
                                <i class="las la-phone-volume"></i>
                            </div>
                            <div class="ms-5">
                                <p class="mb-2 fw-bold text-capitalize t-text-heading xl-text">
                                    @lang('Mobile Number')
                                </p>
                                <p class="mb-0">{{ __($contactContent->data_values->phone) }}</p>
                            </div>
                        </div>
                    </li>

                </ul>
            </div>
            <div class="col-lg-6 col-xl-7">
                <div class="query bg--light-1">
                    <h3 class="query__title text-center text-md-start text-capitalize mt-0">
                        {{ __(@$contactContent->data_values->heading) }}
                    </h3>
                    <form method="post" action="" class="row g-4 verify-gcaptcha">
                        @csrf
                        <div class=" col-sm-6 col-lg-12 col-xl-6">
                            <input type="text" name="name"
                                class="form-control form-control-custom form-control-custom--outline form-control-custom--outline-dark"
                                value="{{ old('name', @$user->fullname) }}"
                                @if ($user) readonly @endif placeholder="@lang('Your Name')"
                                required />
                        </div>
                        <div class=" col-sm-6 col-lg-12 col-xl-6">
                            <input type="email" name="email"
                                class="form-control form-control-custom form-control-custom--outline form-control-custom--outline-dark"
                                value="{{ old('email', @$user->email) }}" @if ($user) readonly @endif
                                placeholder="@lang('Email address')" required />
                        </div>
                        <div class="col-12">
                            <input type="text" name="subject"
                                class="form-control form-control-custom form-control-custom--outline form-control-custom--outline-dark"
                                value="{{ old('subject') }}" placeholder="@lang('Subject')" required />
                        </div>

                        <div class="col-12">
                            <textarea cols="30" rows="5" wrap="off" name="message"
                                class="form-control form-control-custom--outline form-control-custom--dark" placeholder="@lang('Write Your Message')"
                                required>{{ old('message') }}</textarea>
                        </div>
                        <div class="col-12">
                            <x-captcha />
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn--xl btn--base text-capitalize">
                                @lang('Send Message')
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="pt-5">
            @if ($sections->secs != null)
                @foreach (json_decode($sections->secs) as $sec)
                    @include($activeTemplate . 'sections.' . $sec)
                @endforeach
            @endif
        </div>
    </div>

    <div class="container-fluid p-0">
        <div class="row g-0">
            <div class="col-12">
                <div class="gmap">
                    <iframe src="@php echo @$contactContent->data_values->map @endphp"></iframe>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .form--control {
            padding: 22px !important;
            border: 1px solid #e9e9e9 !important;
        }
    </style>
@endpush
