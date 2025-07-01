@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <section class="section">
        <div class="container">
            @include($activeTemplate . 'partials.loan_plans')
        </div>
    </section>
@endsection
