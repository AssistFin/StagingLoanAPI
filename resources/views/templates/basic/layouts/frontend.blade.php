@extends($activeTemplate . 'layouts.app')
@section('main-content')
    @push('frontend-style')
        <link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'css/lib/slick.css') }}" />
        <link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'css/lib/boxicons.css') }}" />
        <link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'css/lib/nice-select.css') }} " />
        <link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'css/style.css') }} " />
    @endpush

    @include($activeTemplate . 'partials.header')
    <div class="main-wrapper">
        @if (!request()->routeIs('home'))
            @include($activeTemplate . 'partials.breadcrumb')
        @endif

        @yield('content')
        @include($activeTemplate . 'partials.footer')
    </div>

    @push('frontend-script')
        <script src="{{ asset($activeTemplateTrue . 'js/jquery.nice-select.js') }}"></script>
        <script src="{{ asset($activeTemplateTrue . 'js/slick.js') }}"></script>
        <script src="{{ asset($activeTemplateTrue . 'js/app.js') }}"></script>
    @endpush
@endsection
