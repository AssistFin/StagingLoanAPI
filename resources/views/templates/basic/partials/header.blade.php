@php
    $language = App\Models\Language::all();
    $contactContent = getContent('contact.content', true);
@endphp

<section class="top-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <ul class="list list--row d-flex align-items-center">
                    <li class="list--row__item">
                        <ul class="list vf-info-list">
                            <li class="vf-info-list__item">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <img src="{{ asset($activeTemplateTrue . 'images/icon-envelop.png') }}">
                                    </div>
                                    <div class="ms-3">
                                        <p class="label mb-0 fw-bold text-uppercase t-text-white">
                                            @lang('Email')
                                        </p>

                                        <a href="mailto:{{ $contactContent->data_values->email }}" class="mb-0 vf-info-list__text">{{ __($contactContent->data_values->email) }}</a>
                                    </div>

                                </div>
                            </li>
                            <li class="vf-info-list__item">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <img src="{{ asset($activeTemplateTrue . 'images/icon-location.png') }}">
                                    </div>
                                    <div class="ms-3">
                                        <p class="label mb-0 fw-bold text-uppercase t-text-white">
                                            @lang('Address')
                                        </p>
                                        <p class="mb-0 vf-info-list__text">
                                            {{ __($contactContent->data_values->location) }}
                                        </p>
                                    </div>
                                </div>
                            </li>
                            <li class="vf-info-list__item">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <img src="{{ asset($activeTemplateTrue . 'images/icon-phone.png') }}">
                                    </div>
                                    <div class="ms-3">
                                        <p class="label mb-0 fw-bold text-uppercase t-text-white">
                                            @lang('Phone')
                                        </p>
                                        <a href="tel:{{ $contactContent->data_values->phone }}" class="mb-0 fw-md vf-info-list__text">
                                            {{ $contactContent->data_values->phone }}</a>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </li>

                    @if ($general->multi_language)
                        <li class="ms-auto">
                            <div class="custom--nice-select">
                                <div class="language-select">
                                    <select class="select langSel">
                                        @foreach ($language as $item)
                                            <option value="{{ $item->code }}" @if (session('lang') == $item->code) selected @endif>{{ __($item->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</section>

<header class="header" id="header">
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-light">
            <a href="{{ route('home') }}" class="logo">
                <img src="{{ getImage('assets/images/logoIcon/logo.png') }}" alt="viserhyip" class="img-fluid logo__is" />
            </a>
            <button class="navbar-toggler header-button" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span id="hiddenNav"><i class="las la-bars"></i></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav nav-menu align-items-lg-center">
                    @if ($general->multi_language)
                        <li class="nav-item mb-2 d-block d-lg-none">
                            <div class="top-button d-flex flex-wrap justify-content-between align-items-center">
                                <div class="custom--nice-select">
                                    <div class="language-box">
                                        <select class="select langSel">
                                            @foreach ($language as $item)
                                                <option value="{{ $item->code }}" @if (session('lang') == $item->code) selected @endif>
                                                    {{ __($item->name) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endif
                    <li class="nav-item">
                        <a class="nav-link {{ menuActive('home') }}" aria-current="page" href="{{ route('home') }}">@lang('Home')</a>
                    </li>
                    @if ($pages)
                        @foreach ($pages as $k => $data)
                            <li class="nav-item">
                                <a class="{{ menuActive('pages', [$data->slug]) }} nav-link" href="">{{ __($data->name) }}</a>
                            </li>
                        @endforeach
                    @endif



                    @auth
                        <li class="nav-item mt-2 d-block d-lg-none">
                            <div class="account mt-1">
                                <a href="{{ route('user.home') }}" class="btn btn--md btn--base fw-bold w-100">
                                    @lang('Dashboard')
                                </a>
                            </div>
                        </li>
                    @else
                        <li class="nav-item mt-2 d-block d-lg-none">
                            <div class="account mt-1">
                                <a href="{{ route('user.login') }}" class="btn btn--md btn--base fw-bold w-100">
                                    @lang('Join Now')
                                </a>
                            </div>
                        </li>
                    @endauth
                </ul>
            </div>

            @auth
                <div class="account d-none d-lg-block">
                    <a href="{{ route('user.home') }}" class="btn btn--md btn--base fw-bold w-100">
                        @lang('Dashboard')
                    </a>
                </div>
            @else
                <div class="account d-none d-lg-block">
                    <a href="{{ route('user.login') }}" class="btn btn--md btn--base fw-bold w-100">
                        @lang('Login')
                    </a>
                </div>
            @endauth
        </nav>
    </div>
</header>
