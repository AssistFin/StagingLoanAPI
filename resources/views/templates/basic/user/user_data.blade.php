@extends($activeTemplate . 'layouts.frontend')

@section('content')
    <div class="container section">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8 col-xl-8">
                <div class="card custom--card">
                    <div class="card-body">
                        <h4 class="text-capitalize text-center mb-4 mt-0">
                            @lang('Please complete your profile')
                        </h4>
                        <form method="POST" action="{{ route('user.data.submit') }}" class="row g-4">
                            @csrf
                            <div class="col-sm-6">
                                <div class="auth-form__input-group">
                                    <span class="auth-form__input-icon">
                                        <i class="las la-user"></i>
                                    </span>
                                    <input type="text" name="firstname" class="auth-form__input checkUser"
                                        value="{{ old('firstname') }}" placeholder="@lang('Firstname')" required
                                        autofocus="off" />
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="auth-form__input-group">
                                    <span class="auth-form__input-icon">
                                        <i class="las la-user"></i>
                                    </span>
                                    <input type="text" name="lastname" class="auth-form__input checkUser"
                                        value="{{ old('lastname') }}" placeholder="@lang('Lastname')" required
                                        autofocus="off" />
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="auth-form__input-group">
                                    <span class="auth-form__input-icon">
                                        <i class="las la-globe-asia"></i>
                                    </span>
                                    <input type="text" name="address" class="auth-form__input checkUser"
                                        value="{{ old('address') }}" placeholder="@lang('Address')" required
                                        autofocus="off" />
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="auth-form__input-group">
                                    <span class="auth-form__input-icon">
                                        <i class="las la-gopuram"></i>
                                    </span>
                                    <input type="text" name="state" class="auth-form__input checkUser"
                                        value="{{ old('state') }}" placeholder="@lang('State')" autofocus="off" />
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="auth-form__input-group">
                                    <span class="auth-form__input-icon">
                                        <i class="las la-sort-numeric-down"></i>
                                    </span>
                                    <input type="text" name="zip" class="auth-form__input checkUser"
                                        value="{{ old('zip') }}" placeholder="@lang('Zip')" autofocus="off" />
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="auth-form__input-group">
                                    <span class="auth-form__input-icon">
                                        <i class="las la-city"></i>
                                    </span>
                                    <input type="text" name="city" class="auth-form__input checkUser"
                                        value="{{ old('city') }}" placeholder="@lang('City')" autofocus="off" />
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn--base btn--xxl w-100 text-capitalize xl-text">
                                    @lang('Submit')
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
