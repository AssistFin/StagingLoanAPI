<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="{{ config('app.locale') }}" itemscope itemtype="http://schema.org/WebPage">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> {{ $general->siteName(__($pageTitle)) }}</title>
    @include('partials.seo')

    <link rel="stylesheet" href="{{ asset('assets/global/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/plan.css') }}">
    @stack('frontend-style')
    @stack('backend-style')

    <link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'css/custom.css') }}">
    <link rel="stylesheet"
        href="{{ asset($activeTemplateTrue . 'css/color.php?color=' . $general->base_color . '&secondColor=' . $general->secondary_color) }}">
    @stack('style-lib')
    @stack('style')

</head>

<body>
    <div class="preloader">
        <div class="animated-preloader"></div>
    </div>
    <div class="body-overlay" id="body-overlay"></div>


    @stack('fbComment')

    <div class="main-wrapper">
        @yield('main-content')
    </div>


    @php
        $cookie = App\Models\Frontend::where('data_keys', 'cookie.data')->first();
    @endphp
    @if ($cookie->data_values->status == Status::ENABLE && !\Cookie::get('gdpr_cookie'))
        <!-- cookies dark version start -->
        <div class="cookies-card text-center hide">
            <div class="cookies-card__icon bg--base">
                <i class="las la-cookie-bite"></i>
            </div>
            <p class="mt-4 cookies-card__content">{{ $cookie->data_values->short_desc }} <a
                    href="{{ route('cookie.policy') }}" target="_blank">@lang('learn more')</a></p>
            <div class="cookies-card__btn mt-4">
                <a href="javascript:void(0)" class="btn btn--base btn--xl w-100 policy">@lang('Allow')</a>
            </div>
        </div>
        <!-- cookies dark version end -->
    @endif

    @stack('modal')

    <script src="{{ asset('assets/global/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset($activeTemplateTrue . 'js/jquery.validate.js') }}"></script>
    @stack('backend-script')
    @stack('frontend-script')
    @stack('script-lib')
    @stack('script')
    @include('partials.plugins')

    <script>
        "use strict";
        (function($) {

            $(".langSel").on("change", function() {
                window.location.href = "{{ url('/') }}/change/" + $(this).val();
            });

            setTimeout(function() {
                $('.cookies-card').removeClass('hide')
            }, 2000);



            $('form').on('submit', function() {
                if ($(this).valid()) {
                    $(':submit', this).attr('disabled', 'disabled');
                }
            });

            var inputElements = $('[type=text],[type=password],select,textarea');

            $.each(inputElements, function(index, element) {
                element = $(element);
                element.closest('.form-group').find('label').attr('for', element.attr('name'));
                element.attr('id', element.attr('name'))
            });

            $.each($('input, select, textarea'), function(i, element) {
                var elementType = $(element);
                if (elementType.attr('type') != 'checkbox' && element.hasAttribute('required')) {
                    $(element).closest('.form-group').find('label').addClass('required');
                }
            });

            $('.showFilterBtn').on('click', function() {
                $('.responsive-filter-card').slideToggle();
            });

            Array.from(document.querySelectorAll('table')).forEach(table => {
                let heading = table.querySelectorAll('thead tr th');
                Array.from(table.querySelectorAll('tbody tr')).forEach((row) => {
                    Array.from(row.querySelectorAll('td')).forEach((colum, i) => {
                        colum.setAttribute('data-label', heading[i].innerText)
                    });
                });
            });

        })(jQuery);
    </script>
    @include('partials.notify')
    @include('partials.push_config')

</body>

</html>
