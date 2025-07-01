@php
    $testimonialElement = getContent('testimonial.element', orderById: true);
@endphp
<section class="feedback-section section">
    <div class="container p-5">
        <div class="row g-5 align-items-center justify-content-xxl-between">
            <div class="col-md-6">
                <div class="feedback-slider-for">
                    @foreach ($testimonialElement as $testimonial)
                        <div class="feedback-slider-for__item">
                            <div class="feedback-slider-for__img text-center text-xl-end">
                                <img src="{{ getImage('assets/images/frontend/testimonial/' . @$testimonial->data_values->image, '440x440') }}"
                                     class="feedback-slider-for__img-is mx-auto ms-xl-auto me-xl-0" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="col-md-6">
                <div class="feedback-slider-nav">
                    @foreach ($testimonialElement as $testimonial)
                        <div class="feedback-slider-nav__item">
                            <div class="feedback-slider-nav__content text-center text-md-start">
                                <span class="feedback-slider-nav__quote">
                                    <i class="bx bxs-quote-right"></i>
                                </span>
                                <h4 class="feedback-slider-nav__title text-capitalize mb-2">
                                    {{ __(@$testimonial->data_values->author) }}
                                </h4>
                                <p class="feedback-slider-nav__sub-title text-capitalize">
                                    {{ __(@$testimonial->data_values->designation) }}
                                </p>
                                <p class="feedback-slider-nav__text">
                                    {{ __(@$testimonial->data_values->quote) }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
