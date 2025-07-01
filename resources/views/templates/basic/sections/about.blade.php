@php
    $aboutContent = getContent('about.content', true);
@endphp
<section class="section--sm section--bottom">
    <div class="container">
        <div class="row justify-content-center align-items-lg-center justify-content-xl-between">
            <div class="col-md-10 col-lg-6 col-xl-5 text-center text-lg-start">
                <div class="d-flex align-items-center justify-content-center justify-content-lg-start">
                    <p class="mb-0 text-capitalize text--primary xxl-text">
                        {{ __($aboutContent->data_values->subheading) }}</p>
                </div>
                <h2>{{ __($aboutContent->data_values->heading) }}</h2>
                <p class="t-short-para">
                    @php
                        echo __(strip_tags($aboutContent->data_values->description));
                    @endphp
                </p>
                @if (request()->routeIs('home'))
                    <a href="{{ @$aboutContent->data_values->button_link }}" class="t-link btn btn--xl xl-text btn--base">
                        {{ __(@$aboutContent->data_values->button_name) }}
                    </a>
                @endif
            </div>
            <div class="col-lg-6 col-xl-7 d-none d-lg-block">
                <img src="{{ getImage('assets/images/frontend/about/' . @$aboutContent->data_values->image, '750x550') }}"
                    class="img-fluid radious-5" />
            </div>
        </div>
    </div>
</section>
