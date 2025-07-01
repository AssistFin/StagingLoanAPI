@extends($activeTemplate . 'layouts.app')
@section('main-content')
    @push('backend-style')
        <link href="{{ asset($activeTemplateTrue . 'css/main.css') }}" rel="stylesheet">
    @endpush
    <div class="d-flex flex-wrap">
        {{-- @include($activeTemplate . 'partials.sidebar') --}}
        <div class="dashboard-wrapper">
            @include($activeTemplate . 'partials.topbar')
            <div class="dashboard-container">
                @yield('content')
            </div>
        </div>
    </div>
    @push('backend-script')
        <script src="{{ asset($activeTemplateTrue . 'js/main.js') }}"></script>
    @endpush
@endsection
