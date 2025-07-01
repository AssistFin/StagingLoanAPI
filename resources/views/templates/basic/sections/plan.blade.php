@php
    $planContent = getContent('plan.content', true);
    $categories = App\Models\Category::active()
        ->whereHas('plans')
        ->with([
            'plans' => function ($query) {
                $query->active()->where('is_featured', Status::ENABLE);
            },
        ])
        ->latest()
        ->get();
    
@endphp
<!-- Status::ENABLE is for is_featured -->
<section class="section--sm section--bottom">
    <div class="section__head">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-xl-8">
                    <div class="text-center">
                        <div class="d-flex align-items-center justify-content-center">
                            <p class="mb-0 text-capitalize text--primary xxl-text">
                                {{ __(@$planContent->data_values->subheading) }}
                            </p>
                        </div>
                        <h2 class="text-capitalize">{{ __(@$planContent->data_values->heading) }}</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        @include($activeTemplate . 'partials.loan_plans')
    </div>

    <div class="d-flex justify-content-center">
        <a href="{{ route('loan') }}" class="btn btn--xl xl-text btn--base mt-5">
            @lang('See All')
        </a>
    </div>
</section>
