@php
    $faqContent = getContent('faq.content', true);
    $faqElement = getContent('faq.element', orderById: true);
@endphp

<section class="section--sm section--bottom">
    <div class="section__head">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-xl-8">
                    <div class="text-center">
                        <div class="d-flex align-items-center justify-content-center">
                            
                            <p class="mb-0 text-capitalize text--primary xxl-text">
                                {{ __(@$faqContent->data_values->subheading) }}</p>
                        </div>
                        <h2 class="text-capitalize">{{ __(@$faqContent->data_values->heading) }}</h2>
                        <p class="t-short-para mx-auto mb-0">
                            {{ __(@$faqContent->data_values->description) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="row justify-content-center g-4">
            <div class="col-lg-10 col-xl-8">
                <div class="accordion vf-accordion" id="faqAccordion">
                    @foreach ($faqElement as $faq)
                        <div class="accordion-item vf-accordion__item">
                            <h2 class="accordion-header vf-accordion__header">
                                <button class="accordion-button vf-accordion__btn {{ !$loop->first ? 'collapsed' : null }}" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#collapse-{{ $faq->id }}"
                                    aria-expanded="{{ !$loop->first ? 'false' : 'true' }}">
                                    {{ __($faq->data_values->question) }}
                                </button>
                            </h2>
                            <div id="collapse-{{ $faq->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : null }}"
                                data-bs-parent="#faqAccordion">
                                <div class="accordion-body vf-accordion__body">
                                    @php
                                        echo $faq->data_values->answer;
                                    @endphp
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
