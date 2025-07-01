@php
    $clients = getContent('client.element', null, false, true);
@endphp

<div class="section--sm">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="client-slider">
                    @foreach ($clients as $client)
                        <div class="client-slider__item">
                            <img src="{{ getImage('assets/images/frontend/client/' . @$client->data_values->image, '100x100') }}" alt="@lang('client')" class="img-fluid mx-auto" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
