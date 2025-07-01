@php
    $breadcrumbContent = getContent('breadcrumb.content', true);
@endphp
<div class="breadcrumb"
    style="background-image: url({{ getImage('assets/images/frontend/breadcrumb/' . @$breadcrumbContent->data_values->image, '1920x840') }});">
    <div class="container">
        <div class="row g-3 justify-content-center">
            <div class="col-lg-10 text-center">
                <div class="breadcrumb__content">
                    <h2 class="hero__content-title text-capitalize t-text-white">
                        {{ __($pageTitle) }}
                    </h2>
                    <ul class="list list--row breadcrumbs justify-content-center">
                        <li class="list--row__item breadcrumbs__item">
                            <a href="{{ route('home') }}"
                                class="t-link breadcrumbs__link text-uppercase t-text-white t-link--primary">@lang('Home')
                            </a>
                        </li>
                        <li class="list--row__item breadcrumbs__item">
                            <span
                                class=" t-link breadcrumbs__link text-uppercase t-text-white t-link--primary ">{{ __($pageTitle) }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
