@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <section class="section">
        <div class="container">
            <p>
                @php
                    echo $policy->data_values->details;
                @endphp
            </p>
        </div>
    </section>
@endsection
