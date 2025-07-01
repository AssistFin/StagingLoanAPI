@php
    $counterContent = getContent('counter.content', true);
    $counterElement = getContent('counter.element', false, 4);
@endphp

<div class="section counter-section"
    style="background-image: url({{ getImage('assets/images/frontend/counter/' . @$counterContent->data_values->image, '1920x960') }});">
    <div class="container">
        <div class="row justify-content-xxl-between align-items-center">
            @foreach ($counterElement as $counter)
            <div class="col-lg-3 col-6">
                <div class="counter-list__item">
                    <h1 class="title text-uppercase t-text-white text-center">
                        <span>{{ __($counter->data_values->counter_digit) }}</span>
                    </h1>
                    <p class="mb-0 t-text-white text-capitalize xxl-text text-center">
                        {{ __($counter->data_values->title) }}
                    </p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>