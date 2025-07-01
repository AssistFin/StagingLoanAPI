@php
    $featureContent = getContent('feature.content', true);
    $featureElement = getContent('feature.element', null, false, true);
@endphp

<section class="section--sm">
    <div class="container">
        <div class="row justify-content-center align-items-lg-center justify-content-xl-between">
            <div class="col-lg-5 col-xl-6 col-xxl-7 d-none d-lg-block">
                <img src="{{ getImage('assets/images/frontend/feature/' . @$featureContent->data_values->image, '715x710') }}"
                    class="img-fluid radious-5" />
            </div>
            <div class="col-md-12 col-lg-7 col-xl-6 col-xxl-5">
                <div class="text-center text-lg-start">
                    <div class="d-flex align-items-center justify-content-center justify-content-lg-start">

                        <p class="mb-0 text-capitalize text--primary xxl-text">
                            {{ __($featureContent->data_values->subheading) }}
                        </p>
                    </div>
                    <h2>{{ __($featureContent->data_values->heading) }}</h2>
                    <p class="t-short-para">
                      @php
                          echo $featureContent->data_values->content;
                      @endphp
                    </p>
                </div>
                <div class="t-mt-40">
                    <div class="row g-4 g-md-3">

                        @foreach ($featureElement as $feature)
                            <div class="col-md-6">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 icon">
                                        @php
                                            echo $feature->data_values->icon;
                                        @endphp
                                    </div>
                                    <div class="ms-3 content">
                                        <h5 class="text-capitalize mt-0 t-body-font fw-md text-start">
                                            {{ __($feature->data_values->title) }}
                                        </h5>
                                        <p class="mb-0 text-start">
                                            {{ __($feature->data_values->details) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>